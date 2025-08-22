<?php
/**
 * Frontend bootstrap: styles + shortcode [sensei_course_maps]
 * - Enqueue van /assets/frontend.css (let op: geen /css submap!)
 * - Render van kolommenweergave via templates/grid-columns.php
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_shortcode( 'sensei_course_maps', [ __CLASS__, 'shortcode_render' ] );
	}

	/**
	 * CSS enqueue – correcte pad: assets/frontend.css
	 */
	public static function enqueue_assets() {
		$handle = 'slme-frontend';
		$css_url = plugins_url( 'assets/frontend.css', dirname( __DIR__ ) . '/sensei-lesson-map-editor.php' );

		// Fallback als bovenstaande resolutie ooit faalt:
		if ( false === strpos( $css_url, '/assets/frontend.css' ) ) {
			$css_url = plugins_url( 'assets/frontend.css', SLME_PLUGIN_FILE );
		}

		wp_enqueue_style( $handle, $css_url, [], '1.0.0' );
	}

	/**
	 * Shortcode: [sensei_course_maps id="123"]
	 * - id: (optioneel) course ID. Zonder id: huidige course proberen te detecteren.
	 */
	public static function shortcode_render( $atts ) {
		$atts = shortcode_atts( [
			'id' => 0,
		], $atts, 'sensei_course_maps' );

		$course_id = intval( $atts['id'] );
		if ( ! $course_id ) {
			$course_id = get_the_ID();
			// fallback voor single course pages
			if ( 'course' !== get_post_type( $course_id ) && function_exists( 'Sensei' ) ) {
				// probeer via global query de course te vinden (veiligheidshalve)
				$q = get_queried_object();
				if ( $q && ! empty( $q->ID ) && 'course' === get_post_type( $q->ID ) ) {
					$course_id = $q->ID;
				}
			}
		}

		if ( ! $course_id || 'course' !== get_post_type( $course_id ) ) {
			return ''; // niets renderen als we geen geldige cursus hebben
		}

		ob_start();
		$tpl = plugin_dir_path( SLME_PLUGIN_FILE ) . 'templates/grid-columns.php';
		if ( file_exists( $tpl ) ) {
			// variabelen voor template
			$assets_url = plugins_url( 'assets/', SLME_PLUGIN_FILE );
			$is_logged_in = is_user_logged_in();
			$user_id = get_current_user_id();

			// probeer modules + lessen op te halen via Sensei-API met fallbacks
			$modules = [];
			if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_course_modules' ) ) {
				$modules = Sensei()->modules->get_course_modules( $course_id );
			}

			// Fallback: als Sensei-modules niet beschikbaar zijn, toon 0 (houdt template rustig leeg)
			if ( ! is_array( $modules ) ) $modules = [];

			include $tpl;
		}
		return ob_get_clean();
	}
}
