"""Summarise append-only phase JSON into evidence/execution-index.json (counts only)."""
import json, pathlib, collections
E = pathlib.Path(__file__).resolve().parent / "evidence"
CMD = {"binding-before": "python3 -I tests/W04_RETRY_BROWSER/binding.py before", "binding-after": "python3 -I tests/W04_RETRY_BROWSER/binding.py after"}
rows = []
for f in sorted(E.glob("*.json")):
    if f.name in ("execution-index.json", "privacy-manifest.json"):
        continue
    d = json.loads(f.read_text())
    if f.name.startswith("binding-"):
        rows.append({"phase": d["phase"], "file": f.name, "command": CMD.get(f.stem), "ok": d["ok"], "at": d["at"], "selected_files": d["selected_total"], "installed_mismatches": len(d["installed_mismatches"]), "exit": 0 if d["ok"] else 1})
        continue
    st = collections.Counter(c["status"] for c in d["checks"] if not c["name"].startswith("masked screenshot"))
    cl = collections.Counter(c["status"] for c in d["cleanup"])
    rows.append({"phase": d["phase"], "file": f.name, "command": "node tests/W04_RETRY_BROWSER/" + {"probe": "probe", "retry": "retry", "scope": "scope", "empty": "empty", "transaction": "transaction", "support": "support", "menu": "menu-diag", "final": "final-audit"}[d["phase"].split("-")[0]] + ".mjs " + " ".join(d.get("argv") or [d["phase"]]),
                 "started": d["started"], "finished": d.get("finished"), "chromium": d.get("chromium"), "browser_contexts": len(d["metrics"]),
                 "checks": dict(st), "screenshots": sum(1 for c in d["checks"] if c["name"].startswith("masked screenshot")), "cleanup": dict(cl),
                 "pageerrors": sum(len(m["pageerrors"]) for m in d["metrics"]), "external_attempts": sum(len(m["external_attempts"]) for m in d["metrics"]),
                 "external_connections": sum(len(m["external_connections"]) for m in d["metrics"]),
                 "exit": 1 if (st.get("FAIL") or cl.get("FAIL")) else 0})
tot = {"phases": len(rows), "browser_contexts": sum(r.get("browser_contexts", 0) for r in rows), "checks": dict(sum((collections.Counter(r.get("checks", {})) for r in rows), collections.Counter())), "screenshots": sum(r.get("screenshots", 0) for r in rows)}
(E / "execution-index.json").write_text(json.dumps({"note": "exit reconstructed from recorded FAIL statuses (process exit codes observed in-session match); probe-1 used supplied bearer that was already 401 and mislabelled its first observation PASS — retained unchanged", "totals": tot, "phases": rows}, indent=1, ensure_ascii=False) + "\n")
print(json.dumps(tot, ensure_ascii=False)); [print(r["phase"], r.get("checks"), r.get("cleanup"), r.get("browser_contexts"), r["exit"]) for r in rows]
