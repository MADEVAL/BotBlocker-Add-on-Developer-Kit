# Code Quality Standard

This is the minimum quality bar for BotBlocker third-party add-ons.

## Global namespace

Prefix every global symbol:

- functions
- classes
- traits
- interfaces
- constants
- options
- transients
- cron hooks
- actions
- filters
- script/style handles
- JavaScript globals
- CSS class names where practical

Example prefix:

```text
vendor_bbcs_addon_
```

Do not create generic functions such as `activate()`, `settings()`, `render()`, or `enqueue_assets()`.

## Direct access guard

Every PHP file must prevent direct access:

```php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

Runtime core files that require BotBlocker may use:

```php
if ( ! defined( 'ABSPATH' ) || ! defined( 'BOTBLOCKER' ) ) {
    exit;
}
```

## Core file behavior

`core.php` must not echo output during include. It may:

- define defaults
- define sanitizers
- define lifecycle callbacks
- register WordPress hooks
- enqueue assets conditionally
- render frontend output only through an explicit hook callback

## Input

Never use raw superglobals directly in business logic.

Use WordPress helpers:

- `wp_unslash()`
- `sanitize_text_field()`
- `sanitize_textarea_field()`
- `sanitize_key()`
- `esc_url_raw()`
- `absint()`
- `wp_validate_boolean()` when appropriate

For custom admin actions, require:

- `current_user_can( 'manage_options' )` or equivalent capability check
- nonce creation and verification
- safe redirect with `wp_safe_redirect()`

## Output

Escape every output value:

- `esc_html()` / `esc_html_e()`
- `esc_attr()` / `esc_attr_e()`
- `esc_url()`
- `esc_textarea()`
- `wp_kses_post()` for intentionally allowed rich text

Do not print unsanitized settings, request values, remote API responses, or file contents.

## Data ownership

Third-party add-ons may own:

- their own options
- their own transients
- their own custom tables when documented
- their own files under a documented runtime data path

Third-party add-ons must not:

- write into BotBlocker plugin source
- delete BotBlocker core options
- modify `wp-config.php` unless the add-on is explicitly designed and documented for that high-risk behavior
- silently disable BotBlocker checks
- silently weaken firewall, CAPTCHA, logging, or request filtering behavior

## Traffic-control add-ons

Traffic add-ons that redirect, allow, bypass, block, or challenge requests are critical-risk code.

Required quality bar:

- disabled by default
- dry-run or log-only mode enabled by default
- same-site redirect targets by default
- loop protection
- safe HTTP method guard
- admin, AJAX, cron, REST, login, payment callback, and BotBlocker security-page guards
- verified legal bot handling documented
- rollback steps documented
- staging test evidence before production activation

Do not use `allow`, `bypass`, `block`, or `captcha` decisions for ordinary marketing routing. Those actions require a documented security or integration reason.

## Remote calls

Remote calls must be:

- necessary for the add-on purpose
- documented in the readme/settings help
- optional when reasonable
- bounded by a timeout
- error handled
- never used to transmit secrets unless explicitly configured by the admin

## Assets

Uploaded add-ons must use `BotBlockerAddons::fileUrl()` for package assets.

Admin assets:

- enqueue only on BotBlocker admin screens or the exact screen that needs them
- use unique handles
- localize only sanitized data

Frontend assets:

- enqueue only when the feature is enabled and needed
- avoid global CSS bleed
- keep JS resilient when DOM nodes are missing

## Lifecycle

Lifecycle callbacks must be idempotent.

- Activation creates missing defaults, but does not overwrite admin choices.
- Deactivation removes temporary runtime effects, not stored configuration unless documented.
- Delete removes package-owned options/data only when intended.
- Update migrates data safely and can be retried.

## Compatibility

Keep metadata accurate:

- `requires_core` only increases when the add-on uses a newer BotBlocker API/behavior.
- `requires_php` is the lowest PHP version tested and supported.
- `version` follows semantic versioning.

## Size and auditability

Keep packages small and inspectable.

Avoid:

- bundled unrelated libraries
- minified code without source or explanation
- generated build artifacts that are not needed at runtime
- large binary assets

BotBlocker rejects ZIP packages larger than 20 MB and entries larger than 5 MB when `ZipArchive` is available.
