# Known Core Contract Gaps

These are known mismatches between the public add-on kit and the BotBlocker Security codebase (`1.7.5` or later, the minimum version required for the Add-on API v2 system).

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

## Pre-run traffic decision provider (system reference - NOT a gap)

The v2 pre-run traffic decision provider system is fully implemented and production-ready as of BotBlocker Security 1.7.5. Add-ons that declare `traffic_decision_provider` in their features list and satisfy the `runtime.pre_run` contract are loaded by the plugin bootstrap (`botblocker-security.php`), and again in the AJAX check handler, via `BotBlockerAddons::includePreRunAddons()` before the main `BotBlocker::initialize()` cycle begins.

Once loaded, registered providers participate at seven decision stages inside `BotBlocker::run()` (six in `class-botblocker.php`, plus `after_request_data` in `class-botblocker-visitor-trait.php`):

- `before_prefly_checks` (before any core preflight checks)
- `after_request_data` (in visitor-trait, after request data is collected)
- `after_visitor_data` (after visitor data is collected)
- `pre_core_rules` (before IP/ASN/rule database/rugov/path rules)
- `post_core_rules` (after all core rules have checked)
- `post_rate_limit` (after core rate limiting, before cookie and heuristic checks)
- `before_final_allow` (final gate before unconditional allow)

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

### Weekly-report add-ons run outside the request cycle (not a gap)

Notification add-ons (`bbcs-pusher`, `bbcs-telegram`) are weekly-report senders: they run on their own cron schedule and read statistics from `bbcs_counters`. They have no in-cycle code and no pre-run contract — the traffic pipeline never executes them.

- The public `bbcs_botblocker_blocked_request` action fires for in-cycle observers (decision-trait `block` decisions and the core response pipeline); the rate-limit path emits `bbcs_rate_limit_blocked` only. Third parties may observe with plain `add_action` — no registration contract needed.
- Dark challenges (captcha decisions and `redirect_to_dark`) intentionally emit no block alert.

### Runtime addon palette entries require matching promo registration

Installed addons get automatic ⌘K palette entries from their `ui.palette` manifest data via `BotBlockerPalette::getAddonPaletteActions()`. For addons NOT yet installed (marketing/promo visibility), entries must be registered in `BotBlockerPalette::getAddonPromoActions()` in `includes/data/class-botblocker-palette.php`.

Third-party addons can register promo entries via the `bbcs_palette_addon_promo_actions` filter:
```php
add_filter('bbcs_palette_addon_promo_actions', function(array $promos) {
    $promos[] = ['ic' => 'star', 't' => 'My Addon', 'go' => 'addons', 'addon' => 'my-addon', 'pro' => true];
    return $promos;
});
```

Active installed addons get `'tab' => $slug` entries (link to settings). Inactive-but-installed addons get `'addon' => $slug` entries (install prompt). Uninstalled addons with promo entries get `'addon' => $slug` entries (marketplace prompt).

**This is a known design tension**: manifest-driven discovery works only for installed addons. The promo list is the sole remaining centralized addon reference in core — a curated marketing catalog that bridges the gap between "not installed" and "discoverable".

**Recommendation for third-party addons**: register your promo entry via the filter in a file that loads early (e.g., a standalone plugin bootstrap or a must-use plugin). This ensures palette visibility before the addon itself is installed.

## Manifest fields parsed but not consumed by core

The following manifest fields are accepted by `BotBlockerAddons` normalization but are currently NOT consumed by any core runtime path:

| Field | Parsed by | Runtime reality |
| --- | --- | --- |
| `gateway.*.mutual_exclusion` | manifest only (declared by `bbcs-early-init`) | `BotBlockerAddons::normalizeGateway()` does not pass it into the gateway registry, so `BotBlockerGateway::enableGateway()` never sees it for v2 packages. Gateway mutual exclusion is supported by the API but not fed by manifests. |
| `ui.settings_sections` | `normalizeUi()` | Stored in the addon array but no core screen reads it; core settings sections are not extendable through this field. |
| `storage.cleanup_on_uninstall` | `normalizeStorage()` | The uninstaller reads only `storage.cache_dirs`; the flag is ignored. |

Do not rely on these fields in third-party packages until core starts consuming them.
