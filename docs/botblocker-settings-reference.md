# BotBlocker Settings Reference For Add-ons

BotBlocker settings are available from:

```php
$bbcs = BotBlocker::getInstance();
$settings = $bbcs->settings ?? null;
```

Treat `$bbcs->settings` as read-only in third-party add-ons. Store add-on configuration in your own manifest-declared `settings.option`.

## Safe read helper

```php
function vendor_addon_bbcs_setting( string $name, $default = null ) {
    if ( ! class_exists( 'BotBlocker' ) ) {
        return $default;
    }

    $bbcs = BotBlocker::getInstance();
    if ( ! isset( $bbcs->settings ) || ! is_object( $bbcs->settings ) ) {
        return $default;
    }

    return property_exists( $bbcs->settings, $name ) ? $bbcs->settings->$name : $default;
}
```

Example:

```php
$secure_mode = (int) vendor_addon_bbcs_setting( 'secure_mode', 1 );
```

## Security mode and request flow

| Setting | Default | Meaning |
| --- | ---: | --- |
| `secure_mode` | `1` | `1` frontend mode: security pages render later through WP templates. `2` full mode: BotBlocker outputs/stops earlier. |
| `disable` | `0` | Disables BotBlocker protection when `1`. |
| `botblocker_force_check` | `0` | Forces check flow even if other checks pass. |
| `force_cloud_validation` | `0` | Forces cloud validation when ultimate cloud mode is active. |
| `unresponsive` | `0` | Defines behavior when cloud check fails. |

Add-ons that redirect traffic must check security page flags before redirecting.

## Cookie and cache settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `cookie` | `BotBlocker` | Main BotBlocker cookie name. |
| `cookie_lifetime` | `604800` | Verification cookie lifetime in seconds. |
| `hits_per_user` | `500` | Daily hit counter threshold before cookie reset. |
| `samesite` | `Lax` | Cookie SameSite policy. |
| `vary_cookie` | `0` | Sends `Vary: Cookie` for verified visitors when enabled. |
| `cache_ui_data` | `0` | Admin UI cache flag. |
| `cache_ui_duration` | `3600` | Admin UI cache duration. |

Do not overwrite BotBlocker cookies from an add-on. Use your own prefixed cookies when needed.

## Header and indexing settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `header_error_code` | `400` | HTTP code used for denied/block responses. |
| `header_test_code` | `200` | HTTP code used for check page. |
| `iframe_stop` | `0` | Sends X-Frame-Options unless handled by a security headers add-on. |
| `noarchive` | `0` | Adds `noarchive` X-Robots behavior. |
| `x_robots_directives` | `[]` | Configured X-Robots directives. |
| `utm_noindex` | `0` | Adds noindex behavior for UTM referrer flow. |
| `utm_referrer` | `1` | Preserves referrer through `utm_referrer` in check redirect flow. |

Header add-ons should use `send_headers` or the documented security headers provider hooks. Do not send duplicate security headers without checking core state.

## Bot and request blocking settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `block_empty_ua` | `1` | Block empty User-Agent. |
| `block_empty_lang` | `1` | Block empty Accept-Language/language. |
| `block_http10_users` | `1` | Block HTTP/1.0 requests. |
| `block_simplebot_ua` | `1` | Block known simple bot User-Agent signatures. |
| `block_fake_ref` | `1` | Block malformed referrer structures. |
| `check_get_ref` | `1` | Gray-mark bad query params in referrer. |
| `block_ipv6_users` | `0` | Block IPv6 users. |
| `block_ip_ptr_match` | `0` | Block when PTR equals IP. |
| `block_proxy_users` | `1` | Block classic proxy header detection. |
| `block_cf_users` | `1` | Block Cloudflare proxy detection when configured that way. |
| `block_tor_users` | `0` | Reserved/traffic policy setting. |
| `block_vpn_users` | `0` | Reserved/traffic policy setting. |
| `block_rkn` | `0` | Enables RKN/government network blocking flow. |
| `hosting_block` | `0` | Blocks hosting/bad IP when cloud data marks it. |

For a traffic management add-on, read these settings to avoid contradicting the site's core protection policy.

## Browser verification and anti-detect settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `bbcs_captcha_mode` | `1` | Captcha/check mode. Constant `BOTBLOCKER_CAPTCHA_MODE_SILENT` is `8`. |
| `bbcs_captcha_wait` | `30` | Captcha wait/timeout threshold. |
| `bbcs_captcha_img_inline` | `1` | Captcha image rendering mode. |
| `bbcs_captcha_img_pack` | `1` | Captcha image pack. |
| `recaptcha_check` | `0` | Enable reCAPTCHA v3 checks. |
| `recaptcha_key2`, `recaptcha_secret2` | empty | reCAPTCHA v2 credentials. |
| `recaptcha_key3`, `recaptcha_secret3` | empty | reCAPTCHA v3 credentials. |
| `recaptcha_tresshold` | `0.5` | reCAPTCHA v3 score threshold. |
| `recaptcha_v3_ipv6_block` | `0` | Disable v3 path for IPv6 when configured. |
| `block_nojs_users` | `1` | Block users failing JavaScript/cookie verification. |
| `block_adblocker_users` | `1` | Block adblock detection from browser verification. |
| `block_incognito_users` | `1` | Block incognito anti-detect group. |
| `block_simple_antidetect` | `1` | Block simple anti-detect group. |
| `block_override` | `0` | Block override/fingerprint group. |
| `block_web_engine_options` | `0` | Block web engine mismatch group. |
| `block_device_options` | `0` | Block device mismatch group. |

Do not collect or transmit browser fingerprint data from an add-on unless the add-on has explicit settings, documentation, and retention behavior.

