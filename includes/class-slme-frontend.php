<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( __NAMESPACE__ . '\Frontend' ) ) {

	class Frontend {

		public static function init() : void {
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

			// Toon automatisch op Sensei cursuspagina (laat de originele content staan)
			add_filter( 'the_content', [ __CLASS__, 'maybe_inject_course_map' ], 9 );

			// Shortcode voor handmatige plaatsing of testen: [slme_grid_columns course_id="123"]
			add_shortcode( 'slme_grid_columns', [ __CLASS__, 'shortcode' ] );
		}

		public static function enqueue_assets() : void {
			$assets_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';
			wp_register_style( 'slme-frontend', $assets_url . 'frontend.css', [], '1.0.0' );
			wp_enqueue_style( 'slme-frontend' );
		}

		public static function shortcode( $atts ) : string {
			$atts      = shortcode_atts( [ 'course_id' => 0 ], $atts, 'slme_grid_columns' );
			$course_id = intval( $atts['course_id'] ) ?: get_the_ID();
			return self::render_grid( $course_id );
		}

		public static function maybe_inject_course_map( $content ) {
			// Alleen op enkele cursuspagina (post type ‘course’ van Sensei)
			if ( function_exists( 'is_singular' ) && is_singular( 'course' ) && in_the_loop() && is_main_query() ) {
				$map = self::render_grid( get_the_ID() );
				// Als er geen output is (bv. geen modules), laat content ongemoeid
				return $map ? ( $map . $content ) : $content;
			}
			return $content;
		}

		private static function render_grid( int $course_id ) : string {
			if ( ! $course_id || ! function_exists( 'Sensei' ) ) {
				return '';
			}

			$modules = [];
			if ( isset( \Sensei()->modules ) && method_exists( \Sensei()->modules, 'get_course_modules' ) ) {
				$modules = \Sensei()->modules->get_course_modules( $course_id );
			}
			if ( empty( $modules ) ) {
				return ''; // stil falen zoals afgesproken
			}

			$assets_url   = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';
			$is_logged_in = is_user_logged_in();
			$user_id      = get_current_user_id();

			// Maak variabelen beschikbaar voor template
			ob_start();
			$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/grid-columns.php';
			if ( file_exists( $template ) ) {
				// Beschikbaar in template: $course_id, $modules, $assets_url, $is_logged_in, $user_id
				$course_id  = $course_id;
				$modules    = $modules;
				$assets_url = $assets_url;
				$is_logged_in = $is_logged_in;
				$user_id    = $user_id;
				include $template;
			}
			return trim( ob_get_clean() );
		}
	}
}
