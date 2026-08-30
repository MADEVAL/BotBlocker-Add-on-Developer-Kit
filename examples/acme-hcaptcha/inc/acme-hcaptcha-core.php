<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function acme_hcaptcha_sanitize_settings( array $raw ): array {
	return array(
		'sitekey' => isset( $raw['sitekey'] ) ? sanitize_text_field( (string) $raw['sitekey'] ) : '',
		'secret'  => isset( $raw['secret'] ) ? sanitize_text_field( (string) $raw['secret'] ) : '',
	);
}

function acme_hcaptcha_read_settings(): array {
	$saved = get_option( 'acme_hcaptcha_settings', array() );
	return acme_hcaptcha_sanitize_settings( is_array( $saved ) ? $saved : array() );
}

function acme_hcaptcha_mode_params( int $mode, $bbcs ): array {
	$s = acme_hcaptcha_read_settings();
	return array(
		'mode'   => $mode,
		'params' => array(
			'sitekey' => $s['sitekey'],
			'theme'   => 'light',
			'size'    => 'normal',
		),
	);
}

function acme_hcaptcha_mode_verify( array $post_data, $bbcs ): bool {
	$token = isset( $post_data['h-captcha-response'] ) ? (string) $post_data['h-captcha-response'] : '';
	if ( '' === $token ) {
		return false;
	}
	$s = acme_hcaptcha_read_settings();
	if ( '' === $s['secret'] ) {
		throw new RuntimeException( 'hCaptcha secret is not configured' );
	}

	$body = array(
		'secret'   => $s['secret'],
		'response' => $token,
	);
	if ( is_object( $bbcs ) && ! empty( $bbcs->ip ) ) {
		$body['remoteip'] = (string) $bbcs->ip;
	}

	$resp = wp_remote_post(
		'https://api.hcaptcha.com/siteverify',
		array(
			'body'    => $body,
			'timeout' => 15,
		)
	);
	if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
		throw new RuntimeException( 'hCaptcha verification failed' );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
	if ( ! is_array( $data ) || ! array_key_exists( 'success', $data ) ) {
		throw new RuntimeException( 'hCaptcha returned malformed response' );
	}

	return true === $data['success'];
}
