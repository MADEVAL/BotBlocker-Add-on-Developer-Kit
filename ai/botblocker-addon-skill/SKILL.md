# BotBlocker Add-on Developer Skill

Use this skill when creating, reviewing, debugging, or documenting BotBlocker Security add-ons.

## Product context

BotBlocker Security is an advanced proactive protection plugin for WordPress. It is a security plugin, anti-bot firewall, and Web Application Firewall designed to stop malicious automation before it becomes a WordPress workload.

BotBlocker protects WordPress sites through early-init filtering, IP and ASN intelligence, fake crawler detection, protocol and browser checks, CAPTCHA layers, two-factor authentication support, XML-RPC and REST API controls, payment gateway bypass rules, security logs, live traffic monitoring, and add-on based premium capabilities.

When writing add-ons, treat BotBlocker as a production security platform. Add-ons must extend protection, integrations, reporting, diagnostics, admin workflows, or safe automation without weakening the core security model. Do not generate marketing-only add-ons, decorative UI-only packages, or code that bypasses BotBlocker checks unless the user explicitly requests a narrowly scoped compatibility exception.

Current baseline:

- BotBlocker Security: `1.6.20+`.
- WordPress: `5.0+`, tested up to `7.0`.
- PHP: `7.4+`.
- Add-on format: Add-on API v2 with `bbcs-addon.json`.

## Use for

- Creating a new BotBlocker Add-on API v2 package.
- Updating `bbcs-addon.json` manifests.
- Adding add-on settings views and sanitize callbacks.
- Implementing lifecycle callbacks.
- Packaging an add-on ZIP for upload from `BotBlocker -> Add-ons`.
- Reviewing compatibility with BotBlocker `1.6.20+`, WordPress `5.0+`, and PHP `7.4+`.
- Preserving legacy v1 add-on compatibility when touching shared scanner or loader code.
- Explaining BotBlocker Security as a proactive WordPress protection and anti-bot firewall platform.

## Do not use for

- General WordPress plugin development unrelated to BotBlocker add-ons.
- Editing BotBlocker core behavior without an explicit request.
- Creating add-ons that disable, weaken, or silently bypass BotBlocker protections.
- Creating add-ons that collect visitor personal data without a clear security purpose and explicit documentation.
- Publishing credentials, license keys, private repository URLs, or production secrets.

## Required context

Before writing code, inspect the current package or requested add-on idea and identify:

- Add-on slug.
- Add-on name.
- Minimum BotBlocker version.
- Minimum PHP version.
- Settings option name.
- Function and class prefix.
- Runtime behavior.
- Security purpose.
- Expected admin workflow.
- Icon path.
- Settings help text.
- Settings help links.
- Admin and frontend asset needs.
- Lifecycle needs.
- Whether the add-on provides a feature capability.

## Package contract

A v2 package must contain exactly one root folder. The folder name must match the manifest `slug`.

```text
{slug}/
  index.php
  bbcs-addon.json
  {slug}.php
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

This is the recommended layout. BotBlocker follows package-relative manifest and code paths, so a real package may use a root icon such as `{slug}.svg` or a script under `inc/` when the manifest/code points there. The ZIP must still contain exactly one top-level folder.

Required manifest fields:

- `schema`
- `slug`
- `name`
- `version`
- `requires_core`
- `core`

Recommended manifest fields:

- `requires_php`
- `author`
- `description`
- `main`
- `settings.view`
- `settings.option`
- `settings.sanitize`
- `lifecycle.file`
- `lifecycle.install`
- `lifecycle.activate`
- `lifecycle.deactivate`
- `lifecycle.delete`
- `lifecycle.update`
- `lifecycle.load`
- `lifecycle.health_check`
- `features`
- `assets.icon`
- `assets.readme`

The manifest `description` is shown on the Add-ons card. It must be concise but complete enough to explain purpose, runtime surface, and configurable behavior.

## Coding rules

- Prefix every function, class, option, action, filter, transient, cron hook, and asset handle.
- Never echo from `inc/core.php` during load.
- Escape all output in settings views.
- Start settings views with the native BotBlocker help block pattern: icon, one or two concise text paragraphs, then footer links.
- Sanitize all settings in the manifest-declared sanitize callback.
- Render settings fields under the manifest `settings.option` array name.
- Use activation callbacks for default options when needed.
- Use delete callbacks to remove package-owned options only when that is intended.
- Declare `assets.icon` for a polished Add-ons UI card.
- Prefer `SVG` or transparent `PNG` icons. `WebP`, `JPG`, `JPEG`, and `GIF` are acceptable only for intentional browser-rendered images.
- Do not use remote URLs, absolute paths, PHP files, or HTML files as icon values.
- Use `bbcs_addon_file_url()` for add-on JavaScript, CSS, and image assets.
- Do not use `plugin_dir_url()` for uploaded runtime add-on assets.
- Enqueue admin scripts only on relevant BotBlocker admin screens.
- Enqueue frontend scripts only when the enabled add-on behavior requires them.
- Do not write into BotBlocker plugin source directories.
- Do not assume the source repository exists in production.
- Keep uploaded packages inactive by design.
- Make activation, deactivation, deletion, and update callbacks idempotent.
- Keep remote network calls explicit, documented, and optional unless the add-on purpose requires them.

## Review checklist

- Root folder and manifest slug match.
- The ZIP archives the add-on folder itself, not loose files.
- `requires_core` is present and accurate.
- `requires_php` is present and accurate.
- `core` path exists.
- `assets.icon` points to an existing package-relative file.
- Add-ons card description is useful and not just a label.
- Settings view has a left info/help column with icon, text, and links.
- Settings option and sanitize callback match the manifest.
- Settings inputs use names such as `{settings.option}[field_name]`.
- Save flow is documented: Tools form, active add-on, sanitizer, `update_option()`.
- Lifecycle callbacks are callable and safe to repeat.
- JS/CSS assets are enqueued through `bbcs_addon_file_url()` with unique handles.
- No unprefixed global symbols are introduced.
- No direct unsanitized `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, or `$_SERVER` values are used.
- No direct file writes are made outside the add-on runtime data plan.
- Package can be zipped with the root folder as the only top-level entry.

## Packaging command

Run from the directory that contains the add-on folder. Archive the folder itself.

PowerShell:

```powershell
Compress-Archive -Path .\{slug} -DestinationPath .\{slug}.zip -Force
```

macOS or Linux:

```bash
zip -r {slug}.zip {slug}
```

## Expected answer format

When producing a new add-on, return:

- File tree.
- Manifest.
- Core behavior summary.
- Settings summary.
- Settings help text and links.
- Packaging command.
- Manual test steps.
- Compatibility notes.