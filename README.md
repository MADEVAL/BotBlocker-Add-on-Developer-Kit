# BotBlocker Add-on Developer Kit

![BotBlocker Security banner](https://ps.w.org/botblocker-security/assets/banner-1544x500.png?rev=3405280)

This repository is the working manual, template, validator, and AI instruction pack for building third-party add-ons for BotBlocker Security.

BotBlocker Security is a WordPress anti-bot firewall and proactive protection plugin. It protects login flows, XML-RPC, REST, comments, file requests, payment callbacks, and high-risk traffic with request checks, rules, CAPTCHA, logs, early-init protection, and add-on based extensions.

## Baseline

- BotBlocker Security: `1.6.20+`
- WordPress: `5.0+`, tested up to `7.0`
- PHP: `7.4+`
- Add-on format: Add-on API v2 with `bbcs-addon.json`

`1.6.20` is the minimum BotBlocker version required for the Add-on API v2 system. New third-party add-ons should target `1.6.20+`.

## What Is Inside

- `examples/acme-botblocker-sample`: canonical v2 sample add-on for normal late-loaded behavior.
- `examples/acme-traffic-guard`: advanced v2 sample add-on for the pre-run `traffic_decision_provider` contract.
- `docs/botblocker-runtime-contract.md`: how BotBlocker scans, installs, activates, and loads add-ons.
- `docs/addon-api-v2.md`: manifest, settings, lifecycle, feature providers, assets.
- `docs/botblocker-core-object.md`: how to safely read the live `BotBlocker` object from an add-on.
- `docs/botblocker-request-data.md`: visitor/request/decision/browser data map available on the BotBlocker object.
- `docs/botblocker-settings-reference.md`: BotBlocker core settings reference for read-only add-on decisions.
- `docs/traffic-and-redirect-addons.md`: practical guide for traffic managers, redirect managers, and routers.
- `docs/core-hook-integration.md`: required core hook contract for add-ons that must run inside the BotBlocker check cycle.
- `docs/settings-contract.md`: exact settings save contract for third-party v2 add-ons.
- `docs/settings-ui-patterns.md`: BotBlocker settings tab layout, help block, field classes, and field examples.
- `docs/lifecycle-and-features.md`: lifecycle callbacks and feature provider rules.
- `docs/code-quality-standard.md`: required security and code quality bar.
- `docs/packaging-and-upload.md`: ZIP shape, upload flow, common upload errors.
- `docs/testing.md`: static, package, WordPress, lifecycle, asset, multisite, and security tests.
- `docs/compatibility-matrix.md`: kit/core/runtime compatibility comparison.
- `docs/known-core-contract-gaps.md`: known implementation gaps to verify or fix in BotBlocker core.
- `tools/validate-addon.php`: static validator for folders and ZIP packages.
- `tools/package-addon.ps1`: PowerShell packager that builds a correct one-root-folder ZIP.
- `ai/botblocker-addon-skill/SKILL.md`: AI instructions for creating/reviewing add-ons.

## Critical Runtime Model

BotBlocker does not load third-party add-ons from this repository or from your source folder. They run only from the WordPress uploads runtime directory after they are uploaded and installed.

The real runtime flow is:

1. Developer builds a source folder named like the add-on slug.
2. Developer creates a ZIP containing exactly that one root folder.
3. Administrator uploads the ZIP in `BotBlocker -> Add-ons`.
4. BotBlocker validates the ZIP and installs it into `wp-content/uploads/botblocker/addons/{slug}`.
5. The add-on stays inactive until the administrator activates it.
6. Active compatible add-ons are loaded from the runtime uploads directory.

Read `docs/botblocker-runtime-contract.md` before writing code.

Important timing note: normal active v2 add-ons are still included after the main BotBlocker request check has run. A traffic add-on can participate inside the request cycle only when it explicitly opts into the pre-run contract described in `docs/core-hook-integration.md`: manifest `features` must include `traffic_decision_provider`, manifest `runtime.pre_run` must name a safe pre-run file, and that file must expose the declared readiness marker plus registration callback.

## Critical Traffic-Control Warning

Traffic-management add-ons are high-risk security and operations code. A bad rule can redirect real visitors, break checkout/payment callbacks, block support/admin workflows, hide BotBlocker challenge or denied pages, create redirect loops, or weaken BotBlocker protection.

Use `traffic_decision_provider` only when a normal post-check WordPress hook cannot solve the problem. Ship traffic add-ons disabled by default, keep `dry_run` enabled first, test on staging, document rollback steps, and require explicit administrator review before production redirects, blocks, bypasses, or CAPTCHA decisions are enabled.

## Fast Path For A New Add-on

1. Copy `examples/acme-botblocker-sample` to a new folder named with your final slug.
2. Rename the folder, manifest `slug`, root PHP file, text domain, PHP function prefix, option name, handles, CSS classes, and JS globals.
3. Define behavior in `inc/core.php`.
4. Define admin controls in `inc/settings.php`.
5. Declare every settings field under `settings.option`, for example `vendor_addon_settings[enabled]`.
6. Implement the manifest-declared sanitizer.
7. If the add-on needs visitor/request data, read `docs/botblocker-core-object.md` and `docs/botblocker-request-data.md`.
8. If the add-on redirects or manages traffic, read `docs/traffic-and-redirect-addons.md`, inspect `examples/acme-traffic-guard`, and choose either post-check WordPress hooks or the stricter pre-run traffic decision provider contract.
9. Implement lifecycle callbacks only when needed.
10. Validate the source folder.
11. Package it.
12. Validate the ZIP.
13. Upload it through BotBlocker admin and run the manual tests.

## Validate

From this kit root:

```powershell
php .\tools\validate-addon.php .\examples\acme-botblocker-sample
php .\tools\validate-addon.php .\examples\acme-traffic-guard
```

Validate a ZIP:

```powershell
php .\tools\validate-addon.php .\dist\acme-botblocker-sample.zip
```

## Package

PowerShell:

```powershell
.\tools\package-addon.ps1 -AddonPath .\examples\acme-botblocker-sample -DestinationPath .\dist\acme-botblocker-sample.zip
.\tools\package-addon.ps1 -AddonPath .\examples\acme-traffic-guard -DestinationPath .\dist\acme-traffic-guard.zip
```

Manual equivalent:

```powershell
Compress-Archive -Path .\acme-botblocker-sample -DestinationPath .\acme-botblocker-sample.zip -Force
```

Run manual packaging from the directory that contains the add-on folder. Archive the folder itself, not the files inside it.

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
  "requires_core": "1.6.20",
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
  "assets": {
    "icon": "assets/icon.svg",
    "readme": "readme.txt"
  }
}
```

Required fields:

- `schema`
- `slug`
- `name`
- `version`
- `requires_core`
- `core`

Quality-required fields for normal add-ons:

- `requires_php`
- `author`
- `description`
- `main`
- `settings.view` when an admin UI exists
- `settings.option` when settings are saved
- `settings.sanitize` when settings are saved
- `assets.icon`
- `assets.readme`

## Settings Contract

Third-party v2 settings must use the manifest option array:

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
```

