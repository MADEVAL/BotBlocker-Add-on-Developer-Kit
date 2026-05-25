# BotBlocker Add-on Developer Skill

Use this skill when creating, reviewing, debugging, packaging, or documenting BotBlocker Security add-ons.

## Product context

BotBlocker Security is a WordPress security plugin, anti-bot firewall, and Web Application Firewall. It protects real production traffic through request checks, rules, CAPTCHA layers, logs, live traffic monitoring, early-init protection, and add-on based extensions.

Treat BotBlocker as a production security platform. Add-ons must extend protection, integrations, reporting, diagnostics, admin workflows, privacy notices, or safe automation without weakening BotBlocker core behavior.

Current baseline:

- BotBlocker Security: `1.6.20+`
- WordPress: `5.0+`, tested up to `7.0`
- PHP: `7.4+`
- Add-on format: Add-on API v2 with `bbcs-addon.json`

## Runtime truth

BotBlocker scans installed add-ons from:

```text
wp-content/uploads/BotBlocker/addons/{slug}
```

It does not load third-party add-ons directly from this skill, from the developer kit repository, or from `plugin/wp-content/plugins/botblocker-security/addons`.

Required flow:

1. Build a source folder.
2. Package a ZIP with exactly one root folder.
3. Upload it in `BotBlocker -> Add-ons`.
4. Activate from the Installed tab.
5. Configure from `BotBlocker -> Tools` when the add-on has settings.

## Use for

- Creating Add-on API v2 packages.
- Updating `bbcs-addon.json`.
- Implementing add-on core files.
- Implementing settings views and sanitizers.
- Implementing lifecycle callbacks.
- Declaring feature providers.
- Packaging ZIPs for BotBlocker upload.
- Reviewing compatibility with BotBlocker `1.6.20+`.
- Preserving v1 compatibility when touching shared scanner/loader code.

## Do not use for

- General WordPress plugin development unrelated to BotBlocker add-ons.
- Editing BotBlocker core unless the user explicitly requests it.
- Creating add-ons that disable, weaken, or silently bypass BotBlocker protections.
- Creating add-ons that collect visitor personal data without clear purpose and documentation.
- Publishing credentials, private URLs, license keys, or production secrets.

## Required discovery before code

Identify:

- slug
- name
- purpose
- runtime surface: admin, frontend, request/headers, cron, API, diagnostics
- whether BotBlocker request data is needed
- whether traffic decisions can run after BotBlocker allows the request
- whether the add-on requires in-cycle BotBlocker core hooks
- minimum BotBlocker version
- minimum PHP version
- function/class prefix
- option name
- settings fields
- sanitizer behavior
- lifecycle needs
- feature capability names
- asset paths
- icon path
- help text and help links
- admin and frontend test steps

Read or follow the local docs when available:

- `docs/botblocker-runtime-contract.md`
- `docs/addon-api-v2.md`
- `docs/botblocker-core-object.md`
- `docs/botblocker-request-data.md`
- `docs/botblocker-settings-reference.md`
- `docs/traffic-and-redirect-addons.md`
- `docs/core-hook-integration.md`
- `docs/settings-contract.md`
- `docs/settings-ui-patterns.md`
- `docs/lifecycle-and-features.md`
- `docs/code-quality-standard.md`
- `docs/testing.md`
- `docs/known-core-contract-gaps.md`

## Package contract

Recommended v2 package:

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

BotBlocker follows package-relative manifest paths. Real packages may place assets elsewhere, such as root `{slug}.svg` or `inc/frontend.js`, if the manifest and code point to those paths.

ZIP rule: exactly one top-level folder, and its name must equal the manifest `slug`.

## Manifest rules

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
- `settings.view` when settings UI exists
- `settings.option` when settings save
- `settings.sanitize` when settings save
- `lifecycle.activate` when defaults are needed
- `lifecycle.delete` when cleanup is intended
- `features` when providing a capability
- `assets.icon`
- `assets.readme`

Use `requires_core: 1.6.20` for new third-party packages unless the user explicitly targets and tests an older BotBlocker version.

## Settings rules

Third-party v2 settings must use the manifest option array.

Correct:

```php
<input type="hidden" name="vendor_addon_settings[enabled]" value="0">
<input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
```

Wrong for third-party v2:

```php
<input type="checkbox" name="enabled" value="1">
```

Some first-party add-ons use plain field names because BotBlocker core has internal save logic. Do not copy that pattern for third-party add-ons.

The settings view renders only controls and current values. It must not save data, mutate runtime state, call remote APIs, or run scans just because it was included.

Match BotBlocker settings UI patterns from `docs/settings-ui-patterns.md`: start with the native help/info column, use `bbcs_settings_h3` group headings, use BotBlocker field wrapper classes, include hidden `0` values before checkboxes, and keep every input name under `settings.option`.

