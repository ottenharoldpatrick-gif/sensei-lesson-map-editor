<?php
/**
 * Admin – menu “Sensei Cursus Maps” en editorpagina.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin {

	public static function init() : void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
	}

	public static function register_menu() : void {
		add_menu_page(
			'Sensei Cursus Maps',         // Pagetitle
			'Sensei Cursus Maps',         // Menutitle
			'manage_options',             // Capability
			'slme-editor',                // Slug
			[ __CLASS__, 'render_editor_page' ],
			'dashicons-screenoptions',
			56
		);
	}

	public static function enqueue_admin_assets( string $hook ) : void {
		if ( $hook !== 'toplevel_page_slme-editor' ) {
			return;
		}

		$version = defined( 'SLME_VERSION' ) ? SLME_VERSION : '0.2.7';

		wp_enqueue_style(
			'slme-admin',
			plugins_url( '../assets/admin.css', __FILE__ ),
			[],
			$version
		);

		// Belangrijk: exact de bestandsnaam uit de stabiele versie
		$admin_js_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/admin-editor.js';
		if ( file_exists( $admin_js_path ) ) {
			wp_enqueue_script(
				'slme-admin',
				plugins_url( '../assets/admin-editor.js', __FILE__ ),
				[ 'jquery' ],
				$version,
				true
			);

			wp_localize_script(
				'slme-admin',
				'SLME_Ajax',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'slme_nonce' ),
				]
			);
		}
	}

	public static function render_editor_page() : void {
		echo '<div class="wrap">';
		echo '  <h1>Sensei Cursus Maps</h1>';
		echo '  <p>Kies een cursus en beheer de weergave-instellingen.</p>';
		echo '  <div id="slme-editor-root"></div>';
		echo '</div>';
	}
}
