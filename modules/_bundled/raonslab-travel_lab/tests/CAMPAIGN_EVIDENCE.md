# Campaign implementation intake — actual local verification

Canonical implementation Request req_7fe59f839ad748fcb2db4c8ad4b488b0 ended
FAILED while its native reviewer was pending; failure cause UNKNOWN. Saved
sourcec85eea30ca674289648c772f7f36ea4cdaa1611f (parent992) was intaken locally
as95d16543. Exact78 changed paths/29PHP inputs were independently compared.
Provider self-assertions were not substituted for executed validation.

The two fixed native Page slots, projection API, safe PageBody/customer home/
list/detail and native admin adapter/provisioning are implemented in source.
No new DB tables, Page default seeding, real commerce or external integration.
Lead integration raises Boardminimum1.1.3, preserves existing Page>=1.1.2 and
Traveltemplate>=0.1.3, adds default-disabled campaign provisioning example plus
explicit guarded native command/actor recipe, and repairs two small test/UX
findings. Runtime remains frozen at the prior repairedcore/boardfc54 target
while its independent live verification runs; campaign source is not deployed.

## Executed checks (not invented unique totals)

- Focused new Campaign PHP20tests/384assertions PASS21.208s, reviewed source Pint
  PASS. Full canonical SQLite initially175/2917 with1source-origin assertion
  failure comparing already-loaded installedModule to new bundledModule.
- Assigned test-only fix checks the bundled declaration in a clean native
  subprocess, verifies path/hash/listener/four sync callbacks and preserves
  ordinary/travel product-option/no-order/no-payment/stock assertions. Focused
  checkout3/61 PASS; final expanded ONCE175/2936 PASS90.541s. Native/MySQLsupport
  four classes remain excluded only by the canonical SQLite config.
- Template typecheck PASS; original Campaign source fulltemplate12files/160tests
  PASS, including PageBody8 and campaign12. Later Boardconstraint change exposed
  existing contract fixture29PASS/1FAIL; authorized expectedversion fix30/30PASS.
- New module native admin layout initially9/9PASS. Permission-navigation repair
  hides edit by rowcan_update and missing creation by collectioncan_create;
  failfirst9PASS/3FAIL, intermediate wrongslug10PASS/2FAIL, corrected12/12PASS.
  Read/detail/version/publish/permission enforcement remains native. Author fix
  evidence and separate nonauthor source review are distinguished.

Full source/commands/pins/boundaries: [backend intake](../../../../docs/symphony/W04_CAMPAIGN_BACKEND_INTAKE.md),
[frontend intake](../../../../docs/symphony/W04_CAMPAIGN_FRONTEND_INTAKE.md),
[package intake](../../../../docs/symphony/W04_CAMPAIGN_PACKAGE_INTAKE.md),
[final source review](../../../../docs/symphony/W04_CAMPAIGN_FINAL_SOURCE_REVIEW.md).
Public normalized runner receipts/logs plus original/publishedhash manifests:
[W04_CAMPAIGN_INTAKE](../../../../docs/symphony/evidence/W04_CAMPAIGN_INTAKE/).
Original failures, CLI config errors and dependency-cache EACCES are retained;
corrected harnesses don't retrospectively erase them.

## Pending fixed-source actual gates

Native installed source/routes/cache/bundle attribution, explicit Page provision
create/skip/guards/version/auth restoration, real390/1440Page admin editor/version/
publish/restore and publicdraft/nonregistry404, catalog/cart continuation, actual
HTML/remote-resource denial, roles/scopes, SEO/cache/localjobs and restart recovery
must be independently checked after integration. General editorcanvas/browser
preview and external/private search engine paths not executed remain NOT_RUN.
HostedCI/canonicalValidationreceipt NOT_RUN, no release/main/production waiver.
