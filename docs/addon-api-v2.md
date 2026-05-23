# Add-on API v2

Add-on API v2 is the preferred contract for third-party BotBlocker add-ons. It uses a JSON manifest, explicit paths, lifecycle callbacks, settings metadata, and optional feature providers.

## Runtime locations

BotBlocker separates source packages from runtime packages.

- Source package: your repository or build output.
- Runtime package: BotBlocker protected uploads add-ons directory.
- Active add-ons option: `botblocker_active_addons`.
- Scanner entry point: `bbcs_scan_addons()`.

An uploaded package is installed inactive. The administrator reviews and activates it from the Installed tab.

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

This is the recommended layout. BotBlocker follows package-relative paths declared in the manifest and code, so real packages may place icons or scripts elsewhere. The working `bbcs-cookie-alert` package uses `bbcs-cookie-alert.svg` in the root and `inc/bbcs-cookie-alert-js.js` for frontend JavaScript. Both are valid because the paths are relative, safe, and inside the package.

## Manifest

File name: `bbcs-addon.json`.

```json
{
  "schema": "2.0",
  "slug": "vendor-addon",
  "name": "Vendor Add-on",
  "version": "1.0.0",
  "requires_core": "1.6.20",
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
- `features`: provider capability names exposed by the active add-on.
- `assets.icon`: relative icon path shown in the Add-ons UI.
- `assets.readme`: package readme path.

The `description` field appears on the Add-ons card. Keep it useful in one sentence: what the add-on does, where it acts, and what the admin can configure.

Example:

```text
Displays a lightweight first-party cookie consent banner with editable notice text, policy link, theme, position, and safe BotBlocker settings storage.
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
 * Requires-Core: 1.6.20
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

If no sanitize callback is declared, BotBlocker applies conservative recursive sanitization.

### Settings save flow

BotBlocker saves settings for active add-ons from the `BotBlocker -> Tools` form.

1. The active add-on declares `settings.option`.
2. The settings view renders fields under that option key.
3. The admin submits the Tools form.
4. BotBlocker reads the posted option array.
5. BotBlocker includes the add-on core file if needed.
6. BotBlocker calls `settings.sanitize` when callable.
7. BotBlocker stores the sanitized array with `update_option()`.

Field names must be option-array names:

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
<input type="text" name="vendor_addon_settings[label]" value="<?php echo esc_attr( $settings['label'] ?? '' ); ?>">
```

Use lifecycle callbacks for defaults and cleanup:

```php
function vendor_addon_activate( array $addon, array $context, string $event, string $slug ): void {
  if ( false === get_option( 'vendor_addon_settings', false ) ) {
    update_option( 'vendor_addon_settings', array( 'enabled' => 1 ) );
  }
}
```

### Settings help block

`settings.view` is included inside the add-on tab on `BotBlocker -> Tools`. Place add-on help inside that view, before controls. Use the native BotBlocker info-card pattern so the page looks consistent with first-party add-ons.

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
if ( bbcs_has_active_addon_provider( 'sample_response_header' ) ) {
    // A compatible active provider is available.
}
```

Legacy add-ons can expose compatibility through filters when needed.

## JavaScript and CSS assets

Uploaded add-ons run from BotBlocker runtime storage, not from the BotBlocker plugin source folder. Do not use `plugin_dir_url()` for add-on assets. Use `bbcs_addon_file_url()`.

```php
function vendor_addon_asset_url( string $relative ): string {
  return function_exists( 'bbcs_addon_file_url' )
    ? bbcs_addon_file_url( 'vendor-addon', $relative )
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

The helper accepts any safe package-relative path that exists in the runtime add-on folder, such as `assets/admin.js`, `assets/frontend.js`, or `inc/bbcs-cookie-alert-js.js`.

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

Do not remove legacy compatibility from shared tooling. Existing first-party and third-party add-ons may still depend on it.