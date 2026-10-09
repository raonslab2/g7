"""Read-only fixed-source / installed / served binding for W04 Retry closure (no app boot, no env/config/DB).

Usage: python3 -I binding.py <phase>   (append-only evidence/binding-<phase>.json)
"""
import hashlib, io, json, pathlib, subprocess, sys, tarfile, urllib.request, datetime, re

SHA = "5783e6ba124061bdfae639cdaf9b1c14a83cdf03"
TREE = "361f9a142572f8a6c0c28326c461a233a92aec4e"
ROOT = pathlib.Path(__file__).resolve().parents[2]
PARENT = pathlib.Path("/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba")
BASE = "http://127.0.0.1:18871"
PINS = {
    "engine": ("public/build/core/template-engine.min.js", "738ee97c6eebc33bc75d29f24397daabb54f124bede254689ca68a18fdb64a0b"),
    "template_app": ("resources/js/core/TemplateApp.ts", "62e89b9dedb93c932a92e1b01944cffb11e88cf0c5cd5a79dc41ca8835f5b76e"),
    "travel_iife": ("templates/_bundled/raonslab-travel_lab/dist/js/components.iife.js", "8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63"),
    "board_controller": (None, "d368cb0108765094f6739c3f3a110d7e597fff6606ad367971943dc091a6c816"),
    "action_dispatcher": ("resources/js/core/template-engine/ActionDispatcher.ts", "bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68"),
}
EXT = [("modules", n) for n in ("raonslab-travel_lab", "sirsoft-ecommerce", "sirsoft-board", "sirsoft-page")] + \
      [("templates", n) for n in ("raonslab-travel_lab", "sirsoft-admin_basic")]


def git(*a):
    return subprocess.check_output(["git", "-C", str(ROOT), *a])


def d(b):
    return hashlib.sha256(b).hexdigest()


def runtime(path):
    parts = pathlib.PurePosixPath(path).parts
    if set(parts) & {"docs", "tests", "__tests__", "scripts", "node_modules"}:
        return False, "docs/tests/tooling"
    if "vendor" in parts and "dist" not in parts:
        return False, "extension composer vendor (not in Git)"
    if path.endswith((".md", ".test.ts", ".test.tsx", ".spec.ts", ".spec.tsx")) or parts[-1] == "LICENSE":
        return False, "documentation/test file"
    if any(p.startswith(".env") for p in parts):
        return False, "env"
    return True, None


def main():
    phase = sys.argv[1]
    dest = ROOT / f"tests/W04_RETRY_BROWSER/evidence/binding-{phase}.json"
    if dest.exists():
        raise SystemExit("append-only phase exists")
    assert git("rev-parse", SHA + "^{tree}").decode().strip() == TREE
    head = git("rev-parse", "HEAD").decode().strip()
    git("merge-base", "--is-ancestor", SHA, head)
    req = ["app", "routes", "resources", "bootstrap/app.php", "public/build", "public/index.php", "config", "lang"] + [f"{k}/_bundled/{n}" for k, n in EXT]
    blobs = {}
    with tarfile.open(fileobj=io.BytesIO(git("archive", SHA, *req))) as t:
        for it in t:
            if it.isfile():
                blobs[it.name] = t.extractfile(it).read()
    groups, excluded, mism = {}, {}, []
    for src, data in sorted(blobs.items()):
        inst, grp = src, src.split("/")[0]
        if src.startswith("public/build/"):
            grp = "public/build"
        for k, n in EXT:
            pre = f"{k}/_bundled/{n}/"
            if src.startswith(pre):
                grp, inst = f"{k}/{n}", f"{k}/{n}/" + src[len(pre):]
        ok, why = runtime(src)
        if not ok:
            excluded[why] = excluded.get(why, 0) + 1
            continue
        tgt = PARENT / inst
        g = groups.setdefault(grp, {"files": 0, "installed_equal": 0, "local_equal": 0})
        g["files"] += 1
        sd = d(data)
        if (ROOT / src).is_file() and d((ROOT / src).read_bytes()) == sd:
            g["local_equal"] += 1
        if tgt.is_file() and d(tgt.read_bytes()) == sd:
            g["installed_equal"] += 1
        else:
            mism.append({"source": src, "installed": inst, "exists": tgt.is_file()})
    pins = {}
    for k, (p, want) in PINS.items():
        if p is None:
            cands = [s for s in blobs if s.endswith(".php") and "sirsoft-board" in s and d(blobs[s]) == want]
            p = cands[0] if cands else None
        row = {"path": p, "expected": want, "git": d(blobs[p]) if p in blobs else None}
        inst = p
        for kk, n in EXT:
            pre = f"{kk}/_bundled/{n}/"
            if p and p.startswith(pre):
                inst = f"{kk}/{n}/" + p[len(pre):]
        row["installed_path"] = inst
        row["installed"] = d((PARENT / inst).read_bytes()) if p and (PARENT / inst).is_file() else None
        row["match"] = row["git"] == want == row["installed"]
        pins[k] = row
    served = []
    urls = [("/build/core/template-engine.min.js", "public/build/core/template-engine.min.js")]
    for n in ("raonslab-travel_lab", "sirsoft-admin_basic"):
        for tail in ("js/components.iife.js", "css/components.css"):
            s = f"templates/_bundled/{n}/dist/{tail}"
            if s in blobs:
                urls.append((f"/api/templates/assets/{n}/{tail}", s))
    for u, s in urls:
        r = {"url": u, "git": d(blobs[s])}
        try:
            with urllib.request.urlopen(BASE + u, timeout=20) as resp:
                b = resp.read()
                r.update(status=resp.status, served=d(b), bytes=len(b))
        except Exception as e:
            r.update(status=getattr(e, "code", None), error=type(e).__name__)
        r["exact"] = r.get("served") == r["git"]
        served.append(r)
    with urllib.request.urlopen(BASE + "/admin/travel-lab/campaigns", timeout=20) as resp:
        shell = resp.read().decode("utf8", "replace")
    shell_assets = sorted(set(re.findall(r'(?:src|href)="([^"]+\.(?:js|css)[^"]*)"', shell)))
    caches = [{"path": p.relative_to(PARENT).as_posix(), "bytes": p.stat().st_size, "sha256": d(p.read_bytes())}
              for pat in ("routes*.php", "hooks.php") for p in sorted((PARENT / "bootstrap/cache").glob(pat))]
    out = {"phase": phase, "at": datetime.datetime.now(datetime.timezone.utc).isoformat(), "fixed_sha": SHA, "tree": TREE,
           "actual_head": head, "parent_git_head": subprocess.check_output(["git", "-C", str(PARENT), "rev-parse", "HEAD"]).decode().strip(),
           "groups": groups, "selected_total": sum(g["files"] for g in groups.values()),
           "installed_mismatches": mism, "excluded_counts": excluded, "pins": pins, "served": served,
           "shell_asset_refs": shell_assets, "route_hook_cache_inventory": caches,
           "scope_note": "config/ and lang/ are root runtime source compared to parent checkout; root vendor/, .env, storage, DB and non-route caches excluded; sirsoft-basic not installed in parent"}
    out["ok"] = all(p["match"] for p in pins.values()) and all(s["exact"] for s in served) and not mism
    dest.write_text(json.dumps(out, indent=1) + "\n")
    print(json.dumps({"ok": out["ok"], "groups": groups, "mismatch": len(mism), "pins": {k: v["match"] for k, v in pins.items()},
                      "served": [(s["url"], s.get("status"), s["exact"]) for s in served], "excluded": excluded}, indent=1))
    sys.exit(0 if out["ok"] else 1)


main()
