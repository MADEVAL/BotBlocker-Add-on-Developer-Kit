# Lifecycle and Features

Add-on API v2 supports explicit lifecycle callbacks and feature provider declarations.

## Manifest

```json
"lifecycle": {
  "install": "vendor_addon_install",
  "activate": "vendor_addon_activate",
  "deactivate": "vendor_addon_deactivate",
  "delete": "vendor_addon_delete",
  "update": "vendor_addon_update",
  "load": "vendor_addon_load",
  "health_check": "vendor_addon_health_check"
},
"features": [
  "vendor_feature_provider"
]
```

`lifecycle.file` is also supported when callbacks live in a separate package-relative file.

## Callback signature

Use this signature for lifecycle callbacks:

```php
function vendor_addon_activate( array $addon, array $context, string $event, string $slug ): void {
    // Idempotent activation logic.
}
```

BotBlocker dispatches callbacks with:

```php
call_user_func( $callback, $addon, $context, $event, $slug );
```

## Events

| Event | When it runs | Use for |
| --- | --- | --- |
| `install` | After a ZIP is validated and moved into runtime | Package-owned one-time setup |
| `activate` | When admin activates an add-on, or an active add-on is reactivated after update | Defaults, cron scheduling, reversible integrations |
| `deactivate` | When admin deactivates, or delete starts (with `reason => delete`), or BotBlocker itself is deactivated | Unschedule, remove temporary integration state |
| `delete` | Before/while runtime folder is removed | Remove package-owned options/data when intended |
| `update` | After package replacement/update install | Version migrations |
| `load` | After an active compatible add-on core file is included | Lightweight runtime checks |
| `health_check` | After active add-ons are loaded | Repair reversible integration state |

Lifecycle callbacks must be safe to run more than once.

### Load and health_check ordering

On every front-end and admin request, `BotBlockerAddons::includeAll()` performs two passes:

1. First pass: it includes each active compatible add-on core file and dispatches that add-on's `load` callback immediately after its core is included.
2. Second pass: after every active add-on has been loaded, it dispatches `health_check` for all of them.

So `health_check` always runs after all `load` callbacks across all add-ons have completed. Use `load` for self-contained per-add-on setup and `health_check` for cross-add-on or repair logic that may depend on other add-ons already being loaded.

### `$addon` and `$context` arguments

`$addon` is the normalized add-on metadata array produced by `BotBlockerAddons::parseManifest()`. Its keys are:

```text
slug, base, root, core, settings, icon, valid, name, author, description,
version, requires_core, requires_php, schema, source_format ('v2' or 'v1'),
has_settings, settings_option, settings_sanitize, lifecycle, pre_run,
features, manifest, readme
```

`$context` is an event context array. The known `$context` values dispatched by core are:

| Context | Event | Meaning |
| --- | --- | --- |
| `array( 'reason' => 'delete' )` | `deactivate` | Delete of an active add-on starts; the `delete` event follows. |
| `array( 'reason' => 'plugin_deactivation' )` | `deactivate` | BotBlocker itself is being deactivated. |
| `array( 'from' => <old>, 'to' => <new> )` | `update` | Ledger-driven version migration after a package update. |
| `array( 'phase' => 'pre_run' )` | pre-run callbacks | Pre-run registration and readiness callbacks receive this instead of a lifecycle event. |

Activations and deactivations triggered directly by an admin from the Add-ons screen pass an empty context, so always treat `$context` as optional and check keys before reading them. Compatibility failures do NOT dispatch `deactivate`: BotBlocker silently drops the add-on from the active list and shows a compatibility alert instead.

### `lifecycle.file`

By default BotBlocker loads the add-on `core` file before dispatching a lifecycle callback that is not yet defined. If you prefer to keep lifecycle callbacks out of `core.php`, declare a separate file:

```json
"lifecycle": {
  "file": "inc/lifecycle.php",
  "activate": "vendor_addon_activate",
  "delete": "vendor_addon_delete"
}
```

`lifecycle.file` is `include_once`'d before every lifecycle event (`install`, `activate`, `deactivate`, `delete`, `update`, `load`, `health_check`). This is useful when delete/deactivate cleanup must be available even when the add-on is inactive and its `core` file would not otherwise load. Keep the file side-effect free at include time: define functions, do not run them.

## Generic actions

BotBlocker also fires generic actions:

