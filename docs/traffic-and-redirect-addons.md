# Traffic And Redirect Add-ons

This document describes how to build add-ons such as traffic managers, redirect managers, geo routers, campaign routers, and request diagnostics using BotBlocker data.

## Critical risk warning

Traffic and redirect add-ons are not ordinary UI extensions. They can change the fate of real production requests. A mistake can redirect paying customers, break checkout or payment gateway callbacks, block support/admin workflows, hide BotBlocker challenge/block/denied pages, create redirect loops, poison analytics, or silently weaken the security policy.

Prefer post-check WordPress hooks when possible. Use the pre-run `traffic_decision_provider` contract only when the add-on truly must decide inside the BotBlocker request cycle. Any add-on using that contract should ship disabled by default, include a dry-run mode, log matched rules without storing raw IP addresses, document rollback steps, and require staging tests before production activation.

Read first:

- `botblocker-core-object.md`
- `botblocker-request-data.md`
- `botblocker-settings-reference.md`
- `core-hook-integration.md`

## Choose the integration level

There are two different integration levels.

| Level | Works in current v2 add-ons | Use for |
| --- | --- | --- |
| Post-check WordPress hooks | Yes | Redirecting or tagging requests that BotBlocker already allowed to continue. |
| In-cycle BotBlocker decisions | Yes, only with explicit pre-run opt-in | Allow/block/captcha/redirect/bypass/log-only decisions before BotBlocker final response. |

Normal active v2 add-ons are loaded after the main BotBlocker `initialize()` call. That means a normal v2 add-on cannot intercept the main check cycle early. It can still read the final object and act on allowed requests during later WordPress hooks.

Traffic add-ons that need to run inside the main cycle must use the stricter `traffic_decision_provider` pre-run contract in `core-hook-integration.md`.

The kit includes `examples/acme-traffic-guard` as the reference traffic add-on. It is intentionally conservative: disabled by default, dry-run by default, same-site target paths only, GET/HEAD only, and guarded against admin, AJAX, cron, REST, payment bypasses, BotBlocker security pages, unsafe methods, and verified legal bots by default.

## Safe current pattern: redirect only allowed requests

Use `template_redirect` after BotBlocker's own security pages have had a chance to run. BotBlocker renders frontend-mode security pages at priorities `1`, `2`, and `3`; those callbacks exit when a security page is selected.

Recommended:

```php
function vendor_traffic_template_redirect(): void {
    $settings = vendor_traffic_settings();
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    if ( ! class_exists( 'BotBlocker' ) || ! class_exists( 'BotBlockerBase' ) ) {
        return;
    }

    $bbcs = BotBlocker::getInstance();

    if (
        ! empty( $bbcs->should_show_check_page )
        || ! empty( $bbcs->should_show_block_page )
        || ! empty( $bbcs->should_show_denied_page )
    ) {
        return;
    }

    $visitor_type = isset( $bbcs->visitorType ) ? (int) $bbcs->visitorType : BotBlockerBase::VISITOR_UNDEFINED;
    if ( ! in_array( $visitor_type, array( BotBlockerBase::VISITOR_HUMAN, BotBlockerBase::VISITOR_LEGALBOT, BotBlockerBase::VISITOR_ADMIN ), true ) ) {
        return;
    }

    $target = vendor_traffic_match_redirect( $bbcs, $settings );
    if ( '' === $target ) {
        return;
    }

    wp_safe_redirect( $target, 302, 'Vendor Traffic Add-on' );
    exit;
}
add_action( 'template_redirect', 'vendor_traffic_template_redirect', 20 );
```

Why priority `20`: if BotBlocker selected a check/block/denied page in frontend secure mode, its render callbacks run first and exit. Your redirect then applies only to normal allowed page requests.

## Match example: country plus path

```php
function vendor_traffic_match_redirect( BotBlocker $bbcs, array $settings ): string {
    $country = isset( $bbcs->country ) ? strtoupper( (string) $bbcs->country ) : '';
    $uri     = isset( $bbcs->uri ) ? (string) $bbcs->uri : '';

    if ( 'DE' === $country && 0 === strpos( $uri, '/pricing' ) ) {
        return home_url( '/de/preise/' );
    }

    if ( 'US' === $country && 0 === strpos( $uri, '/special-offer' ) ) {
        return home_url( '/us/special-offer/' );
    }

    return '';
}
```

Avoid redirect loops:

```php
function vendor_traffic_same_url( string $target ): bool {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
    $current = home_url( $uri );
    return untrailingslashit( $current ) === untrailingslashit( $target );
}
```

## Match example: ASN/data center handling

```php
function vendor_traffic_should_route_to_static_page( BotBlocker $bbcs ): bool {
    $asnum = isset( $bbcs->asnum ) ? preg_replace( '/[^0-9]/', '', (string) $bbcs->asnum ) : '';

    if ( in_array( $asnum, array( '15169', '8075', '16509' ), true ) ) {
        return true;
    }

    return isset( $bbcs->hosting ) && in_array( $bbcs->hosting, array( 1, '1' ), true );
}
```

Do not block verified legal bots or payment callbacks with marketing redirects.

```php
function vendor_traffic_must_not_redirect( BotBlocker $bbcs ): bool {
    if ( ! empty( $bbcs->payment_bypass_reason ) ) {
        return true;
    }

    $type = isset( $bbcs->visitorType ) ? (int) $bbcs->visitorType : 0;
    return in_array(
        $type,
        array( BotBlockerBase::VISITOR_LEGALBOT, BotBlockerBase::VISITOR_ADMIN, BotBlockerBase::VISITOR_BOTBLOCKER ),
        true
    );
}
```

