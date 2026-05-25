# Known Core Contract Gaps

These are known mismatches between the public add-on kit and the current BotBlocker Security 1.6.20 codebase.

## Runtime static assets may be blocked

BotBlocker installs add-ons into a protected uploads directory and writes `.htaccess`/`web.config` files that deny direct web access.

At the same time, v2 docs and examples use `bbcs_addon_file_url()` for icons, JavaScript, CSS, and images under that runtime directory.

Impact:

- Add-on cards may not display icons.
- Frontend/admin scripts may enqueue but fail with 403.
- CSS/image assets may fail in some server configurations.

Required follow-up:

- Test asset URLs in a real WordPress install.
- If blocked, add a BotBlocker core safe asset delivery endpoint or adjust protection rules to allow declared read-only assets while protecting PHP/data files.

Kit status:

- The kit documents the risk.
- The validator checks that asset paths exist and that code uses the correct helper.
- HTTP status must be tested in WordPress because it depends on server configuration.

## Marketplace builder is not manifest-first

The local `bbcs-addons` manager parses root PHP plugin headers and root `{slug}.svg/png` icons from first-party source folders. It does not use the v2 manifest as the authoritative metadata source.

Impact:

- Marketplace metadata can diverge from `bbcs-addon.json`.
- v2 packages with icons under `assets/icon.svg` may not publish icons through the current builder.
- First-party source conventions can leak into third-party docs.

Required follow-up:

- Update the marketplace builder to prefer `bbcs-addon.json`.
- Use manifest `assets.icon`, `version`, `requires_core`, `description`, and package `slug`.
- Keep root PHP headers only as a fallback.

Kit status:

- The kit documents marketplace publishing as separate from third-party ZIP upload.

## First-party settings patterns are mixed

Some first-party add-ons save settings through BotBlocker core hardcoded option groups and plain field names. New third-party v2 add-ons should not copy that pattern.

Impact:

- An AI or developer copying first-party settings fields may create a settings UI that renders but never saves through the generic v2 settings flow.

Required follow-up:

- Keep docs explicit that third-party settings must use `settings.option[field]`.
- Optionally migrate first-party settings views to the v2 option-array shape where compatible.

Kit status:

- The kit sample uses the correct v2 option-array pattern.

## First-party source folder is not runtime

The bundled add-ons live under `plugin/wp-content/plugins/botblocker-security/addons`, but BotBlocker runtime scanning uses `bbcs_addons_dir()` in protected uploads.

Impact:

- Editing/copying into the plugin source add-ons folder does not prove third-party runtime behavior.
- Tests must install a ZIP into runtime.

Required follow-up:

- Keep source/runtime distinction visible in README and runtime docs.

Kit status:

- Documented in `docs/botblocker-runtime-contract.md`.

## Generic v2 add-ons are not in-cycle traffic providers

BotBlocker loads normal active v2 add-on core files after `$botBlocker->initialize()` has already run the main request-check cycle. Only add-ons that explicitly opt into `runtime.pre_run` with `traffic_decision_provider` are loaded before the cycle.

Impact:

- A normal v2 add-on can read the final `BotBlocker::getInstance()` state from later WordPress hooks.
- A normal v2 add-on can redirect or tag requests that BotBlocker already allowed.
- In full secure mode, blocked/challenged requests may stop before normal v2 add-ons are included.
- A normal v2 add-on cannot reliably make allow/block/captcha/redirect/bypass decisions before BotBlocker core challenges or blocks a request.
- Traffic-management add-ons that need to participate inside the core decision pipeline must use the `runtime.pre_run` manifest contract.
- Pre-run traffic providers are critical-risk add-ons. They should be disabled by default, support dry-run, and be tested on staging before production traffic is affected.

Required follow-up:

- Keep the distinction between normal late-loaded add-ons and pre-run traffic providers explicit.
- Keep provider decisions routed through BotBlocker core validation instead of letting add-ons mutate BotBlocker properties directly.

Kit status:

- Post-check redirect patterns are documented in `docs/traffic-and-redirect-addons.md`.
- Pre-run traffic provider hooks are documented in `docs/core-hook-integration.md`.
- The reference pre-run traffic provider package is `examples/acme-traffic-guard`.
- Available object fields are documented in `docs/botblocker-core-object.md`, `docs/botblocker-request-data.md`, and `docs/botblocker-settings-reference.md`.
