# BotBlocker Add-on Developer Kit

![BotBlocker Security banner](https://ps.w.org/botblocker-security/assets/banner-1544x500.png?rev=3405280)

Manual, template, validator, AI instruction pack for third-party BotBlocker Security add-ons — WordPress anti-bot firewall + proactive protection: request checks, rules, CAPTCHA, logs, early-init protection, add-on extensions guard login flows, XML-RPC, REST, comments, file requests, payment callbacks, high-risk traffic.

## Baseline

- BotBlocker Security: `1.7.5+` | WordPress: `5.9+` (tested to `7.1`) | PHP: `7.4+` | Add-on API v2 (`bbcs-addon.json`)

`1.7.5` = minimum BotBlocker for Add-on API v2; third-party add-ons target `1.7.5+`.

## What Is Inside

- `examples/acme-botblocker-sample`: canonical v2 sample, normal late-loaded behavior.
- `examples/acme-traffic-guard`: advanced v2 sample, pre-run `traffic_decision_provider` contract.
- `examples/acme-hcaptcha`: minimal captcha sample — external CAPTCHA (hCaptcha, mode 90) via `captcha.modes`.
- `docs/`: `botblocker-runtime-contract.md`, `addon-api-v2.md`, `botblocker-core-object.md`, `botblocker-request-data.md`, `botblocker-settings-reference.md`, `traffic-and-redirect-addons.md`, `core-hook-integration.md`, `tools-page-hooks.md`, `settings-contract.md`, `settings-ui-patterns.md`, `lifecycle-and-features.md`, `code-quality-standard.md`, `packaging-and-upload.md`, `testing.md`, `compatibility-matrix.md`, `known-core-contract-gaps.md`
- `tools/`: `validate-addon.php`, `package-addon.ps1`
- `ai/botblocker-addon-skill/SKILL.md`

## Critical Runtime Model

Add-ons load only from the WordPress uploads runtime dir (after upload + install), never from this repo/source folder.

Flow:

1. Build source folder named like add-on slug.
2. ZIP = exactly that one root folder.
3. Admin uploads ZIP in `BotBlocker -> Add-ons`.
4. BotBlocker validates ZIP, installs to `wp-content/uploads/botblocker/addons/{slug}`.
5. Inactive until admin activates.
6. Active compatible add-ons load from runtime uploads dir.

Read `docs/botblocker-runtime-contract.md` first.

Timing: normal active v2 add-ons still load after the main request check. Traffic add-on joins the request cycle only via pre-run contract (`docs/core-hook-integration.md`): manifest `features` must include `traffic_decision_provider`, `runtime.pre_run` must name a safe pre-run file exposing the declared readiness marker + registration callback.

Weekly-report add-ons need no in-cycle contract: own cron, stats from BotBlocker DB. See `docs/addon-api-v2.md` (Weekly-report add-ons); `bbcs-pusher`/`bbcs-telegram` = references.

## Critical Traffic-Control Warning

Traffic-management add-ons = high-risk security + ops code: a bad rule can redirect real visitors, break checkout/payment callbacks, block support/admin flows, hide challenge/denied pages, create redirect loops, weaken protection.

Use `traffic_decision_provider` only when no normal post-check WordPress hook solves it. Ship disabled by default, `dry_run` first, test on staging, document rollback, require admin review before production redirects/blocks/bypasses/CAPTCHA.

## Fast Path For A New Add-on

1. Copy `examples/acme-botblocker-sample` → new folder = final slug.
2. Rename folder, manifest `slug`, root PHP file, text domain, PHP function prefix, option name, handles, CSS classes, JS globals.
3. Behavior in `inc/core.php`.
4. Admin controls in `inc/settings.php`.
5. Settings fields under `settings.option`, e.g. `vendor_addon_settings[enabled]`.
6. Implement manifest-declared sanitizer.
7. Visitor/request data → `docs/botblocker-core-object.md` + `docs/botblocker-request-data.md`.
8. Traffic → `docs/traffic-and-redirect-addons.md`, inspect `examples/acme-traffic-guard`, choose post-check WordPress hooks or the stricter pre-run traffic decision provider contract.
9. Lifecycle callbacks only when needed.
10. Validate source folder.
11. Package.
12. Validate ZIP.
13. Upload via BotBlocker admin, run manual tests.

## Validate

Kit root:

```powershell
php .\tools\validate-addon.php .\examples\acme-botblocker-sample
php .\tools\validate-addon.php .\examples\acme-traffic-guard
```

ZIP:

```powershell
php .\tools\validate-addon.php .\dist\acme-botblocker-sample.zip
```

## Package

PowerShell:

```powershell
.\tools\package-addon.ps1 -AddonPath .\examples\acme-botblocker-sample -DestinationPath .\dist\acme-botblocker-sample.zip
.\tools\package-addon.ps1 -AddonPath .\examples\acme-traffic-guard -DestinationPath .\dist\acme-traffic-guard.zip
```

Manual (Linux/macOS, or WSL on Windows):

```bash
zip -r acme-botblocker-sample.zip acme-botblocker-sample
```

Run manual packaging from dir containing the add-on folder; archive the folder itself, not files inside. Do NOT use `Compress-Archive` on Windows: writes backslash separators WordPress `unzip_file` does not normalize → extracts to literal `\`-named files on Linux hosting, install fails. Use `tools/package-addon.ps1` (forward-slash entries) or `zip -r`.

Correct ZIP:

```text
acme-botblocker-sample.zip
  acme-botblocker-sample/
    bbcs-addon.json
    acme-botblocker-sample.php
    inc/core.php
    inc/settings.php
    assets/icon.svg
    readme.txt
```

Wrong ZIP:

```text
acme-botblocker-sample.zip
  bbcs-addon.json
  inc/core.php
```

## Minimal Manifest

```json
{
  "schema": "2.0",
  "slug": "vendor-addon",
  "name": "Vendor Add-on",
  "version": "1.0.0",
  "requires_core": "1.7.5",
  "requires_php": "7.4",
  "author": "Vendor",
  "description": "Adds a focused BotBlocker extension with configurable runtime behavior.",
  "main": "vendor-addon.php",
  "core": "inc/core.php",
  "settings": {
    "view": "inc/settings.php",
    "option": "vendor_addon_settings",
    "sanitize": "vendor_addon_sanitize_settings"
  },
  "lifecycle": {
    "activate": "vendor_addon_activate",
    "delete": "vendor_addon_delete"
  },
  "features": [
    "vendor_feature_provider"
  ],
  "ui": {
    "palette": {
      "icon": "puzzle",
      "title": "Vendor Add-on",
      "priority": 50
    }
  },
  "gateway": {},
  "storage": {},
  "assets": {
    "icon": "assets/icon.svg",
    "readme": "readme.txt"
  }
}
```

Required: `schema`, `slug`, `name`, `version`, `requires_core`, `core`.

Quality-required (normal add-ons): `requires_php`, `author`, `description`, `main`, `settings.view` (admin UI), `settings.option` + `settings.sanitize` (settings saved), `assets.icon`, `assets.readme`.

## Settings Contract

Third-party v2 settings must use the manifest option array:

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
```

Do not copy BotBlocker built-in plain-field settings such as:

```php
<input type="checkbox" name="disable_emojis" value="1">
```

Those work only via core hardcoded save logic. Third-party add-ons saved by `BotBlockerAddons::saveSettingsFromPost()` via `settings.option`.

## Settings UI Pattern

`settings.view` renders in the add-on settings tab on `BotBlocker -> Add-ons` (active add-ons): BotBlocker-style help block, then grouped controls.

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
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
            <?php else : ?>
                <i class="fa-solid fa-puzzle-piece fa-3x bbcs_color_green mb-3" aria-hidden="true"></i>
            <?php endif; ?>
            <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the add-on does and where it acts.', 'vendor-addon' ); ?></p>
            <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the admin can configure and what data is stored.', 'vendor-addon' ); ?></p>
            <hr class="bbcs-info-hr">
            <div class="bbcs-info-footer">
                <i class="fa-regular fa-circle-question"></i>
                <a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'vendor-addon' ); ?></a>
                <a href="https://botblocker.top/contacts/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Support', 'vendor-addon' ); ?></a>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Main', 'vendor-addon' ); ?></h3>
        <!-- Controls go here. -->
    </div>
