# W04 private attachment evidence intake — ACCEPT_WITH_LIMITS

Lead compared original nonauthor commit
`abf354abcab07f171a4e83989561a16364adb113`, Request
`req_34bd43fee9d043399c7662d480a9dbc0`, fixed target
`992f9a65ac3f8957e5ec618f21072dc499053810`. Canonical state COMPLETED,
Validation receipt absent, cached Git review blocker retained. This is evidence
acceptance, not permission, official Validation or overall release PASS.

Exactly four paths changed, all under `docs/symphony`: report, probe, source
provenance and scrubbed results. No product or parent environment/source changes.
The public result JSON has no long credential/signature fields or bearer tokens
under the lead's bounded scan; this scan is not a general secret-detector PASS.
Source comparison documents 21 relevant installed/core paths and runtime source
inheritance from fa552317 to 992. Git reachability is separately preserved by
lead publication, not inferred from the Provider result.

## Accepted observations

The actual support API created one own synthetic private question; native admin
uploaded a real inert text file and PNG without enabling member uploads. Admin
download/preview and owner PNG preview matched real stored bytes. Other-member
and guest unsigned download/preview/delete/post-read attempts exposed no bytes,
filename or body. This closes the earlier **real-file unauthorized access**
NOT_RUN within the routes and actors exercised; nonexistent IDs were not used
as the positive control. Six derived direct-storage paths exposed no bytes.
Deleted-attachment/post transitions were rechecked against formerly readable
files. Physical files and soft-deleted records remain by native design; no
prune or physical removal is claimed. Three newly issued login tokens were
logged out and rejected with 401; supplied tokens remained available.

The raw step summary is 11 PASS / 1 OBSERVED / 0 FAIL, scoped to the probe's
confidentiality checks. It does **not** erase the following product defect or
prove a release free of defects.

## Limits and concrete defect

- **W04PA-01 P3:** non-image preview returns 500 because the native board
  controller calls nonexistent `badRequest()`. The cause is visible in source,
  and live text-preview responses reproduce it. Lead assigned a minimal native
  board repair, fail-first response regression and later fixed-source live check.
  It remains OPEN until those checks pass; the reviewer authored no fix.
- Valid admin-issued signed PNG preview URLs are bearer capabilities under the
  documented native contract: guest possessing the valid URL obtained bytes,
  altered/absent signature was denied. Thus the accepted denial claim concerns
  **unauthorized requests without delegated preview capability**, not every
  conceivable guest request. No signed URL is included in public evidence.
- The question owner cannot download attachments on the travel board because
  native download permissions remain admin-only. Owner PNG preview works; the
  travel support resource intentionally omits all attachments. No customer
  upload/download UX is promised or enabled by this verification.
- The probe's numeric user-ID comparison was false because the native resource
  returns UUID. Authorship is corroborated by `is_mine`, distinct owner/foreign
  behavior and normal logins, rather than silently treating that comparison as
  PASS. No independent SQL or browser UI was executed.
- Storage deletion is native soft deletion. Retained synthetic files are
  inaccessible through the exercised routes, not physically absent.
- Supplied source SHA fa552317 differs from review head 992; relevant runtime
  source identity is demonstrated by Git/installed hash comparisons. This is
  explicit inheritance, not an unchanged handoff SHA comparison PASS.

Original report/probe/raw observations remain unchanged. Native board repair
and campaign implementation will require their own final source attribution
and postintegration verification. Hosted CI/canonical Validation NOT_RUN.
