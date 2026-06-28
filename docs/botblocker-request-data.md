# BotBlocker Request Data Map

This is the field map for data that a BotBlocker add-on may want to read from `BotBlocker::getInstance()`.

Fields are populated progressively. Always check `isset()` and normalize values before using them. BotBlocker uses `BOTBLOCKER_EMPTY` with value `-` for many unknown values.

## Core and request identity

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->time` | int | `time()` | Request processing timestamp. |
| `$bbcs->date` | string | `gmdate('Y.m.d')` | Request date string used in BotBlocker checks. |
| `$bbcs->cid` | string | generated | Connection/request id, format like `{timestamp}.{random}`. |
| `$bbcs->version` | string | `BOTBLOCKER_VERSION` | BotBlocker core version. |
| `$bbcs->botblockerUrl` | string | `BOTBLOCKER_URL` | BotBlocker plugin URL. |
| `$bbcs->dirs` | array | core init | Absolute directories for root/public/includes/admin/data/vendor. |
| `$bbcs->prefly` | array | environment check | Environment capability data from `BotBlockerEnv::prefly_check()`. |
| `$bbcs->isAdmin` | bool | current WP user | `true` for administrator, editor, or moderator roles. |
| `$bbcs->isDisabled` | bool | settings/secret flow | `true` when BotBlocker is disabled for the request. |

Example:

```php
$bbcs = BotBlocker::getInstance();
$cid  = isset( $bbcs->cid ) ? sanitize_text_field( (string) $bbcs->cid ) : '';
```

## HTTP request fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->host` | string | `HTTP_HOST` | Sanitized request host without trailing dot. |
| `$bbcs->scheme` | string | `HTTP_X_FORWARDED_PROTO`, `REQUEST_SCHEME`, fallback `https` | Request scheme used to build page URL. |
| `$bbcs->uri` | string | `REQUEST_URI` | Normalized request URI. |
| `$bbcs->page` | string | derived | Full URL: `{scheme}://{host}{uri}`. |
| `$bbcs->save_page` | string | derived | Page URL saved to logs, optionally without query string. |
| `$bbcs->request_method` | string | `REQUEST_METHOD` | Sanitized HTTP method. |
| `$bbcs->protocol` | string | `SERVER_PROTOCOL` | Protocol such as `HTTP/1.1`; fallback `HTTP/1.0`. |
| `$bbcs->http_accept` | string | `HTTP_ACCEPT` | Raw Accept header after stripping tags. |
| `$bbcs->useragent` | string | `HTTP_USER_AGENT` | Sanitized user agent. |

Use cases:

- traffic routing by path: `$bbcs->uri`
- method restrictions: `$bbcs->request_method`
- header diagnostics: `$bbcs->http_accept`
- page-level reporting: `$bbcs->save_page`

## Visitor IP and network fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->ip` | string | `REMOTE_ADDR`, then trusted proxy mapping | Visitor IP after BotBlocker proxy handling. IPv6 is expanded. |
| `$bbcs->ip_version` | int | parsed IP | `4` or `6`. |
| `$bbcs->ipnum` | string/int | parsed IP | Numeric IPv4 or binary/comparable IPv6 representation used by rules. |
| `$bbcs->ip_short` | string | parsed IP | IPv4 `/24` or IPv6 `/64` network shortcut. |
| `$bbcs->ptr` | string | DNS/PTR helper | Reverse DNS value or fallback. |
| `$bbcs->isProxy` | string | proxy rules/headers | `-`, `PROXY_v4`, `PROXY_v6`, or `DETECTED`. |
| `$bbcs->is_proxy_det` | string | proxy detection | Matched proxy header or `CLASSIC`. |
| `$bbcs->cidr` | string | local ASN DB/cloud | Network CIDR when available. |
| `$bbcs->asnum` | string | local ASN DB/cloud | Autonomous system number. |
| `$bbcs->asname` | string | local ASN DB/cloud | Autonomous system name. |
| `$bbcs->hosting` | mixed | cloud extended | Hosting/bad IP flag, usually `1`, `0`, or `-`. |

Example: skip an add-on feature for data centers.

```php
function vendor_addon_is_likely_hosting_request( BotBlocker $bbcs ): bool {
    return isset( $bbcs->hosting ) && in_array( $bbcs->hosting, array( 1, '1' ), true );
}
```

## Geo and language fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->country` | string | local ASN DB, SxGeo, cloud, ip2c fallback | ISO country code or `-`. |
| `$bbcs->country_name` | string | language map | Human-readable country name when known. |
| `$bbcs->accept_lang` | string | `HTTP_ACCEPT_LANGUAGE` | Raw Accept-Language after stripping tags. |
| `$bbcs->lang` | string | parsed Accept-Language | First language code. |
| `$bbcs->name_lang` | string | language map | Human-readable language name when known. |
| `$bbcs->timezone` | string | local verification/rules | Timezone-related state when present. |

