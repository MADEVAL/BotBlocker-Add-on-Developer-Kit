---
name: botblocker-addon-skill
description: >-
  Use when building, reviewing, debugging, packaging, or validating BotBlocker
  Security add-ons — WordPress anti-bot firewall extensions that ship as Add-on
  API v2 packages with a bbcs-addon.json manifest. Trigger keywords: BotBlocker
  add-on, bbcs-addon.json, Add-on API v2, addon lifecycle callback,
  settings.option sanitizer, traffic_decision_provider, runtime.pre_run,
  BotBlockerAddons::fileUrl, validate-addon, package-addon. Use ONLY for
  BotBlocker add-on development; do not use for general WordPress plugins or for
  editing BotBlocker core itself.
license: GPL-2.0-or-later
metadata:
  baseline: "BotBlocker Security 1.6.20+"
  format: "Add-on API v2 (bbcs-addon.json)"
---

# BotBlocker Add-on Developer Skill

This skill is the orchestrator for the **BotBlocker Add-on Developer Kit**. It
routes you to the right kit document for each task instead of restating it.
Read the mapped doc before writing code.

## Kit scope and wiring

- All `docs/`, `tools/`, and `examples/` paths below are **relative to the kit
  root** (the folder that contains this kit's `README.md`). The skill assumes
  the kit repository is the working directory.
- To register as an opencode skill: add the kit's `ai` directory to
  `skills.paths` in `opencode.json`, or copy `ai/botblocker-addon-skill/` into
  `.opencode/skills/`. Keep the full kit reachable so the referenced docs and
  tools resolve.
- This skill never edits BotBlocker core. It builds and reviews third-party
  add-on packages only.

## Product context

BotBlocker Security is a WordPress anti-bot firewall and Web Application
Firewall. It protects production traffic through request checks, rules, CAPTCHA
layers, logs, live monitoring, early-init protection, and add-on extensions.

- Baseline: **BotBlocker Security `1.6.20`** (minimum version for the Add-on
  API v2 system), WordPress `5.0+` (tested to `7.0`), PHP `7.4+`.
- Add-ons must extend protection, integrations, reporting, diagnostics, admin
  workflows, privacy notices, or safe automation **without weakening core**.

## Runtime truth (memorize)

- Installed add-ons are scanned and loaded from
  `wp-content/uploads/botblocker/addons/{slug}` — **not** from this kit and
  **not** from your source folder.
- Delivery flow: build a source folder → ZIP it with exactly one root folder
  named after the slug → upload in `BotBlocker -> Add-ons` → activate from the
  Installed tab → configure in `BotBlocker -> Tools` when settings exist.
- A normal active add-on `core` file is included **after** the main
  request-check cycle. Only a `runtime.pre_run` `traffic_decision_provider`
  participates **inside** the cycle. See `docs/botblocker-runtime-contract.md`.

## When to use

Building Add-on API v2 packages; editing `bbcs-addon.json`; implementing core
files, settings views, sanitizers, lifecycle callbacks, or feature providers;
packaging/validating add-on ZIPs; reviewing compatibility with `1.6.20+`;
preserving v1 compatibility when touching shared scanner/loader code.

## Do NOT use for

- General WordPress plugin work unrelated to BotBlocker add-ons.
- Editing BotBlocker core unless the user explicitly requests it.
- Add-ons that disable, weaken, or silently bypass BotBlocker protections.
- Add-ons that collect visitor personal data without a clear, documented purpose.
- Publishing credentials, private URLs, license keys, or production secrets.

## Pre-flight: mandatory discovery

Before writing any code, settle these. Ask the user when unknown:

slug · name · purpose · runtime surface (admin / frontend / request-headers /
cron / API / diagnostics) · whether BotBlocker request data is needed · whether
decisions can run **after** BotBlocker allows the request, or must run
**in-cycle** · minimum BotBlocker and PHP versions · function/class prefix ·
option name · settings fields · sanitizer behavior · lifecycle needs · feature
capability names · asset/icon paths · help text and links · admin/frontend test
steps.

## Reference map — read the doc, do not guess

| Task | Read |
| --- | --- |
| Scan/install/activate/load model, runtime dirs | `docs/botblocker-runtime-contract.md` |
| Manifest schema, fields, validity, packaging | `docs/addon-api-v2.md` |
| ZIP shape, upload flow, common upload errors | `docs/packaging-and-upload.md` |
| Lifecycle callbacks, `$addon`/`$context`, features, `BotBlockerAddons` API | `docs/lifecycle-and-features.md` |
| Settings save/read contract and option array | `docs/settings-contract.md` |
| Settings tab layout, field wrappers, markup | `docs/settings-ui-patterns.md` |
| Reading the live BotBlocker object safely | `docs/botblocker-core-object.md` |
| Available visitor/request/decision fields | `docs/botblocker-request-data.md` |
| Read-only core settings for decisions | `docs/botblocker-settings-reference.md` |
| Post-check redirects vs pre-run providers | `docs/traffic-and-redirect-addons.md` |
| In-cycle pre-run `traffic_decision_provider` contract | `docs/core-hook-integration.md` |
| Required security/quality bar | `docs/code-quality-standard.md` |
| Static / package / WordPress / asset / multisite tests | `docs/testing.md` |
| Kit/core/runtime compatibility comparison | `docs/compatibility-matrix.md` |
| Known core implementation gaps to verify | `docs/known-core-contract-gaps.md` |
| Public links, banners, icons, screenshots | `docs/links-and-assets.md` |

Reference packages: `examples/acme-botblocker-sample` (canonical normal add-on)
and `examples/acme-traffic-guard` (advanced pre-run traffic provider — use only
when in-cycle decisions are truly required).

## Fast path

1. Copy `examples/acme-botblocker-sample` to a folder named with the final slug.
2. Rename the folder, manifest `slug`, root PHP file, text domain, function
   prefix, option name, handles, CSS classes, and JS globals.
3. Define behavior in `inc/core.php`; admin controls in `inc/settings.php`.
4. Declare every field under `settings.option`; implement the manifest sanitizer.
5. For visitor/request data or traffic control, follow the mapped docs above.
6. Validate the folder, package the ZIP, validate the ZIP, then upload and test.

## Essential patterns (full detail in the mapped docs)

- **Manifest minimum** (`docs/addon-api-v2.md`): a valid package needs `slug`,
  `name`, `version`, `requires_core`, and an existing `core` file. Always also
  declare `schema: "2.0"`, `requires_php`, `description`, `main`, `assets.icon`,
  and `assets.readme`.
- **Settings field naming** (`docs/settings-contract.md`) — third-party v2 must
  use the option array; plain names only work for BotBlocker's built-in core logic:

  ```php
  <input type="hidden"   name="vendor_addon_settings[enabled]" value="0">
  <input type="checkbox" name="vendor_addon_settings[enabled]" value="1">
  ```

- **Asset URLs** (`docs/addon-api-v2.md`) — uploaded add-ons run outside the
  plugin source; never use `plugin_dir_url()`:

  ```php
  BotBlockerAddons::fileUrl( 'vendor-addon', 'assets/admin.js' );
  ```

- **Lifecycle signature** (`docs/lifecycle-and-features.md`): every callback is
  `function vendor_addon_event( array $addon, array $context, string $event, string $slug ): void`
  and must be idempotent. Use your own prefixed function names.

## Hard rules (non-negotiable guardrails)

- Prefix every symbol: function, class, option, action, filter, transient, cron
  hook, asset handle, JS global, and package-owned CSS class.
- Guard every PHP file with `ABSPATH`; pre-run files may also require `BOTBLOCKER`.
- Never echo from `inc/core.php` (or a pre-run file) during load.
- Register WordPress hooks only when the add-on is active and should run.
- Escape all output; sanitize every stored field in the declared sanitizer.
- Use activation callbacks for defaults and delete callbacks for owned cleanup.
- Never write into the BotBlocker plugin source directory; never assume the kit
  repository exists in production.
- Never read raw unsanitized `$_GET`/`$_POST`/`$_FILES`/`$_COOKIE`/`$_SERVER`.
- Keep remote calls explicit, documented, timeout-bound, and optional.
- Stay PHP 7.4 compatible (no `match`, enums, `readonly`, named args,
  `str_contains`, typed properties in add-on style that targets 7.4).

## Traffic add-ons: critical-risk gate

A traffic add-on can redirect, allow, bypass, block, or challenge real
production requests. Treat every decision as a security change.

- Prefer post-check WordPress hooks. Use the `runtime.pre_run`
  `traffic_decision_provider` contract only when in-cycle decisions are truly
  required (`docs/core-hook-integration.md`).
- Ship disabled by default, dry-run (`log_only`) first, staging-tested, with
  documented rollback.
- Skip BotBlocker check/block/denied pages, admin, AJAX, cron, REST, unsafe HTTP
  methods, payment callbacks, and verified legal bots by default.
- Use `wp_safe_redirect()`, loop protection, and same-site or allowlisted hosts.
- Do not use `allow`, `bypass`, `block`, or `captcha` decisions for marketing
  routing — those require an explicit security/integration rationale.

## Review checklist

- Root folder name equals manifest `slug`; ZIP archives the folder, not loose files.
- `bbcs-addon.json` is valid JSON; required fields present; `requires_core`/`requires_php` accurate.
- Declared `core`, `settings.view`, `assets.icon`, `assets.readme` paths exist.
- `description` explains purpose, runtime surface, and configurable behavior.
- Settings view opens with a BotBlocker help block; inputs use `{settings.option}[field]` names.
- Sanitizer normalizes every stored field; lifecycle callbacks are callable and idempotent.
- Assets use `BotBlockerAddons::fileUrl()` with unique handles; no `plugin_dir_url()`.
- No unprefixed globals; no raw superglobal use; no writes outside the owned data plan.
- Package passes `tools/validate-addon.php` (folder and ZIP).
- Package installs, activates, saves settings, deactivates, deletes, and reinstalls cleanly.

## Packaging and validation

From the kit root (see kit `README.md` for the full reference):

```powershell
php  .\tools\validate-addon.php .\examples\acme-botblocker-sample
.\tools\package-addon.ps1 -AddonPath .\examples\acme-botblocker-sample -DestinationPath .\dist\acme-botblocker-sample.zip
php  .\tools\validate-addon.php .\dist\acme-botblocker-sample.zip
```

Manual ZIP must archive the folder itself (one root folder):
`Compress-Archive -Path .\{slug} -DestinationPath .\{slug}.zip -Force`
(or `zip -r {slug}.zip {slug}`).

## Output format when creating an add-on

Return: file tree · manifest summary · runtime behavior summary · settings
summary · lifecycle summary · feature providers · asset paths · packaging
command · validation command · manual test steps · compatibility notes
(target BotBlocker version, PHP version, and WordPress version).
