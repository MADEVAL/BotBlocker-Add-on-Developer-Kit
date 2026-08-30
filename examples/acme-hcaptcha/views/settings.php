<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$acme_hcaptcha_settings = acme_hcaptcha_read_settings();
?>
<div class="bbcs-protect-layout">
	<div>
		<p>
			<?php echo esc_html__( 'Enter your hCaptcha keys (from dashboard.hcaptcha.com), then select CAPTCHA mode 90 (ACME hCaptcha) in BotBlocker settings.', 'botblocker-security' ); ?>
		</p>
		<p>
			<label>Site Key<br>
				<input type="text" name="acme_hcaptcha_settings[sitekey]" value="<?php echo esc_attr( $acme_hcaptcha_settings['sitekey'] ); ?>" class="regular-text">
			</label>
		</p>
		<p>
			<label>Secret Key<br>
				<input type="text" name="acme_hcaptcha_settings[secret]" value="<?php echo esc_attr( $acme_hcaptcha_settings['secret'] ); ?>" class="regular-text">
			</label>
		</p>
	</div>
</div>