Example:

```php
$country = isset( $bbcs->country ) ? strtoupper( (string) $bbcs->country ) : '';
if ( in_array( $country, array( 'US', 'CA', 'GB' ), true ) ) {
    // Route to English landing flow.
}
```

## Referrer and analytics fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->referer` | string | `HTTP_REFERER` | Sanitized raw referrer URL/string. |
| `$bbcs->refhost` | string | parsed referrer | Referrer host, lowercased. |
| `$bbcs->refhost_scheme` | string | parsed referrer | Referrer scheme. |
| `$bbcs->save_referer` | string | derived | Referrer saved to logs, optionally without query string. |
| `$bbcs->ym_uid` | string | `_ym_uid` cookie | Yandex Metrica visitor id digits. |
| `$bbcs->ga_uid` | string | `_ga` cookie | Google Analytics client id value. |

Privacy rule: do not store analytics identifiers in add-on data unless the add-on settings and readme explain why they are needed.

## Browser and device fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->browser` | string | user agent helper | Browser type when `get_browser_type` is enabled. |
| `$bbcs->os` | string | user agent helper | OS type when `get_os_type` is enabled. |
| `$bbcs->device` | string | MobileDetect/user agent | `pc`, `phone`, `tablet`, `tv`, or `box` when enabled. |

These values depend on BotBlocker settings. They may be `-` when disabled.

## Cookie and visitor continuity fields

| Property | Type | Source | Meaning |
| --- | --- | --- | --- |
| `$bbcs->uid` | string | BotBlocker cookie or generated | BotBlocker visitor uid. |
| `$bbcs->cookie_hits_counter` | int | `{cookie}_hits` cookie | Daily hit counter, assets do not increment it. |
| `$bbcs->cookie_visitor_data` | string | `{uid}` cookie | Stored verification hash and timestamp. |
| `$bbcs->cookie_timestamp` | int | parsed visitor data cookie | Timestamp for verification cookie. |
| `$bbcs->cookie_stored_hash` | string | parsed visitor data cookie | Hash stored in visitor data cookie. |
| `$bbcs->cookie_expected_hash` | string | computed | Expected hash for current request. |
| `$bbcs->is_asset_request` | bool | URI extension | `true` for static asset-style requests. |

Do not recreate BotBlocker cookie verification in third-party add-ons. Use these fields only for diagnostics or coarse routing.

## Decision and result fields

| Property | Type | Meaning |
| --- | --- | --- |
| `$bbcs->visitorType` | int | Current visitor classification. See `botblocker-core-object.md`. |
| `$bbcs->suspect_status` | int | `0` normal, `1` gray/suspect, `2` dark/check page. |
| `$bbcs->result_of_action` | string | Human-readable internal result text. |
| `$bbcs->reason_for_action` | int | BotBlocker code used for logs/counters. |
| `$bbcs->rule_record_id` | int | Matched rule id when available. |
| `$bbcs->white_bot` | string | Matched legal bot or provider label. |
| `$bbcs->payment_bypass_reason` | string | Matched payment bypass reason. |
| `$bbcs->x_robots_tag` | array | Pending X-Robots directives. |
| `$bbcs->should_show_check_page` | bool | Frontend check page selected. |
| `$bbcs->should_show_block_page` | bool | Frontend block page selected. |
| `$bbcs->should_show_denied_page` | bool | Frontend denied page selected. |

Decision-safe helper:

```php
function vendor_addon_botblocker_allows_normal_page( BotBlocker $bbcs ): bool {
    if ( ! empty( $bbcs->should_show_check_page ) || ! empty( $bbcs->should_show_block_page ) || ! empty( $bbcs->should_show_denied_page ) ) {
        return false;
    }

    return in_array(
        isset( $bbcs->visitorType ) ? (int) $bbcs->visitorType : 0,
        array( BotBlockerBase::VISITOR_HUMAN, BotBlockerBase::VISITOR_LEGALBOT, BotBlockerBase::VISITOR_ADMIN, BotBlockerBase::VISITOR_BOTBLOCKER ),
        true
    );
}
```

## Rule and provider arrays

| Property | Type | Meaning |
| --- | --- | --- |
| `$bbcs->bbcs_rule` | array | Search-engine/user-agent rule map. |
| `$bbcs->bbcs_se` | array | Search-engine/user-agent verification token map. |
| `$bbcs->bbcs_asn` | array | ASN rule map. |
| `$bbcs->bbcs_path` | array | Path substring rule map. |
| `$bbcs->bbcs_proxy` | array | Trusted proxy network to header map. |
| `$bbcs->self_ips` | array | Site/server self IP allow map. |
| `$bbcs->admin_ips` | array | Admin IP allow map. |
| `$bbcs->bbcs_good_bots` | array | Known good bot data loaded from bundled base data. |

