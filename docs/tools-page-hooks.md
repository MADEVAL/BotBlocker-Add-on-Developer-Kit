# Tools Page Integration

Add-ons can extend the BotBlocker Tools page (`page=bbcs_tools`) sidebar navigation and tab content through WordPress filters. These hooks let you add custom nav groups, nav items, tabpanel templates, and contribute to the global search index (used by both the sidebar search and ⌘K command palette). The manifest `settings.view` renders separately on the Add-ons page (`page=bbcs_addons`) and needs no filters.

**This replaces the old `inc/hooks.php` pattern.** Instead of a procedural file, each add-on uses a dedicated `Tools_Page` class that encapsulates all filter registrations.

Read this together with:

- `addon-api-v2.md` for manifest and package setup.
- `settings-ui-patterns.md` for tabpanel markup conventions.
- `settings-contract.md` for option storage and sanitization.

## Quick reference

| Filter | Page | What it controls |
|--------|------|-----------------|
| `bbcs_tools_nav_groups` | Tools | Sidebar navigation groups and items |
| `bbcs_tools_tabpanels` | Tools | Tabpanel template file paths |
| `bbcs_global_search_index` | All pages | Global search index for sidebar search and ⌘K palette |

## Global Search Index (`bbcs_global_search_index`)

This is the **single source of truth** for all searchable settings across the entire plugin. Every add-on that exposes configurable settings MUST register them here so they appear in both the sidebar navigation search (`#bbcs-snav`) and the ⌘K command palette (`#bbcs-palette`).

### Data structure

```php
array(
    't'    => 'Group Title',        // translated, e.g. __( 'Speed Up', 'botblocker-security' )
    'ic'   => 'icon-name',          // SVG icon from bbcs sprite (e.g. 'gauge', 'shield')
    'go'   => 'addons',             // REQUIRED - target page slug: 'tools', 'addons', 'settings', 'integrations'
    'tabs' => array(
        array(
            't'   => 'Tab Title',   // translated
            'tab' => 'tab-id',      // matches data-snav-tab / data-tab attribute
            'go'  => 'addons',      // REQUIRED - explicit routing for ⌘K palette
            'sg'  => array(         // sub-groups of settings
                array(
                    't' => 'Subgroup Title',
                    's' => array(
                        array( __( 'Setting Label', 'domain' ), 'prefixed_setting_key' ),  // Tuple [ label, key ]
                        //                                                ^^^^^^^^^^^^^^^^^
                        //                                     MUST match data-anchor in template
                    ),
                ),
            ),
        ),
    ),
)
```

### Key naming convention (CRITICAL)

To avoid collisions between add-ons, every setting key MUST use a unique prefix. Use the pattern:

```
bbcs_{addon-slug}_{setting_name}
```

Examples:
- `bbcs_speedup_disable_emojis`
- `bbcs_behavior_enabled`
- `bbcs_cookie_alert_message`
- `bbcs_hide_admin_login_url`

**Never use bare keys** like `enabled`, `message`, or `style` - these will collide with other add-ons.

### `go` property (REQUIRED)

Every top-level group and every tab MUST declare `'go' => 'page_slug'`. This tells the ⌘K command palette which admin page to navigate to. Valid slugs:
- `'settings'` → `admin.php?page=bbcs_settings`
- `'integrations'` → `admin.php?page=bbcs_integrations`
- `'tools'` → `admin.php?page=bbcs_tools`
- `'addons'` → `admin.php?page=bbcs_addons`

A missing tab-level `go` falls back to the group-level `go`. If neither is present, the palette skips the entry (and logs a `Missing required "go" property` notice when `WP_DEBUG` is on). The sidebar navigation itself falls back to the `settings` page. Always declare `go` explicitly.

### `data-anchor` in templates (REQUIRED)

For instant jump-to-setting highlighting to work, every form element rendered in your tabpanel MUST set a `data-anchor` attribute matching the search index key. Use the component's `->withAnchor()` method:

```php
ToggleOption::make()
    ->withName( 'disable_emojis' )
    ->withAnchor( 'bbcs_speedup_disable_emojis' )  // matches the key in global_search_index
    ->withChecked( ! empty( $settings['disable_emojis'] ) )
    ->withLabel( __( 'Disable Emojis', 'botblocker-security' ) )
    ->render();
```

All BotBlocker form components support `->withAnchor()`:
- `ToggleOption`
- `TextInput`
- `Textarea`
- `CustomSelect`

When the user clicks a setting in the sidebar search or ⌘K palette, the shared helpers JS finds `[data-anchor="key"]` and scrolls + highlights the element.

### Complete example

