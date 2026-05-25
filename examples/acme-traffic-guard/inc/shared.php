<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function acme_bbcs_traffic_guard_defaults(): array {
    return array(
        'enabled'          => 0,
        'dry_run'          => 1,
        'log_matches'      => 1,
        'stage'            => 'post_core_rules',
        'country'          => 'DE',
        'path_prefix'      => '/pricing',
        'target_path'      => '/de/preise/',
        'redirect_status'  => 302,
        'allow_legal_bots' => 0,
    );
}

function acme_bbcs_traffic_guard_allowed_stages(): array {
    return array(
        'after_visitor_data',
        'pre_core_rules',
        'post_core_rules',
        'before_final_allow',
    );
}

function acme_bbcs_traffic_guard_allowed_statuses(): array {
    return array( 301, 302, 303, 307, 308 );
}

function acme_bbcs_traffic_guard_settings(): array {
    $settings = get_option( 'acme_bbcs_traffic_guard_settings', array() );

    return acme_bbcs_traffic_guard_sanitize_settings(
        array_merge(
            acme_bbcs_traffic_guard_defaults(),
            is_array( $settings ) ? $settings : array()
        )
    );
}

function acme_bbcs_traffic_guard_sanitize_path( string $value, string $fallback ): string {
    $clean = preg_replace( '/[\x00-\x1F\x7F]/', '', $value );
    $value = trim( is_string( $clean ) ? $clean : '' );
    if ( '' === $value || '/' !== $value[0] || false !== strpos( $value, '//' ) ) {
        return $fallback;
    }

    return substr( $value, 0, 180 );
}

function acme_bbcs_traffic_guard_sanitize_settings( $raw ): array {
    $raw      = is_array( $raw ) ? $raw : array();
    $defaults = acme_bbcs_traffic_guard_defaults();

    $country = isset( $raw['country'] ) ? strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $raw['country'] ) ) : $defaults['country'];
    if ( ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
        $country = $defaults['country'];
    }

    $stage = isset( $raw['stage'] ) ? sanitize_key( (string) $raw['stage'] ) : $defaults['stage'];
    if ( ! in_array( $stage, acme_bbcs_traffic_guard_allowed_stages(), true ) ) {
        $stage = $defaults['stage'];
    }

    $status = isset( $raw['redirect_status'] ) ? (int) $raw['redirect_status'] : $defaults['redirect_status'];
    if ( ! in_array( $status, acme_bbcs_traffic_guard_allowed_statuses(), true ) ) {
        $status = $defaults['redirect_status'];
    }

    return array(
        'enabled'          => ! empty( $raw['enabled'] ) ? 1 : 0,
        'dry_run'          => ! empty( $raw['dry_run'] ) ? 1 : 0,
        'log_matches'      => ! empty( $raw['log_matches'] ) ? 1 : 0,
        'stage'            => $stage,
        'country'          => $country,
        'path_prefix'      => acme_bbcs_traffic_guard_sanitize_path( (string) ( $raw['path_prefix'] ?? $defaults['path_prefix'] ), $defaults['path_prefix'] ),
        'target_path'      => acme_bbcs_traffic_guard_sanitize_path( (string) ( $raw['target_path'] ?? $defaults['target_path'] ), $defaults['target_path'] ),
        'redirect_status'  => $status,
        'allow_legal_bots' => ! empty( $raw['allow_legal_bots'] ) ? 1 : 0,
    );
}

function acme_bbcs_traffic_guard_is_safe_runtime_request( BotBlocker $bbcs, array $settings ): bool {
    if ( function_exists( 'is_admin' ) && is_admin() ) {
        return false;
    }

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return false;
    }

    if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
        return false;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return false;
    }

    if ( ! empty( $bbcs->payment_bypass_reason ) ) {
        return false;
    }

    if (
        ! empty( $bbcs->should_show_check_page )
        || ! empty( $bbcs->should_show_block_page )
        || ! empty( $bbcs->should_show_denied_page )
    ) {
        return false;
    }

    $method = isset( $bbcs->request_method ) ? strtoupper( (string) $bbcs->request_method ) : '';
    if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
        return false;
    }

    if ( empty( $settings['allow_legal_bots'] ) && class_exists( 'BotBlockerBase' ) ) {
        $visitor_type = isset( $bbcs->visitorType ) ? (int) $bbcs->visitorType : BotBlockerBase::VISITOR_UNDEFINED;
        if ( in_array( $visitor_type, array( BotBlockerBase::VISITOR_LEGALBOT, BotBlockerBase::VISITOR_ADMIN, BotBlockerBase::VISITOR_BOTBLOCKER ), true ) ) {
            return false;
        }
    }

    return true;
}

function acme_bbcs_traffic_guard_current_path( BotBlocker $bbcs ): string {
    $uri = isset( $bbcs->uri ) ? (string) $bbcs->uri : '';
    if ( '' === $uri ) {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
    }

    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    return '' !== $path ? $path : '/';
}

function acme_bbcs_traffic_guard_same_target( string $current_path, string $target_path ): bool {
    return untrailingslashit( $current_path ) === untrailingslashit( $target_path );
}

function acme_bbcs_traffic_guard_matches( BotBlocker $bbcs, array $settings ): bool {
    $country = isset( $bbcs->country ) ? strtoupper( (string) $bbcs->country ) : '';
    if ( $country !== $settings['country'] ) {
        return false;
    }

    $current_path = acme_bbcs_traffic_guard_current_path( $bbcs );
    if ( 0 !== strpos( $current_path, (string) $settings['path_prefix'] ) ) {
        return false;
    }

    return ! acme_bbcs_traffic_guard_same_target( $current_path, (string) $settings['target_path'] );
}

function acme_bbcs_traffic_guard_log_match( BotBlocker $bbcs, array $settings, string $action ): void {
    if ( empty( $settings['log_matches'] ) ) {
        return;
    }

    $events = get_option( 'acme_bbcs_traffic_guard_recent_matches', array() );
    $events = is_array( $events ) ? $events : array();

    $ip = isset( $bbcs->ip ) ? (string) $bbcs->ip : '';
    $events[] = array(
        'time'     => time(),
        'cid'      => isset( $bbcs->cid ) ? sanitize_text_field( (string) $bbcs->cid ) : '',
        'ip_hash'  => '' !== $ip ? hash( 'sha256', $ip . wp_salt( 'auth' ) ) : '',
        'country'  => isset( $bbcs->country ) ? sanitize_text_field( (string) $bbcs->country ) : '',
        'uri'      => isset( $bbcs->uri ) ? sanitize_text_field( (string) $bbcs->uri ) : '',
        'stage'    => sanitize_key( (string) $settings['stage'] ),
        'action'   => sanitize_key( $action ),
        'target'   => sanitize_text_field( (string) $settings['target_path'] ),
    );

    update_option( 'acme_bbcs_traffic_guard_recent_matches', array_slice( $events, -20 ), false );
}
