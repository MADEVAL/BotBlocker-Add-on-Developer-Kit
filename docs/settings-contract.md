# Settings Contract

BotBlocker Add-on API v2 settings are saved through the BotBlocker Tools page for active add-ons.

## Save flow

1. Add-on is installed.
2. Administrator activates it from `BotBlocker -> Add-ons`.
3. Add-on declares `settings.view` and `settings.option`.
4. BotBlocker renders the settings view inside `BotBlocker -> Tools`.
5. The settings view renders fields under the declared option array.
6. Administrator clicks the Tools save button.
7. BotBlocker reads `$_POST[settings.option]`.
8. BotBlocker includes the add-on core file when needed.
9. BotBlocker calls `settings.sanitize` when callable.
10. BotBlocker stores the sanitized array with `update_option()`.

Settings are saved only for active add-ons.

## Manifest

```json
"settings": {
  "view": "inc/settings.php",
  "option": "vendor_addon_settings",
  "sanitize": "vendor_addon_sanitize_settings"
}
```

Rules:

- `settings.view` must be a safe package-relative PHP path.
- `settings.option` must be unique and prefixed.
- `settings.option` is normalized with `sanitize_key()`, so use lowercase letters, numbers, and underscores.
- `settings.sanitize` must be a prefixed callable declared in the add-on core file.

## Field names

Every field must be named under the manifest option.

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
<input type="text" name="vendor_addon_settings[label]" value="<?php echo esc_attr( $settings['label'] ?? '' ); ?>">
```

Do not use plain field names for third-party v2 packages:

```php
<!-- Wrong for third-party v2 -->
<input type="checkbox" name="enabled" value="1">
```

BotBlocker's own built-in settings use plain field names because core has internal save logic for them. That pattern is not the public third-party v2 settings contract.

## Defaults

Keep defaults in the core file:

```php
function vendor_addon_defaults(): array {
    return array(
        'enabled' => 1,
        'label'   => __( 'Default label', 'vendor-addon' ),
    );
}
```

Create defaults in the activation callback:

```php
function vendor_addon_activate( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );

    if ( false === get_option( 'vendor_addon_settings', false ) ) {
        update_option( 'vendor_addon_settings', vendor_addon_defaults() );
    }
}
```

## Sanitizer

Every stored field must be normalized.

```php
function vendor_addon_sanitize_settings( $raw ): array {
    $raw      = is_array( $raw ) ? $raw : array();
    $defaults = vendor_addon_defaults();

    return array(
        'enabled' => ! empty( $raw['enabled'] ) ? 1 : 0,
        'label'   => isset( $raw['label'] )
            ? sanitize_text_field( (string) $raw['label'] )
            : $defaults['label'],
    );
}
```

BotBlocker has a conservative fallback sanitizer, but a quality third-party add-on must declare its own sanitizer because only the add-on knows expected field types and allowed values.

## Settings view

The settings view should render only HTML controls and read current settings. It should not save data, call remote services, run scans, or mutate runtime state on include.

Use:

- `esc_html()`, `esc_html_e()` for text
- `esc_attr()` for attributes
- `esc_url()` for URLs
- `esc_textarea()` for textarea content
- `wp_kses_post()` only for intentionally allowed rich text

For visual layout, help blocks, field wrappers, checkbox/text/textarea/select examples, footer links, and card description guidance, see `settings-ui-patterns.md`.

## Delete behavior

Use the `delete` lifecycle callback to remove package-owned options when that is the expected behavior:

```php
function vendor_addon_delete( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );
    delete_option( 'vendor_addon_settings' );
}
```

Do not delete shared BotBlocker options from a third-party add-on.
