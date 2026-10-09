"""Read-only fixed-source binding; output only paths, digests and HTTP status.

Run before browser work, after cleanup, and after a separately authorized sort extension.
Each named phase is append-only; the original after snapshot is preserved. No application boot, credentials,
environment/config caches or database access. The parent tree is never written.
"""
import argparse
import datetime
import hashlib
import io
import json
import pathlib
import re
import urllib.parse
import subprocess
import tarfile
import urllib.error
import urllib.request

SHA = "31a18318f91dde34a9c75eaaac65ae1d434b7bc3"
TREE = "c0984e682a77153753edcde318a6d44b6973b1c0"
ROOT = pathlib.Path(__file__).resolve().parents[2]
PARENT = pathlib.Path("/home/ubuntu/.agentopt-v2/workspaces/req_81ac33cac94046b9a2249cd14c0d00ba")
DEST = ROOT / "tests/W04_CAMPAIGN_BROWSER/evidence/source-binding.json"
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
                or parts[-1] in {"AGENTS.md", "LICENSE"}
                or any(part.startswith(".env") for part in parts))


def collect():
    assert git("rev-parse", SHA + "^{tree}").decode().strip() == TREE
    actual_head = git("rev-parse", "HEAD").decode().strip()
    git("merge-base", "--is-ancestor", SHA, actual_head)
    changed = git("diff", "--name-only", SHA).decode().splitlines()
    assert all(p.startswith(("tests/W04_CAMPAIGN_BROWSER/", "tests/W04_CAMPAIGN_BROWSER/evidence/"))
               or p == "docs/symphony/W04_CAMPAIGN_BROWSER_FINAL.md" for p in changed)
    extension_roots = [("modules", n) for n in
                       ("raonslab-travel_lab", "sirsoft-ecommerce", "sirsoft-board", "sirsoft-page")]
    extension_roots += [("templates", n) for n in
                        ("raonslab-travel_lab", "sirsoft-admin_basic", "sirsoft-basic")]
    prefixes = [f"{kind}/_bundled/{name}" for kind, name in extension_roots]
    requested = ["app", "routes", "resources", "bootstrap/app.php", "public/build", *prefixes]
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
            excluded.append({"source": source, "reason": "config directory excluded by request" if "config" in pathlib.PurePosixPath(source).parts else "documentation/test/tooling exclusion"})
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
        row["required_active_package"] = group != "templates/sirsoft-basic"
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
            "excluded_optional_packages": optional_packages(prefixes),
            "parent_directory_inventory": directory_inventory(),
            "served_assets": served,
            "public_shell_asset_binding": public_shell_assets(),
            "supplemental_core_entrypoints": supplemental_core_entrypoints(),
            "supplemental_core_languages": supplemental_core_languages(),
            "source_all_equal": all(s["installed_all_equal"] and s["local_all_equal"] for s in summary.values()),
            "served_all_equal": all(a["exact_match"] for a in served)}


def supplemental_core_entrypoints():
    # Discovered during verification; never rewrite the initial-before count.
    rows = []
    for source in ("public/index.php", "bootstrap/providers.php"):
        expected = git("show", SHA + ":" + source)
        local = ROOT / source
        installed = PARENT / source
        row = {"source": source, "source_sha256": digest(expected), "source_bytes": len(expected),
               "local_matches_pinned": local.is_file() and digest(local.read_bytes()) == digest(expected),
               "installed_exists": installed.is_file()}
        row["installed_sha256"] = digest(installed.read_bytes()) if installed.is_file() else None
        row["equal"] = row["installed_sha256"] == row["source_sha256"]
        rows.append(row)
    return {"observed_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
            "timing": "During-verification supplement or after phase; not initial-before", "count": len(rows),
            "all_equal": all(r["equal"] and r["local_matches_pinned"] for r in rows), "files": rows}


