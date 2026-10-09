#!/usr/bin/env python3
"""W03 nonauthor live HTTP security probe against the frozen Travel Lab preview.

Usage: python3 -I live_security_probe.py ACCESS_JSON OUTPUT_JSON

Reads synthetic lab credentials privately; never prints tokens/passwords.
Writes only: the probing member's own cart rows (created then deleted).
Never submits inquiries, never reserves capacity, never creates temp orders,
orders, payments, support posts, mail or SMS.
"""
import json
import sys
import urllib.error
import urllib.request

access = json.load(open(sys.argv[1]))
BASE = access["base_url"].rstrip("/")
TL = BASE + "/api/modules/raonslab-travel_lab"
EC = BASE + "/api/modules/sirsoft-ecommerce"
results = []


def call(method, url, who=None, body=None, headers=None):
    h = {"Accept": "application/json", "Accept-Language": "en"}
    if who:
        h["Authorization"] = "Bearer " + access[who]["bearer_token"]
    if body is not None:
        h["Content-Type"] = "application/json"
    h.update(headers or {})
    req = urllib.request.Request(url, method=method, headers=h,
                                 data=None if body is None else json.dumps(body).encode())
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            raw = r.read()
            code = r.status
    except urllib.error.HTTPError as e:
        raw = e.read()
        code = e.code
    try:
        data = json.loads(raw or b"null")
    except ValueError:
        data = None
    return code, data


def check(name, method, url, who, expect, body=None, headers=None):
    code, data = call(method, url, who, body, headers)
    ok = code in (expect if isinstance(expect, (list, tuple)) else [expect])
    path = url.replace(BASE, "")
    results.append({"check": name, "method": method, "path": path, "actor": who or "anonymous",
                    "expected": expect, "actual": code, "result": "PASS" if ok else "FAIL"})
    return code, data


def departures_snapshot():
    _, d = call("GET", TL + "/catalog?per_page=48")
    return {dep["id"]: dep["available"] for p in d["data"]["data"] for dep in p["departures"]}


check("health", "GET", BASE + "/up", None, 200)
for path in ["/cart", "/inquiries", "/admin/inquiries", "/admin/catalog"]:
    check("anonymous rejected", "GET", TL + path, None, 401)
check("anonymous submit rejected", "POST", TL + "/inquiries", None, 401,
      {"cart_ids": [1], "contact": {"name": "x"}, "idempotency_key": "w03-anon-0001"})
for path in ["/admin/inquiries", "/admin/catalog"]:
    check("member denied admin read", "GET", TL + path, "member", 403)
check("member denied admin transition", "PATCH", TL + "/admin/inquiries/1", "member", 403, {"status": "DECLINED"})
check("member denied departure edit", "PUT", TL + "/admin/catalog/1/departures/1", "member", 403,
      {"product_option_id": 1, "capacity": 999, "departure_date": "2026-11-01", "return_date": "2026-11-03"})
check("admin reads inquiries", "GET", TL + "/admin/inquiries?per_page=100", "admin", 200)

before = departures_snapshot()
dep_id = sorted(before)[0]

# Foreign inquiry IDs: other_member must get 404 for every inquiry it does not own.
_, own_other = call("GET", TL + "/inquiries?per_page=100", "other_member")
own_ids = {i["id"] for i in (own_other or {}).get("data", {}).get("data", [])}
_, adm = call("GET", TL + "/admin/inquiries?per_page=100", "admin")
foreign = [i["id"] for i in (adm or {}).get("data", {}).get("data", []) if i["id"] not in own_ids][:3]
for iid in foreign:
    check("foreign inquiry hidden", "GET", TL + f"/inquiries/{iid}", "other_member", 404)
if not foreign:
    results.append({"check": "foreign inquiry hidden", "result": "NOT_RUN", "reason": "no visible foreign inquiry"})

# Tamper/validation at the HTTP boundary: no writes expected.
for extra in [{"unit_price": 1}, {"user_id": access["other_member"]["user_id"]}, {"price": 0}]:
    check("cart rejects client field " + list(extra)[0], "POST", TL + "/cart", "member", 422,
          {"departure_id": dep_id, "quantity": 1, **extra})
