<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/shared.php';

define( 'ACME_BBCS_TRAFFIC_GUARD_PRE_RUN_READY', true );

function acme_bbcs_traffic_guard_pre_run_register( array $addon, array $context, string $event, string $slug ): void {
    unset( $addon, $context, $event );

    if ( function_exists( 'bbcs_register_traffic_decision_provider' ) ) {
        bbcs_register_traffic_decision_provider( $slug, 'acme_bbcs_traffic_guard_decide', 20 );
    }
}

function acme_bbcs_traffic_guard_decide( BotBlocker $bbcs, string $stage, array $provider ): ?array {
    $settings = acme_bbcs_traffic_guard_settings();

    if ( empty( $settings['enabled'] ) || $stage !== $settings['stage'] ) {
        return null;
    }

    if ( ! acme_bbcs_traffic_guard_is_safe_runtime_request( $bbcs, $settings ) ) {
        return null;
    }

    if ( ! acme_bbcs_traffic_guard_matches( $bbcs, $settings ) ) {
        return null;
    }

    if ( ! empty( $settings['dry_run'] ) ) {
        acme_bbcs_traffic_guard_log_match( $bbcs, $settings, 'log_only' );

        return array(
            'action' => 'log_only',
            'code'   => 901,
            'reason' => 'ACME Traffic Guard dry-run route matched',
            'source' => $provider['slug'] ?? 'acme-traffic-guard',
        );
    }

    acme_bbcs_traffic_guard_log_match( $bbcs, $settings, 'redirect' );

    return array(
        'action' => 'redirect',
        'url'    => home_url( (string) $settings['target_path'] ),
        'status' => (int) $settings['redirect_status'],
        'reason' => 'ACME Traffic Guard route matched',
        'source' => $provider['slug'] ?? 'acme-traffic-guard',
    );
}
