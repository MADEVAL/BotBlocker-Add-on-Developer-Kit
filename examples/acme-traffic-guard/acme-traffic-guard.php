<?php
/**
 * Plugin Name: ACME Traffic Guard for BotBlocker
 * Description: Example traffic decision provider for BotBlocker Add-on API v2.
 * Version: 1.0.0
 * Author: ACME Security
 * Requires-Core: 1.6.20
 * Requires PHP: 7.4
 * Text Domain: acme-traffic-guard
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/inc/core.php';
