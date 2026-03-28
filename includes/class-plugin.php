<?php

namespace STN\VideoPerformance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for STN Video Performance enhancements.
 */
final class Plugin {
	/**
	 * Option name for storing settings.
	 */
	const OPTION_NAME = 'stn_video_performance_settings';

	/**
	 * Constructor - Initialize hooks.
	 */
	public function __construct() {
		// Add our performance settings section to the Video Settings page.
		add_action( 'stn_video_settings_sections', [ $this, 'render_performance_settings_section' ] );

		// Save our settings when the shared Video Settings form is saved.
		add_action( 'stn_video_settings_saved', [ $this, 'handle_settings_save' ] );

		// Delay video script loading if configured - hook into shortcode output
		add_filter( 'do_shortcode_tag', [ $this, 'delay_video_script_loading' ], 10, 4 );

		// Add settings link to plugin actions
		add_filter( 'plugin_action_links_' . plugin_basename( STNVP_MAIN_FILE ), [ $this, 'add_plugin_action_links' ] );
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=video-settings' ) ) . '">' . __( 'Settings', 'stn-video-performance' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the performance settings section on the STN Video settings page.
	 */
	public function render_performance_settings_section() {
		$settings = $this->get_settings();
		?>
		<h3><?php esc_html_e( 'Performance Options', 'stn-video-performance' ); ?></h3>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="stn_video_load_delay">
							<?php esc_html_e( 'Video Player Load Delay', 'stn-video-performance' ); ?>
						</label>
					</th>
					<td>
						<input type="number"
								id="stn_video_load_delay"
								name="stn_video_load_delay"
								value="<?php echo esc_attr( $settings['stn_video_load_delay'] ); ?>"
								min="0"
								class="small-text" />
						<span><?php esc_html_e( 'milliseconds', 'stn-video-performance' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'Delay loading of video player scripts to prioritize critical page resources. Set to 0 for immediate loading. Recommended: 1000-3000ms for improved page load performance.', 'stn-video-performance' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handle settings save.
	 */
	public function handle_settings_save() {
		// Nonce and capability already verified by stn-video-meta before this hook fires.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in Settings::handle_settings_save().
		$stn_video_load_delay = isset( $_POST['stn_video_load_delay'] ) ? absint( $_POST['stn_video_load_delay'] ) : 0;

		update_option(
			self::OPTION_NAME,
			[
				'stn_video_load_delay' => $stn_video_load_delay,
			],
			false
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Settings array.
	 */
	private function get_settings() {
		$defaults = [
			'stn_video_load_delay' => 0,
		];

		$settings = get_option( self::OPTION_NAME, [] );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Delay STN Video script loading based on configured delay.
	 *
	 * This filter runs only when the [sendtonews] shortcode is processed.
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag    Shortcode tag name.
	 * @param array|string $attr   Shortcode attributes.
	 * @return string Modified shortcode output with delayed script loading.
	 */
	public function delay_video_script_loading( $output, $tag, $attr ) {
		// Only process sendtonews shortcode.
		if ( 'sendtonews' !== $tag ) {
			return $output;
		}

		// Don't process in admin.
		if ( is_admin() ) {
			return $output;
		}

		$settings = $this->get_settings();
		$delay    = absint( $settings['stn_video_load_delay'] );

		$video_thumbnail = $this->get_stn_video_thumbnail( $attr );// Extract video metadata for enhanced loading experience.

		// If no delay configured, return output unchanged.
		if ( 0 === $delay ) {
			return $output;
		}

		// Find the script tag in the shortcode output and extract its src.
		$script_pattern = '/<script[^>]+src=["\']?([^"\']+)["\']?[^>]*><\/script>/i';

		if ( ! preg_match( $script_pattern, $output, $matches ) ) {
			return $output;
		}

		$script_url = $matches[1];

		// Enforce https on protocol-relative URLs (//embed.sendtonews.com).
		if ( 0 === strpos( $script_url, '//' ) ) {
			$script_url = 'https:' . $script_url;
		}

		$script_url = esc_url_raw( $script_url, [ 'https' ] );

		// Build the delayed script loader.
		$delayed_script = sprintf(
			'<script>
			(function() {
				function loadSTNVideo() {
					var script = document.createElement( "script" );
					script.src = "%s";
					script.async = true;
					script.type = "text/javascript";
					script.setAttribute( "data-type", "s2nScript" );
					document.body.appendChild( script );
				}

				if ( "complete" === document.readyState ) {
					setTimeout( loadSTNVideo, %d );
				} else {
					window.addEventListener( "load", function() {
						setTimeout( loadSTNVideo, %d );
					});
				}
			})();
			</script>',
			esc_js( $script_url ),
			absint( $delay ),
			absint( $delay )
		);

		// CLS optimization: prevent layout shift while video player loads.
		$inline_styles = '<style>
			.s2nPlayer {
				aspect-ratio: 16 / 9;
				background-color: rgba( 0, 0, 0, 0.05 );
				background-size: cover;
				background-position: center;
				background-repeat: no-repeat;
				background-image: var( --background-image );
				padding: 1px;
			}
			';

		// Add the video thumbnail as background if available for better user experience.
		if ( ! empty( $video_thumbnail ) && isset( $attr['key'] ) ) {
			$inline_styles .= '
			.s2nPlayer.k-' . esc_attr( $attr['key'] ) . ' {
				--background-image: url( "' . esc_attr( $video_thumbnail ) . '" );
			}';
		}

		$inline_styles .= '</style>';

		// Replace the script tag when delay is configured.
		if ( $delay > 0 ) {
			$modified_output = preg_replace( $script_pattern, $delayed_script, $output );
		} else {
			$modified_output = $output;
		}

		return $inline_styles . $modified_output;
	}

	/**
	 * Get STN video thumbnail from stored metadata.
	 *
	 * Retrieves the thumbnail URL from the STN Video Meta plugin's stored data.
	 * This works for both shortcode and block embeds that have been processed.
	 *
	 * @param array|string $attr Shortcode attributes.
	 * @return string Thumbnail URL or empty string if not found.
	 */
	private function get_stn_video_thumbnail( $attr ) {
		// Get the current post ID.
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		// Parse attributes if they're a string.
		if ( is_string( $attr ) ) {
			$attr = shortcode_parse_atts( $attr );
		}
		if ( ! is_array( $attr ) ) {
			return '';
		}

		// Extract the video key from attributes.
		$video_key = isset( $attr['key'] ) ? sanitize_text_field( $attr['key'] ) : '';
		if ( empty( $video_key ) ) {
			return '';
		}

		// Check if this post has STN video metadata processed.
		$status = get_post_meta( $post_id, 'stnvm_status', true );
		if ( 'ok' !== $status ) {
			return '';
		}

		// Check if this specific key is in the processed keys.
		$stored_keys = (array) get_post_meta( $post_id, 'stnvm_keys', true );
		if ( ! in_array( $video_key, $stored_keys, true ) ) {
			return '';
		}

		// Get the schema JSON data stored by STN Video Meta plugin.
		$schema_meta_key = apply_filters( 'stnvm_schema_meta_key', 'hvy_video_schema_data' );
		$schema_json     = get_post_meta( $post_id, $schema_meta_key, true );

		if ( empty( $schema_json ) || ! is_string( $schema_json ) ) {
			return '';
		}

		// Decode the JSON to extract thumbnail.
		$schema_data = json_decode( $schema_json, true );
		if ( ! is_array( $schema_data ) ) {
			return '';
		}

		// Extract thumbnail URL (stored as array in schema).
		if ( ! empty( $schema_data['thumbnailUrl'] ) && is_array( $schema_data['thumbnailUrl'] ) ) {
			$thumbnail = reset( $schema_data['thumbnailUrl'] ); // Get first thumbnail.
			if ( is_string( $thumbnail ) && ! empty( $thumbnail ) ) {
				// Ensure HTTPS for protocol-relative URLs.
				if ( 0 === strpos( $thumbnail, '//' ) ) {
					$thumbnail = 'https:' . $thumbnail;
				}
				return $thumbnail;
			}
		}

		return '';
	}
}
