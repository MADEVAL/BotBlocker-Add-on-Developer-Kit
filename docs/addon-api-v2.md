# Add-on API v2

Add-on API v2 is the preferred contract for third-party BotBlocker add-ons. It uses a JSON manifest, explicit paths, lifecycle callbacks, settings metadata, and optional feature providers.

Read this document together with:

- `botblocker-runtime-contract.md` for the real scan/load/install model.
- `botblocker-core-object.md` and `botblocker-request-data.md` for reading live BotBlocker request state.
- `botblocker-settings-reference.md` for read-only core settings available to add-ons.
- `traffic-and-redirect-addons.md` for traffic managers, redirect managers, and routers.
- `core-hook-integration.md` for add-ons that require in-cycle BotBlocker decisions.
- `settings-contract.md` for the exact third-party settings save contract.
- `settings-ui-patterns.md` for BotBlocker settings tab layout and field markup.
- `lifecycle-and-features.md` for lifecycle dispatch and feature providers.
- `code-quality-standard.md` for the required quality bar.
- `testing.md` for validation and manual tests.

## Runtime locations

BotBlocker separates your source package from the runtime package.

- Source package: your repository or build output. You edit here.
- Runtime package: `wp-content/uploads/botblocker/addons/{slug}` - the protected uploads directory BotBlocker scans and loads add-ons from. Your ZIP is validated and copied here on upload.
- Active add-ons option: `bbcs_active_addons`.
- Scanner entry point: `BotBlockerAddons::scanAll()`.

An uploaded package is installed inactive. The administrator reviews and activates it from the Installed tab. BotBlocker never loads an add-on from your source repository - only from the uploads runtime directory after upload.

## Minimal package

```text
vendor-addon/
  index.php
  bbcs-addon.json
  vendor-addon.php
  assets/
    index.php
    icon.svg
    admin.js
    frontend.js
  inc/
    index.php
    core.php
    settings.php
  readme.txt
```

The ZIP must contain exactly one root folder. The folder name must match the manifest `slug`. Archive the folder itself, not loose files inside the folder.

This is the recommended layout. BotBlocker follows package-relative paths declared in the manifest and code, so real packages may place icons or scripts elsewhere. A root icon such as `{slug}.svg` or a frontend script under `inc/` is valid when the manifest/code points to the correct package-relative path.

## Manifest

File name: `bbcs-addon.json`.

```json
{
  "schema": "2.0",
  "slug": "vendor-addon",
  "name": "Vendor Add-on",
  "version": "1.0.0",
  "requires_core": "1.7.5",
  "requires_php": "7.4",
  "author": "Vendor Name",
  "description": "Adds a focused BotBlocker extension.",
  "main": "vendor-addon.php",
  "core": "inc/core.php",
  "settings": {
    "view": "inc/settings.php",
    "option": "vendor_addon_settings",
    "sanitize": "vendor_addon_sanitize_settings"
  },
  "lifecycle": {
    "activate": "vendor_addon_activate",
    "deactivate": "vendor_addon_deactivate",
    "delete": "vendor_addon_delete",
    "update": "vendor_addon_update"
  },
  "features": [
    "vendor_feature"
  ],
  // "gateway": {
  //   "early_init": {
  //     "router_file": "inc/router.php",
  //     "entry_file": "inc/entry.php",
  //     "entry_class": "Namespace\\ClassName",
  //     "deploy_target": "data",
  //     "wp_config_block": true,
  //     "consistency_check": "namespace_func_name",
  //     "data_file_probe": "key-file.php"
  //   },
  //   "mu_plugin": {
  //     "source_file": "mu/plugin.php",
  //     "target_filename": "output-filename.php",
  //     "auto_deploy": true
  //   }
  // },
  // "ui": {
  //   "palette": {
  //     "icon": "speed",
  //     "title": "Speed up - performance",
  //     "priority": 10
  //   }
  // },
  // "storage": {
  //   "cache_dirs": ["my-cache-dir"]
  // },
  "assets": {
    "icon": "assets/icon.svg",
    "readme": "readme.txt"
  }
}
```

## Required fields

- `schema`: manifest schema, currently `2.0`.
- `slug`: sanitized package slug. It must match the root folder.
- `name`: human-readable add-on name.
- `version`: add-on version.
- `requires_core`: minimum BotBlocker version.
- `core`: PHP file loaded for active runtime behavior and callbacks.

