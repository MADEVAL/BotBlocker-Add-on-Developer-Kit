# BotBlocker Add-on Developer Kit

![BotBlocker Security banner](https://ps.w.org/botblocker-security/assets/banner-1544x500.png?rev=3405280)

A repository-ready manual for building, packaging, testing, and shipping third-party add-ons for BotBlocker Security, an advanced proactive protection plugin for WordPress.

## What is BotBlocker Security

BotBlocker Security is a modern WordPress security plugin and anti-bot firewall built for proactive site protection. It analyzes requests before they become a WordPress workload, blocks malicious automation, reduces attack noise, and helps site owners defend login forms, XML-RPC, REST endpoints, comment flows, file requests, payment callbacks, and high-risk traffic patterns.

The plugin is designed for real production traffic, not only dashboard hardening. BotBlocker combines a Web Application Firewall, early-init protection, IP and ASN intelligence, fake crawler detection, browser and protocol checks, configurable CAPTCHA layers, two-factor authentication support, security logs, live traffic monitoring, payment gateway bypass rules, and add-on based premium capabilities.

When early-init protection is enabled, BotBlocker can run before the active theme and most plugins load. This lets it stop abusive requests at the front gate, reduce server load during attacks, and keep WordPress available for real visitors. BotBlocker is also built with privacy in mind: protection is based on technical request parameters rather than visitor profiling.

## Why this kit exists

BotBlocker Security 1.6.20 introduced Add-on API v2, a manifest-first package format designed so external developers can build extensions without editing BotBlocker core files. Add-ons are uploaded as ZIP packages from the BotBlocker admin screen, installed into the protected runtime add-ons directory, and activated by the site administrator after review.

This kit documents that add-on contract and provides a working sample package so agencies, freelancers, vendors, and AI coding assistants can create BotBlocker add-ons with predictable structure, safer packaging, and clear compatibility metadata.

## What is inside

- `docs/addon-api-v2.md`: manifest, runtime model, settings, lifecycle callbacks, and provider features.
- `docs/packaging-and-upload.md`: ZIP layout, package rules, upload flow, and release checklist.
- `docs/links-and-assets.md`: public BotBlocker links and WordPress.org image assets.
- `examples/acme-botblocker-sample`: a working add-on package with settings, lifecycle callbacks, and visible runtime behavior.
- `ai/botblocker-addon-skill/SKILL.md`: an AI skill for generating and reviewing BotBlocker add-ons.

## Requirements

- BotBlocker Security `1.6.20` or newer.
- WordPress `5.0` or newer. BotBlocker is tested up to WordPress `7.0`.
- PHP `7.4` or newer.
- A local WordPress development site for testing.

## Quick start

1. Copy `examples/acme-botblocker-sample` to your development workspace.
2. Rename the folder, manifest slug, PHP prefixes, option names, and text domain.
3. Implement the add-on behavior in `inc/core.php`.
4. Update `bbcs-addon.json` with your final metadata.
5. Create a ZIP that contains exactly one root folder.
6. In WordPress admin, open `BotBlocker -> Add-ons`, click `Upload ZIP`, upload the package, then activate it from the Installed tab.

PowerShell packaging command:

```powershell
Compress-Archive -Path .\acme-botblocker-sample -DestinationPath .\acme-botblocker-sample.zip -Force
```

Run the command from the directory that contains the add-on folder. Archive the folder itself, not only the files inside it. This is the same package shape used by the working `bbcs-cookie-alert` v2 add-on: the ZIP opens to one top-level folder, and that folder name equals the manifest `slug`.

## Package shape

```text
acme-botblocker-sample/
  index.php
  bbcs-addon.json
  acme-botblocker-sample.php
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

The root folder name must match the manifest `slug`.

This is the recommended layout for new add-ons. BotBlocker does not require JavaScript or images to live specifically under `assets/`; it follows the package-relative paths declared in the manifest and PHP code. For example, the first working `bbcs-cookie-alert` v2 add-on uses a root icon path (`bbcs-cookie-alert.svg`) and a frontend script under `inc/`. The important rule is that every declared path must be relative, safe, inside the package, and present in the ZIP.

Recommended file roles:

- `bbcs-addon.json`: BotBlocker Add-on API v2 manifest. This is the primary contract.
- `{slug}.php`: root metadata file and optional bootstrap that includes `inc/core.php`.
- `inc/core.php`: runtime hooks, lifecycle callbacks, settings defaults, sanitizers, and asset enqueue functions.
- `inc/settings.php`: admin settings view rendered inside `BotBlocker -> Tools` when the add-on is active.
- `assets/icon.svg`: add-on icon shown in the Add-ons UI.
- `assets/admin.js`: JavaScript for BotBlocker admin screens.
- `assets/frontend.js`: JavaScript for public frontend behavior.
- `readme.txt`: package notes, installation, and changelog.
- `index.php`: silence files for direct directory browsing protection.

## Manifest fields

Every v2 add-on must include `bbcs-addon.json` in the package root.

```json
{
  "schema": "2.0",
  "slug": "acme-botblocker-sample",
  "name": "ACME BotBlocker Sample Add-on",
  "version": "1.0.0",
  "requires_core": "1.6.20",
  "requires_php": "7.4",
  "author": "ACME Security",
  "description": "Working sample add-on with settings, lifecycle callbacks, assets, and optional scripts.",
  "main": "acme-botblocker-sample.php",
  "core": "inc/core.php",
  "settings": {
    "view": "inc/settings.php",
    "option": "acme_bbcs_sample_settings",
    "sanitize": "acme_bbcs_sample_sanitize_settings"
  },
  "lifecycle": {
    "activate": "acme_bbcs_sample_activate",
    "deactivate": "acme_bbcs_sample_deactivate",
    "delete": "acme_bbcs_sample_delete"
  },
  "features": [
    "sample_response_header"
  ],
  "assets": {
    "icon": "assets/icon.svg",
    "readme": "readme.txt"
  }
}
```

Required fields:

- `schema`: manifest schema, currently `2.0`.
- `slug`: sanitized package slug. It must match the root folder.
- `name`: add-on name shown in BotBlocker admin.
- `version`: add-on version.
- `requires_core`: minimum BotBlocker Security version.
- `core`: relative path to the runtime PHP file.

Recommended fields:

- `requires_php`: minimum PHP version.
- `author`: vendor or author name.
- `description`: short admin UI description.
- `main`: root PHP metadata file.
- `settings.view`: settings view included in `BotBlocker -> Tools`.
- `settings.option`: WordPress option name used for this add-on settings array.
- `settings.sanitize`: callable sanitizer used before `update_option()`.
- `lifecycle.file`: optional relative file loaded before lifecycle callbacks.
- `lifecycle.install`: optional callback for package install.
- `lifecycle.activate`: callback called when the add-on is activated.
- `lifecycle.deactivate`: callback called when the add-on is deactivated.
- `lifecycle.delete`: callback called before/while the add-on is removed.
- `lifecycle.update`: optional callback for package replacement/update flows.
- `lifecycle.load`: optional callback for active add-on load events.
- `lifecycle.health_check`: optional callback for diagnostic flows.
- `features`: capability names exposed by the active add-on.
- `assets.icon`: relative icon path used by the Add-ons UI.
- `assets.readme`: relative readme path.

The `description` field is the text shown on the Add-ons card. Keep it short but useful. A good card description explains what the add-on does, where it acts, and what the admin can configure.

Example:

```text
Displays a lightweight first-party cookie consent banner with editable notice text, policy link, theme, position, and safe BotBlocker settings storage.
```

## Root plugin header

The root PHP file should include WordPress-style metadata for compatibility, debugging, and human review.

```php
<?php
/**
 * Plugin Name: ACME BotBlocker Sample Add-on
 * Description: Working sample package for BotBlocker Add-on API v2.
 * Version: 1.0.0
 * Author: ACME Security
 * Requires-Core: 1.6.20
 * Requires PHP: 7.4
 * Text Domain: acme-botblocker-sample
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/inc/core.php';
```

Keep the manifest authoritative. Keep the root header aligned with the manifest so administrators can inspect packages quickly.

Root header fields commonly used by BotBlocker add-ons:

- `Plugin Name`: human-readable add-on name.
- `Description`: short add-on description.
- `Version`: add-on version.
- `Author`: vendor or author name.
- `Requires-Core`: minimum BotBlocker Security version.
- `Requires PHP`: minimum PHP version.
- `Text Domain`: translation text domain, usually the slug.
- `Plugin URI`: optional public add-on page.
- `Author URI`: optional author or vendor page.
- `License`: optional package license.
- `License URI`: optional license URL.

## Icons

Use `assets.icon` in the manifest for v2 add-ons:

```json
"assets": {
  "icon": "assets/icon.svg",
  "readme": "readme.txt"
}
```

Icon recommendations:

- Preferred formats: `SVG` or transparent `PNG`.
- Also acceptable for browser-rendered UI icons: `WebP`, `JPG`, `JPEG`, and `GIF` when there is a real reason to use them.
- Use a square canvas, for example `128x128` or `256x256`.
- Keep the file inside the package, usually under `assets/`.
- Use a relative path only. Do not use remote URLs or absolute paths.
- Do not point the icon path to PHP, HTML, or a dynamic endpoint.
- The icon is optional for v2, but strongly recommended for a polished Add-ons UI card.

BotBlocker resolves the icon from `assets.icon` first. It also supports a top-level manifest `icon` field for compatibility. Legacy v1 packages use `{slug}.svg` or `{slug}.png` in the root folder. New packages should use the v2 `assets.icon` manifest field.

## Settings and data saving

BotBlocker saves active add-on settings from the Tools form. The save flow is:

1. The add-on is active and declares `settings.option`.
2. The settings view renders form fields named under that option array.
3. The administrator clicks the BotBlocker Tools save button.
4. BotBlocker reads `$_POST[settings.option]` for active add-ons only.
5. BotBlocker includes the add-on `core` file when needed.
6. BotBlocker calls `settings.sanitize` if callable.
7. BotBlocker stores the sanitized array with `update_option(settings.option, $clean)`.

Settings field names must match the option declared in the manifest:

```php
<input type="hidden" name="acme_bbcs_sample_settings[enabled]" value="0">
<input type="checkbox" name="acme_bbcs_sample_settings[enabled]" value="1">
<input type="text" name="acme_bbcs_sample_settings[header_value]" value="<?php echo esc_attr( $settings['header_value'] ?? '' ); ?>">
```

Use activation callbacks to create defaults and delete callbacks to clean up package-owned options when that is the intended behavior.

## Settings help block

The settings page for an active add-on is rendered from `settings.view` inside `BotBlocker -> Tools`, under a tab named after the add-on. Put the add-on help block at the top of that settings view, before controls.

Use the native BotBlocker pattern: first an icon, then one or two concise help paragraphs, then footer links. This is the same pattern used by first-party add-ons such as Cookie Alert and Malware Scanner.

```php
<div class="row">
  <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
    <div class="bbcs-info-inner">
      <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
      <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the add-on does and where it acts.', 'vendor-addon' ); ?></p>
      <p class="bbcs-info-text"><?php esc_html_e( 'Explain what the admin can configure and what data is stored.', 'vendor-addon' ); ?></p>
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
    <!-- Settings controls go here. -->
  </div>
</div>
```

Cookie Alert help text example:

```text
Displays a lightweight first-party cookie notice for visitors and stores consent locally in a BotBlocker-named cookie.

Use it for simple privacy notices where no external consent platform is required. Configure message text, policy link, button label, theme, position, and optional CSS.
```

Good footer links for a Cookie Alert add-on:

- `Cookie guidance`: `https://gdpr.eu/cookies/`
- `WordPress privacy`: `https://wordpress.org/documentation/article/settings-privacy-screen/`
- `BotBlocker docs`: `https://botblocker.top/docs/`

## JavaScript and CSS assets

Do not use `plugin_dir_url()` for uploaded add-ons. Runtime add-ons live outside the BotBlocker plugin source directory. Use BotBlocker runtime asset helpers instead.

```php
function acme_bbcs_sample_asset_url( string $relative ): string {
    return function_exists( 'bbcs_addon_file_url' )
        ? bbcs_addon_file_url( 'acme-botblocker-sample', $relative )
        : '';
}
```

Admin script example:

```php
function acme_bbcs_sample_enqueue_admin_assets(): void {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || false === strpos( (string) $screen->id, 'botblocker' ) ) {
        return;
    }

    $url = acme_bbcs_sample_asset_url( 'assets/admin.js' );
    if ( '' === $url ) {
        return;
    }

    wp_enqueue_script( 'acme-bbcs-sample-admin', $url, array(), '1.0.0', true );
}
add_action( 'admin_enqueue_scripts', 'acme_bbcs_sample_enqueue_admin_assets' );
```

Frontend script example:

```php
function acme_bbcs_sample_enqueue_frontend_assets(): void {
    $url = acme_bbcs_sample_asset_url( 'assets/frontend.js' );
    if ( '' === $url ) {
        return;
    }

    wp_enqueue_script( 'acme-bbcs-sample-frontend', $url, array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'acme_bbcs_sample_enqueue_frontend_assets' );
```

Use unique handles, enqueue only when needed, and pass server data with `wp_add_inline_script()` or `wp_localize_script()` after escaping and sanitizing the source values.

Assets may live under `assets/`, `inc/`, or another package directory. The path passed to `bbcs_addon_file_url()` must match the file's package-relative path, for example `assets/frontend.js` or `inc/bbcs-cookie-alert-js.js`.

## Runtime model

BotBlocker scans installed add-ons from its protected uploads runtime directory. Your source repository is not the runtime location. Uploading a ZIP installs or replaces the runtime copy, but the add-on remains inactive until an administrator activates it.

BotBlocker supports both contracts:

- v2 manifest add-ons using `bbcs-addon.json`.
- v1 legacy add-ons using the classic root file, `inc/{slug}-core.php`, `inc/{slug}-settings.php`, icon, and `readme.txt` layout.

Use v2 for new development.

## Compatibility reference

This kit is aligned with the working `bbcs-cookie-alert` v2 add-on and the real BotBlocker runtime mechanics:

- `bbcs-cookie-alert.zip` is created by archiving the `bbcs-cookie-alert` folder itself.
- The ZIP contains exactly one top-level folder named `bbcs-cookie-alert`.
- The folder name matches `bbcs-addon.json` `slug`.
- The manifest points to the real root file, core file, settings view, icon, and readme.
- The settings view is rendered under `BotBlocker -> Tools` only after activation.
- Settings are saved through `settings.option` and `settings.sanitize` from the Tools form.
- Frontend JavaScript is loaded from the runtime add-on directory with a BotBlocker add-on asset URL helper.

## Official links

- WordPress.org plugin page: https://wordpress.org/plugins/botblocker-security/
- Product site: https://botblocker.top/products/
- Documentation: https://botblocker.top/docs/
- Support: https://botblocker.top/contacts/
- Community: https://botblocker.top/community/
- Changelog: https://botblocker.top/changelog/
- Developer studio: https://globus.studio/
- Architecture and code: https://leonidov.dev/

See `docs/links-and-assets.md` for screenshots, banners, icons, and additional public links.

## Development rules

- Prefix every function, class, option, action, filter, transient, cron hook, and asset handle.
- Do not write into the BotBlocker plugin source directory.
- Do not assume your add-on source repository exists on the production site.
- Do not auto-enable destructive behavior on upload or activation.
- Treat uploaded add-ons as executable PHP plugins. Keep the package small, auditable, and reversible.
- Keep compatibility metadata accurate: `requires_core` and `requires_php` are enforced.

## License

This manual and sample code are intended for BotBlocker add-on development. Choose the final license for the standalone repository before publishing.