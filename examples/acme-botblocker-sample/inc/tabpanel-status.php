<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabpanel — Status for ACME BotBlocker Sample.
 *
 * Renders inside the Tools page as a bbcs-tabpanel.
 *
 * @package AcmeBotBlockerSample
 */

return static function ( Botblocker_ToolsViewModel $data, bool $is_active ): void {
	$settings = function_exists( 'acme_bbcs_sample_settings' ) ? acme_bbcs_sample_settings() : array();
	$icon_url = function_exists( 'acme_bbcs_sample_asset_url' )
		? acme_bbcs_sample_asset_url( 'assets/icon.svg' )
		: '';
?>
<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="acme-sample-status"<?php echo $is_active ? '' : ' hidden'; ?>>
	<h3><?php esc_html_e( 'ACME Sample Status', 'acme-botblocker-sample' ); ?></h3>
	<p><?php esc_html_e( 'Current runtime status of the sample add-on.', 'acme-botblocker-sample' ); ?></p>

	<table class="bbcs-table">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Enabled', 'acme-botblocker-sample' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['enabled'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'Yes', 'acme-botblocker-sample' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge"><?php esc_html_e( 'No', 'acme-botblocker-sample' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Response Header', 'acme-botblocker-sample' ); ?></td>
				<td>
					<code><?php echo esc_html( ( $settings['header_name'] ?? 'X-BotBlocker-Sample' ) . ': ' . ( $settings['header_value'] ?? 'active' ) ); ?></code>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Admin Notice', 'acme-botblocker-sample' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['admin_notice'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'On', 'acme-botblocker-sample' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge"><?php esc_html_e( 'Off', 'acme-botblocker-sample' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Admin Script', 'acme-botblocker-sample' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['admin_script'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'On', 'acme-botblocker-sample' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge"><?php esc_html_e( 'Off', 'acme-botblocker-sample' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Frontend Script', 'acme-botblocker-sample' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['frontend_script'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'On', 'acme-botblocker-sample' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge"><?php esc_html_e( 'Off', 'acme-botblocker-sample' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<?php
};