```php
<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBCS_SpeedUp_Tools_Page {

    public static function init(): void {
        add_filter( 'bbcs_global_search_index', array( self::class, 'global_search_index' ), 10, 1 );
    }

    public static function global_search_index( array $groups ): array {
        $groups[] = array(
            't'    => __( 'Speed Up', 'botblocker-security' ),
            'ic'   => 'gauge',
            'go'   => 'addons',                              // REQUIRED
            'tabs' => array(
                array(
                    't'   => __( 'Performance', 'botblocker-security' ),
                    'tab' => 'bbcs-speedup',
                    'go'  => 'addons',                       // REQUIRED
                    'sg'  => array(
                        array(
                            't' => __( 'Performance Optimization', 'botblocker-security' ),
                            's' => array(
                                // Tuple format: [ label, prefixed_key ]
                                array( __( 'Disable Emojis', 'botblocker-security' ), 'bbcs_speedup_disable_emojis' ),
                                array( __( 'Remove jQuery Migrate', 'botblocker-security' ), 'bbcs_speedup_remove_jquery_migrate' ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        return $groups;
    }
}
```

And in the corresponding template:

```php
ToggleOption::make()
    ->withName( 'disable_emojis' )
    ->withAnchor( 'bbcs_speedup_disable_emojis' )
    ->withChecked( ! empty( $settings['disable_emojis'] ) )
    ->withLabel( __( 'Disable Emojis', 'botblocker-security' ) )
    ->render();
```

## Class structure

Create a file named `inc/class-{slug}-tools-page.php` with a class `BBCS_{Name}_Tools_Page` (or `Acme_{Name}_Tools_Page` for vendor add-ons).

```text
vendor-addon/
  bbcs-addon.json
  inc/
    core.php                          ← main runtime behavior
    class-vendor-addon-tools-page.php ← tools page integration (NEW)
    settings.php                      ← settings view
```

## Loading the class

Include the class file and call `init()` from your add-on's core file:

```php
// inc/core.php
require_once __DIR__ . '/class-vendor-addon-tools-page.php';
Vendor_Addon_Tools_Page::init();
```

## Filter reference

### bbcs_tools_nav_groups

Register additional sidebar navigation groups and items on the Tools page.

**Parameters:**
- `$nav_groups` - array of groups. Each group is `{ title, icon, items: TabItem[] }`.
- `$addon_tabs` - array of `Botblocker_AddonTabData` for active add-ons with settings (same shape the Addons page builds).

Both filters are applied by the `Botblocker_ToolsViewModel` constructor on every Tools page render, after the core `WordPress` / `BotBlocker` / `Maintenance` tabs are built. Returning the input unchanged keeps the core Tools page untouched.

**TabItem constructor (`BotBlocker\Component\TabItem`):**

```php
new TabItem(
    string $id,          // unique tab ID, used as data-snav-tab and data-tabpanel
    string $href,        // empty for button-style nav items; defaults to '#' . $id
    bool   $active,      // only one item should be active
    string $class,       // optional CSS class for the nav item
    string $item_class,  // optional CSS class for the item element
    string $label,       // display label
    string $icon         // SVG icon name (from bbcs sprite)
)
```

The `id` must match a tabpanel `data-tabpanel` attribute.

### bbcs_tools_tabpanels

Register additional tabpanel template files for the Tools page.

```php
public static function tools_tabpanels( array $tabpanels, array $addon_tabs ): array {
    $tabpanels['vendor-tab-1'] = dirname( __DIR__ ) . '/inc/tabpanel-vendor.php';
    return $tabpanels;
}
```

**Parameters:**
- `$tabpanels` - map of `tab_id => absolute_file_path`.
- `$addon_tabs` - array of `Botblocker_AddonTabData` for active add-ons with settings.

The tabpanel template receives `( $data, $is_active )` where `$data` is the `Botblocker_ToolsViewModel` and `$is_active` is a boolean.

**Template wrapper:**

```php
<?php
// inc/tabpanel-vendor.php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

return static function ( Botblocker_ToolsViewModel $data, bool $is_active ): void {
?>
<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="vendor-tab-1"<?php echo $is_active ? '' : ' hidden'; ?>>
    <h3><?php esc_html_e( 'My Tab', 'vendor-addon' ); ?></h3>
    <!-- your settings fields here -->
</div>
<?php
};
```

### bbcs_global_search_index

Contribute to the global search index. This powers both the sidebar search and the ⌘K command palette. Every add-on setting MUST be registered here.

**Parameters:**
- `$groups` - array of search index groups.

See the [Global Search Index](#global-search-index-bbcs_global_search_index) section above for the complete data structure.

## When to use each filter

| Scenario | Filter |
|----------|--------|
| Add a new sidebar nav group with custom tabs | `bbcs_tools_nav_groups` |
| Add a tabpanel template for a custom nav item | `bbcs_tools_tabpanels` |
| Make settings searchable and jumpable | `bbcs_global_search_index` |
| Standard add-on with settings view in manifest | None needed - auto-registered |

## Migration from old `bbcs_snav_groups`

The filter `bbcs_snav_groups` has been **renamed** to `bbcs_global_search_index`. The old name is no longer supported.

1. Change `add_filter( 'bbcs_snav_groups', ... )` → `add_filter( 'bbcs_global_search_index', ... )`
2. Add `'go' => 'page_slug'` to every group and tab entry
3. Convert setting names to tuples: `'Setting Name'` → `array( __( 'Setting Name', 'domain' ), 'prefixed_key' )`
4. Add `->withAnchor( 'prefixed_key' )` to every form element in your templates
5. Use a unique prefix for all keys: `bbcs_{addon-slug}_{setting_name}`
