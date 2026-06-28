<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/shared.php';

function acme_bbcs_traffic_guard_asset_url( string $relative ): string {
    return class_exists( 'BotBlockerAddons' )
        ? BotBlockerAddons::fileUrl( 'acme-traffic-guard', $relative )
        : '';
}

function acme_bbcs_traffic_guard_activate( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );

    if ( false === get_option( 'acme_bbcs_traffic_guard_settings', false ) ) {
        update_option( 'acme_bbcs_traffic_guard_settings', acme_bbcs_traffic_guard_defaults() );
    }
}

function acme_bbcs_traffic_guard_deactivate( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );
}

function acme_bbcs_traffic_guard_delete( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );

    delete_option( 'acme_bbcs_traffic_guard_settings' );
    delete_option( 'acme_bbcs_traffic_guard_recent_matches' );
}

function acme_bbcs_traffic_guard_admin_notice(): void {
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || false === strpos( (string) $screen->id, 'botblocker' ) ) {
        return;
    }

    $settings = acme_bbcs_traffic_guard_settings();
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    $message = ! empty( $settings['dry_run'] )
        ? __( 'ACME Traffic Guard is enabled in dry-run mode. Matched traffic is logged, not redirected.', 'acme-traffic-guard' )
        : __( 'ACME Traffic Guard is actively redirecting matched traffic. Review rules carefully.', 'acme-traffic-guard' );

    echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
}

function acme_bbcs_traffic_guard_boot(): void {
    if ( class_exists( 'BotBlockerAddons' ) && ! BotBlockerAddons::isActive( 'acme-traffic-guard' ) ) {
        return;
    }

    add_action( 'admin_notices', 'acme_bbcs_traffic_guard_admin_notice' );
}

if ( function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
    acme_bbcs_traffic_guard_boot();
} else {
    add_action( 'plugins_loaded', 'acme_bbcs_traffic_guard_boot', 20 );
}