## Match example: campaign/referrer routing

```php
function vendor_traffic_referrer_bucket( BotBlocker $bbcs ): string {
    $refhost = isset( $bbcs->refhost ) ? strtolower( (string) $bbcs->refhost ) : '';

    if ( false !== strpos( $refhost, 'google.' ) ) {
        return 'search';
    }

    if ( false !== strpos( $refhost, 'facebook.' ) || false !== strpos( $refhost, 'instagram.' ) ) {
        return 'social';
    }

    return 'direct_or_other';
}
```

## Data to use for routing

Good routing inputs:

- `$bbcs->uri`
- `$bbcs->request_method`
- `$bbcs->country`
- `$bbcs->lang`
- `$bbcs->asnum`
- `$bbcs->asname`
- `$bbcs->hosting`
- `$bbcs->device`
- `$bbcs->browser`
- `$bbcs->refhost`
- `$bbcs->visitorType`
- `$bbcs->payment_bypass_reason`

Use with caution:

- `$bbcs->useragent`: easy to spoof.
- `$bbcs->referer`: optional and easy to spoof.
- analytics cookie ids: privacy-sensitive.
- browser verification `post_*` fields: only available during verification POST flows.

Never use:

- BotBlocker salts or API credentials.
- raw unsanitized `$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`.
- direct mutation of BotBlocker rule arrays.

## Settings design for traffic add-ons

Recommended manifest feature:

```json
"features": [
  "traffic_manager"
]
```

Recommended settings fields:

```text
enabled
dry_run
log_matches
preserve_query
redirect_status
default_target
rules
```

Rule shape:

```php
array(
    'enabled'       => 1,
    'name'          => 'DE pricing route',
    'countries'     => array( 'DE', 'AT', 'CH' ),
    'path_prefix'   => '/pricing',
    'visitor_types' => array( 'human' ),
    'target_url'    => home_url( '/de/preise/' ),
    'status'        => 302,
)
```

Sanitize all rule fields:

- countries: uppercase allowlist pattern `/^[A-Z]{2}$/`
- path prefixes: begin with `/`, strip control characters
- status: allow only `301`, `302`, `303`, `307`, `308`
- target URL: same-site relative path or `wp_http_validate_url()` plus explicit host allowlist
- booleans: `0` or `1`

## Redirect safety rules

- Treat every redirect rule as a production traffic-control rule, not as a cosmetic frontend behavior.
- Use `wp_safe_redirect()` for external URL protection.
- Prefer same-site URLs unless the add-on has an explicit allowed-hosts setting.
- Skip admin, AJAX, REST write endpoints, cron, login, payment callbacks, and BotBlocker security pages unless the add-on explicitly supports them.
- Do not redirect `POST`, `PUT`, `PATCH`, or `DELETE` requests by default.
- Avoid loops by comparing current URL and target URL.
- Add a `dry_run` mode that logs matched rules without redirecting.
- Do not redirect verified legal bots unless the setting explicitly allows it.
- Do not redirect BotBlocker check/block/denied pages.

Safe request guard:

```php
function vendor_traffic_is_redirectable_method( BotBlocker $bbcs ): bool {
    $method = isset( $bbcs->request_method ) ? strtoupper( (string) $bbcs->request_method ) : '';
    return in_array( $method, array( 'GET', 'HEAD' ), true );
}
```

## Logging a match

Use your own option/table/transient. Do not write directly to BotBlocker hit tables.

```php
function vendor_traffic_log_match( array $match, BotBlocker $bbcs ): void {
    $events = get_option( 'vendor_traffic_recent_matches', array() );
    $events = is_array( $events ) ? $events : array();

    $events[] = array(
        'time'    => time(),
        'cid'     => isset( $bbcs->cid ) ? sanitize_text_field( (string) $bbcs->cid ) : '',
        'ip_hash' => isset( $bbcs->ip ) ? hash( 'sha256', (string) $bbcs->ip . wp_salt( 'auth' ) ) : '',
        'country' => isset( $bbcs->country ) ? sanitize_text_field( (string) $bbcs->country ) : '',
        'uri'     => isset( $bbcs->uri ) ? sanitize_text_field( (string) $bbcs->uri ) : '',
        'rule'    => sanitize_text_field( (string) ( $match['name'] ?? '' ) ),
    );

    $events = array_slice( $events, -100 );
    update_option( 'vendor_traffic_recent_matches', $events, false );
}
```

Hash IP addresses unless the add-on has a clear operational reason and documented retention policy.

## When current v2 is not enough

Use `core-hook-integration.md` when the add-on must:

- decide before BotBlocker blocks or challenges a request
- add a new allow/block/gray/dark decision type
- replace or extend core rule matching
- alter BotBlocker response page selection
- inspect visitor data immediately after collection and before rules
- manage traffic in early-init mode before WordPress fully loads

Those use cases require BotBlocker core hook points and earlier add-on loading.

Do not implement `allow`, `bypass`, `block`, or `captcha` decisions as a shortcut for marketing routing. Those actions belong to security policy, verified integration bypasses, or carefully tested operational controls. A traffic add-on that returns those decisions must document exactly when it does so, why it is safe, how it is tested, and how an administrator can disable it quickly.