Always declare these fields in a well-formed v2 manifest. Internally, BotBlocker marks a package valid only when `slug`, `name`, `version`, and `requires_core` are non-empty and the `core` file exists on disk; `schema` defaults to `2.0` when it is absent, so a missing `schema` does not by itself make a package invalid. Declaring `schema: "2.0"` explicitly is still expected by this kit so manifests are unambiguous and forward-compatible; the validator emits a warning when it is missing or not `2.0`.

## Optional fields

- `requires_php`: minimum PHP version.
- `author`: vendor or author name.
- `description`: short admin UI description.
- `main`: root metadata file.
- `settings.view`: PHP settings view.
- `settings.option`: WordPress option key used by the settings view.
- `settings.sanitize`: sanitize callback for the option.
- `lifecycle.file`: optional relative file loaded before lifecycle callbacks.
- `lifecycle.install`: callback for package install.
- `lifecycle.activate`: callback for activation.
- `lifecycle.deactivate`: callback for deactivation.
- `lifecycle.delete`: callback for deletion.
- `lifecycle.update`: callback for package replacement/update flows.
- `lifecycle.load`: callback for active add-on load events.
- `lifecycle.health_check`: callback for diagnostic flows.
- `runtime.pre_run`: optional strict pre-run contract for in-cycle traffic decision providers.
- `gateway`: optional gateway configurations for Layer 1 early-init / Layer 2 MU-plugin deployment.
- `ui`: optional UI integration metadata for the admin panel and ⌘K command palette.
- `storage`: optional storage cleanup metadata for uninstall flows.
- `features`: provider capability names exposed by the active add-on.
- `assets.icon`: relative icon path shown in the Add-ons UI.
- `assets.readme`: package readme path.

The `description` field appears on the Add-ons card. Keep it useful in one sentence: what the add-on does, where it acts, and what the admin can configure.

Example:

```text
Displays a lightweight cookie consent banner with editable notice text, policy link, theme, position, and safe BotBlocker settings storage.
```

## Root metadata file

The `main` file is a human-readable root file and optional bootstrap. Keep its header aligned with the manifest.

```php
<?php
/**
 * Plugin Name: Vendor Add-on
 * Description: Adds a focused BotBlocker extension.
 * Version: 1.0.0
 * Author: Vendor Name
 * Requires-Core: 1.7.5
 * Requires PHP: 7.4
 * Text Domain: vendor-addon
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/inc/core.php';
```

## Icons

For v2 add-ons, declare the icon in the manifest:

```json
"assets": {
  "icon": "assets/icon.svg",
  "readme": "readme.txt"
}
```

Rules:

- Use a package-relative path.
- Do not use a remote URL.
- Prefer square `SVG` or transparent `PNG` assets.
- `WebP`, `JPG`, `JPEG`, and `GIF` are acceptable only when they are intentional browser-rendered image assets.
- Keep icons small and inspectable.
- Do not use PHP, HTML, remote endpoints, or absolute paths as icon values.
- BotBlocker reads `assets.icon` first and also accepts a top-level `icon` field for compatibility.
- Legacy v1 add-ons use `{slug}.svg` or `{slug}.png` in the root folder.

## Core file

The core file is loaded when the add-on is active or when BotBlocker needs a lifecycle or settings callback. Keep it predictable.

- Do not echo output from `core.php`.
- Register WordPress hooks only when the add-on is active.
- Prefix all symbols.
- Keep activation and deletion reversible.
- Store runtime data in WordPress options, transients, custom tables, or protected uploads as appropriate.

## Pre-run traffic providers

Normal v2 add-on `core` files are loaded late, after BotBlocker has completed the main request-check cycle. Add-ons that must participate inside that cycle need an explicit pre-run contract.

This is a critical-risk mode. A pre-run traffic provider can affect real production requests before BotBlocker finishes its own decision path. Prefer normal post-check hooks unless in-cycle decisions are truly required. Pre-run traffic add-ons should ship disabled by default, start in dry-run, and include rollback instructions.

Use `examples/acme-traffic-guard` as the complete reference package for this contract.

Manifest example:

```json
{
  "features": [
    "traffic_decision_provider"
  ],
  "runtime": {
    "pre_run": {
      "enabled": true,
      "file": "inc/pre-run.php",
      "contract": "traffic_decision_provider",
      "ready_constant": "VENDOR_TRAFFIC_PRE_RUN_READY",
      "register": "vendor_traffic_pre_run_register"
    }
  }
}
```

Pre-run rules:

- `features` must include `traffic_decision_provider`.
- `runtime.pre_run.enabled` must be `true`.
- `runtime.pre_run.file` must be a safe package-relative PHP path.
- `runtime.pre_run.contract` must be `traffic_decision_provider`.
- `runtime.pre_run.register` must be a callable function or static method name.
- The manifest must declare either `ready_constant` or `ready_callback`.
- If the declared marker is missing after the pre-run file is included, BotBlocker refuses to register the provider.
- The pre-run file must only register callbacks. Do not echo, scan files, call remote APIs, update settings, render UI, or run expensive work.

