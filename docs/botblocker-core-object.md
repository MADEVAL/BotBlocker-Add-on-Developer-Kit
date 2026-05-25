# BotBlocker Core Object Access

Use this document when an add-on needs data from the live BotBlocker request engine.

BotBlocker exposes its request state through the `BotBlocker` singleton. The object is public in PHP, but it is still the security engine's internal state. Add-ons should read from it, build their own normalized context arrays, and avoid mutating BotBlocker properties unless a documented core hook explicitly allows a decision.

## Access pattern

Use the singleton only after the BotBlocker class exists:

```php
function vendor_addon_botblocker(): ?BotBlocker {
    if ( ! class_exists( 'BotBlocker' ) ) {
        return null;
    }

    return BotBlocker::getInstance();
}
```

Build a narrow snapshot instead of passing the whole object through unrelated add-on code:

```php
function vendor_addon_request_context(): array {
    $bbcs = vendor_addon_botblocker();
    if ( ! $bbcs ) {
        return array();
    }

    return array(
        'cid'          => isset( $bbcs->cid ) ? (string) $bbcs->cid : '',
        'ip'           => isset( $bbcs->ip ) ? (string) $bbcs->ip : '',
        'ip_version'   => isset( $bbcs->ip_version ) ? (int) $bbcs->ip_version : 0,
        'country'      => isset( $bbcs->country ) ? (string) $bbcs->country : '',
        'asnum'        => isset( $bbcs->asnum ) ? (string) $bbcs->asnum : '',
        'uri'          => isset( $bbcs->uri ) ? (string) $bbcs->uri : '',
        'method'       => isset( $bbcs->request_method ) ? (string) $bbcs->request_method : '',
        'visitor_type' => isset( $bbcs->visitorType ) ? (int) $bbcs->visitorType : 0,
        'result'       => isset( $bbcs->result_of_action ) ? (string) $bbcs->result_of_action : '',
    );
}
```

For diagnostics, BotBlocker has:

```php
$snapshot = BotBlocker::getInstance()->get_bot_blocker_hive();
```

Do not expose the full hive to public visitors. It can contain operational details, settings, salts, API metadata, and visitor request data.

## Current v2 timing

In BotBlocker Security `1.6.20`, `bbcs_include_active_addons()` runs after `$botBlocker->initialize()` in the normal `plugins_loaded` boot flow.

Practical meaning:

- A normal v2 add-on can read the final BotBlocker object from later WordPress hooks.
- It can add headers, admin behavior, frontend behavior, logging, redirects for requests that BotBlocker already allowed, and settings screens.
- In full secure mode, a blocked/challenged request may terminate before active v2 add-ons are included, so normal add-ons may never see that request.
- It cannot reliably register a callback that runs inside `BotBlocker::run()` before core checks unless it uses the explicit `runtime.pre_run` traffic provider contract.
- For true in-cycle traffic management, use the contract in `core-hook-integration.md`.

## Visitor type constants

BotBlocker visitor classification values:

| Constant | Value | Meaning |
| --- | ---: | --- |
| `BotBlockerBase::VISITOR_UNDEFINED` | `0` | No final classification yet. |
| `BotBlockerBase::VISITOR_BOTBLOCKER` | `1` | BotBlocker server/internal service request. |
| `BotBlockerBase::VISITOR_HUMAN` | `2` | Allowed human or verified visitor. |
| `BotBlockerBase::VISITOR_LEGALBOT` | `3` | Verified legal bot or explicitly allowed service. |
| `BotBlockerBase::VISITOR_ADMIN` | `4` | Logged-in administrator/editor/moderator request. |
| `BotBlockerBase::VISITOR_FAKEBOT` | `5` | Fake bot classification. |
| `BotBlockerBase::VISITOR_SECRET` | `6` | Request matched BotBlocker secret parameter flow. |

Safe helper:

