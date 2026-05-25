<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = function_exists( 'acme_bbcs_traffic_guard_settings' ) ? acme_bbcs_traffic_guard_settings() : array();
$option   = 'acme_bbcs_traffic_guard_settings';
$icon_url = function_exists( 'acme_bbcs_traffic_guard_asset_url' ) ? acme_bbcs_traffic_guard_asset_url( 'assets/icon.svg' ) : '';
$recent   = get_option( 'acme_bbcs_traffic_guard_recent_matches', array() );
$recent   = is_array( $recent ) ? array_reverse( array_slice( $recent, -5 ) ) : array();
?>
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
        <div class="bbcs-info-inner">
            <?php if ( '' !== $icon_url ) : ?>
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
            <?php else : ?>
                <i class="fa-solid fa-route fa-3x bbcs_color_blue mb-3" aria-hidden="true"></i>
            <?php endif; ?>
            <p class="bbcs-info-text"><?php esc_html_e( 'This add-on demonstrates the dangerous pre-run traffic decision provider contract. It can log or redirect matched traffic inside the BotBlocker request cycle.', 'acme-traffic-guard' ); ?></p>
            <p class="bbcs-info-text"><?php esc_html_e( 'Keep dry-run enabled until every rule is tested. Wrong traffic rules can redirect customers, break payment callbacks, create loops, or hide security pages.', 'acme-traffic-guard' ); ?></p>
            <hr class="bbcs-info-hr">
            <div class="bbcs-info-footer">
                <i class="fa-regular fa-circle-question"></i>
                <a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'acme-traffic-guard' ); ?></a>
                <a href="https://botblocker.top/contacts/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Support', 'acme-traffic-guard' ); ?></a>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Safety', 'acme-traffic-guard' ); ?></h3>

        <div class="notice notice-warning inline">
            <p><strong><?php esc_html_e( 'Critical traffic-control mode.', 'acme-traffic-guard' ); ?></strong> <?php esc_html_e( 'Use this only on a staging site first. Production activation should require change review, dry-run evidence, and rollback steps.', 'acme-traffic-guard' ); ?></p>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="<?php echo esc_attr( $option ); ?>[enabled]" value="0">
                <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable traffic guard', 'acme-traffic-guard' ); ?></span>
            </div>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="<?php echo esc_attr( $option ); ?>[dry_run]" value="0">
                <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[dry_run]" value="1" <?php checked( ! empty( $settings['dry_run'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Dry-run: log matches without redirecting', 'acme-traffic-guard' ); ?></span>
            </div>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="<?php echo esc_attr( $option ); ?>[log_matches]" value="0">
                <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[log_matches]" value="1" <?php checked( ! empty( $settings['log_matches'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Store recent matched routes', 'acme-traffic-guard' ); ?></span>
            </div>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="<?php echo esc_attr( $option ); ?>[allow_legal_bots]" value="0">
                <input type="checkbox" name="<?php echo esc_attr( $option ); ?>[allow_legal_bots]" value="1" <?php checked( ! empty( $settings['allow_legal_bots'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Allow redirects for verified legal bots', 'acme-traffic-guard' ); ?></span>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Route Rule', 'acme-traffic-guard' ); ?></h3>

        <div class="bbcs_select_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Decision stage', 'acme-traffic-guard' ); ?></span>
            </div>
            <div class="bbcs_select_input_inner">
                <select name="<?php echo esc_attr( $option ); ?>[stage]" class="bbcs_select_input_select">
                    <?php foreach ( acme_bbcs_traffic_guard_allowed_stages() as $stage ) : ?>
                        <option value="<?php echo esc_attr( $stage ); ?>" <?php selected( $settings['stage'] ?? '', $stage ); ?>><?php echo esc_html( $stage ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="bbcs_text_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Country code', 'acme-traffic-guard' ); ?></span>
            </div>
            <div class="bbcs_text_input_inner">
                <input type="text" maxlength="2" class="bbcs_text_input_input" name="<?php echo esc_attr( $option ); ?>[country]" value="<?php echo esc_attr( $settings['country'] ?? 'DE' ); ?>">
            </div>
        </div>

        <div class="bbcs_text_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Path prefix', 'acme-traffic-guard' ); ?></span>
            </div>
            <div class="bbcs_text_input_inner">
                <input type="text" class="bbcs_text_input_input" name="<?php echo esc_attr( $option ); ?>[path_prefix]" value="<?php echo esc_attr( $settings['path_prefix'] ?? '/pricing' ); ?>">
            </div>
        </div>

        <div class="bbcs_text_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Target path', 'acme-traffic-guard' ); ?></span>
            </div>
            <div class="bbcs_text_input_inner">
                <input type="text" class="bbcs_text_input_input" name="<?php echo esc_attr( $option ); ?>[target_path]" value="<?php echo esc_attr( $settings['target_path'] ?? '/de/preise/' ); ?>">
            </div>
        </div>

        <div class="bbcs_select_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Redirect status', 'acme-traffic-guard' ); ?></span>
            </div>
            <div class="bbcs_select_input_inner">
                <select name="<?php echo esc_attr( $option ); ?>[redirect_status]" class="bbcs_select_input_select">
                    <?php foreach ( acme_bbcs_traffic_guard_allowed_statuses() as $status ) : ?>
                        <option value="<?php echo esc_attr( (string) $status ); ?>" <?php selected( (int) ( $settings['redirect_status'] ?? 302 ), $status ); ?>><?php echo esc_html( (string) $status ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Recent Matches', 'acme-traffic-guard' ); ?></h3>
        <?php if ( empty( $recent ) ) : ?>
            <p class="bbcs-info-text"><?php esc_html_e( 'No route matches have been stored yet.', 'acme-traffic-guard' ); ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ( $recent as $event ) : ?>
                    <li>
                        <?php
                        echo esc_html(
                            sprintf(
                                '%s %s %s -> %s',
                                gmdate( 'Y-m-d H:i:s', (int) ( $event['time'] ?? 0 ) ),
                                (string) ( $event['action'] ?? '' ),
                                (string) ( $event['uri'] ?? '' ),
                                (string) ( $event['target'] ?? '' )
                            )
                        );
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