Do not copy BotBlocker's built-in plain-field settings such as:

```php
<input type="checkbox" name="disable_emojis" value="1">
```

Those fields work only because BotBlocker core has hardcoded internal save logic for its own built-in options. Third-party add-ons are saved by `BotBlockerAddons::saveSettingsFromPost()` through `settings.option`.

## Settings UI Pattern

`settings.view` is rendered inside `BotBlocker -> Tools` as an add-on tab. It should start with a BotBlocker-style help block and then render grouped controls.

Use this layout:

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
                <i class="fa-solid fa-puzzle-piece fa-3x bbcs_color_blue mb-3" aria-hidden="true"></i>
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

Common BotBlocker field wrappers:

- `bbcs_checkbox_input`, `bbcs_label_checkbox_box`, `bbcs_label_input_checkbox` for checkboxes.
- `bbcs_text_input`, `bbcs_label_input_box`, `bbcs-label-input`, `bbcs_text_input_inner`, `bbcs_text_input_input` for text, URL, and number fields.
- `bbcs_textarea_input`, `bbcs_textarea_input_inner`, `bbcs_textarea_input_input` for textarea fields.
- `bbcs_select_input`, `bbcs_select_input_inner`, `bbcs_select_input_select` for select fields.

Checkbox example:

```php
<div class="bbcs_checkbox_input mb-2">
    <div class="bbcs_label_checkbox_box">
        <input type="hidden" name="<?php echo esc_attr( $option ); ?>[enabled]" value="0">
        <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( 1, $settings['enabled'] ?? 0 ); ?>>
        <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable add-on', 'vendor-addon' ); ?></span>
    </div>
</div>
```

Text field example:

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

Textarea and select examples are in `docs/settings-ui-patterns.md`.

## Assets

Uploaded add-ons live outside the BotBlocker plugin source directory. Do not use `plugin_dir_url()` for package assets.

Use:

```php
function vendor_addon_asset_url( string $relative ): string {
    return class_exists( 'BotBlockerAddons' )
        ? BotBlockerAddons::fileUrl( 'vendor-addon', $relative )
        : '';
}
```

Server caveat: BotBlocker installs runtime packages into a protected uploads directory. Test every icon/JS/CSS URL in a real WordPress install and confirm HTTP 200. See `docs/known-core-contract-gaps.md`.

## Code Quality Gate

An add-on is not done until it passes:

- unique slug and prefix
- valid manifest
- valid one-root-folder ZIP
- PHP lint
- validator
- escaped settings view output
- explicit settings sanitizer
- idempotent lifecycle callbacks
- no `plugin_dir_url()` for runtime assets
- no unprefixed globals
- no unsafe superglobal usage
- no writes into BotBlocker plugin source
- WordPress upload/activate/save/deactivate/delete test
- asset HTTP 200 test when using static assets

Read `docs/code-quality-standard.md` and `docs/testing.md` for the full checklist.

## Reference Add-ons

Use `examples/acme-botblocker-sample` as the canonical normal add-on template.

Use `examples/acme-traffic-guard` only for advanced traffic-management add-ons that must participate inside the BotBlocker request cycle. It demonstrates:

- manifest `features: ["traffic_manager", "traffic_decision_provider"]`
- manifest `runtime.pre_run`
- separate `inc/pre-run.php`
- readiness marker and provider registration callback
- dry-run `log_only` decisions
- guarded `redirect` decisions
- BotBlocker Tools settings under `settings.option`
- recent match logging with hashed IP values

Both kit examples are the canonical third-party references. Copy and adapt them rather than any other add-on source.

## Official Links

- WordPress.org plugin page: https://wordpress.org/plugins/botblocker-security/
- Product site: https://botblocker.top/products/
- Documentation: https://botblocker.top/docs/
- Support: https://botblocker.top/contacts/
- Community: https://botblocker.top/community/
- Changelog: https://botblocker.top/changelog/
- Developer studio: https://globus.studio/
- Architecture and code: https://leonidov.dev/

See `docs/links-and-assets.md` for screenshots, banners, icons, and public links.

## Definition Of Done

A developer or AI can use this kit to create a v2 add-on, validate it, package it, upload it through BotBlocker admin, activate it, see its Add-ons card and Tools tab, save settings, observe runtime behavior, deactivate it, delete it, and reinstall it without fatal errors, PHP warnings, manual BotBlocker core edits, or undocumented assumptions.
