<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acme_bbcs_sample_defaults(): array {
    return array(
        'enabled'      => 1,
        'header_name'  => 'X-BotBlocker-Sample',
        'header_value' => 'active',
        'admin_notice' => 1,
        'admin_script' => 1,
        'frontend_script' => 1,
    );
}

function acme_bbcs_sample_settings(): array {
    $settings = get_option( 'acme_bbcs_sample_settings', array() );

    return array_merge(
        acme_bbcs_sample_defaults(),
        is_array( $settings ) ? $settings : array()
    );
}

function acme_bbcs_sample_sanitize_settings( $raw ): array {
    $raw      = is_array( $raw ) ? $raw : array();
    $defaults = acme_bbcs_sample_defaults();

    $header_name = isset( $raw['header_name'] ) ? preg_replace( '/[^A-Za-z0-9-]/', '', (string) $raw['header_name'] ) : $defaults['header_name'];
    if ( '' === $header_name ) {
        $header_name = $defaults['header_name'];
    }

    $header_value = isset( $raw['header_value'] ) ? sanitize_text_field( (string) $raw['header_value'] ) : $defaults['header_value'];
    if ( '' === $header_value ) {
        $header_value = $defaults['header_value'];
    }

    return array(
        'enabled'         => ! empty( $raw['enabled'] ) ? 1 : 0,
        'header_name'     => $header_name,
        'header_value'    => $header_value,
        'admin_notice'    => ! empty( $raw['admin_notice'] ) ? 1 : 0,
        'admin_script'    => ! empty( $raw['admin_script'] ) ? 1 : 0,
        'frontend_script' => ! empty( $raw['frontend_script'] ) ? 1 : 0,
    );
}

function acme_bbcs_sample_asset_url( string $relative ): string {
    return function_exists( 'bbcs_addon_file_url' )
        ? bbcs_addon_file_url( 'acme-botblocker-sample', $relative )
        : '';
}

function acme_bbcs_sample_activate( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );

    if ( false === get_option( 'acme_bbcs_sample_settings', false ) ) {
        update_option( 'acme_bbcs_sample_settings', acme_bbcs_sample_defaults() );
    }
}

function acme_bbcs_sample_deactivate( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );
}

function acme_bbcs_sample_delete( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event, $slug );

    delete_option( 'acme_bbcs_sample_settings' );
}

function acme_bbcs_sample_send_header(): void {
    if ( headers_sent() ) {
        return;
    }

    $settings = acme_bbcs_sample_settings();
    if ( empty( $settings['enabled'] ) ) {
        return;
    }

    header( $settings['header_name'] . ': ' . $settings['header_value'] );
}

function acme_bbcs_sample_admin_notice(): void {
    $settings = acme_bbcs_sample_settings();
    if ( empty( $settings['enabled'] ) || empty( $settings['admin_notice'] ) ) {
        return;
    }

    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || false === strpos( (string) $screen->id, 'botblocker' ) ) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'ACME BotBlocker Sample Add-on is active.', 'acme-botblocker-sample' ) . '</p></div>';
}

function acme_bbcs_sample_enqueue_admin_assets(): void {
    $settings = acme_bbcs_sample_settings();
    if ( empty( $settings['enabled'] ) || empty( $settings['admin_script'] ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || false === strpos( (string) $screen->id, 'botblocker' ) ) {
        return;
    }

    $url = acme_bbcs_sample_asset_url( 'assets/admin.js' );
    if ( '' === $url ) {
        return;
    }

    wp_enqueue_script( 'acme-bbcs-sample-admin', $url, array(), '1.0.0', true );
    wp_add_inline_script(
        'acme-bbcs-sample-admin',
        'window.acmeBotBlockerSampleAdmin = ' . wp_json_encode(
            array(
                'headerName'  => $settings['header_name'],
                'headerValue' => $settings['header_value'],
            )
        ) . ';',
        'before'
    );
}

function acme_bbcs_sample_enqueue_frontend_assets(): void {
    $settings = acme_bbcs_sample_settings();
    if ( empty( $settings['enabled'] ) || empty( $settings['frontend_script'] ) ) {
        return;
    }

    $url = acme_bbcs_sample_asset_url( 'assets/frontend.js' );
    if ( '' === $url ) {
        return;
    }

    wp_enqueue_script( 'acme-bbcs-sample-frontend', $url, array(), '1.0.0', true );
    wp_add_inline_script(
        'acme-bbcs-sample-frontend',
        'window.acmeBotBlockerSample = ' . wp_json_encode(
            array(
                'enabled'     => ! empty( $settings['enabled'] ),
                'headerName'  => $settings['header_name'],
                'headerValue' => $settings['header_value'],
            )
        ) . ';',
        'before'
    );
}

function acme_bbcs_sample_boot(): void {
    if ( function_exists( 'bbcs_is_addon_active' ) && ! bbcs_is_addon_active( 'acme-botblocker-sample' ) ) {
        return;
    }

    add_action( 'send_headers', 'acme_bbcs_sample_send_header', 50 );
    add_action( 'admin_notices', 'acme_bbcs_sample_admin_notice' );
    add_action( 'admin_enqueue_scripts', 'acme_bbcs_sample_enqueue_admin_assets' );
    add_action( 'wp_enqueue_scripts', 'acme_bbcs_sample_enqueue_frontend_assets' );
}

if ( function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
    acme_bbcs_sample_boot();
} else {
    add_action( 'plugins_loaded', 'acme_bbcs_sample_boot', 20 );
}
