<?php
/**
 * Admin – menu + admin assets.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\SLME\Admin' ) ) {

	class Admin {

		const CAPABILITY = 'manage_options';
		const MENU_SLUG  = 'slme-course-maps';

		public static function init() {
			add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		}

		public static function add_menu() {
			add_menu_page(
				__( 'Sensei Cursus Maps', 'slme' ),
				__( 'Sensei Cursus Maps', 'slme' ),
				self::CAPABILITY,
				self::MENU_SLUG,
				[ __CLASS__, 'render_page' ],
				'dashicons-screenoptions',
				56
			);
		}

		public static function enqueue_admin_assets( $hook ) {
			// Alleen op onze eigen admin-pagina.
			if ( strpos( $hook, self::MENU_SLUG ) === false ) {
				return;
			}

			$base_url = plugin_dir_url( dirname( __FILE__ ) );

			// Houd stabiele bestandsnamen aan:
			wp_enqueue_style(
				'slme-admin',
				$base_url . 'assets/css/admin.css',
				[],
				filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/css/admin.css' )
			);

			wp_enqueue_script(
				'slme-admin-editor',
				$base_url . 'assets/js/admin-editor.js',
				[ 'jquery' ],
				filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/admin-editor.js' ),
				true
			);

			wp_localize_script( 'slme-admin-editor', 'SLMEAdmin', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'slme_admin_nonce' ),
			] );
		}

		public static function render_page() {
			if ( ! current_user_can( self::CAPABILITY ) ) {
				wp_die( __( 'Je hebt geen toegang tot deze pagina.', 'slme' ) );
			}

			echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'Sensei Cursus Maps', 'slme' ) . '</h1>';
			echo '<p>' . esc_html__( 'Beheer hier de weergave-instellingen voor cursus maps en kolommen.', 'slme' ) . '</p>';

			// Eenvoudige placeholder – jouw bestaande editor-HTML kan hier blijven/terugkomen.
			echo '<div id="slme-admin-editor-root"></div>';

			echo '</div>';
		}
	}
}