```php
do_action( 'bbcs_addon_lifecycle', $event, $slug, $addon, $context );
do_action( "bbcs_addon_{$event}", $slug, $addon, $context );
do_action( "bbcs_addon_{$slug}_{$event}", $addon, $context );
```

The legacy toggle hook remains available:

```php
do_action( 'bbcs_addon_toggled', $slug, $is_active );
```

Pre-run providers also get a dedicated notification:

```php
do_action( 'bbcs_addon_pre_run_loaded', $slug, $addon );
```

Prefer v2 lifecycle callbacks for new code.

## Feature providers

Features let BotBlocker core or another add-on depend on a capability instead of a hardcoded slug.

Manifest:

```json
"features": [
  "cookie_consent_provider"
]
```

Runtime check:

```php
if ( class_exists( 'BotBlockerAddons' )
    && BotBlockerAddons::hasActiveFeature( 'cookie_consent_provider' ) ) {
    // A compatible active provider exists.
}
```

Current core-recognized feature examples (as declared by built-in add-ons):

- `traffic_decision_provider` (bbcs-behavior, bbcs-xmlrpc-tunnel)
- `behavioral_analysis_engine`
- `cookie_consent_provider`
- `login_url_provider`
- `https_protocol_provider`
- `malware_scanner_provider`
- `performance_tools_provider`
- `pusher_weekly_digest_provider`
- `security_headers_provider`
- `telegram_notifications_provider`
- `truth_source_provider`
- `xmlrpc_tunnel_provider`

Only one feature name actually gates behavior inside BotBlocker core today:

- `traffic_decision_provider` is required for the pre-run traffic decision contract (see `core-hook-integration.md`). Without it, `includePreRunAddons()` will not load the pre-run file.

Every other feature name - whether built-in or your own - is a declarative capability tag. It does not change core behavior on its own; it is only discoverable through the helper methods below. Third-party add-ons may declare their own feature names. Use sanitized lowercase keys with underscores. Note: early-init deployment is NOT feature-gated; it uses the `gateway.early_init` manifest contract and `bbcs-early-init` declares no features.

## BotBlockerAddons API for add-on authors

These public static methods are stable for add-on code. Guard calls with `class_exists( 'BotBlockerAddons' )` because add-on core files can run very early.

| Method | Returns | Purpose |
| --- | --- | --- |
| `isActive( string $slug )` | `bool` | Whether a slug is in `bbcs_active_addons`. |
| `getActive()` | `array` | List of active add-on slugs. |
| `isCompatible( array $addon, string $core_version = '' )` | `bool` | Whether an add-on's `requires_core` is satisfied. |
| `hasActiveFeature( string $feature )` | `bool` | Whether any active compatible add-on declares the feature. |
| `hasActiveProvider( string $feature, string $legacy_filter = '' )` | `bool` | `hasActiveFeature()` plus an optional legacy `bbcs_*` filter fallback. |
| `getByFeature( string $feature )` | `array` | Active compatible add-ons (keyed by slug) that declare the feature. |
| `getActiveFeatures()` | `array` | All feature tags exposed by active compatible add-ons. |
| `declaresFeature( array $addon, string $feature )` | `bool` | Whether a given add-on array declares the feature. |
| `fileUrl( string $slug, string $relative )` | `string` | Safe runtime URL for a package-relative asset. |
| `fileRequiresCore( string $slug )` | `string` | The `requires_core` value declared by an installed add-on. |
| `registerTrafficDecisionProvider( string $slug, $callback, int $priority = 10 )` | `bool` | Register a pre-run traffic decision provider (priority range `-9999`..`9999`). |

Scanning, install, settings-save, lifecycle dispatch, and market methods exist on the same class but are core-internal. Do not call them from add-on code.

## Runtime hooks

Register WordPress hooks only from the add-on core file and only after checking the add-on should run.

```php
function vendor_addon_boot(): void {
    if ( class_exists( 'BotBlockerAddons' ) && ! BotBlockerAddons::isActive( 'vendor-addon' ) ) {
        return;
    }

    add_action( 'wp_enqueue_scripts', 'vendor_addon_enqueue_frontend_assets' );
}

if ( function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
    vendor_addon_boot();
} else {
    add_action( 'plugins_loaded', 'vendor_addon_boot', 20 );
}
```

Because BotBlocker includes active add-on core files during `plugins_loaded`, this pattern prevents accidental runtime registration when a callback includes the core file for settings or lifecycle work.

