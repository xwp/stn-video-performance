<?php
/**
 * Plugin Name: STN Video Performance
 * Description: Web vitals improvements for STN Videos.
 * Version:     1.0.0
 * Author:      XWP
 * License:     GPL-2.0-or-later
 * Requires Plugin: sendtonews/sendtonews.php
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
 * Checks if a required plugin is active.
 *
 * @param string $plugin Plugin folder/name.php.
 * @return bool
 */
function stn_video_performance_is_plugin_active( $plugin ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	return is_plugin_active( $plugin );
}

/**
 * Activation hook to check for required plugins.
 */
function stn_video_performance_activate() {
	$required_plugin = 'sendtonews/sendtonews.php';

	if ( ! stn_video_performance_is_plugin_active( $required_plugin ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		wp_die(
			sprintf(
				wp_kses(
					/* translators: 1: Plugin Name, 2: Required Plugin Name */
					__( 'Sorry, but <strong>%1$s</strong> requires <strong>%2$s</strong> to be installed and active.', 'stn-video-performance' ),
					[ 'strong' => [] ]
				),
				'<strong>' . esc_html( __( 'STN Video Performance', 'stn-video-performance' ) ) . '</strong>',
				'<strong>' . esc_html( __( 'STN Video Player Selector', 'stn-video-performance' ) ) . '</strong>'
			),
			esc_html( __( 'Plugin Dependency Check', 'stn-video-performance' ) ),
			[ 'back_link' => true ]
		);
	}
}

register_activation_hook( __FILE__, 'stn_video_performance_activate' );

/**
 * Initialize the plugin.
 */
function stn_video_performance_init(): void {
	// Ensure the required plugin is active
	$required_plugin = 'sendtonews/sendtonews.php';
	if ( ! stn_video_performance_is_plugin_active( $required_plugin ) ) {
		return;
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';
	// Bootstrap the plugin.
	new \STN\VideoPerformance\Plugin();
}
add_action( 'plugins_loaded', 'stn_video_performance_init' );
