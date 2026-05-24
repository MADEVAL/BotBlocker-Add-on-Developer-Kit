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
| `deactivate` | When admin deactivates, update starts, delete starts, or compatibility fails | Unschedule, remove temporary integration state |
| `delete` | Before/while runtime folder is removed | Remove package-owned options/data when intended |
| `update` | After package replacement/update install | Version migrations |
| `load` | After an active compatible add-on core file is included | Lightweight runtime checks |
| `health_check` | After active add-ons are loaded | Repair reversible integration state |

Lifecycle callbacks must be safe to run more than once.

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
if ( function_exists( 'bbcs_has_active_addon_provider' )
    && bbcs_has_active_addon_provider( 'cookie_consent_provider' ) ) {
    // A compatible active provider exists.
}
```

Current core-recognized feature examples:

- `early_init_provider`
- `security_headers_provider`
- `login_url_provider`
- `https_protocol_provider`
- `malware_scanner_provider`
- `performance_tools_provider`

Third-party add-ons may declare their own feature names. Use sanitized lowercase keys with underscores.

## Runtime hooks

Register WordPress hooks only from the add-on core file and only after checking the add-on should run.

```php
function vendor_addon_boot(): void {
    if ( function_exists( 'bbcs_is_addon_active' ) && ! bbcs_is_addon_active( 'vendor-addon' ) ) {
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

