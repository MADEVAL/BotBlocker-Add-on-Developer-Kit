# Testing

Use this checklist before shipping a BotBlocker add-on.

## Static checks

From the developer kit root:

```powershell
php .\tools\validate-addon.php .\examples\acme-botblocker-sample
php .\tools\validate-addon.php .\examples\acme-traffic-guard
```

Run PHP lint:

```powershell
Get-ChildItem .\examples\acme-botblocker-sample -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
Get-ChildItem .\examples\acme-traffic-guard -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Expected:

- manifest is valid JSON
- root folder matches slug
- required paths exist
- PHP files have no syntax errors
- settings fields use `settings.option[field]`
- assets use `BotBlockerAddons::fileUrl()`
- no `plugin_dir_url()` usage in add-on code

## Package check

Create a ZIP:

```powershell
.\tools\package-addon.ps1 -AddonPath .\examples\acme-botblocker-sample -DestinationPath .\dist\acme-botblocker-sample.zip
.\tools\package-addon.ps1 -AddonPath .\examples\acme-traffic-guard -DestinationPath .\dist\acme-traffic-guard.zip
```

Validate the ZIP:

```powershell
php .\tools\validate-addon.php .\dist\acme-botblocker-sample.zip
```

Expected:

- exactly one root folder
- root folder is the add-on slug
- no unsafe paths
- manifest and core file exist inside the root folder

## WordPress manual test

1. Install/activate BotBlocker Security `1.6.20+`.
2. Open `BotBlocker -> Add-ons`.
3. Click `Upload ZIP`.
4. Upload the add-on ZIP.
5. Confirm it appears in the Installed tab.
6. Confirm card metadata: name, version, description, icon, compatibility.
7. Activate the add-on.
8. Open `BotBlocker -> Tools`.
9. Confirm the add-on tab appears.
10. Save settings.
11. Reload Tools and confirm settings persist.
12. Confirm frontend/admin runtime behavior works.
13. Deactivate the add-on.
14. Confirm runtime behavior stops.
15. Reactivate the add-on.
16. Confirm settings are preserved unless delete behavior says otherwise.
17. Delete the add-on.
18. Reinstall the same ZIP.

## Asset delivery test

For every declared icon, JS, CSS, or image URL returned by `BotBlockerAddons::fileUrl()`:

1. Open the URL in a browser while logged in and logged out.
2. Confirm the response is HTTP 200.
3. Confirm the MIME type is usable by the browser.
4. Confirm the browser console has no 403/404 asset errors.

If runtime static assets are blocked by server protection files, either use inline output where practical or fix BotBlocker core asset delivery for safe declared add-on assets.

## BotBlocker data access test

For add-ons that read `BotBlocker::getInstance()`:

- Visit a normal frontend page and confirm the add-on can read `cid`, `ip`, `uri`, `request_method`, `country`, `visitorType`, and `result_of_action` without PHP notices.
- Test an admin page and confirm admin requests are skipped or handled intentionally.
- Test a BotBlocker check/block/denied flow and confirm the add-on does not expose hive data or override security pages.
- Test with missing/empty optional fields such as country, ASN, referrer, browser, OS, and device.
- Confirm salts, API credentials, raw cookies, and full hive data are never printed to frontend output or public logs.

## Traffic and redirect test

For traffic managers and redirect add-ons:

- Treat the add-on as critical production traffic-control code.
- Confirm the add-on is disabled by default after install/activate.
- Enable dry-run mode and confirm matching rules are logged without redirecting.
- Keep dry-run evidence before enabling real redirects in production.
- Confirm redirects run only for `GET` and `HEAD` by default.
- Confirm `POST`, AJAX, cron, REST write requests, login, admin, and payment callbacks are not redirected by default.
- Confirm verified legal bots and BotBlocker security pages are not redirected.
- Confirm every target uses `wp_safe_redirect()` and passes same-site or allowed-host validation.
- Confirm loop protection by testing a rule whose target equals the current URL.
- Confirm country, ASN, path, referrer, and device rules behave correctly when fields are empty or `BOTBLOCKER_EMPTY`.
- Confirm the add-on documents when it needs the core hook contract from `core-hook-integration.md`.
- Confirm there is a documented rollback path: deactivate the add-on, disable the rule, or restore dry-run.

## Lifecycle test

If the add-on declares lifecycle callbacks, test:

- install
- activate twice
- deactivate twice
- delete
- update over an active install
- update rollback behavior if the update ZIP is invalid
- incompatible `requires_core` handling

Callbacks must not fatal when repeated.

## Multisite test

When supporting multisite:

- test normal site admin activation
- test network-active BotBlocker
- verify capability checks use `current_user_can( 'manage_options' )` or an equivalent
- verify options are intentionally site-level or network-level
- verify runtime paths resolve on the correct site

## Security test

Review:

- custom admin actions have capability and nonce checks
- all output is escaped
- all settings are sanitized
- no secrets are logged or rendered
- no raw request values are used directly
- remote calls have timeouts
- destructive behavior is explicit and reversible