def supplemental_core_languages():
    rows = []
    for source in git("ls-tree", "-r", "--name-only", SHA, "--", "lang/").decode().splitlines():
        if not runtime_path(source):
            continue
        expected = git("show", SHA + ":" + source)
        installed = PARENT / source
        local = ROOT / source
        row = {"source": source, "source_sha256": digest(expected), "source_bytes": len(expected),
               "local_matches_pinned": local.is_file() and digest(local.read_bytes()) == digest(expected),
               "installed_exists": installed.is_file(),
               "installed_sha256": digest(installed.read_bytes()) if installed.is_file() else None}
        row["equal"] = row["installed_sha256"] == row["source_sha256"]
        rows.append(row)
    known = {r["source"] for r in rows}
    extras = [{"path": p.relative_to(PARENT).as_posix(), "sha256": digest(p.read_bytes())}
              for p in (PARENT / "lang").rglob("*") if p.is_file()
              and runtime_path(p.relative_to(PARENT).as_posix()) and p.relative_to(PARENT).as_posix() not in known]
    return {"observed_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
            "timing": "During-verification root lang/ supplement or after phase; not initial-before", "count": len(rows),
            "all_equal": all(r["equal"] and r["local_matches_pinned"] for r in rows) and not extras,
            "files": rows, "runtime_extras": extras}


def public_shell_assets():
    """Only public HTML asset URLs and asset digests; never persist HTML or runtime JSON.

    CSS is compared to pinned source after the pinned AssetCssUrlRewriter's relative
    URL transformation. This labels transformed serving separately from literal matches.
    CSS subresource URLs are inventoried, not claimed browser-executed.
    """
    refs = {}
    for shell in ("/", "/admin"):
        with urllib.request.urlopen(BASE + shell, timeout=20) as response:
            html = response.read().decode()
        for ref in re.findall(r'(?:src|href)=["\']([^"\']+)["\']', html):
            parsed = urllib.parse.urlsplit(urllib.parse.urljoin(BASE, ref))
            if parsed.netloc != urllib.parse.urlsplit(BASE).netloc:
                continue
            if parsed.path.endswith((".js", ".css")):
                refs.setdefault(parsed.path + ("?" + parsed.query if parsed.query else ""), []).append(shell)
    rows = []
    for url, shells in sorted(refs.items()):
        parsed = urllib.parse.urlsplit(url)
        path = parsed.path
        extension = re.fullmatch(r'/api/templates/assets/([^/]+)/(.*)', path)
        source = "public" + path if path.startswith("/build/") else None
        if extension:
            name, tail = extension.groups()
            source = f"templates/_bundled/{name}/dist/{tail}"
        row = {"url": url, "referenced_by_shells": shells, "fixed_git_path": source}
        with urllib.request.urlopen(BASE + url, timeout=20) as response:
            served = response.read()
            row.update(status=response.status, served_sha256=digest(served))
        if source is None:
            row.update(binding="NOT_RUN", reason="no fixed Git asset mapping")
            rows.append(row)
            continue
        original = git("show", SHA + ":" + source)
        row["source_sha256"] = digest(original)
        row["literal_equal"] = original == served
        expected = original
        if extension and path.endswith(".css"):
            css = original.decode()
            prefix = f"/api/templates/assets/{name}/"
            version = urllib.parse.parse_qs(parsed.query).get("v", [None])[0]
            def replace(match):
                quote, reference = match[1], match[2].strip()
                if not reference or reference.startswith(("/", "#")) or re.match(r'^[a-zA-Z][a-zA-Z0-9+.-]*:', reference):
                    return match[0]
                fragment = ""
                if "#" in reference:
                    reference, fragment = reference.split("#", 1)
                    fragment = "#" + fragment
                reference = reference.split("?", 1)[0]
                if not reference:
                    return match[0]
                segments = tail.split("/")[:-1]
                for segment in reference.split("/"):
                    if segment in ("", "."):
                        continue
                    if segment == "..":
                        if segments: segments.pop()
                    else:
                        segments.append(segment)
                if not segments:
                    return match[0]
                absolute = prefix + "/".join(segments) + ("?v=" + version if version else "") + fragment
                quote = quote or '"'
                return ("@import " if match[0].startswith("@import") else "url(") + quote + absolute + quote + ("" if match[0].startswith("@import") else ")")
            css = re.sub(r'\burl\(\s*(["\']?)(.*?)\1\s*\)', replace, css, flags=re.S)
            css = re.sub(r'@import\s+(["\'])(.*?)\1', replace, css, flags=re.S)
            expected = css.encode()
            row["binding_method"] = "Pinned AssetCssUrlRewriter relative url()/@import rewrite; public extension URL mode observed"
        else:
            row["binding_method"] = "literal fixed Git bytes"
        row.update(expected_served_sha256=digest(expected), expected_served_equal=expected == served)
        row["binding"] = "PASS" if row["expected_served_equal"] else "FAIL"
        rows.append(row)
    return {"observed_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(), "asset_count": len(rows), "all_equal": all(r["binding"] == "PASS" for r in rows),
            "assets": rows, "css_subresources_note": "Installed dist subtree hashed; actual browser-executed subresource requests require browser trace comparison."}