## Weekly-report add-ons (no pre-run contract)

Notification add-ons such as the built-in `bbcs-pusher` and `bbcs-telegram` deliver a weekly statistics report on their own cron schedule and read the numbers from the BotBlocker database (`bbcs_counters`). They deliberately have NO in-cycle contract: they never run on visitor requests, never touch the traffic pipeline, and need no pre-run registration. The public block events (`bbcs_botblocker_blocked_request`, `bbcs_rate_limit_blocked`) remain available for third-party in-cycle observers via plain `add_action`.

Pre-run file example:

```php
<?php
if ( ! defined( 'ABSPATH' ) || ! defined( 'BOTBLOCKER' ) ) {
    exit;
}

define( 'VENDOR_TRAFFIC_PRE_RUN_READY', true );

function vendor_traffic_pre_run_register( array $addon, array $context, string $event, string $slug ): void {
    BotBlockerAddons::registerTrafficDecisionProvider( $slug, 'vendor_traffic_decide', 20 );
}

function vendor_traffic_decide( BotBlocker $bbcs, string $stage, array $provider ): ?array {
    if ( 'pre_core_rules' !== $stage ) {
        return null;
    }

    if ( 'DE' === strtoupper( (string) $bbcs->country ) ) {
        return array(
            'action' => 'redirect',
            'url'    => home_url( '/de/' ),
            'status' => 302,
            'reason' => 'Country route',
            'source' => $provider['slug'],
        );
    }

    return null;
}
```

## Gateway

The `gateway` field maps gateway types to configurations. Use it when the add-on needs special integration at Layer 1 (early-init) or Layer 2 (MU-plugin). Add-ons that run only at Layer 3 (main shield) do not need a `gateway` block.

```json
"gateway": {
    "early_init": {
        "router_file": "inc/router.php",
        "entry_file": "inc/entry.php",
        "entry_class": "Namespace\\ClassName",
        "deploy_target": "data",
        "wp_config_block": true,
        "consistency_check": "namespace_func_name",
        "data_file_probe": "key-file.php"
    },
    "mu_plugin": {
        "source_file": "mu/plugin.php",
        "target_filename": "output-filename.php",
        "auto_deploy": true
    }
}
```

| Sub-field | Type | Purpose |
|-----------|------|---------|
| `gateway.early_init.router_file` | string | Relative path to the early router PHP file deployed to data dir |
| `gateway.early_init.entry_file` | string | Relative path to the main early-init PHP file |
| `gateway.early_init.entry_class` | string | Fully qualified class name for early-init bootstrap |
| `gateway.early_init.deploy_target` | string | Target directory for deployment: `"data"` |
| `gateway.early_init.wp_config_block` | bool | Whether to inject the `/* BBCS Start */` block into wp-config.php |
| `gateway.early_init.consistency_check` | string | Callable name for consistency verification on each request |
| `gateway.early_init.data_file_probe` | string | Key filename checked post-install to verify deployment |
| `gateway.mu_plugin.source_file` | string | Relative path to the MU-plugin PHP file in the package |
| `gateway.mu_plugin.target_filename` | string | Output filename when deployed to `WPMU_PLUGIN_DIR` |
| `gateway.mu_plugin.auto_deploy` | bool | Whether to auto-deploy on activation |

These fields are OPTIONAL and only needed when the add-on operates at Layer 1 (early-init, before WordPress core) or Layer 2 (MU-plugin, muplugins_loaded). Both layers require disciplined isolation — early-init has no WordPress API, MU-plugin has no main plugin classes.

> Note: `gateway.*.mutual_exclusion` is declared by built-in manifests (for example `bbcs-early-init`) but the current manifest normalizer does not pass it into the gateway registry, so it is not enforced at runtime for v2 packages. Do not rely on it. See `known-core-contract-gaps.md`.

## UI

The `ui` field provides UI integration metadata for the admin panel and ⌘K command palette.

```json
"ui": {
    "palette": {
        "icon": "speed",
        "title": "Speed up - performance",
        "priority": 10
    }
}
```

| Sub-field | Type | Default | Purpose |
|-----------|------|---------|---------|
| `ui.palette.icon` | string | `"puzzle"` | SVG icon name for ⌘K command palette |
| `ui.palette.title` | string | addon `name` | Display title in palette (install prompt or settings link) |
| `ui.palette.priority` | int | 50 | Declared sort hint; the palette currently sorts entries by title, not by this value |

