# Add-on abilities (WordPress Abilities API)

BotBlocker can publish add-on abilities from the manifest. Core owns registration, capability checks, audit logging, rate limiting and the Integrations list. Your add-on supplies a definition, an executor and an optional permission callback.

The `abilities` key is optional. An add-on without it behaves exactly as before. A broken entry is dropped; a broken `abilities` value becomes an empty list. Neither invalidates the add-on.

## Manifest

```json
{
  "abilities": [
    {
      "name": "botblocker/get-sample-header",
      "label": "Get sample header",
      "setting_key": "bbcs_ability_get_sample_header",
      "definition": "acme_bbcs_sample_ability_definition",
      "execute": "acme_bbcs_sample_ability_execute",
      "permission": "acme_bbcs_sample_ability_can_read",
      "public": true,
      "pii": false,
      "risk": "read"
    }
  ]
}
```

| Field | Required | Rule |
|---|---|---|
| `name` | yes | `botblocker/` prefix, lowercase kebab-case, globally unique. |
| `setting_key` | yes | `bbcs_ability_[a-z0-9_]+`, unique within the manifest. This is the toggle key inside `settings.option`, not a core setting. |
| `definition` | yes | Safe callable returning the full ability definition array. |
| `execute` | yes | Safe callable receiving `$input` and returning an array or `WP_Error`. |
| `permission` | no | Safe callable. When present it runs in addition to the core capability check. |
| `public` | no | Boolean, default `false`. MCP discovery visibility only, never authorization. |
| `pii` | no | Boolean, default `false`. `true` forces `public=false`. |
| `risk` | no | `read`, `manage` or `danger`, default `read`. |
| `label` | no | Fallback UI label when the definition callable is not loaded. |

Validation at runtime: `name` pattern, `setting_key` pattern, `safeCallableName()` for all three callables, and duplicate protection. Callables are checked when the ability is registered, not when the manifest is parsed; a missing callable skips that ability and the add-on keeps loading.

## Toggle

The toggle lives in your add-on settings option (`settings.option`). Core reads it with `BotBlockerMultisite::getOption()`:

- absent key or `0` = off;
- `1` = on;
- master switch `bbcs_wp_abilities_enabled` must also be on;
- the add-on must be active and `requires_core`-compatible.

Do not add add-on ability keys to `BotBlockerSettings`. Do not register abilities with `wp_register_ability()` yourself.

## Definition callback

```php
function acme_bbcs_sample_ability_definition(): array {
    return array(
        'label'         => __( 'Get sample header', 'acme-botblocker-sample' ),
        'description'   => __( 'Returns the response header configured by the sample add-on.', 'acme-botblocker-sample' ),
        'annotations'   => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
        'input_schema'  => array( 'type' => 'object', 'default' => array(), 'properties' => array(), 'additionalProperties' => false ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'header_name'  => array( 'type' => 'string' ),
                'header_value' => array( 'type' => 'string' ),
                'enabled'      => array( 'type' => 'boolean' ),
            ),
            'required'   => array( 'header_name', 'header_value', 'enabled' ),
            'additionalProperties' => false,
        ),
    );
}
```

`annotations` must agree with `risk`:

- `read` -> `readonly=true`, `destructive=false`;
- `manage` / `danger` -> `readonly=false`, `destructive=true`.

The definition array may also include `capability` to override the default `manage_options` requirement. Core still applies `bbcs_ability_required_capability` and `bbcs_ability_capability_by_risk` on top of it.

## Execute callback

```php
function acme_bbcs_sample_ability_execute( $input = null ) {
    unset( $input );
    $settings = acme_bbcs_sample_settings();

    return array(
        'header_name'  => (string) $settings['header_name'],
        'header_value' => (string) $settings['header_value'],
        'enabled'      => ! empty( $settings['enabled'] ),
    );
}
```

Rules:

- Return the declared output shape, or `WP_Error` with a stable code.
- Do not call `wp_register_ability()`.
- Do not write to the database for `risk=read`.
- For `manage` / `danger`, accept `confirm` and `dry_run`, and make `dry_run` change nothing.
- Never return secrets, license keys, passwords or recovery codes.
- PII outputs (`pii=true`) are masked by default and cannot unmask for `risk=read`.

## Destructive ability contract

A writable ability must be explicit about risk. It needs `risk` set to `manage` or `danger`, `public: true` in the manifest entry (a destructive ability is hidden from MCP without that flag), and an `annotations` block with `readonly=false`, `destructive=true`.

Use the core guard and a string confirmation token, never a boolean:

```php
public static function executeSetDomains( $input = null ) {
    $guard = bbcs_wp_ability_guard( 'botblocker/set-security-headers-domains', $input, 'DOMAINS', true );
    if ( is_wp_error( $guard ) ) {
        return $guard;
    }
    // validate input, compute the diff, write only when dry_run is false
}
```

`bbcs_wp_ability_guard()` applies the capability check, the confirmation token and the rate limiter. The string token is case-insensitive after trimming; `confirm: true` does not match. Add `dry_run` to the input schema and return `applied => false` when it is set.

Validation rules for a setter:

- validate every item before writing; one invalid item means no write at all;
- return `ability_invalid_input` with the index and the original value in the message;
- enforce the documented list limit;
- preserve every option key you do not own;
- read and write through `BotBlockerMultisite::getOption()` / `updateOption()`, then run the result through the add-on sanitizer;
- if a request-scoped cache exists in another class, reset it after a successful write.

Dry run still computes and returns `changed`, `previous`, `current`, `added` and `removed`, so a caller can inspect the result before writing.

## Permission callback

```php
function acme_bbcs_sample_ability_can_read( $ability_name = '' ): bool {
    unset( $ability_name );
    return current_user_can( 'manage_options' );
}
```

The core capability check always runs first. The manifest callback is an additional gate, not a replacement. Multisite network-admin checks still apply.

## Integrations UI

Core adds an **Add-on abilities** block to **BotBlocker → Integrations → WP Abilities API**:

- lists installed add-ons that declare abilities;
- shows the ability labels and read-only state badges;
- links to the add-on settings;
- marks inactive add-ons and the master switch state.

The block is informational. It does not save add-on toggles.

## Compatibility

- Old core + new add-on: `abilities` is ignored, the add-on loads normally.
- New core + old add-on: no `abilities` key, behavior unchanged.
- `requires_core` is yours to raise when you rely on the abilities contract. Core does not change it for you.

See `examples/acme-botblocker-sample` for a working read-only ability.
