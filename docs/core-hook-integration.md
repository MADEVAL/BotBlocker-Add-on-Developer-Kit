# Core Hook Integration For Deep Traffic Add-ons

This document defines the core-side contract for add-ons that must participate inside the BotBlocker request-check cycle.

## Critical warning

The pre-run traffic decision provider contract is a critical security and operations surface. A provider runs inside BotBlocker's request decision path and can return `allow`, `bypass`, `block`, `captcha`, `redirect`, or `log_only`. Incorrect code can lock out visitors, bypass security policy, break payment callbacks, create redirect loops, or stop BotBlocker from challenging suspicious requests.

Use this contract only for add-ons that cannot be implemented safely with normal post-check WordPress hooks. Keep pre-run files small, deterministic, auditable, and free of output. Traffic add-ons should be disabled by default, provide dry-run logging first, and include explicit rollback instructions.

BotBlocker exposes several add-on hooks:

- lifecycle actions around add-on install/activate/load/delete
- feature provider helpers
- security header dispatch hooks

Normal v2 add-ons are loaded after `$botBlocker->initialize()`, so they cannot register callbacks before the main cycle starts. The exception is the explicit pre-run traffic decision provider contract below.

See `examples/acme-traffic-guard` for a complete reference package that uses this contract conservatively.

## Pre-run load order

BotBlocker does not load every active add-on before the engine starts. It loads only active, valid, compatible v2 add-ons that explicitly opt into pre-run traffic decisions.

Implemented shape:

```php
BotBlockerInstall::checkInstall();
$plugin = new Cyber_Secure_Botblocker();
BotBlockerAddons::includePreRunAddons();
$plugin->run();

BotBlockerAddons::includeAll();
```

This happens after `BotBlockerInstall::checkInstall()` and after `new Cyber_Secure_Botblocker()` has loaded BotBlocker classes, but before `$plugin->run()` calls `BotBlocker::initialize()`.

Compatibility requirement:

- Pre-run files must be separate, small files such as `inc/pre-run.php`.
- Normal `core` files still load late through `BotBlockerAddons::includeAll()`.
- Pre-run files must not echo during load.
- Pre-run files must register a provider and return.
- Pre-run files must not perform remote calls, long scans, package updates, migrations, or expensive diagnostics.
- Pre-run providers must fail safely: return `null` or `log_only` when required data is missing.
- Lifecycle `load` and `health_check` callbacks remain late.

Manifest:

```json
{
  "features": [
    "traffic_decision_provider"
  ],
  "runtime": {
    "pre_run": {
      "enabled": true,
      "file": "inc/pre-run.php",
      "contract": "traffic_decision_provider",
      "ready_constant": "VENDOR_TRAFFIC_PRE_RUN_READY",
      "register": "vendor_traffic_pre_run_register"
    }
  }
}
```

BotBlocker refuses pre-run registration unless all are true:

- add-on is active, valid, compatible, and v2;
- `features` contains `traffic_decision_provider`;
- `runtime.pre_run.enabled` is true;
- `runtime.pre_run.file` exists inside the package;
- `runtime.pre_run.contract` is `traffic_decision_provider`;
- `runtime.pre_run.register` is callable after the file is included;
- the declared `ready_constant` is truthy or the declared `ready_callback` returns true.

## Recommended hook points

BotBlocker exposes these stages to registered traffic decision providers.

| Stage | When it runs |
| --- | --- |
| `before_prefly_checks` | At the start of `BotBlocker::run()`, before preflight checks. |
| `after_request_data` | After server request fields are read and normalized, before proxy/IP info and country blocking. |
| `after_visitor_data` | After full visitor data collection and visitor-based setting updates. |
| `pre_core_rules` | After payment bypass and safe request checks, before white bot/IP/ASN/database/path rules. |
| `post_core_rules` | After core rule checks, before request mode, headers, cookies, and simple bot checks. |
| `before_final_allow` | Before BotBlocker finishes a normally allowed request. |

## Decision array contract

Provider callbacks should return `null` to do nothing, or an array with this shape:

```php
array(
    'action' => 'redirect',
    'url'    => 'https://example.com/de/',
    'status' => 302,
    'reason' => 'Traffic manager: country route',
    'source' => 'vendor-traffic',
)
```

Supported actions and exact core behavior:

| Action | Typical fields | Core behavior (from `BotBlockerAddonDecisionTrait::execute_addon_traffic_decision()`) |
| --- | --- | --- |
| `allow` | `reason` optional | Sets visitor type to human, records `reason`, fires `bbcs_botblocker_allowed_request`, stops BotBlocker checks for this request. |
| `bypass` | `reason` optional | Identical to `allow`. It is a semantic alias for an integration bypass. |
| `block` | `code`, `reason` | Calls `redirect_to_denied( $code, $reason )` and stops. |
| `captcha` | `reason` | If the visitor already holds a valid verification cookie (`is_verified()`), the challenge is skipped and BotBlocker continues normally. Otherwise calls `redirect_to_dark( $reason )` (a DARK challenge) and stops. |
| `redirect` | `url`, `status` | If `url` is empty or headers were already sent, the decision is abandoned and BotBlocker continues normally. Otherwise fires `bbcs_botblocker_redirected_request`, calls `wp_safe_redirect( $url, $status )`, and `exit`s. |
| `log_only` | `code`, `reason` | Calls `BotBlockerStore::storeData( $reason, $code )` when available, fires `bbcs_botblocker_log_only_request`, then returns control so BotBlocker continues normal checks. It never stops the request. |