## Visitor enrichment settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `get_browser_type` | `1` | Populate `$bbcs->browser`. |
| `get_os_type` | `1` | Populate `$bbcs->os`. |
| `get_device_type` | `1` | Populate `$bbcs->device`. |
| `ptr_cache_in_db` | `1` | PTR cache behavior. |
| `ptrcache_time` | `86400` | PTR cache TTL in seconds. |

These settings control whether enriched fields are available. Add-ons must handle `-` or empty values.

## Geo, cloud, and API settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `cloud_api_timeout` | `5` | Cloud API timeout. |
| `cloud_api_type` | empty | Cloud API type. |
| `cloud_api_email` | empty | Account email used in core hash/API flows. |
| `cloud_api_key` | empty | Cloud API key. |
| `cloud_api_pass` | empty | Secret used in verification hashes. |
| `cloud_api_secret` | empty | Domain API secret. |
| `cloud_api_tier` | empty | Cloud tier. |
| `bbcs_api_url` | empty | Main API URL override/data. |
| `bbcs_api_gs_url` | empty | Reserve API URL override/data. |

Never print, log, or expose cloud credentials from `$bbcs->settings`.

## Payment bypass settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `payment_bypass_enable` | `0` | Enables payment gateway callback bypass detection. |
| `payment_bypass_log` | `1` | Logs payment bypass events. |

Traffic add-ons must not redirect or block requests when `$bbcs->payment_bypass_reason` is non-empty or `visitorType` is `VISITOR_LEGALBOT` because of payment bypass.

## Admin, cron, and logging settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `admin_gmt_offset` | `0` | Admin timezone offset. |
| `daylight_saving_time` | `0` | Adjust admin offset for DST. |
| `admin_report_period` | `5` | Admin report period. |
| `admin_store_period` | `7` | Admin data retention period. |
| `admin_uniq_type` | `host` | Admin unique visitor grouping mode. |
| `allow_self_ip_req` | `1` | Allows self IP requests. |
| `autosave_admin_ip` | `0` | Saves admin IP automatically when needed. |
| `botblocker_log_error` | `1` | Log internal errors. |
| `botblocker_log_admin` | `0` | Log admin requests. |
| `botblocker_log_allow` | `1` | Log allow decisions. |
| `botblocker_log_bbcs` | `0` | Log BotBlocker admin pages. |
| `botblocker_log_block` | `1` | Log block/deny decisions. |
| `botblocker_log_cli` | `0` | Log CLI requests. |
| `botblocker_log_disabled` | `0` | Log disabled state. |
| `botblocker_log_fake` | `1` | Log fake bot decisions. |
| `botblocker_log_goodip` | `1` | Log good IP/bot decisions. |
| `botblocker_log_local` | `1` | Log local check/cookie decisions. |
| `botblocker_log_tests` | `1` | Log check/test flows. |
| `botblocker_log_wp` | `0` | Log WordPress system pages. |

Add-ons should use their own logging toggle and avoid writing to BotBlocker logs unless a dedicated API exists.

## Login protection and notifications

| Setting | Default | Meaning |
| --- | ---: | --- |
| `login_brutforce_enabled` | `1` | Login brute-force protection enabled. |
| `login_brutforce_attempts` | `5` | Attempts before action. |
| `login_brutforce_period` | `900` | Counting window in seconds. |
| `login_brutforce_primary_block_time` | `900` | Primary block duration. |
| `login_brutforce_secondary_block_time` | `1800` | Secondary block duration. |
| `telegram_notification` | `0` | Telegram notifications. |
| `email_notifications` | `0` | Email notifications. |
| `pusher_notifications` | `0` | Pusher notifications. |
| `critical_load_notifications` | `0` | Critical load notifications. |
| `regular_notifications_frequency` | `disabled` | Regular notification frequency. |
| `bbcs_2fa_enable` | `0` | 2FA integration setting. |

## Storage/cache backend settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `memcached_enable` | `1` | Memcached cache enabled. |
| `memcached_host` | `127.0.0.1` | Memcached host. |
| `memcached_port` | `11211` | Memcached port. |
| `memcached_prefix` | `bb_` | Memcached key prefix. |
| `redis_enable` | `0` | Redis cache enabled. |
| `redis_host` | `127.0.0.1` | Redis host. |
| `redis_port` | `6379` | Redis port. |
| `redis_db` | `0` | Redis DB index. |
| `redis_password` | empty | Redis password. |
| `redis_prefix` | `bb_` | Redis key prefix. |
| `use_transients_for_cloud` | `0` | Uses WP transients for cloud data cache. |

Never expose passwords or backend host details in frontend output.

## Early-init and MU settings

| Setting | Default | Meaning |
| --- | ---: | --- |
| `mu_enable` | `0` | MU integration flag. |
| `early_init_enable` | `0` | Early-init protection flag. |

Normal v2 add-ons are WordPress-runtime add-ons. Early-init behavior needs a specific provider contract and cannot assume full WordPress/plugin APIs are available before WordPress loads.

## Secret flow and salts

| Setting | Default | Meaning |
| --- | ---: | --- |
| `secret_botblocker_get_param` | empty | Secret parameter name. |
| `action_disable` | empty | Secret action to disable for request. |
| `action_off` | empty | Secret action to turn protection off. |
| `action_on` | empty | Secret action to turn protection on. |
| `salt`, `salt_pz`, `salt_ps`, `salt_bb` | empty | Core verification salts. |
| `host_key` | empty | Host key. |
| `time_ban` | `200` | First CAPTCHA failure ban time. |
| `time_ban_2` | `400` | Repeated CAPTCHA failure ban time. |

Never print, log, store, or send salts and secret action values from an add-on.

