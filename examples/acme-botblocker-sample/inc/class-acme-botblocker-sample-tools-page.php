<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools page integration — ACME BotBlocker Sample.
 *
 * Demonstrates how an add-on registers additional navigation items,
 * tabpanels, and sidebar search entries on the BotBlocker Tools page.
 *
 * @package AcmeBotBlockerSample
 */

use BotBlocker\Component\TabItem;

class Acme_BotBlocker_Sample_Tools_Page {

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
			'title' => __( 'ACME Sample', 'acme-botblocker-sample' ),
			'icon'  => 'puzzle',
			'items' => array(
				( new TabItem(
					'acme-sample-status',
					'',
					false,
					'',
					'',
					__( 'Status', 'acme-botblocker-sample' ),
					'chart'
				) )->withIconImage(
					class_exists( 'BotBlockerAddons' )
						? BotBlockerAddons::fileUrl( 'acme-botblocker-sample', 'assets/icon.svg' )
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
		$tabpanels['acme-sample-status'] = dirname( __DIR__ ) . '/inc/tabpanel-status.php';
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
			't'    => __( 'ACME Sample', 'acme-botblocker-sample' ),
			'ic'   => 'puzzle',
			'go'   => 'tools',
			'tabs' => array(
				array(
					't'   => __( 'Sample Settings', 'acme-botblocker-sample' ),
					'tab' => 'acme-sample-status',
					'go'  => 'tools',
					'sg'  => array(
						array(
							't' => __( 'Main', 'acme-botblocker-sample' ),
							's' => array(
								array( __( 'Enable sample response header', 'acme-botblocker-sample' ), 'acme_sample_enable_header' ),
								array( __( 'Header name', 'acme-botblocker-sample' ), 'acme_sample_header_name' ),
								array( __( 'Header value', 'acme-botblocker-sample' ), 'acme_sample_header_value' ),
							),
						),
						array(
							't' => __( 'Runtime', 'acme-botblocker-sample' ),
							's' => array(
								array( __( 'Show an admin notice on BotBlocker screens', 'acme-botblocker-sample' ), 'acme_sample_admin_notice' ),
								array( __( 'Load the sample admin script on BotBlocker screens', 'acme-botblocker-sample' ), 'acme_sample_admin_script' ),
								array( __( 'Load the sample frontend script for visitors', 'acme-botblocker-sample' ), 'acme_sample_frontend_script' ),
							),
						),
					),
				),
			),
		);
		return $groups;
	}
}
