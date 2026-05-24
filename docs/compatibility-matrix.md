# Compatibility Matrix

This matrix maps the developer kit against the current BotBlocker Security add-on implementation.

## Runtime surfaces

| Surface | Location | Purpose | Contract status |
| --- | --- | --- | --- |
| Third-party source package | Any developer workspace | Edited source code | Must become a valid v2 ZIP |
| Developer kit sample | `examples/acme-botblocker-sample` | Canonical third-party v2 template | Recommended |
| First-party source add-ons | `plugin/wp-content/plugins/botblocker-security/addons` | BotBlocker-owned add-ons and marketplace source | Useful, but not always third-party-safe |
| Runtime add-ons | `wp-content/uploads/BotBlocker/addons` | Installed, scanned, activated packages | Authoritative |
| Marketplace builder input | `plugin/wp-content/plugins/botblocker-security/addons` | Source for `bbcs-addons` manager | Internal/legacy |

## v2 package requirements

| Requirement | Core behavior | Kit status | Action |
| --- | --- | --- | --- |
| `bbcs-addon.json` in root | Parsed by `bbcs_parse_addon_manifest()` | Documented | Keep |
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

| Package type | Observed baseline | Recommended baseline |
| --- | --- | --- |
| New third-party v2 package | `1.6.20+` | `1.6.20+` |
| Developer kit sample | `1.6.20` | `1.6.20` |
| Bundled first-party add-ons | commonly `1.6.15` | Internal only |
| PHP | `7.4+` | `7.4+` |
| WordPress | `5.0+`, tested to `7.0` | `5.0+` |

## Settings patterns

| Pattern | Example | Third-party v2 status |
| --- | --- | --- |
| Manifest option array | `acme_bbcs_sample_settings[enabled]` | Required/recommended |
| Third-party v2 option array | `vendor_addon_settings[enabled]` | Required/recommended |
| First-party legacy plain field | `disable_emojis` | Do not copy for third-party v2 |
| Shared BotBlocker option | `botblocker_tools_core_settings` | Internal first-party only |

Third-party packages must not rely on BotBlocker core's hardcoded first-party option processing. They should use `settings.option` and render every field under that option array.

## Asset patterns

| Pattern | Example | Status |
| --- | --- | --- |
| Runtime asset helper | `bbcs_addon_file_url( 'slug', 'assets/admin.js' )` | Required for uploaded add-ons |
| Source plugin URL | `plugin_dir_url( __FILE__ )` | Wrong for uploaded add-ons |
| Direct uploads URL | `bbcs_addons_url() . 'slug/file.svg'` | Works in some first-party code, but less safe than helper |
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

## Known mismatches

| Mismatch | Impact | Resolution |
| --- | --- | --- |
| First-party add-ons use internal plain settings fields | AI may copy an invalid third-party save pattern | Document as internal only |
| First-party source lives in plugin directory, runtime scan lives in uploads | Developers may test the wrong folder | Document three-location model |
| Marketplace builder parses root PHP headers, not v2 manifest | Marketplace publishing can diverge from v2 metadata | Track as core/tooling gap |
| Protected uploads may block static asset URLs | Frontend/admin JS or icons may fail in some servers | Require HTTP 200 asset test; track core delivery gap |
