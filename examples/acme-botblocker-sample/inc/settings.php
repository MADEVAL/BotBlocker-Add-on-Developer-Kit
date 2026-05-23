<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = function_exists( 'acme_bbcs_sample_settings' ) ? acme_bbcs_sample_settings() : array();
$icon_url = function_exists( 'acme_bbcs_sample_asset_url' ) ? acme_bbcs_sample_asset_url( 'assets/icon.svg' ) : '';
?>
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
        <div class="bbcs-info-inner">
            <?php if ( '' !== $icon_url ) : ?>
                <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                <img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
            <?php else : ?>
                <i class="fa-solid fa-puzzle-piece fa-3x bbcs_color_blue mb-3" aria-hidden="true"></i>
            <?php endif; ?>
            <p class="bbcs-info-text"><?php esc_html_e( 'Demonstrates the recommended BotBlocker Add-on API v2 structure with manifest metadata, runtime hooks, settings storage, lifecycle callbacks, icon, and optional scripts.', 'acme-botblocker-sample' ); ?></p>
            <p class="bbcs-info-text"><?php esc_html_e( 'Use this layout for add-ons that need a clear settings introduction. Put the icon first, then concise help text, then useful links.', 'acme-botblocker-sample' ); ?></p>
            <hr class="bbcs-info-hr">
            <div class="bbcs-info-footer">
                <i class="fa-regular fa-circle-question"></i>
                <a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'acme-botblocker-sample' ); ?></a>
                <a href="https://wordpress.org/plugins/botblocker-security/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Plugin page', 'acme-botblocker-sample' ); ?></a>
                <a href="https://botblocker.top/contacts/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'Support', 'acme-botblocker-sample' ); ?></a>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Main', 'acme-botblocker-sample' ); ?></h3>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="acme_bbcs_sample_settings[enabled]" value="0">
                <input type="checkbox" name="acme_bbcs_sample_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Enable sample response header', 'acme-botblocker-sample' ); ?></span>
            </div>
        </div>

        <div class="bbcs_text_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Header name', 'acme-botblocker-sample' ); ?></span>
            </div>
            <div class="bbcs_text_input_inner">
                <input id="acme-bbcs-sample-header-name" type="text" class="bbcs_text_input_input" name="acme_bbcs_sample_settings[header_name]" value="<?php echo esc_attr( $settings['header_name'] ?? 'X-BotBlocker-Sample' ); ?>">
            </div>
        </div>

        <div class="bbcs_text_input mb-2">
            <div class="bbcs_label_input_box">
                <span class="bbcs-label-input"><?php esc_html_e( 'Header value', 'acme-botblocker-sample' ); ?></span>
            </div>
            <div class="bbcs_text_input_inner">
                <input id="acme-bbcs-sample-header-value" type="text" class="bbcs_text_input_input" name="acme_bbcs_sample_settings[header_value]" value="<?php echo esc_attr( $settings['header_value'] ?? 'active' ); ?>">
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
        <h3 class="bbcs_settings_h3"><?php esc_html_e( 'Runtime', 'acme-botblocker-sample' ); ?></h3>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="acme_bbcs_sample_settings[admin_notice]" value="0">
                <input type="checkbox" name="acme_bbcs_sample_settings[admin_notice]" value="1" <?php checked( ! empty( $settings['admin_notice'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Show an admin notice on BotBlocker screens', 'acme-botblocker-sample' ); ?></span>
            </div>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="acme_bbcs_sample_settings[admin_script]" value="0">
                <input type="checkbox" name="acme_bbcs_sample_settings[admin_script]" value="1" <?php checked( ! empty( $settings['admin_script'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Load the sample admin script on BotBlocker screens', 'acme-botblocker-sample' ); ?></span>
            </div>
        </div>

        <div class="bbcs_checkbox_input mb-2">
            <div class="bbcs_label_checkbox_box">
                <input type="hidden" name="acme_bbcs_sample_settings[frontend_script]" value="0">
                <input type="checkbox" name="acme_bbcs_sample_settings[frontend_script]" value="1" <?php checked( ! empty( $settings['frontend_script'] ) ); ?>>
                <span class="bbcs_label_input_checkbox"><?php esc_html_e( 'Load the sample frontend script for visitors', 'acme-botblocker-sample' ); ?></span>
            </div>
        </div>
    </div>
</div>
