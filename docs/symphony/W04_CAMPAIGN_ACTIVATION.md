# W04 campaign activation — lead execution, independent gates pending

Observed 2026-10-09 19:49:08 UTC. Source checkpoint
`d059d735cd17ed3c88dd27cca5a9f83f00042011`, tree
`6dc9381b1d50196c81867ef3f9e31ca86611f410`. This is lead execution evidence,
not an independent product PASS. Existing Request-owned APP preview only;
Spring/business/production environments were not changed. TEST is exclusively
assigned to independent CODEX `req_812d0334c2064f5d88339114e7954235`.

The prior independent fc54 runtime review has completed with bounded PASS.
Original `e9025b56a1702ea28ca2cb1bcb18ec43d211385f`, intake
[W04_COMMON_BINDING_ATTACHMENT_INTAKE.md](W04_COMMON_BINDING_ATTACHMENT_INTAKE.md),
covers 12 rapid-save cases across 390/1440 and real native attachment bytes,
nonimage400, authorization/deletion/signature contracts. Twenty newly issued
tokens were revoked. Its evidence remains bound to travel0.1.2, not this campaign
activation. Native HTTP naturally writes throttle counters; the reviewer made
no direct counter/cache manipulation.

## Actual native lifecycle and guarded provisioning

All commands used the marked package runner, with native local APP schema
`req81_travel_lab`, scoped account, mailarray/queuesync/local storage/mysql-fulltext
and database cache. No setup/account/password regeneration or TEST command.

| Command | Exit / observation UTC |
| --- | --- |
| `template:build raonslab-travel_lab --production` | 0 / 19:46:21 |
| `module:update raonslab-travel_lab --force --source=bundled --no-interaction` | 0 / 19:46:30, 0.1.2→0.1.3 |
| `template:update raonslab-travel_lab --force --source=bundled --no-interaction` | 0 / 19:46:48, 0.1.2→0.1.3 |
| Explicit `campaigns-provision --lab-confirm --actor=56`, process-only campaign flag1 | 0 / 19:47:05, Pages7/8 created |
| Same explicit command again | 0 / 19:47:21, created0/skipped2 |
| Ordinary flag-off command, same explicit actor/confirm | 1, before-write rejection |
| Measured explicit replay with process-only flag1 | 0, identical whole Page row hashes, IDs, version1/publication and one native snapshot each |

Actor56 is the existing synthetic review administrator, explicitly supplied;
native Page read/create permissions were checked. No automatic actor selection,
new roles or permission changes. The private APP environment has no campaign
flag, so default provisioning remains false before and after; only the confirmed
CLI subprocess received flag1. No persistent environment change/service restart.
Published campaign list returned HTTP200 and the two native persisted titles,
versions and theme catalog links. Actual 390/1440 administration/publication,
HTML/draft/version transitions still require the new independent browser gate.

## Runtime artifacts

The official production rebuild changed one character of the supplied compiled
bundle: embedded Board dependency floor `>=1.1.2`→`>=1.1.3` (not template version).
Both template versions are already0.1.3. Character lengths68807, offset64272;
native readonly frontend reviewer independently confirmed the diff and eleven
installed/bundled source/layout/manifest/JS/CSS pairs. No sourcemap reference or
`.map` artifact is present. New bundle SHA256:
`8941442d1a921c2f366063359e39f79ed3a719bce77a174b6c0aa5657b691d63`.

Core served engine remains `b8cf27fab58e108b1509c379a5f4d0860a21a90bc591bb34b48a94d6e56cb76d`;
ActionDispatcher remains `bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68`;
bundled/installed Board controller remains
`d368cb0108765094f6739c3f3a110d7e597fff6606ad367971943dc091a6c816`.
The [runtime parity inventory](evidence/W04_CAMPAIGN_ACTIVATION/runtime-source-parity.json)
states its selection/exclusions; it is a lead observation, not blanket installed
tree equivalence. [Commands/measurements and original/published hashes](evidence/W04_CAMPAIGN_ACTIVATION/manifest.json)
retain only normalized Request paths and synthetic IDs/hashes, no environments,
tokens/passwords/emails/contact/signed URLs or DB dumps.

Hosted Actions/check runs at d059: 0/0, **NOT_RUN**. Canonical Validation receipt:
absent. Fixed-source independent TEST installation and new campaign browser
results are pending; earlier results are preserved at their original source.
