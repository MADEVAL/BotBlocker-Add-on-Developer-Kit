<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = acme_hcaptcha_read_settings();
$option   = 'acme_hcaptcha_settings';
$icon_url = function_exists( 'acme_hcaptcha_asset_url' ) ? acme_hcaptcha_asset_url( 'acme-hcaptcha.svg' ) : '';
?>
<div class="row">
	<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12 bbcs-info-column">
		<div class="bbcs-info-inner">
			<?php if ( '' !== $icon_url ) : ?>
				<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="img-fluid bbcs-info-image mb-3">
			<?php else : ?>
				<i class="fa-solid fa-shield-halved fa-3x bbcs_color_green mb-3" aria-hidden="true"></i>
			<?php endif; ?>
			<p class="bbcs-info-text"><?php esc_html_e( 'Registers hCaptcha as an external CAPTCHA provider (mode 90) for the BotBlocker check page.', 'acme-hcaptcha' ); ?></p>
			<p class="bbcs-info-text"><?php esc_html_e( 'Enter the site key and secret from your hCaptcha dashboard, then select mode 90 (ACME hCaptcha) in BotBlocker settings. Verification runs server-side; a provider outage degrades to the simple CAPTCHA instead of banning visitors.', 'acme-hcaptcha' ); ?></p>
			<hr class="bbcs-info-hr">
			<div class="bbcs-info-footer">
				<i class="fa-regular fa-circle-question"></i>
				<a href="https://dashboard.hcaptcha.com/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'hCaptcha dashboard', 'acme-hcaptcha' ); ?></a>
				<a href="https://botblocker.top/docs/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a"><?php esc_html_e( 'BotBlocker docs', 'acme-hcaptcha' ); ?></a>
			</div>
		</div>
	</div>

	<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12">
		<h3 class="bbcs_settings_h3"><?php esc_html_e( 'hCaptcha', 'acme-hcaptcha' ); ?></h3>

		<div class="bbcs_text_input mb-2">
			<div class="bbcs_label_input_box">
				<span class="bbcs-label-input"><?php esc_html_e( 'Site key', 'acme-hcaptcha' ); ?></span>
			</div>
			<div class="bbcs_text_input_inner">
				<input type="text" name="<?php echo esc_attr( $option ); ?>[sitekey]" class="bbcs_text_input_input" value="<?php echo esc_attr( $settings['sitekey'] ); ?>" placeholder="10000000-ffff-ffff-ffff-000000000001">
			</div>
		</div>

		<div class="bbcs_text_input mb-2">
			<div class="bbcs_label_input_box">
				<span class="bbcs-label-input"><?php esc_html_e( 'Secret key', 'acme-hcaptcha' ); ?></span>
			</div>
			<div class="bbcs_text_input_inner">
				<input type="text" name="<?php echo esc_attr( $option ); ?>[secret]" class="bbcs_text_input_input" value="<?php echo esc_attr( $settings['secret'] ); ?>" placeholder="0x...">
			</div>
		</div>
	</div>
</div>
