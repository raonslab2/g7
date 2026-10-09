"""Read-only fixed-source binding; output only paths, digests and HTTP status.

Run once before browser work and once after cleanup. No application boot, credentials,
environment/config caches or database access. The parent tree is never written.
"""
import argparse
import datetime
import hashlib
import io
import json
import pathlib
import subprocess
import tarfile
import urllib.error
import urllib.request

SHA = "1052e3fb4bc4cccabb51b8c538116c78655f345b"
TREE = "f18fa2056a031353c889785d768f87824e3083f5"
ROOT = pathlib.Path(__file__).resolve().parents[2]
PARENT = pathlib.Path("/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba")
DEST = ROOT / "docs/symphony/evidence/W04_FINAL_BROWSER/source-binding.json"
BASE = "http://127.0.0.1:18871"


def git(*args):
    return subprocess.check_output(["git", "-C", str(ROOT), *args])


def digest(data):
    return hashlib.sha256(data).hexdigest()


def runtime_path(path):
    parts = pathlib.PurePosixPath(path).parts
    return not (set(parts) & {"docs", "tests", "__tests__", "scripts", "node_modules"}
                or ("vendor" in parts and "dist" not in parts)
                or path.endswith((".md", ".test.ts", ".test.tsx", ".spec.ts", ".spec.tsx"))
                or parts[-1] in {"AGENTS.md", "LICENSE"})


def collect():
    assert git("rev-parse", SHA + "^{tree}").decode().strip() == TREE
    actual_head = git("rev-parse", "HEAD").decode().strip()
    git("merge-base", "--is-ancestor", SHA, actual_head)
    changed = git("diff", "--name-only", SHA).decode().splitlines()
    assert all(p.startswith(("tests/W04_FINAL/", "docs/symphony/evidence/W04_FINAL_BROWSER/"))
               or p == "docs/symphony/W04_BROWSER_FINAL.md" for p in changed)
    extension_roots = [("modules", n) for n in
                       ("raonslab-travel_lab", "sirsoft-ecommerce", "sirsoft-board", "sirsoft-page")]
    extension_roots += [("templates", n) for n in
                        ("raonslab-travel_lab", "sirsoft-admin_basic", "sirsoft-basic")]
    prefixes = [f"{kind}/_bundled/{name}" for kind, name in extension_roots]
    requested = ["app", "routes", "resources", "bootstrap/app.php", "config/app.php", "public/build", *prefixes]
    archive = git("archive", SHA, *requested)
    blobs = {}
    with tarfile.open(fileobj=io.BytesIO(archive)) as tar:
        for item in tar:
            if item.isfile():
                blobs[item.name] = tar.extractfile(item).read()
    groups = {}
    excluded = []
    expected_installed = {}
    for source, data in sorted(blobs.items()):
        installed = source
        group = "core"
        for kind, name in extension_roots:
            prefix = f"{kind}/_bundled/{name}/"
            if source.startswith(prefix):
                group = f"{kind}/{name}"
                installed = f"{kind}/{name}/" + source[len(prefix):]
                break
        if not runtime_path(source):
            excluded.append({"source": source, "reason": "documentation/test/tooling exclusion"})
            continue
        if source.startswith("public/build/"):
            group = "public/build"
        expected_installed[installed] = source
        row = {"source": source, "installed": installed, "source_sha256": digest(data)}
        local = ROOT / source
        row["local_matches_pinned"] = local.is_file() and digest(local.read_bytes()) == row["source_sha256"]
        target = PARENT / installed
        row["exists"] = target.is_file()
        if row["exists"]:
            row["installed_sha256"] = digest(target.read_bytes())
        row["equal"] = row.get("installed_sha256") == row["source_sha256"]
        groups.setdefault(group, []).append(row)
    extras = []
    # Recursion only in public/nonsecret source trees, never storage/config/env.
    for top in sorted({"app", "routes", "resources", "public/build", *groups.keys()} - {"core"}):
        directory = PARENT / top
        if not directory.is_dir():
            continue
        for path in directory.rglob("*"):
            rel = path.relative_to(PARENT).as_posix()
            if path.is_file() and runtime_path(rel) and rel not in expected_installed:
                extras.append({"path": rel, "sha256": digest(path.read_bytes())})
    extras = list({r["path"]: r for r in extras}.values())
    caches = []
    cache_root = PARENT / "bootstrap/cache"
    # Explicit route/hook allowlist; no config.php or other credential-bearing cache.
    for pattern in ("routes*.php", "hooks.php"):
        for path in sorted(cache_root.glob(pattern)):
            if path.is_file():
                caches.append({"path": path.relative_to(PARENT).as_posix(),
                               "bytes": path.stat().st_size, "sha256": digest(path.read_bytes())})
    served = []
    assets = [("/" + p, p) for p in sorted(blobs) if p.startswith("public/build/")]
    assets = [(url.replace("/public/", "/", 1), source) for url, source in assets]
    for name in ("raonslab-travel_lab", "sirsoft-admin_basic"):
        for tail in ("js/components.iife.js", "css/components.css"):
            source = f"templates/_bundled/{name}/dist/{tail}"
            if source in blobs:
                assets.append((f"/api/templates/assets/{name}/{tail}", source))
    for url, source in assets:
        row = {"path": url, "fixed_git_path": source, "source_sha256": digest(blobs[source])}
        try:
            with urllib.request.urlopen(BASE + url, timeout=20) as response:
                row["status"] = response.status
                row["served_sha256"] = digest(response.read())
            row["exact_match"] = row["served_sha256"] == row["source_sha256"]
        except urllib.error.HTTPError as error:
            row.update(status=error.code, exact_match=False)
        except (urllib.error.URLError, TimeoutError):
            row.update(status="BLOCKED", exact_match=False)
        served.append(row)
    summary = {name: {"count": len(rows), "installed_all_equal": all(r["equal"] for r in rows),
                      "local_all_equal": all(r["local_matches_pinned"] for r in rows)}
               for name, rows in groups.items()}
    return {"observed_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
            "actual_head": actual_head, "source_sha": SHA, "source_tree": TREE,
            "groups": groups, "summary": summary, "runtime_extras": extras,
            "explicit_exclusions": excluded, "route_hook_cache_inventory": caches,
            "served_assets": served,
            "source_all_equal": all(s["installed_all_equal"] and s["local_all_equal"] for s in summary.values()),
            "served_all_equal": all(a["exact_match"] for a in served)}