for q in [0, -1, 100, "1.5", "abc"]:
    check(f"cart rejects quantity {q}", "POST", TL + "/cart", "member", 422, {"departure_id": dep_id, "quantity": q})
check("cart unknown departure", "POST", TL + "/cart", "member", 404, {"departure_id": 99999999, "quantity": 1})
for extra in [{"total_amount": 1}, {"status": "TEST_ACCEPTED"}, {"currency_code": "USD"}]:
    check("inquiry rejects client field " + list(extra)[0], "POST", TL + "/inquiries", "member", 422,
          {"cart_ids": [1], "contact": {"name": "W03"}, "idempotency_key": "w03-live-tamper-01", **extra})
check("inquiry rejects bad key", "POST", TL + "/inquiries", "member", 422,
      {"cart_ids": [1], "contact": {"name": "W03"}, "idempotency_key": "bad key!"})
check("inquiry rejects header/body key mismatch", "POST", TL + "/inquiries", "member", 422,
      {"cart_ids": [1], "contact": {"name": "W03"}, "idempotency_key": "w03-live-key-0001"},
      {"Idempotency-Key": "w03-live-key-0002"})

# Own cart row (no capacity change) -> server totals -> native checkout guard -> cleanup.
cart_id = None
code, cart = check("member adds own cart row", "POST", TL + "/cart", "member", [200, 201],
                   {"departure_id": dep_id, "quantity": 1})
try:
    items = [i for i in (cart or {}).get("data", {}).get("items", []) if i["departure_id"] == dep_id]
    if items:
        cart_id = items[0]["id"]
        t = cart["data"]["totals"]
        ok = (t.get("total_shipping") == 0 and t.get("total_discount", 0) == 0
              and t.get("points_used", 0) == 0 and items[0]["quantity"] == 1)
        results.append({"check": "server totals zero shipping/discount/points", "result": "PASS" if ok else "FAIL",
                        "actual": {k: t.get(k) for k in ("subtotal", "total_shipping", "total_discount", "points_used", "final_amount")}})
        check("other member cannot patch", "PATCH", TL + f"/cart/{cart_id}", "other_member", 404, {"quantity": 2})
        check("other member cannot delete", "DELETE", TL + f"/cart/{cart_id}", "other_member", 404)
        check("over-capacity patch rejected", "PATCH", TL + f"/cart/{cart_id}", "member", [409, 422], {"quantity": 99})
        check("native checkout item_ids blocked", "POST", EC + "/checkout", "member", 400, {"item_ids": [cart_id]})
        _, cat = call("GET", TL + "/catalog?per_page=48")
        dep = next(d for p in cat["data"]["data"] for d in p["departures"] if d["id"] == dep_id)
        check("native checkout direct_items blocked", "POST", EC + "/checkout", "member", 400,
              {"direct_items": [{"product_id": dep["product_id"], "product_option_id": dep["product_option_id"], "quantity": 1}]})
        check("no temp order exists", "GET", EC + "/checkout", "member", [404, 400])
finally:
    if cart_id is not None:
        check("cleanup own cart row", "DELETE", TL + f"/cart/{cart_id}", "member", 200)
        _, after_cart = call("GET", TL + "/cart", "member")
        left = [i for i in after_cart["data"]["items"] if i["id"] == cart_id]
        results.append({"check": "cleanup verified", "result": "PASS" if not left else "FAIL"})

after = departures_snapshot()
results.append({"check": "catalog availability unchanged by probe", "result": "PASS" if before == after else "FAIL"})

summary = {r["result"]: 0 for r in results}
for r in results:
    summary[r["result"]] += 1
out = {"source_sha": access.get("source_sha"), "base_url": BASE, "db": access.get("db"),
       "actors": {k: access[k]["user_id"] for k in ("member", "other_member", "admin")},
       "summary": summary, "results": results}
json.dump(out, open(sys.argv[2], "w"), indent=1, ensure_ascii=False)
print(json.dumps(summary))
