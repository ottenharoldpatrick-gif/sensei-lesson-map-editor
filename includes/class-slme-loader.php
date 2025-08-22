<?php
/**
 * Loader – laadt alle onderdelen en start ze op.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Loader {

	public static function init() : void {

		$includes_dir = __DIR__;
		$plugin_root  = plugin_dir_path( dirname( __FILE__ ) ); // root van de plugin

		// Vereiste klassen inladen
		require_once $includes_dir . '/class-slme-frontend.php';
		require_once $plugin_root . 'admin/class-slme-admin.php';

		// Optioneel (AJAX handlers)
		$ajax_file = $includes_dir . '/class-slme-ajax.php';
		if ( file_exists( $ajax_file ) ) {
			require_once $ajax_file;
		}

		// Klassen initialiseren
		Frontend::init();
		Admin::init();
		if ( class_exists( __NAMESPACE__ . '\\Ajax' ) ) {
			Ajax::init();
		}
	}
}