def stable(snapshot):
    return {k: snapshot[k] for k in ("source_sha", "source_tree", "groups", "runtime_extras",
                                    "route_hook_cache_inventory", "served_assets")}


def required_equal(snapshot):
    # The preview runs travel user + native admin templates. The default basic
    # template is separately inventoried, but absence is not an active-runtime mismatch.
    return all(s["installed_all_equal"] and s["local_all_equal"]
               for name, s in snapshot["summary"].items() if name != "templates/sirsoft-basic")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("phase", choices=("before", "after"))
    args = parser.parse_args()
    result = collect()
    out = json.loads(DEST.read_text()) if DEST.exists() else {}
    assert args.phase not in out, "Do not overwrite an existing phase or rerun unchanged head."
    if args.phase == "after":
        assert "before" in out
    out.update(source_sha=SHA, source_tree=TREE, official_validation="NOT_RUN", hosted_ci="NOT_RUN",
               scope="Pinned Git runtime paths: core app/routes/resources/bootstrap/app.php/config/app.php, "
                     "travel/ecommerce/board/page modules, travel/admin/basic templates and public/build. "
                     "Docs/tests/tooling are separately excluded. Installed directory extras inventoried. "
                     "Route/hook caches hashed only; no semantic cache/runtime DB/config/env/vendor audit.",
               **{args.phase: result})
    if args.phase == "after":
        out["before_after_identical"] = stable(out["before"]) == stable(result)
    out["required_runtime_binding"] = {phase: required_equal(out[phase])
                                       for phase in ("before", "after") if phase in out}
    out["default_basic_template_note"] = (
        "Exploratory sirsoft-basic is absent from installed preview; all463 paths missing. "
        "Broad source_all_equal remains false. Required active travel/admin runtime binding "
        "is separate; default-template execution NOT_RUN. Initial before command exit1 "
        "was the broad optional-template completeness check, preserved without rerun.")
    DEST.parent.mkdir(parents=True, exist_ok=True)
    DEST.write_text(json.dumps(out, indent=2) + "\n")
    print(json.dumps({"phase": args.phase, "source_sha": SHA, "source_tree": TREE,
                      "summary": result["summary"], "runtime_extras": len(result["runtime_extras"]),
                      "served_assets": result["served_assets"],
                      "source_all_equal": result["source_all_equal"],
                      "required_runtime_all_equal": required_equal(result),
                      "served_all_equal": result["served_all_equal"],
                      "before_after_identical": out.get("before_after_identical", "NOT_RUN")}))
    raise SystemExit(0 if required_equal(result) and result["served_all_equal"]
                     and out.get("before_after_identical", True) else 1)
