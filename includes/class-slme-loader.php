<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) exit;

class Loader {

	public static function init() {
		self::load_files();
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
		add_shortcode( 'slme_course_map', [ __CLASS__, 'render_shortcode' ] );
	}

	/** Pad naar plugin-root (…/sensei-lesson-map-editor-main) */
	protected static function base_dir() {
		return dirname( __DIR__ );
	}

	/** URL naar /assets/ (plat, geen submappen verplicht) */
	protected static function assets_url() {
		return plugin_dir_url( dirname( __FILE__ ) ) . 'assets/';
	}

	/** Frontend CSS inschieten */
	public static function enqueue_frontend() {
		$css = self::assets_url() . 'frontend.css';
		wp_enqueue_style( 'slme-frontend', $css, [], '2025-08-22' );
	}

	/** Bestanden laden met juiste paden */
	protected static function load_files() {
		$base = self::base_dir();

		// Admin (menu "Sensei Cursus Maps", schermen, etc.)
		$admin = $base . '/admin/class-slme-admin.php';
		if ( file_exists( $admin ) ) {
			require_once $admin;
		}

		// AJAX handlers
		$ajax = $base . '/includes/class-slme-ajax.php';
		if ( file_exists( $ajax ) ) {
			require_once $ajax;
		}

		// Frontend helpers (optioneel)
		$frontend = $base . '/includes/class-slme-frontend.php';
		if ( file_exists( $frontend ) ) {
			require_once $frontend;
		}
	}

	/** Shortcode renderer */
	public static function render_shortcode( $atts = [] ) {
		if ( ! function_exists( 'Sensei' ) ) {
			return '';
		}

		global $post;
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'slme_course_map' );
		$course_id = intval( $atts['id'] );

		// Op een cursuspagina: pak het huidige ID als dat nog niet is meegegeven
		if ( ! $course_id && $post && isset( $post->ID ) ) {
			// Let op: Sensei gebruikt meestal post_type 'course'
			$course_id = $post->ID;
		}

		if ( ! $course_id ) {
			return '';
		}

		$modules = [];
		if ( isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_course_modules' ) ) {
			$modules = Sensei()->modules->get_course_modules( $course_id );
		}

		$assets_url   = self::assets_url();
		$is_logged_in = is_user_logged_in();
		$user_id      = get_current_user_id();

		$template = self::base_dir() . '/templates/grid-columns.php';
		if ( ! file_exists( $template ) ) {
			return '';
		}

		ob_start();
		// Maak variabelen beschikbaar in de template-scope
		/** @var int $course_id */
		/** @var array $modules */
		/** @var string $assets_url */
		/** @var bool $is_logged_in */
		/** @var int $user_id */
		include $template;
		return ob_get_clean();
	}
}
