<?php
/**
 * Loader – laadt klassen en start plugin.
 *
 * Houdt stabiele mapstructuur aan:
 *  - admin/class-slme-admin.php
 *  - includes/class-slme-frontend.php
 *  - includes/class-slme-ajax.php
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\SLME\Loader' ) ) {

	class Loader {

		public static function init() {
			self::load_files();

			// Init in vaste volgorde; methodes bestaan gegarandeerd.
			Admin::init();
			Frontend::init();
			Ajax::init();
		}

		private static function load_files() {
			$base = plugin_dir_path( __FILE__ );

			// LET OP: admin-bestand staat in /admin, niet in /includes.
			require_once $base . '../admin/class-slme-admin.php';
			require_once $base . 'class-slme-frontend.php';
			require_once $base . 'class-slme-ajax.php';
		}
	}
}
