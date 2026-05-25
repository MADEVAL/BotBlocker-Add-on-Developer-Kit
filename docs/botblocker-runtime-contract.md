# BotBlocker Runtime Contract

This document describes how BotBlocker Security 1.6.20 loads add-ons at runtime. Treat this as the operational contract for third-party Add-on API v2 packages.

## Three locations

BotBlocker add-ons can appear in three different places. Do not confuse them.

```text
source package
  Your repository or build folder. This is where you edit code.

first-party source
  plugin/wp-content/plugins/botblocker-security/addons/{slug}
  BotBlocker-owned add-ons used as source packages and marketplace inputs.

runtime package
  wp-content/uploads/BotBlocker/addons/{slug}
  Installed add-ons scanned and loaded by BotBlocker.
```

The runtime package is authoritative for activation and execution. A third-party package is not loaded from the developer kit repository and is not loaded from the BotBlocker plugin source directory.

## Runtime directory

BotBlocker creates a protected uploads area through `bbcs_create_protected_upload_dir()`:

```text
wp-content/uploads/BotBlocker/
  addons/
  data/
  bbcs-owner.txt
  .htaccess
  web.config
  index.php
```

Add-ons are installed under:

```text
wp-content/uploads/BotBlocker/addons/{slug}/
```

Runtime directory helpers:

- `bbcs_uploads_dir()`
- `bbcs_data_dir()`
- `bbcs_addons_dir()`
- `bbcs_addons_url()`
- `bbcs_addon_file_url( $slug, $relative )`

## Load order

BotBlocker boots from `botblocker-security.php`.

1. Core helpers and upload helpers are included.
2. `bbcs_run_botblocker_shield()` runs on `plugins_loaded` with priority `-9998`.
3. BotBlocker checks installation state and initializes the core plugin.
4. `bbcs_include_active_v2_addons_pre_run()` loads only active compatible v2 traffic providers that explicitly opt into the pre-run contract.
5. BotBlocker initializes and runs the main request-check cycle.
6. `bbcs_include_active_addons()` scans runtime add-ons and includes active compatible add-on core files for normal late runtime behavior.
7. Admin menu, admin assets, and setup wizard are initialized.

Your add-on `core` file is included only when the add-on is active, or when BotBlocker needs the file to call a lifecycle/settings callback.

Important timing rule: normal active v2 add-on core files are included after `$botBlocker->initialize()` has completed the main request-check cycle. That is enough for admin screens, frontend assets, headers, settings, lifecycle callbacks, and post-check WordPress hooks. A third-party add-on can register callbacks inside `BotBlocker::run()` only through the stricter `runtime.pre_run` traffic decision provider contract.

The pre-run contract is intentionally narrow because it is dangerous. A traffic provider can affect live request outcomes before BotBlocker finishes checks. Use `examples/acme-traffic-guard` as the reference, keep providers disabled/dry-run by default, and avoid remote calls or heavy diagnostics in pre-run files.

In full secure mode, a BotBlocker block/check/deny path can stop execution during `initialize()`. In that case a normal v2 add-on loaded after `initialize()` may not run at all for the blocked request.

Use:

- `botblocker-core-object.md` to read the final live object safely.
- `botblocker-request-data.md` to understand available visitor/request fields.
- `traffic-and-redirect-addons.md` for post-check redirect patterns and traffic provider selection.
- `core-hook-integration.md` for the pre-run traffic decision provider contract.

## Scanner

The scanner entry point is `bbcs_scan_addons()`.

It scans `bbcs_addons_dir()` and ignores backup directories ending in `_bbcs_bak`.

For every runtime folder:

1. If `bbcs-addon.json` exists, BotBlocker parses it as v2.
2. Otherwise BotBlocker falls back to legacy v1 scanning.
3. Invalid add-ons are shown as broken and are not activated.

The v2 parser normalizes:

- slug
- base path
- root file
- core file
- settings view
- icon URL
- readme path
- lifecycle callbacks
- declared features
- compatibility metadata

## Active state

Active add-ons are stored in the WordPress option:

```text
botblocker_active_addons
```

Helpers:

- `bbcs_get_active_addons()`
- `bbcs_is_addon_active( $slug )`

Only active, valid, compatible add-ons are included by `bbcs_include_active_addons()`.

## Compatibility

Compatibility is checked with:

```php
bbcs_is_addon_compatible( $addon, $core_version = '' )
```

`requires_core` is required. A v2 package without `requires_core` is invalid. `requires_php` is enforced when declared.

New third-party add-ons should target:

- BotBlocker Security `1.6.20+`
- WordPress `5.0+`
- PHP `7.4+`

Some bundled first-party add-ons declare `requires_core: 1.6.15`. That is internal compatibility history, not the recommended baseline for new third-party packages.

## Upload and install flow

Admin upload uses:

- page: `BotBlocker -> Add-ons`
- action: `admin_post_bbcs_upload_addon`
- handler: `bbcs_upload_addon_handler()`
- installer: `bbcs_install_addon_package()`

The uploaded package is validated before it is moved into runtime storage:

- ZIP extension only
- size <= 20 MB
- safe paths only
- exactly one root folder
- root folder is a sanitized slug
- root folder matches v2 manifest slug
- `requires_core` is present
- PHP version is compatible when `requires_php` is present
- core file exists

Successful upload installs the package inactive. The administrator must activate it from the Installed tab.

## Activation and loading

Activation/deactivation uses:

- action: `admin_post_bbcs_toggle_addon`
- handler: `bbcs_toggle_addon_handler()`

When activated:

1. The add-on core file is included.
2. The slug is added to `botblocker_active_addons`.
3. The `activate` lifecycle callback is dispatched when declared.
4. The legacy `bbcs_addon_toggled` action fires.

On every request after activation, `bbcs_include_active_addons()` includes the add-on core file and dispatches `load`, then later `health_check`.

## Asset URLs

Uploaded add-ons live outside the BotBlocker plugin source directory. Do not use `plugin_dir_url()` for add-on assets.

Use:

```php
function vendor_addon_asset_url( string $relative ): string {
    return function_exists( 'bbcs_addon_file_url' )
        ? bbcs_addon_file_url( 'vendor-addon', $relative )
        : '';
}
```

Paths passed to `bbcs_addon_file_url()` must be package-relative and must match files inside the runtime add-on folder.

Examples:

```text
assets/icon.svg
assets/frontend.js
inc/frontend.js
{slug}.svg
```

## Runtime asset caveat

BotBlocker writes protection files into the uploads runtime folders. Depending on web-server configuration, static assets under `wp-content/uploads/BotBlocker/addons/` may be blocked by `.htaccess` or `web.config`.

Before claiming a frontend or admin asset works, test that its `bbcs_addon_file_url()` URL returns HTTP 200 in the target environment. If the server blocks it, use inline CSS/JS where practical or fix BotBlocker core asset delivery to expose safe declared add-on assets.

## First-party source is not the third-party contract

Bundled first-party add-ons under `plugin/wp-content/plugins/botblocker-security/addons` are useful references, but several of them use internal/legacy settings patterns and shared BotBlocker options.

For third-party v2 packages:

- use `bbcs-addon.json`
- use unique option names
- name settings fields under `settings.option`
- use `bbcs_addon_file_url()` for assets
- package as a ZIP with exactly one root folder
- install through the Add-ons admin UI
