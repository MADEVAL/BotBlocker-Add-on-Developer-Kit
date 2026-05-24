# Settings UI Patterns

Use this document when building `settings.view` for a BotBlocker Add-on API v2 package.

The settings view is included inside `BotBlocker -> Tools` after the add-on is active. It is not a standalone WordPress admin page. Match the native BotBlocker layout so the add-on feels like part of the product.

## Required structure

Use a Bootstrap-style `.row` with:

- one help/info column first
- one or more settings columns after it
- grouped fields under `bbcs_settings_h3` headings

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = function_exists( 'vendor_addon_settings' ) ? vendor_addon_settings() : array();
$option   = 'vendor_addon_settings';
$icon_url = function_exists( 'vendor_addon_asset_url' ) ? vendor_addon_asset_url( 'assets/icon.svg' ) : '';
?>
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
        <div class="bbcs-info-inner">
            <?php if ( '' !== $icon_url ) : ?>
                <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
            <?php else : ?>
                <i class="fa-solid fa-puzzle-piece fa-3x bbcs_color_blue mb-3" aria-hidden="true"></i>
            <?php endif; ?>

            <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the add-on does and where it acts.', 'vendor-addon' ); ?></p>
            <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the administrator can configure and what data is stored.', 'vendor-addon' ); ?></p>

            <hr class="bbcs-info-hr">
            <div class="bbcs-info-footer">
                <i class="fa-regular fa-circle-question"></i>
                <a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'vendor-addon' ); ?></a>
                <a href="https://wordpress.org/plugins/botblocker-security/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Plugin page', 'vendor-addon' ); ?></a>
                <a href="https://botblocker.top/contacts/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Support', 'vendor-addon' ); ?></a>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Main', 'vendor-addon' ); ?></h3>
        <!-- Fields go here. -->
    </div>
</div>
```

## Field naming

Every field must be named under the manifest `settings.option`.

Manifest:

```json
"settings": {
  "view": "inc/settings.php",
  "option": "vendor_addon_settings",
  "sanitize": "vendor_addon_sanitize_settings"
}
```

Field:

```php
<input type="text" name="<?php echo esc_attr( $option ); ?>[message]" value="<?php echo esc_attr( $settings['message'] ?? '' ); ?>">
```

Do not use plain field names such as `name="enabled"` or `name="disable_emojis"` in third-party v2 add-ons.

## Checkbox

Always include a hidden `0` before a checkbox so disabled states save correctly.

```php
<div class="bbcs_checkbox_input mb-2">
    <div class="bbcs_label_checkbox_box">
        <input type="hidden" name="<?php echo esc_attr( $option ); ?>[enabled]" value="0">
        <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( 1, $settings['enabled'] ?? 0 ); ?>>
        <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable add-on', 'vendor-addon' ); ?></span>
    </div>
    <i class="fa-regular fa-circle-question" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
       data-bs-original-title="<?php esc_attr_e( 'Turns this add-on behavior on or off without deleting saved settings.', 'vendor-addon' ); ?>"></i>
</div>
```

## Text input

```php
<div class="bbcs_text_input mb-2">
    <div class="bbcs_label_input_box">
        <span class="bbcs-label-input"><?php esc_html_e( 'Label', 'vendor-addon' ); ?></span>
    </div>
    <div class="bbcs_text_input_inner">
        <input type="text" name="<?php echo esc_attr( $option ); ?>[label]" class="bbcs_text_input_input" value="<?php echo esc_attr( $settings['label'] ?? '' ); ?>">
    </div>
</div>
```

## URL input

```php
<div class="bbcs_text_input mb-2">
    <div class="bbcs_label_input_box">
        <span class="bbcs-label-input"><?php esc_html_e( 'Policy URL', 'vendor-addon' ); ?></span>
    </div>
    <div class="bbcs_text_input_inner">
        <input type="url" name="<?php echo esc_attr( $option ); ?>[policy_url]" class="bbcs_text_input_input" value="<?php echo esc_attr( $settings['policy_url'] ?? '' ); ?>" placeholder="https://example.com/privacy-policy">
    </div>
