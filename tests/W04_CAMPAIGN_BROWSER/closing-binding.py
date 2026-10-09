"""Final read-only binding after bounded browser follow-up; raw snapshots preserved."""
import importlib.util,json,pathlib
root=pathlib.Path(__file__).resolve().parents[2]
out=root/'tests/W04_CAMPAIGN_BROWSER/evidence/closing-binding.json'
assert not out.exists()
spec=importlib.util.spec_from_file_location('fixed_binding',root/'tests/W04_CAMPAIGN_BROWSER/source-binding.py')
mod=importlib.util.module_from_spec(spec);spec.loader.exec_module(mod)
old=json.loads(mod.DEST.read_text());current=mod.collect()
checks={'active_required_matches':mod.required_equal(current),'direct_served_assets_match':current['served_all_equal'],'shell_assets_match':current['public_shell_asset_binding']['all_equal'],'initial_selected_inventory_unchanged':mod.stable(old['before'])==mod.stable(current),'after_selected_inventory_unchanged':mod.stable(old['after'])==mod.stable(current),'supplemental_entrypoints_unchanged':old['before']['supplemental_core_entrypoints']['files']==current['supplemental_core_entrypoints']['files'],'supplemental_languages_unchanged':old['before']['supplemental_core_languages']['files']==current['supplemental_core_languages']['files']}
out.write_text(json.dumps({'checks':checks,'status':'PASS' if all(checks.values()) else 'FAIL','snapshot':current,'scope':'Same bounded installed/source/served selection after final browser follow-up, no config/env/DB/cache writes.'},indent=2)+'\n')
print(json.dumps(checks));raise SystemExit(0 if all(checks.values()) else 1)