The palette data is read by `BotBlockerAddons::normalizeUi()` and surfaced to the admin ⌘K command palette.

This field is OPTIONAL — declare it only when the add-on needs palette visibility. Note: `ui.settings_sections` (declared in some built-in manifests) is parsed but not consumed by any core screen — do not rely on it. See `known-core-contract-gaps.md`.

## Storage

The `storage` field declares storage cleanup metadata for uninstall flows.

```json
"storage": {
    "cache_dirs": ["my-cache-dir"]
}
```

| Sub-field | Type | Default | Purpose |
|-----------|------|---------|---------|
| `storage.cache_dirs` | string[] | `[]` | Relative directory names under uploads to remove on uninstall |

The cache dirs are collected by `BotBlockerAddons::normalizeStorage()` and used by the BotBlocker uninstaller. `storage.cleanup_on_uninstall` is accepted by the normalizer but currently not consumed by the uninstaller — do not rely on it. See `known-core-contract-gaps.md`.

This field is OPTIONAL — only needed when the add-on creates cache directories or requires special cleanup on uninstall.

## Settings

The settings view is rendered inside BotBlocker admin. It should only render fields and read current values. Sanitization belongs in the callback declared in the manifest.

```php
function vendor_addon_sanitize_settings( $raw ): array {
    $raw = is_array( $raw ) ? $raw : array();

    return array(
        'enabled' => ! empty( $raw['enabled'] ) ? 1 : 0,
        'label'   => isset( $raw['label'] ) ? sanitize_text_field( (string) $raw['label'] ) : '',
    );
}
```

If no `settings.sanitize` callback is declared, BotBlocker falls back to `BotBlockerAddons::sanitizeSettingsValue()`. That fallback is intentionally conservative and recursive:

- Arrays: every key is passed through `sanitize_key()` and every value is sanitized recursively with the same rules.
- Booleans: stored as `1` or `0`.
- Numeric values: passed through `sanitize_text_field()`.
- All other strings: passed through `sanitize_textarea_field()`.

The fallback never enforces an allowlist of keys, never type-casts to your intended shape, and never drops unexpected fields. A quality add-on should always declare its own `settings.sanitize` callback so unknown keys are rejected and each field is normalized to the exact type it expects.

### Settings save flow

BotBlocker saves settings for active add-ons from the Add-ons page settings form.

1. The active add-on declares `settings.option`.
2. The settings view renders fields under that option key.
3. The admin submits the settings form on the Add-ons page.
4. BotBlocker reads the posted option array.
5. BotBlocker includes the add-on core file if needed.
6. BotBlocker calls `settings.sanitize` when callable.
7. BotBlocker stores the sanitized array with `BotBlockerMultisite::updateOption()`.
8. BotBlocker fires `bbcs_addon_settings_saved` with the posted array.

Field names must be option-array names:

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
<input type="text" name="vendor_addon_settings[label]" value="<?php echo esc_attr( $settings['label'] ?? '' ); ?>">
```

Do not copy BotBlocker's built-in plain field settings into third-party v2 add-ons. Fields like `disable_emojis` or `security_headers_enable` are saved by BotBlocker core's internal hardcoded logic, not by the generic third-party v2 settings flow.

Use lifecycle callbacks for defaults and cleanup:

```php
function vendor_addon_activate( array $addon, array $context, string $event, string $slug ): void {
  if ( false === BotBlockerMultisite::getOption( 'vendor_addon_settings', false ) ) {
    BotBlockerMultisite::updateOption( 'vendor_addon_settings', array( 'enabled' => 1 ) );
  }
}
```

### Settings help block

`settings.view` is included inside the add-on settings tab on `BotBlocker -> Add-ons`. Place add-on help inside that view, before controls. Use the native BotBlocker info-card pattern so the page looks consistent with BotBlocker's own settings pages.

Recommended order:

1. Icon or Font Awesome fallback.
2. One or two short `bbcs-info-text` paragraphs.
3. Footer links in `bbcs-info-footer`.
4. Settings columns with `bbcs_settings_h3` headings and BotBlocker input classes.

```php
<div class="row">
  <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
    <div class="bbcs-info-inner">
      <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
      <p class="bbcs-info-text"><?php esc_html_e( 'Explain the add-on behavior.', 'vendor-addon' ); ?></p>
      <p class="bbcs-info-text"><?php esc_html_e( 'Explain configuration, storage, and operational impact.', 'vendor-addon' ); ?></p>
      <hr class="bbcs-info-hr">
      <div class="bbcs-info-footer">
        <i class="fa-regular fa-circle-question"></i>
        <a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'vendor-addon' ); ?></a>
        <a href="https://botblocker.top/contacts/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Support', 'vendor-addon' ); ?></a>
      </div>
    </div>
  </div>