```php
function vendor_addon_visitor_type_label( int $type ): string {
    switch ( $type ) {
        case BotBlockerBase::VISITOR_HUMAN:
            return 'human';
        case BotBlockerBase::VISITOR_LEGALBOT:
            return 'legalbot';
        case BotBlockerBase::VISITOR_ADMIN:
            return 'admin';
        case BotBlockerBase::VISITOR_BOTBLOCKER:
            return 'botblocker';
        case BotBlockerBase::VISITOR_FAKEBOT:
            return 'fakebot';
        case BotBlockerBase::VISITOR_SECRET:
            return 'secret';
        default:
            return 'undefined';
    }
}
```

## Security page flags

These flags tell whether BotBlocker selected a frontend-mode security page:

| Property | Meaning |
| --- | --- |
| `$bbcs->should_show_check_page` | BotBlocker will show the browser/CAPTCHA check page on `template_redirect`. |
| `$bbcs->should_show_block_page` | BotBlocker will show the temporary block page on `template_redirect`. |
| `$bbcs->should_show_denied_page` | BotBlocker will show the denied page on `template_redirect`. |

A redirect add-on must not override these pages:

```php
function vendor_addon_botblocker_has_security_page( BotBlocker $bbcs ): bool {
    return ! empty( $bbcs->should_show_check_page )
        || ! empty( $bbcs->should_show_block_page )
        || ! empty( $bbcs->should_show_denied_page );
}
```

## Main request cycle

Current `BotBlocker::initialize()` flow:

1. `start_bbcs()`
2. `check_admin_status()`
3. `load_directories()`
4. `generate_missing_files()`
5. `load_data()`
6. `initialize_config()`
7. `load_settings()`
8. `generate_connection_id()`
9. `check_secret_parameter()`
10. `process_disabled_state()`
11. `run()`

Current `BotBlocker::run()` flow:

1. `perform_prefly_checks()`
2. `collect_visitor_data()`
3. `update_settings_based_on_visitor_data()`
4. `check_payment_bypass()`
5. `is_safe_request()`
6. `check_white_bot()`
7. `check_ip_rules()`
8. `check_rugov_rules()`
9. `check_asn_rules()`
10. `check_rules_database()`
11. `check_path_rules()`
12. `select_request_mode()`
13. `process_headers()`
14. `process_cookies()`
15. `perform_simple_bot_checks()`
16. `validate_referer()`
17. `check_referer_get_params()`
18. `check_hosting()`
19. `check_language_mismatch()`
20. optional force check
21. `set_x_robot_headers()`

The most useful read points are:

| Point | Available data |
| --- | --- |
| After `load_settings()` | settings, directories, rule arrays, connection id. |
| After `collect_visitor_data()` | request, IP, user agent, language, referrer, country, ASN, browser/device data. |
| After rule checks | decision state such as `visitorType`, `result_of_action`, `suspect_status`, page flags. |
| After `process_cookies()` | UID, BotBlocker cookies, hit counter, analytics cookie IDs. |
| Later WP hooks after normal v2 loading | final state for allowed requests and pending frontend-mode security pages. |

## Safe read-only example

This example logs a compact context after BotBlocker allowed WordPress to continue.

```php
function vendor_addon_log_allowed_context(): void {
    $bbcs = vendor_addon_botblocker();
    if ( ! $bbcs || vendor_addon_botblocker_has_security_page( $bbcs ) ) {
        return;
    }

    $context = vendor_addon_request_context();
    error_log( 'Vendor add-on context: ' . wp_json_encode( $context ) );
}
add_action( 'template_redirect', 'vendor_addon_log_allowed_context', 20 );
```

Use a package-owned log table or option for production diagnostics. Avoid `error_log()` in shipping add-ons unless explicitly controlled by a setting.

## Mutation rules

Read safely:

- scalar request properties
- final decision properties
- `$bbcs->settings` values
- `get_bot_blocker_hive()` for admin-only diagnostics

Do not mutate directly:

- `$bbcs->ip`, `$bbcs->country`, `$bbcs->visitorType`
- `$bbcs->settings`
- `$bbcs->bbcs_rule`, `$bbcs->bbcs_asn`, `$bbcs->bbcs_path`
- cookies or response flags

Use documented WordPress APIs, package-owned options, and future BotBlocker hook decisions instead.
