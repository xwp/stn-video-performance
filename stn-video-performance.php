<?php
/**
 * Plugin Name: STN Video Performance
 * Description: Web vitals improvements for STN Videos.
 * Version:     1.0.0
 * Author:      XWP
 * License:     GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin main file path.
 */
define( 'STNVP_MAIN_FILE', __FILE__ );

/**
 * Initialize the plugin.
 */
function stn_video_performance_init(): void {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';
	// Bootstrap the plugin.
	new \STN\VideoPerformance\Plugin();
}
add_action( 'plugins_loaded', 'stn_video_performance_init' );
