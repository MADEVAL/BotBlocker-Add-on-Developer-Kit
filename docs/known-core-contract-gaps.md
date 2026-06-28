# Known Core Contract Gaps

These are known mismatches between the public add-on kit and the BotBlocker Security codebase (`1.6.20` or later, the minimum version required for the Add-on API v2 system).

## Runtime static assets may be blocked

BotBlocker installs add-ons into a protected uploads directory and writes `.htaccess`/`web.config` files that deny direct web access.

At the same time, v2 docs and examples use `BotBlockerAddons::fileUrl()` for icons, JavaScript, CSS, and images under that runtime directory. This helper builds a URL rooted at `BotBlockerMultisite::getAddonsUrl()` with the correct slug prefix and safely-encoded relative path.

Impact:

- Add-on cards may not display icons.
- Frontend/admin scripts may enqueue but fail with 403.
- CSS/image assets may fail in some server configurations.

Required follow-up:

- Test asset URLs in a real WordPress install.
- If blocked, add a BotBlocker core safe asset delivery endpoint or adjust protection rules to allow declared read-only assets while protecting PHP/data files.

Kit status:

- The kit documents the risk.
- The validator checks that asset paths exist and that code uses the correct helper (`BotBlockerAddons::fileUrl`).
- HTTP status must be tested in WordPress because it depends on server configuration.

## Pre-run traffic decision provider (system reference — NOT a gap)

The v2 pre-run traffic decision provider system is fully implemented and production-ready as of BotBlocker Security 1.6.20. Add-ons that declare `traffic_decision_provider` in their features list and satisfy the `runtime.pre_run` contract are loaded by the plugin bootstrap (`botblocker-security.php`), and again in the AJAX check handler, via `BotBlockerAddons::includePreRunAddons()` before the main `BotBlocker::initialize()` cycle begins.

Once loaded, registered providers participate at six decision stages inside `BotBlocker::run()`:

- `before_prefly_checks` (before any core preflight checks)
- `after_visitor_data` (after visitor data is collected)
- `pre_core_rules` (before IP/ASN/rule database/rugov/path rules)
- `post_core_rules` (after all core rules have checked)
- `before_final_allow` (final gate before unconditional allow)
- `after_request_data` (in visitor-trait, after request data is collected)

All six decision actions are supported: `allow`, `bypass`, `block`, `captcha`, `redirect`, and `log_only`.

Design distinction:

- Normal v2 add-ons (without `runtime.pre_run`) are loaded after `BotBlocker::initialize()` completes. They can read final state and react from later WordPress hooks, but cannot make in-cycle traffic decisions.
- Pre-run traffic providers (with `runtime.pre_run.contract = traffic_decision_provider`) are included before the request-check cycle and their decisions are routed through `BotBlockerAddonDecisionTrait::apply_addon_traffic_decisions()` at each declared stage.
- This distinction is by design, not a missing feature. Traffic decision add-ons MUST use the pre-run contract.

Required follow-up:

- Keep the distinction between normal late-loaded add-ons and pre-run traffic providers explicit.
- Keep provider decisions routed through BotBlocker core validation instead of letting add-ons mutate BotBlocker properties directly.
- Pre-run traffic providers are critical-risk add-ons. They should be disabled by default, support dry-run, and be tested on staging before production traffic is affected.

Kit status:

- Post-check redirect patterns are documented in `docs/traffic-and-redirect-addons.md`.
- Pre-run traffic provider hooks are documented in `docs/core-hook-integration.md`.
- The reference pre-run traffic provider package is `examples/acme-traffic-guard`.
- Available object fields are documented in `docs/botblocker-core-object.md`, `docs/botblocker-request-data.md`, and `docs/botblocker-settings-reference.md`.
- The pre-run manifest contract is: `pre_run.enabled = true`, `pre_run.file` points to the bootstrap, `pre_run.contract = 'traffic_decision_provider'`, `pre_run.register` is the callable that invokes `BotBlockerAddons::registerTrafficDecisionProvider()`.
- Validated provider callback signature: `function(BotBlocker $bbcs, string $stage, array $provider): ?array`.
- Validated decision return: `['action' => 'allow|bypass|block|captcha|redirect|log_only', 'reason' => '...', 'code' => 901, 'url' => '...', 'status' => 302]`.
