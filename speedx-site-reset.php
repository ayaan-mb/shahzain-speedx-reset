<?php
/**
 * Plugin Name:       SpeedX Site Reset
 * Description:       Loader file for source-distribution installs. Boots the plugin from /speedx-site-reset.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            SpeedX
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       speedx-site-reset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_main_file = __DIR__ . '/speedx-site-reset/speedx-site-reset.php';

if ( file_exists( $plugin_main_file ) ) {
	require_once $plugin_main_file;
}