</div>
```

Field wrappers: `bbcs_checkbox_input`, `bbcs_label_checkbox_box`, `bbcs_label_input_checkbox` (checkboxes); `bbcs_text_input`, `bbcs_label_input_box`, `bbcs-label-input`, `bbcs_text_input_inner`, `bbcs_text_input_input` (text, URL, number, textarea, select — BotBlocker styles `input`, `select`, `textarea` inside `.bbcs_text_input_inner`).

Checkbox:

```php
<div class="bbcs_checkbox_input mb-2">
    <div class="bbcs_label_checkbox_box">
        <input type="hidden" name="<?php echo esc_attr( $option ); ?>[enabled]" value="0">
        <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( 1, $settings['enabled'] ?? 0 ); ?>>
        <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable add-on', 'vendor-addon' ); ?></span>
    </div>
</div>
```

Text field:

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

Textarea + select examples: `docs/settings-ui-patterns.md`.

## Assets

`plugin_dir_url()` does not work for uploaded add-ons (outside plugin source dir):

```php
function vendor_addon_asset_url( string $relative ): string {
    return class_exists( 'BotBlockerAddons' )
        ? BotBlockerAddons::fileUrl( 'vendor-addon', $relative )
        : '';
}
```

Runtime packages install into a protected uploads dir; test every icon/JS/CSS URL on real WordPress, confirm HTTP 200. See `docs/known-core-contract-gaps.md`.

## Code Quality Gate

Not done until passes: unique slug + prefix, valid manifest, valid one-root-folder ZIP, PHP lint, validator, escaped settings view output, explicit settings sanitizer, idempotent lifecycle callbacks, no `plugin_dir_url()` for runtime assets, no unprefixed globals, no unsafe superglobal usage, no writes into BotBlocker plugin source, WordPress upload/activate/save/deactivate/delete test, asset HTTP 200 test when using static assets.

Full checklist: `docs/code-quality-standard.md`, `docs/testing.md`.

## Reference Add-ons

`examples/acme-botblocker-sample` = canonical normal template.

`examples/acme-traffic-guard` = advanced traffic-management add-ons in the request cycle. Shows: manifest `features: ["traffic_manager", "traffic_decision_provider"]`, manifest `runtime.pre_run`, separate `inc/pre-run.php`, readiness marker + provider registration callback, dry-run `log_only` decisions, guarded `redirect` decisions, BotBlocker settings under `settings.option`, recent match logging with hashed IP values.

Both = canonical third-party references; copy/adapt, not any other source.

## Official Links

https://wordpress.org/plugins/botblocker-security/ , https://botblocker.top/products/ , https://botblocker.top/docs/ , https://botblocker.top/contacts/ , https://botblocker.top/community/ , https://botblocker.top/changelog/ , https://globus.studio/ , https://leonidov.dev/

Screenshots, banners, icons, public links: `docs/links-and-assets.md`.

## Definition Of Done

Create a v2 add-on, validate, package, upload via BotBlocker admin, activate, see Add-ons card + settings tab, save settings, observe runtime, deactivate/delete/reinstall — no fatal errors, PHP warnings, manual core edits, undocumented assumptions.
