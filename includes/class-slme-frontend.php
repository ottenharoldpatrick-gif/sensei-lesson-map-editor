<?php
/**
 * Frontend – shortcode en assets voor de voorkant.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Frontend {

	public static function init() : void {
		add_shortcode( 'sensei_lesson_map', [ __CLASS__, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	/**
	 * Alleen registreren; pas enqueuen als de shortcode rendert.
	 */
	public static function register_assets() : void {
		$version = defined( 'SLME_VERSION' ) ? SLME_VERSION : '0.2.7';

		wp_register_style(
			'slme-frontend',
			plugins_url( '../assets/frontend.css', __FILE__ ),
			[],
			$version
		);

		// frontend.js alleen registreren als het bestand echt bestaat
		$frontend_js_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/frontend.js';
		if ( file_exists( $frontend_js_path ) ) {
			wp_register_script(
				'slme-frontend',
				plugins_url( '../assets/frontend.js', __FILE__ ),
				[ 'jquery' ],
				$version,
				true
			);
		}
	}

	public static function render_shortcode( $atts = [] ) : string {
		$atts = shortcode_atts(
			[
				'course_id' => get_the_ID(),
				'display'   => 'columns', // standaard
			],
			$atts,
			'sensei_lesson_map'
		);

		// Assets alleen nu enqueuen (want de shortcode staat er)
		wp_enqueue_style( 'slme-frontend' );
		if ( wp_script_is( 'slme-frontend', 'registered' ) ) {
			wp_enqueue_script( 'slme-frontend' );
		}

		$course_id = (int) $atts['course_id'];
		$display   = (string) $atts['display'];

		$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/grid-columns.php';

		ob_start();

		if ( file_exists( $template ) ) {
			// Variabelen beschikbaar maken in het template
			$slme_course_id = $course_id;
			$slme_display   = $display;
			include $template;
		} else {
			echo '<div class="slme-warning">SLME: template <code>templates/grid-columns.php</code> niet gevonden.</div>';
		}

		return (string) ob_get_clean();
	}
}
