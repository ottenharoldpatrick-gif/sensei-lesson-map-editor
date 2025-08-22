<?php
/**
 * Frontend – shortcode + assets.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\SLME\Frontend' ) ) {

	class Frontend {

		public static function init() {
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
			add_shortcode( 'slme_course_map', [ __CLASS__, 'shortcode_course_map' ] );
		}

		public static function enqueue_frontend_assets() {
			$base_dir = plugin_dir_path( dirname( __FILE__ ) );
			$base_url = plugin_dir_url( dirname( __FILE__ ) );

			$css_path = $base_dir . 'assets/css/frontend.css';
			$css_url  = $base_url . 'assets/css/frontend.css';

			if ( file_exists( $css_path ) ) {
				wp_enqueue_style(
					'slme-frontend',
					$css_url,
					[],
					filemtime( $css_path )
				);
			}
		}

		/**
		 * [slme_course_map course_id=""] – toont kolommen/grid.
		 */
		public static function shortcode_course_map( $atts ) {
			$atts = shortcode_atts( [
				'course_id' => '',
				'view'      => 'grid-columns', // behoud stabiele template-naam
			], $atts, 'slme_course_map' );

			$course_id = absint( $atts['course_id'] );
			if ( ! $course_id ) {
				// Probeer huidige post indien cursus.
				if ( is_singular( 'course' ) ) {
					$course_id = get_the_ID();
				}
			}

			if ( ! $course_id ) {
				return '<div class="slme-notice slme-notice--warn">' .
					esc_html__( 'Geen cursus gevonden voor weergave.', 'slme' ) .
				'</div>';
			}

			// Laad template: templates/grid-columns.php
			$template_file = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/grid-columns.php';

			if ( ! file_exists( $template_file ) ) {
				return '<div class="slme-notice slme-notice--error">' .
					esc_html__( 'Template grid-columns.php ontbreekt.', 'slme' ) .
				'</div>';
			}

			ob_start();
			$slme_course_id = $course_id; // beschikbaar in template
			include $template_file;
			return ob_get_clean();
		}
	}
}
