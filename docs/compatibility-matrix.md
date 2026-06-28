# Compatibility Matrix

This matrix maps the developer kit against the BotBlocker Security add-on implementation.

## Runtime surfaces

| Surface | Location | Purpose | Contract status |
| --- | --- | --- | --- |
| Third-party source package | Any developer workspace | Edited source code | Must become a valid v2 ZIP |
| Developer kit sample | `examples/acme-botblocker-sample` | Canonical third-party v2 template | Recommended |
| Developer kit traffic sample | `examples/acme-traffic-guard` | Advanced pre-run traffic decision provider template | Critical-risk; use only when needed |
| Runtime add-ons | `wp-content/uploads/botblocker/addons` | Installed, scanned, activated packages | Authoritative |
| BotBlocker object read access | `BotBlocker::getInstance()` | Final request state for add-ons | Read-only; timing-limited |
| In-cycle traffic decisions | Pre-run `traffic_decision_provider` plus `BotBlocker::run()` stages | Allow/block/captcha/redirect/bypass/log-only before core final response | Supported only with explicit pre-run opt-in |

## v2 package requirements

| Requirement | Core behavior | Kit status | Action |
| --- | --- | --- | --- |
| `bbcs-addon.json` in root | Parsed by `BotBlockerAddons::parseManifest()` | Documented | Keep |
| Root folder equals slug | Enforced by upload validation and manifest normalization | Documented | Keep |
| `requires_core` present | Required and enforced | Documented | Keep |
| `requires_php` | Enforced when declared | Documented | Keep recommended |
| `core` file exists | Required for valid v2 package | Documented | Keep |
| `main` file | Optional metadata/bootstrap | Documented | Keep recommended |
| `settings.view` | Renders Tools tab only when active | Documented | Keep |
| `settings.option` | Required for generic v2 settings save | Documented | Strengthen |
| `settings.sanitize` | Called when callable | Documented | Make mandatory for quality |
| `assets.icon` | Used for Add-ons UI card when file exists | Documented | Keep |
| `features` | Exposed through provider helpers | Documented | Keep |

## Baseline versions

| Package type | Recommended baseline |
| --- | --- |
| New third-party v2 package | `1.6.20+` |
| Developer kit sample | `1.6.20` |
| PHP | `7.4+` |
| WordPress | `5.0+`, tested to `7.0` |

## Settings patterns

| Pattern | Example | Third-party v2 status |
| --- | --- | --- |
| Manifest option array | `acme_bbcs_sample_settings[enabled]` | Required/recommended |
| Third-party v2 option array | `vendor_addon_settings[enabled]` | Required/recommended |
| BotBlocker built-in plain field | `disable_emojis` | Do not copy for third-party v2 |
| Shared BotBlocker option | `botblocker_tools_core_settings` | Internal/built-in only |

Third-party packages must not rely on BotBlocker core's hardcoded built-in option processing. They should use `settings.option` and render every field under that option array.

## Asset patterns

| Pattern | Example | Status |
| --- | --- | --- |
| Runtime asset helper | `BotBlockerAddons::fileUrl( 'slug', 'assets/admin.js' )` | Required for uploaded add-ons |
| Source plugin URL | `plugin_dir_url( __FILE__ )` | Wrong for uploaded add-ons |
| Direct uploads URL | `BotBlockerMultisite::getAddonsUrl() . 'slug/file.svg'` | Works but less safe than the helper |
| Inline style/script | `wp_add_inline_style()` | Valid when asset URL delivery is blocked or unnecessary |

## Lifecycle support

| Event | Dispatch point | Recommended use |
| --- | --- | --- |
| `install` | Package install/update installer | Validate/create package-owned data only when needed |
| `activate` | Admin activation or update reactivation | Create defaults, schedule jobs |
| `deactivate` | Admin deactivation, incompatible add-on, update/delete | Unschedule jobs, disable runtime side effects |
| `delete` | Before runtime folder deletion | Remove package-owned options/data when intended |
| `update` | Package replacement | Migrate package-owned data |
| `load` | Active add-on include on request | Lightweight diagnostics only |
| `health_check` | After active add-on load | Repair reversible integration state |

## Traffic add-on support

Traffic add-ons are critical-risk code. Prefer post-check hooks for ordinary redirects. Use the pre-run provider path only when the add-on must participate inside BotBlocker's request cycle and can be tested with dry-run, staging, loop protection, and rollback steps.

| Use case | Current v2 status | Required pattern |
| --- | --- | --- |
| Read final visitor/request data | Supported from later WP hooks | `BotBlocker::getInstance()` snapshot, see `botblocker-request-data.md` |
| Redirect allowed frontend requests | Supported | `template_redirect` after BotBlocker security pages, see `traffic-and-redirect-addons.md` |
| Redirect/check admin pages | Supported only when explicitly scoped | Capability checks, nonce checks, no public redirects |
| Override BotBlocker block/check/deny decision | Supported only for `traffic_decision_provider` pre-run add-ons | `runtime.pre_run` manifest contract and decision provider from `core-hook-integration.md` |
| Early-init traffic routing | Not supported by normal v2 add-ons | Separate early-init provider contract |

## Known mismatches

| Mismatch | Impact | Resolution |
| --- | --- | --- |
| BotBlocker built-in options use plain settings fields | AI may copy an invalid third-party save pattern | Documented in settings-contract |
| Protected uploads may block static asset URLs | Frontend/admin JS or icons may fail in some servers | Require HTTP 200 asset test; track core delivery gap |
| Normal active v2 add-ons load after the main request-check cycle | Generic add-ons cannot make in-cycle traffic decisions | Use post-check pattern or explicit `traffic_decision_provider` pre-run contract |
