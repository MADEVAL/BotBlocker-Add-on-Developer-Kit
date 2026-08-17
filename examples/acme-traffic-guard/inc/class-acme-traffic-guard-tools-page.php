<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools page integration — ACME Traffic Guard.
 *
 * Registers additional sidebar navigation items, tabpanels, and
 * search index entries on the BotBlocker Tools page.
 *
 * @package AcmeTrafficGuard
 */

use BotBlocker\Component\TabItem;

class Acme_Traffic_Guard_Tools_Page {

	/**
	 * Register WordPress hooks for tools page integration.
	 */
	public static function init(): void {
		add_filter( 'bbcs_tools_nav_groups', array( self::class, 'tools_nav_groups' ), 10, 2 );
		add_filter( 'bbcs_tools_tabpanels', array( self::class, 'tools_tabpanels' ), 10, 2 );
		add_filter( 'bbcs_global_search_index', array( self::class, 'global_search_index' ), 10, 1 );
	}

	/**
	 * Register sidebar navigation groups on the Tools page.
	 *
	 * @param array $nav_groups Existing nav groups.
	 * @param array $addon_tabs Active add-on tab data.
	 * @return array
	 */
	public static function tools_nav_groups( array $nav_groups, array $addon_tabs ): array {
		$nav_groups[] = array(
			'title' => __( 'Traffic Guard', 'acme-traffic-guard' ),
			'icon'  => 'shield',
			'items' => array(
				( new TabItem(
					'acme-traffic-routes',
					'',
					false,
					'',
					'',
					__( 'Routes', 'acme-traffic-guard' ),
					'signpost'
				) )->withIconImage(
					class_exists( 'BotBlockerAddons' )
						? BotBlockerAddons::fileUrl( 'acme-traffic-guard', 'assets/icon.svg' )
						: ''
				),
			),
		);
		return $nav_groups;
	}

	/**
	 * Register tabpanel template files for the Tools page.
	 *
	 * @param array $tabpanels Existing tabpanel map.
	 * @param array $addon_tabs Active add-on tab data.
	 * @return array
	 */
	public static function tools_tabpanels( array $tabpanels, array $addon_tabs ): array {
		$tabpanels['acme-traffic-routes'] = dirname( __DIR__ ) . '/inc/tabpanel-routes.php';
		return $tabpanels;
	}

	/**
	 * Register global navigation & command palette search index groups.
	 *
	 * @param array $groups Existing global search index groups.
	 * @return array
	 */
	public static function global_search_index( array $groups ): array {
		$groups[] = array(
			't'    => __( 'Traffic Guard', 'acme-traffic-guard' ),
			'ic'   => 'shield',
			'go'   => 'tools',
			'tabs' => array(
				array(
					't'   => __( 'Route Rules', 'acme-traffic-guard' ),
					'tab' => 'acme-traffic-routes',
					'go'  => 'tools',
					'sg'  => array(
						array(
							't' => __( 'Country Routes', 'acme-traffic-guard' ),
							's' => array(
								array( __( 'Enable traffic guard', 'acme-traffic-guard' ), 'acme_traffic_guard_enabled' ),
								array( __( 'Country code', 'acme-traffic-guard' ), 'acme_traffic_guard_country' ),
								array( __( 'Redirect URL', 'acme-traffic-guard' ), 'acme_traffic_guard_redirect' ),
							),
						),
					),
				),
			),
		);
		return $groups;
	}
}
