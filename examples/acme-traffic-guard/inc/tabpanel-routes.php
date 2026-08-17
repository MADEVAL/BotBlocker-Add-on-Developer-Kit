<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabpanel — Routes for ACME Traffic Guard.
 *
 * Renders inside the Tools page as a bbcs-tabpanel.
 *
 * @package AcmeTrafficGuard
 */

return static function ( Botblocker_ToolsViewModel $data, bool $is_active ): void {
	$settings = function_exists( 'acme_bbcs_traffic_guard_settings' )
		? acme_bbcs_traffic_guard_settings()
		: array();
	$icon_url = class_exists( 'BotBlockerAddons' )
		? BotBlockerAddons::fileUrl( 'acme-traffic-guard', 'assets/icon.svg' )
		: '';
?>
<div role="tabpanel" class="bbcs-tabpanel" data-tabpanel="acme-traffic-routes"<?php echo $is_active ? '' : ' hidden'; ?>>
	<h3><?php esc_html_e( 'Traffic Guard Routes', 'acme-traffic-guard' ); ?></h3>
	<p><?php esc_html_e( 'Current country-based traffic routing rules.', 'acme-traffic-guard' ); ?></p>

	<table class="bbcs-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Status', 'acme-traffic-guard' ); ?></th>
				<th><?php esc_html_e( 'Value', 'acme-traffic-guard' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Enabled', 'acme-traffic-guard' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['enabled'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'Active', 'acme-traffic-guard' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge"><?php esc_html_e( 'Disabled', 'acme-traffic-guard' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Dry Run Mode', 'acme-traffic-guard' ); ?></td>
				<td>
					<?php if ( ! empty( $settings['dry_run'] ) ) : ?>
						<span class="bbcs-badge bbcs_color_red"><?php esc_html_e( 'Log Only', 'acme-traffic-guard' ); ?></span>
					<?php else : ?>
						<span class="bbcs-badge bbcs_color_green"><?php esc_html_e( 'Live', 'acme-traffic-guard' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Provider Stage', 'acme-traffic-guard' ); ?></td>
				<td><code><?php echo esc_html( $settings['stage'] ?? 'pre_core_rules' ); ?></code></td>
			</tr>
		</tbody>
	</table>
</div>
<?php
};