## Coding rules

- Prefix every function, class, option, action, filter, transient, cron hook, asset handle, JS global, and package-owned CSS class.
- Guard every PHP file with `ABSPATH`; core files may also require `BOTBLOCKER`.
- Never echo from `inc/core.php` during load.
- Register hooks only when the add-on should run.
- Escape all output in settings and frontend views.
- Sanitize every setting in the manifest-declared sanitizer.
- Use activation callbacks for defaults.
- Use delete callbacks for package-owned cleanup only when intended.
- Make lifecycle callbacks idempotent.
- Use `bbcs_addon_file_url()` for add-on assets.
- Do not use `plugin_dir_url()` for uploaded runtime add-ons.
- Enqueue admin scripts only on relevant BotBlocker admin screens.
- Enqueue frontend assets only when enabled and needed.
- Use nonce and capability checks for custom admin actions.
- Do not write into BotBlocker plugin source directories.
- Do not assume the source repository exists in production.
- Keep remote network calls explicit, documented, timeout-bound, and optional when practical.

## BotBlocker data and traffic rules

When an add-on needs visitor data, read from `BotBlocker::getInstance()` through a narrow helper and normalize values into an add-on-owned context array. Do not expose `get_bot_blocker_hive()` to public visitors.

Traffic-management add-ons are critical-risk code. They can redirect, allow, bypass, block, or challenge real production requests inside BotBlocker's security flow. Treat every traffic decision as a security-sensitive change: default to disabled, default to dry-run, require staging tests, document rollback, and never generate an active redirect/block/bypass behavior casually.

For redirect or traffic-management add-ons:

- Use `docs/traffic-and-redirect-addons.md` for choosing post-check redirects versus pre-run traffic decisions.
- Prefer post-check WordPress hooks unless the user explicitly needs in-cycle request decisions.
- Use `examples/acme-traffic-guard` as the reference only for advanced traffic-management add-ons.
- Redirect only requests BotBlocker already allowed unless the add-on explicitly implements the `traffic_decision_provider` pre-run contract.
- Skip BotBlocker check/block/denied pages, admin, AJAX, cron, unsafe HTTP methods, payment callbacks, and verified legal bots by default.
- Use `wp_safe_redirect()`, loop checks, same-site targets or an explicit host allowlist, and a dry-run setting.
- If the add-on must decide before BotBlocker core blocks/challenges the request, require manifest `features: ["traffic_decision_provider"]`, `runtime.pre_run`, a readiness marker, and a registration callback as documented in `docs/core-hook-integration.md`.
- Do not use `allow`, `bypass`, `block`, or `captcha` decisions for marketing routing. Those decisions require an explicit security or integration rationale.

## Asset caveat

BotBlocker installs runtime packages into a protected uploads directory. Static asset URLs returned by `bbcs_addon_file_url()` must be tested in a real WordPress install for HTTP 200. If the server blocks them, use inline output where practical or raise a BotBlocker core asset-delivery fix.

## Review checklist

- Root folder and manifest slug match.
- ZIP archives the root folder, not loose files.
- `bbcs-addon.json` is valid JSON.
- Required manifest fields are present.
- `requires_core` and `requires_php` are accurate.
- `core`, `settings.view`, `assets.icon`, and `assets.readme` paths exist when declared.
- Description explains purpose, runtime surface, and configurable behavior.
- Settings view begins with a BotBlocker-style help block.
- Settings inputs use `{settings.option}[field]` names.
- Sanitizer normalizes every stored field.
- Lifecycle callbacks are callable and safe to repeat.
- Assets use `bbcs_addon_file_url()` and unique handles.
- No unprefixed global symbols are introduced.
- No raw unsanitized `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, or `$_SERVER` values are used.
- No direct file writes happen outside the documented package-owned data plan.
- Package validates with `tools/validate-addon.php`.
- Package installs, activates, saves settings, deactivates, deletes, and reinstalls on WordPress.

## Packaging commands

Preferred PowerShell from kit root:

```powershell
.\tools\package-addon.ps1 -AddonPath .\examples\acme-botblocker-sample -DestinationPath .\dist\acme-botblocker-sample.zip
```

Traffic example:

```powershell
.\tools\package-addon.ps1 -AddonPath .\examples\acme-traffic-guard -DestinationPath .\dist\acme-traffic-guard.zip
```

Manual PowerShell from the parent directory:

```powershell
Compress-Archive -Path .\{slug} -DestinationPath .\{slug}.zip -Force
```

macOS/Linux:

```bash
zip -r {slug}.zip {slug}
```

## Expected answer format when creating an add-on

Return:

- file tree
- manifest summary
- runtime behavior summary
- settings summary
- lifecycle summary
- feature providers
- asset paths
- packaging command
- validation command
- manual test steps
- compatibility notes
