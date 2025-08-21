<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLME_Assets {
	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
		add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'frontend_assets' ] );
	}
	public static function admin_assets( $hook ) {
		// Alleen op cursus bewerken en onze eigen admin pagina's.
		$is_course_edit = ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $_GET['post'] ) && 'course' === get_post_type( intval( $_GET['post'] ) );
		$is_slme_page   = isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( $_GET['page'] ), 'slme-' );

		if ( ! $is_course_edit && ! $is_slme_page ) { return; }

		wp_enqueue_media();

		wp_enqueue_style( 'slme-editor', SLME_URL . 'assets/editor.css', [], SLME_VERSION );
		wp_enqueue_script( 'slme-admin', SLME_URL . 'assets/admin.js', [], SLME_VERSION, true );

		wp_localize_script( 'slme-admin', 'SLME', [
			'nonce'   => wp_create_nonce( 'slme_admin' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => [
				'saved'    => __( 'Opgeslagen', 'slme' ),
				'failed'   => __( 'Opslaan mislukt', 'slme' ),
			],
		] );
	}
	public static function frontend_assets() {
		wp_enqueue_style(  'slme-frontend', SLME_URL . 'assets/frontend.css', [], SLME_VERSION );
		wp_enqueue_script( 'slme-frontend', SLME_URL . 'assets/frontend.js', [], SLME_VERSION, true );
	}
}
SLME_Assets::init();
