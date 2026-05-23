# Packaging and Upload Guide

This guide describes the expected ZIP package and the BotBlocker upload flow.

## ZIP rules

BotBlocker validates uploaded add-ons before installing them.

- The uploaded file must be a `.zip` package.
- Other archive formats are not supported for admin upload: do not use `.rar`, `.7z`, `.tar`, `.tar.gz`, or `.gz`.
- The package must contain exactly one root folder.
- The root folder must be a valid sanitized slug.
- The root folder must match `bbcs-addon.json` `slug`.
- Unsafe paths are rejected, including absolute paths, drive letters, null bytes, and `../` traversal.
- Packages larger than 20 MB are rejected.
- ZIPs with fewer than 1 or more than 500 entries are rejected when PHP `ZipArchive` is available.
- Individual ZIP entries larger than 5 MB are rejected when PHP `ZipArchive` is available.
- `requires_core` is required and enforced.
- `requires_php` is enforced when declared.
- Upload installs the add-on inactive by default.
- Replacing an existing add-on uses backup and rollback handling.

Archive the folder itself. Do not select all files inside the folder and compress them directly. The working Cookie Alert package was installed successfully because `bbcs-cookie-alert.zip` opened to `bbcs-cookie-alert/`, not to loose files.

## Correct package layout

```text
acme-botblocker-sample.zip
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

Do not ZIP only the files inside the folder. ZIP the folder itself. When you open the ZIP, the first thing you should see is one folder named exactly like the add-on slug.

## PowerShell packaging

Run this command from the directory that contains the add-on folder:

```powershell
Compress-Archive -Path .\acme-botblocker-sample -DestinationPath .\acme-botblocker-sample.zip -Force
```

## macOS or Linux packaging

Run this command from the directory that contains the add-on folder:

```bash
zip -r acme-botblocker-sample.zip acme-botblocker-sample
```

## Upload flow

1. Open WordPress admin.
2. Go to `BotBlocker -> Add-ons`.
3. Click `Upload ZIP` in the Add-ons card header.
4. Select the package.
5. Click `Install Package`.
6. Open the Installed tab.
7. Review the add-on metadata.
8. Activate the add-on.
9. Configure settings from BotBlocker tools if the add-on declares a settings view.

Settings pages are displayed only for active add-ons that have `settings.view`. The settings view appears in `BotBlocker -> Tools` as an add-on tab.

## Image and icon rules

- Declare v2 icons with `assets.icon`, for example `assets/icon.svg`.
- The value may point to any safe package-relative image path, including a root icon such as `bbcs-cookie-alert.svg`.
- Preferred formats: `SVG` and transparent `PNG`.
- Acceptable browser image formats: `WebP`, `JPG`, `JPEG`, and `GIF` when intentionally used.
- Keep icons square and small, for example `128x128` or `256x256`.
- Do not use remote URLs, absolute paths, PHP files, or HTML files as icons.

## Settings help block

Write add-on help inside the `settings.view` PHP file. Put it before the settings controls so the administrator first sees context, then fields.

Recommended pattern:

- Left column: `.bbcs-info-column` and `.bbcs-info-inner`.
- First: icon image or Font Awesome fallback.
- Then: one or two `.bbcs-info-text` paragraphs.
- Then: `.bbcs-info-footer` with a question icon and useful links.
- Remaining columns: grouped settings with `.bbcs_settings_h3` and BotBlocker input classes.

Cookie Alert text example:

```text
Displays a lightweight first-party cookie notice for visitors and stores consent locally in a BotBlocker-named cookie.

Use it for simple privacy notices where no external consent platform is required. Configure message text, policy link, button label, theme, position, and optional CSS.
```

Cookie Alert links example:

- Cookie guidance: `https://gdpr.eu/cookies/`
- WordPress privacy: `https://wordpress.org/documentation/article/settings-privacy-screen/`
- BotBlocker docs: `https://botblocker.top/docs/`

## Release checklist

- Manifest slug matches the root folder.
- Manifest `assets.icon` points to an existing package-relative browser image file.
- Add-ons card `description` is clear and not too short.
- Settings view starts with a BotBlocker-style help block: icon, text, and links.
- All PHP symbols are prefixed.
- Settings option uses a unique prefix.
- Settings fields use the manifest `settings.option` array name.
- The manifest `settings.sanitize` callback sanitizes every stored field.
- JavaScript and CSS assets use `bbcs_addon_file_url()` rather than `plugin_dir_url()`.
- Admin scripts are limited to BotBlocker admin screens when possible.
- Frontend scripts are only enqueued when the add-on behavior requires them.
- Activate, deactivate, delete, and update callbacks are safe to run more than once.
- No files are written into BotBlocker source directories.
- No remote calls are made without admin configuration or a documented reason.
- Frontend output is escaped.
- Admin output is escaped.
- User input is sanitized.
- Capability and nonce checks are used for custom admin actions.
- Package was installed, activated, deactivated, deleted, and reinstalled on a local WordPress site.

## Common upload errors

- `zip_extension`: the uploaded file is not a ZIP.
- `zip_unsafe_path`: the ZIP contains a dangerous path.
- `package_root`: the ZIP does not contain exactly one root folder.
- `package_slug`: the root folder is not a valid slug.
- `slug_mismatch`: root folder and manifest slug do not match.
- `package_invalid`: required manifest data or core file is missing.
- `requires_core`: the target BotBlocker version is too old.
- `requires_php`: the target PHP version is too old.
- `move_failed`: WordPress could not move the validated package into runtime storage.

## Versioning

Use semantic versions for add-ons. Raise `requires_core` only when your package actually depends on a new BotBlocker API or behavior. Keep `requires_php` at the lowest version you test and support.