# Public Header brand verification — 2026-10-04

Implementation: `400bb6ff` (main, AWS deployed). Product module 0.5.2.

The existing G7 public Header logo is replaced with a typography wordmark: RAON (22px) above AGENT FACTORY (9px). Mobile uses the native 56px Header with a 44px HOME link and accessible name RAON Agent Factory. Desktop retains the native Header component, HOME button, search, theme and account controls; its top bar remains 64px and navigation remains 50px. Hero wording is unchanged. No core or official template edits.

Changes: product-nav overlay (mobile logo replacement, desktop brand props and DOM marker), scoped module CSS and built CSS, synchronized module/components/composer/npm version metadata, changelog and version/injection contract assertions. Taxonomy generator remains the source for generated menu sections.

Validation: 108 Vitest tests passed; taxonomy check passed; Vite production build passed; git diff whitespace check passed. `browser.json` records real public SPA Chromium checks at 360×800, 390×844, 412×915 and 1440×900. Screenshots are committed alongside evidence. HOME and login navigation, mobile menu open/close, light/dark selection, no public Header G7 text, no overlap, no horizontal overflow and no uncaught JS errors are verified. Desktop has its existing navigation rather than the mobile menu.

AWS used an origin/main fast-forward followed by native `module:update raonslab-product --force --source=bundled --layout-strategy=overwrite`. Native module backup was created; env/storage were preserved. G7 FPM/web/queue services are active. No host reboot or core build was needed.

Runtime API regression evidence is in `runtime.json`: admin auth/users/settings, Page administration and private consultation board remain HTTP 200. Public native Page APIs remain 404 as before this work because original business content has not been migrated to AWS. This verifies preservation of that existing condition, not successful business Page content restoration. Registration and community routes are also checked by the browser script. Public consultation readiness gates were not altered.

Public URL: http://g7.3.34.73.254.sslip.io/