Do not mutate these arrays directly from third-party add-ons. If a traffic add-on needs persistent rules, store its own settings or use documented BotBlocker admin/rule APIs when they exist.

## Local browser verification fields

These fields are populated during BotBlocker verification POST flows, not during every normal page request.

| Property | Type | Meaning |
| --- | --- | --- |
| `$bbcs->post_start_time` | float | Start time of local verification request. |
| `$bbcs->post_width`, `$bbcs->post_height` | int | Screen dimensions. |
| `$bbcs->post_client_width`, `$bbcs->post_client_height` | int | Browser viewport dimensions. |
| `$bbcs->post_color_depth`, `$bbcs->post_pixel_depth` | int | Browser display depth. |
| `$bbcs->post_referrer` | string | Browser-submitted referrer. |
| `$bbcs->post_timezone` | string | Browser timezone. |
| `$bbcs->post_adblocker_found` | int | Adblock detection flag. |
| `$bbcs->post_cookie_disabled` | int | Cookie-disabled flag. |
| `$bbcs->post_from_suspect` | int | Whether check came from gray/dark state. |
| `$bbcs->post_cloudflare_country` | string | `HTTP_CF_IPCOUNTRY` during verification. |
| `$bbcs->post_ip_database_result` | string | Browser-side IP DB result. |
| `$bbcs->post_ipv4_value` | string | Browser-side IPv4 value if present. |
| `$bbcs->post_http_accept` | string | Browser-submitted Accept header. |
| `$bbcs->post_recaptcha_token` | string | reCAPTCHA token. |
| `$bbcs->post_recaptcha_score` | mixed | reCAPTCHA v3 score. |
| `$bbcs->post_antidetect_scope` | array/string | Browser anti-detect findings. |
| `$bbcs->cloud_data` | array | Cloud check result from local verification flow. |
| `$bbcs->cloud_error` | string | Cloud request failure state. |

Anti-detect keys include:

```text
navigatorMismatch
unsupportedFeatures
fakePlugins
fontRenderMismatch
chromiumProperties
jitter
webGLMismatch
touchEventMismatch
batteryAPIMismatch
mediaDevicesMismatch
permissionsMismatch
languageMismatch
crossbrowserIncognito
browserFingerprint
```

Use these fields only when your add-on explicitly handles BotBlocker verification flows. Most traffic and redirect add-ons should rely on server-side request, geo, network, and decision fields.

## Template and frontend data fields

| Property | Meaning |
| --- | --- |
| `$bbcs->template_data_check` | Data for check page template. |
| `$bbcs->template_data_block` | Data for block page template. |
| `$bbcs->template_data_denied` | Data for denied page template. |
| `$bbcs->captcha_data` | Captcha JS payload. |
| `$bbcs->js_data` | Check page JS payload. |
| `$bbcs->block_js_data` | Block page JS payload. |
| `$bbcs->denied_data` | Denied page reason text when debug reason view is enabled. |
| `$bbcs->block_data` | Block page reason text when debug reason view is enabled. |
| `$bbcs->block_wait_seconds` | Temporary block countdown seconds. |
| `$bbcs->csp_nonce` | CSP nonce in full secure mode. |

These are rendering internals. Add-ons should read them only for diagnostics.

## Public helper functions useful to add-ons

These helpers are available after BotBlocker core helpers are loaded:

| Helper | Use |
| --- | --- |
| `BotBlockerAddons::fileUrl( $slug, $relative )` | Build runtime asset URL for uploaded add-on files. |
| `BotBlockerAddons::isActive( $slug )` | Check active state. |
| `BotBlockerAddons::getActive()` | Read active add-on slugs. |
| `BotBlockerAddons::scanAll()` | Read normalized installed add-on metadata. |
| `BotBlockerAddons::hasActiveFeature( $feature )` | Check active feature declaration. |
| `BotBlockerAddons::getByFeature( $feature )` | Get active add-ons declaring a feature. |
| `BotBlockerMultisite::getDataDir()` | BotBlocker protected data directory. Read only unless core API says otherwise. |
| `BotBlockerMultisite::getAddonsDir()` | BotBlocker runtime add-ons directory. |
| `BotBlockerMultisite::getAddonsUrl()` | Runtime add-ons base URL. |
| `BotBlockerIp::netMatch( $network, $ip )` | Check IP/CIDR style match. |
| `BotBlockerIp::getPtr( $ip, $time, $ttl )` | PTR lookup with cache behavior. |
| `bbcs_codeList( $code )` | Get BotBlocker log code metadata. |

Avoid writing to BotBlocker core data files or tables unless the add-on is explicitly designed and tested as a core-management extension.