def optional_packages(selected):
    paths = git("ls-tree", "-d", "--name-only", SHA, "modules/_bundled/", "plugins/_bundled/", "templates/_bundled/").decode().splitlines()
    return [{"path": path, "reason": "not installed in active preview; execution NOT_RUN"}
            for path in paths if path not in selected]


def directory_inventory():
    return {kind: sorted(p.name for p in (PARENT / kind).iterdir()
                         if p.is_dir() and not p.name.startswith("_"))
            for kind in ("modules", "templates", "plugins")}


def stable(snapshot):
    result = {k: snapshot[k] for k in ("source_sha", "source_tree", "groups", "runtime_extras",
                                    "route_hook_cache_inventory", "served_assets", "parent_directory_inventory", "public_shell_asset_binding")}
    result["public_shell_asset_binding"] = {k: v for k, v in result["public_shell_asset_binding"].items() if k != "observed_utc"}
    return result


def required_equal(snapshot):
    # The preview runs travel user + native admin templates. The default basic
    # template is separately inventoried, but absence is not an active-runtime mismatch.
    return (all(s["installed_all_equal"] and s["local_all_equal"]
                for name, s in snapshot["summary"].items() if name != "templates/sirsoft-basic")
            and all(snapshot[key]["all_equal"] for key in ("supplemental_core_entrypoints", "supplemental_core_languages") if key in snapshot))


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("phase", choices=("before", "after", "after_sort_extension"))
    args = parser.parse_args()
    out = json.loads(DEST.read_text()) if DEST.exists() else {}
    assert args.phase not in out, "Do not overwrite an existing phase."
    if args.phase in ("after", "after_sort_extension"):
        assert "before" in out
    if args.phase == "after_sort_extension":
        assert "after" in out, "Preserve the original after measurement before the sort extension."
    original_after_digest = digest(json.dumps(out["after"], sort_keys=True, separators=(",", ":")).encode()) if args.phase == "after_sort_extension" else None
    result = collect()
    out.update(source_sha=SHA, source_tree=TREE, official_validation="NOT_RUN", hosted_ci="NOT_RUN",
               scope="Pinned Git runtime paths: core app/routes/resources/bootstrap/app.php, "
                     "travel/ecommerce/board/page modules, travel/admin/basic templates and public/build. "
                     "Docs/tests/tooling/config directories are separately excluded; root config/env/vendor/storage excluded entirely. Installed directory extras inventoried. "
                     "Route/hook caches hashed only; no semantic cache/runtime DB/config/env/vendor audit.",
               **{args.phase: result})
    if args.phase == "after":
        out["before_after_identical"] = stable(out["before"]) == stable(result)
        out["during_after_entrypoints_identical"] = out.get("during_verification_entrypoints", {}).get("files") == result["supplemental_core_entrypoints"]["files"]
        out["during_after_languages_identical"] = out.get("during_verification_languages", {}).get("files") == result["supplemental_core_languages"]["files"]
    if args.phase == "after_sort_extension":
        out["before_after_sort_extension_identical"] = stable(out["before"]) == stable(result)
        out["during_after_sort_extension_entrypoints_identical"] = out.get("during_verification_entrypoints", {}).get("files") == result["supplemental_core_entrypoints"]["files"]
        out["during_after_sort_extension_languages_identical"] = out.get("during_verification_languages", {}).get("files") == result["supplemental_core_languages"]["files"]
        out["closing_runtime_binding"] = {
            "phase": args.phase,
            "required_source_equal": required_equal(result),
            "direct_served_assets_equal": result["served_all_equal"],
            "public_shell_assets_equal": result["public_shell_asset_binding"]["all_equal"],
            "initial_before_scope_identical": out["before_after_sort_extension_identical"],
            "during_entrypoints_identical": out["during_after_sort_extension_entrypoints_identical"],
            "during_root_languages_identical": out["during_after_sort_extension_languages_identical"],
            "original_after_serialized_sha256": original_after_digest,
            "original_after_preserved": original_after_digest == digest(json.dumps(out["after"], sort_keys=True, separators=(",", ":")).encode()),
        }
        out["closing_runtime_binding"]["all_equal"] = all(
            value for key, value in out["closing_runtime_binding"].items()
            if key not in {"phase", "original_after_serialized_sha256"})
    out["required_runtime_binding"] = {phase: required_equal(out[phase])
                                       for phase in ("before", "after", "after_sort_extension") if phase in out}
    optional = result["summary"].get("templates/sirsoft-basic", {})
    out["default_basic_template_note"] = (
        f"sirsoft-basic optional inventory: {optional.get('count', 0)} pinned paths. "
        "Not installed in this preview, execution NOT_RUN. Broad source_all_equal "
        "does not hide its absence; required_runtime_binding explicitly covers installed active packages.")
    out["excluded_sensitive_surfaces"] = [
        "root config/ only; immutable extension config source IS INCLUDED", "all .env variants",
        "bootstrap/cache/config.php and non-route/hook caches", "storage/ and DB",
        "root vendor/ third-party dependency implementation including vendor/autoload.php: execution not bound by this collector",
        "bootstrap/cache/autoload-extensions.php: excluded by route/hook-cache-only allowlist"]
    DEST.parent.mkdir(parents=True, exist_ok=True)
    DEST.write_text(json.dumps(out, indent=2) + "\n")
    print(json.dumps({"phase": args.phase, "source_sha": SHA, "source_tree": TREE,
                      "summary": result["summary"], "runtime_extras": len(result["runtime_extras"]),
                      "served_assets": result["served_assets"],
                      "source_all_equal": result["source_all_equal"],
                      "required_runtime_all_equal": required_equal(result),
                      "served_all_equal": result["served_all_equal"],
                      "before_after_identical": out.get("before_after_identical", "NOT_RUN"),
                      "supplemental_entrypoint_count": result["supplemental_core_entrypoints"]["count"],
                      "supplemental_root_lang_count": result["supplemental_core_languages"]["count"],
                      "during_after_entrypoints_identical": out.get("during_after_entrypoints_identical", "NOT_RUN"),
                      "during_after_languages_identical": out.get("during_after_languages_identical", "NOT_RUN"),
                      "closing_runtime_binding": out.get("closing_runtime_binding", "NOT_RUN")}))
    if args.phase == "after_sort_extension":
        raise SystemExit(0 if out["closing_runtime_binding"]["all_equal"] else 1)
    raise SystemExit(0 if required_equal(result) and result["served_all_equal"]
                     and result["public_shell_asset_binding"]["all_equal"]
                     and result["supplemental_core_entrypoints"]["all_equal"]
                     and result["supplemental_core_languages"]["all_equal"]
                     and out.get("during_after_languages_identical", True)
                     and out.get("during_after_entrypoints_identical", True)
                     and out.get("before_after_identical", True) else 1)