</div>
```

Sanitize URL fields with `esc_url_raw()` in the sanitizer and render with `esc_attr()` in inputs or `esc_url()` in links.

## Number input

```php
<div class="bbcs_text_input mb-2">
    <div class="bbcs_label_input_box">
        <span class="bbcs-label-input"><?php esc_html_e( 'Limit', 'vendor-addon' ); ?></span>
    </div>
    <div class="bbcs_text_input_inner">
        <input type="number" min="1" max="100" step="1" name="<?php echo esc_attr( $option ); ?>[limit]" class="bbcs_text_input_input" value="<?php echo esc_attr( (string) ( $settings['limit'] ?? 10 ) ); ?>">
    </div>
</div>
```

Normalize numbers with `absint()`, `min()`, and `max()` in the sanitizer.

## Textarea

```php
<div class="bbcs_textarea_input mb-2">
    <div class="bbcs_label_input_box">
        <span class="bbcs-label-input"><?php esc_html_e( 'Message', 'vendor-addon' ); ?></span>
    </div>
    <div class="bbcs_textarea_input_inner">
        <textarea name="<?php echo esc_attr( $option ); ?>[message]" class="bbcs_textarea_input_input" rows="4" style="width: -webkit-fill-available;"><?php echo esc_textarea( $settings['message'] ?? '' ); ?></textarea>
    </div>
</div>
```

Use `sanitize_textarea_field()` unless the field intentionally stores restricted HTML.

## Select

```php
<div class="bbcs_select_input mb-2">
    <div class="bbcs_label_input_box">
        <span class="bbcs-label-input"><?php esc_html_e( 'Position', 'vendor-addon' ); ?></span>
    </div>
    <div class="bbcs_select_input_inner">
        <select name="<?php echo esc_attr( $option ); ?>[position]" class="bbcs_select_input_select">
            <option value="bottom" <?php selected( 'bottom', $settings['position'] ?? 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'vendor-addon' ); ?></option>
            <option value="top" <?php selected( 'top', $settings['position'] ?? 'bottom' ); ?>><?php esc_html_e( 'Top', 'vendor-addon' ); ?></option>
        </select>
    </div>
</div>
```

Sanitize select values with an allowlist:

```php
$position = isset( $raw['position'] ) ? sanitize_key( (string) $raw['position'] ) : 'bottom';
$position = in_array( $position, array( 'bottom', 'top' ), true ) ? $position : 'bottom';
```

## Settings help copy

Good help copy answers:

- What does this add-on do?
- Where does it act: frontend, admin, request headers, login, API, cron?
- What can the administrator configure?
- What data does it store?
- What operational risk or compatibility issue should be known?

Example:

```text
Displays a lightweight first-party notice for visitors and stores acknowledgement locally in a BotBlocker-named cookie.

Use it for simple notices where no external consent platform is required. Configure message text, policy link, button label, theme, position, and optional CSS.
```

## Footer links

Use links that help the administrator make an operational decision.

Generic links:

- BotBlocker docs: `https://botblocker.top/docs/`
- Plugin page: `https://wordpress.org/plugins/botblocker-security/`
- Support: `https://botblocker.top/contacts/`

Feature-specific examples:

- WordPress privacy: `https://wordpress.org/documentation/article/settings-privacy-screen/`
- HTTP security headers: `https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers`
- Content Security Policy: `https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP`

## Add-ons card description

The manifest `description` appears on the Add-ons card. It should be one concise sentence that names:

- behavior
- runtime surface
- configurable controls

Good:

```text
Adds configurable security response headers with HSTS, frame protection, permissions policy, and CSP controls.
```

Weak:

```text
Security headers add-on.
```