Use `log_only` for dry-run and rollout verification. Use `redirect` only after loop protection and safe-target validation are in place. Use `allow`, `bypass`, `block`, and `captcha` only for security policy or integration requirements that have been explicitly reviewed.

### Field normalization

Core normalizes every returned decision through `normalize_addon_traffic_decision()` before it is executed:

- `action` is lowercased with `sanitize_key()` and must be one of `allow`, `bypass`, `block`, `captcha`, `redirect`, `log_only`. Any other value (or a missing/empty `action`) makes the whole decision void, and core moves on to the next provider.
- `status` accepts only `301`, `302`, `303`, `307`, `308`. Any other value is coerced to `302`. The default is `302`.
- `source` is passed through `sanitize_key()`. When empty it falls back to the provider slug, then to the literal `addon`.
- `reason` is passed through `sanitize_text_field()`. When empty at execution time it becomes `BotBlocker add-on traffic decision`.
- `code` is cast to an integer. The default is `901`. It is used as the BotBlocker reason code for `block` and `log_only`.
- `url` is passed through `esc_url_raw()` and validated again by `wp_safe_redirect()`.
- A `stage` key is injected by core; unknown fields you add are ignored.

### Provider order and short-circuit

- Providers run in ascending `priority` order (range `-9999`..`9999`, default `10`); ties are broken by slug, alphabetically.
- The first provider that returns a decision which actually stops the request (`allow`, `bypass`, `block`, a fired `redirect`, or an unverified `captcha`) wins. Core stops calling further providers and further stages for that request.
- A `log_only` decision, a voided decision, or a `null` return does not stop iteration, so later providers (and the `bbcs_botblocker_traffic_decision` filter) still run.
- If a provider callback throws, core catches it, fires `bbcs_botblocker_traffic_decision_error`, and continues with the next provider. A throwing provider never blocks traffic by accident.

## Example pre-run provider

```php
define( 'VENDOR_TRAFFIC_PRE_RUN_READY', true );

function vendor_traffic_pre_run_register( array $addon, array $context, string $event, string $slug ): void {
    BotBlockerAddons::registerTrafficDecisionProvider( $slug, 'vendor_traffic_decision', 10 );
}

function vendor_traffic_decision( BotBlocker $bbcs, string $stage, array $provider ): ?array {
    if ( 'pre_core_rules' !== $stage ) {
        return null;
    }

    $settings = vendor_traffic_settings();
    if ( empty( $settings['enabled'] ) ) {
        return null;
    }

    $country = isset( $bbcs->country ) ? strtoupper( (string) $bbcs->country ) : '';
    $uri     = isset( $bbcs->uri ) ? (string) $bbcs->uri : '';

    if ( 'DE' === $country && 0 === strpos( $uri, '/pricing' ) ) {
        return array(
            'action' => 'redirect',
            'url'    => home_url( '/de/preise/' ),
            'status' => 302,
            'reason' => 'Traffic redirect: DE pricing',
            'source' => $provider['slug'],
        );
    }

    return null;
}
```

## Core helper: traffic decision execution

BotBlocker core should own decision execution. Add-ons should return a decision, not directly call internal response methods from arbitrary hooks.

The implemented core entry point is `apply_addon_traffic_decisions( $stage )`. It calls registered providers in priority order, normalizes the first non-null decision, fires `bbcs_botblocker_decision_selected`, and executes only allowlisted actions.

Add-ons register through:

```php
BotBlockerAddons::registerTrafficDecisionProvider( 'vendor-traffic', 'vendor_traffic_decision', 20 );
```

Provider callback signature:

```php
function vendor_traffic_decision( BotBlocker $bbcs, string $stage, array $provider ): ?array {
    return null;
}
```

Relevant actions:

- `bbcs_botblocker_before_traffic_decision`
- `bbcs_botblocker_decision_selected`
- `bbcs_botblocker_allowed_request`
- `bbcs_botblocker_blocked_request`
- `bbcs_botblocker_redirected_request`
- `bbcs_botblocker_log_only_request`
- `bbcs_botblocker_traffic_decision_error`

## Recommended order for traffic decisions

For security, payment and self/admin safety should usually win over marketing routes.

Suggested order:

1. preflight checks
2. raw request data and optional `after_request_data` decision
3. full visitor data and optional `after_visitor_data` decision
4. payment bypass
5. safe request/admin/self checks
6. optional `pre_core_rules` decision
7. core white bot/IP/ASN/database/path rules
8. optional `post_core_rules` decision
9. cookies/simple bot/referrer/hosting/language checks
10. optional `before_final_allow` decision

If an add-on needs earlier priority than core blocks, it must be explicitly documented as a security policy add-on, not a marketing redirect add-on.

## Early-init warning

Early-init protection can run before most WordPress plugins and theme code. A normal v2 add-on must not assume it can participate in early-init traffic decisions.

An early-init traffic provider needs a separate provider contract:

- no normal WP hooks
- no admin APIs
- no remote calls by default
- no dependency on plugin/theme load order
- file-based or generated bootstrap configuration
- strict fail-closed/fail-open policy documented in settings

Do not market a normal v2 add-on as early-init capable unless it implements and tests that provider path.