</div>
```

## Lifecycle callbacks

Supported events include:

- `install`
- `activate`
- `deactivate`
- `delete`
- `update`
- `load`
- `health_check`

Callback signature:

```php
function vendor_addon_activate( array $addon, array $context, string $event, string $slug ): void {
    // Initialize defaults or runtime data here.
}
```

Every lifecycle callback receives the same four arguments: the normalized `$addon` metadata array, an event `$context` array, the `$event` name, and the add-on `$slug`. The exact shape of `$addon` and the known `$context['reason']` values are documented in `lifecycle-and-features.md`. A callback that internally references a built-in PHP function name is skipped, so always use your own prefixed callback names.

BotBlocker also fires generic lifecycle actions:

```php
do_action( 'bbcs_addon_lifecycle', $event, $slug, $addon, $context );
do_action( "bbcs_addon_{$event}", $slug, $addon, $context );
do_action( "bbcs_addon_{$slug}_{$event}", $addon, $context );
```

The legacy compatibility hook remains available:

```php
do_action( 'bbcs_addon_toggled', $slug, $is_active );
```

## Feature providers

Use features when BotBlocker core or another add-on needs to depend on a capability instead of a concrete slug.

Manifest:

```json
"features": ["sample_response_header"]
```

Runtime check:

```php
if ( BotBlockerAddons::hasActiveFeature( 'sample_response_header' ) ) {
    // A compatible active provider is available.
}
```

Legacy add-ons can expose compatibility through filters when needed.

## JavaScript and CSS assets

Uploaded add-ons run from BotBlocker runtime storage, not from the BotBlocker plugin source folder. Do not use `plugin_dir_url()` for add-on assets. Use `BotBlockerAddons::fileUrl()`.

```php
function vendor_addon_asset_url( string $relative ): string {
  return class_exists( 'BotBlockerAddons' )
    ? BotBlockerAddons::fileUrl( 'vendor-addon', $relative )
    : '';
}
```

Admin enqueue example:

```php
function vendor_addon_enqueue_admin_assets(): void {
  $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
  if ( ! $screen || false === strpos( (string) $screen->id, 'botblocker' ) ) {
    return;
  }

  $url = vendor_addon_asset_url( 'assets/admin.js' );
  if ( '' !== $url ) {
    wp_enqueue_script( 'vendor-addon-admin', $url, array(), '1.0.0', true );
  }
}
add_action( 'admin_enqueue_scripts', 'vendor_addon_enqueue_admin_assets' );
```

Frontend enqueue example:

```php
function vendor_addon_enqueue_frontend_assets(): void {
  $url = vendor_addon_asset_url( 'assets/frontend.js' );
  if ( '' !== $url ) {
    wp_enqueue_script( 'vendor-addon-frontend', $url, array(), '1.0.0', true );
  }
}
add_action( 'wp_enqueue_scripts', 'vendor_addon_enqueue_frontend_assets' );
```

Use unique handles. Enqueue only on screens or requests that need the asset. Pass settings to scripts with `wp_add_inline_script()` or `wp_localize_script()` after sanitizing source values.

The helper accepts any safe package-relative path that exists in the runtime add-on folder, such as `assets/admin.js`, `assets/frontend.js`, or `inc/frontend.js`.

Runtime asset caveat: BotBlocker installs add-ons into a protected uploads directory. In some web-server configurations, direct static asset requests under that directory may return 403. Always test declared icon, JS, CSS, and image URLs in a real WordPress install. See `known-core-contract-gaps.md`.

## Validation

Validate a source folder:

```powershell
php .\tools\validate-addon.php .\examples\acme-botblocker-sample
```

Validate a ZIP:

```powershell
php .\tools\validate-addon.php .\dist\acme-botblocker-sample.zip
```

The validator checks the manifest, slug/root match, required paths, PHP syntax, settings option field names, lifecycle callbacks, sanitizer callback, unsafe paths, and common asset mistakes.

## v1 compatibility

New add-ons should use v2. BotBlocker still scans legacy v1 packages that contain:

```text
legacy-addon/
  legacy-addon.php
  legacy-addon.svg or legacy-addon.png
  inc/
    legacy-addon-core.php
    legacy-addon-settings.php
  readme.txt
```

Do not remove legacy compatibility from shared tooling. Existing add-ons may still depend on it.
