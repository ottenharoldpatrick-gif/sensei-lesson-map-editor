<?php
/**
 * Loader – laadt classes en start plugin onderdelen.
 */
namespace SLME;

if ( ! class_exists( '\SLME\Loader' ) ) {

	class Loader {

		/** @var Frontend */
		private static $frontend;
		/** @var Admin */
		private static $admin;
		/** @var Ajax */
		private static $ajax;

		/**
		 * Wordt aangeroepen vanuit het hoofd‑pluginbestand (op plugins_loaded).
		 */
		public static function init() {
			self::load_files();

			self::$frontend = new Frontend();
			self::$frontend->init(); // <-- Bestond eerder niet; nu aanwezig.

			self::$ajax = new Ajax();
			self::$ajax->init();

			if ( is_admin() ) {
				self::$admin = new Admin();
				self::$admin->init();
			}
		}

		/**
		 * Zorgt dat de class-bestanden er zijn; geen dubbele includes.
		 */
		private static function load_files() {
			$base = __DIR__; // .../includes

			// Volgorde is belangrijk: eerst frontend/admin, dan ajax oké.
			if ( ! class_exists( '\SLME\Frontend' ) ) {
				require_once $base . '/class-slme-frontend.php';
			}
			if ( ! class_exists( '\SLME\Admin' ) ) {
				require_once $base . '/class-slme-admin.php';
			}
			if ( ! class_exists( '\SLME\Ajax' ) ) {
				require_once $base . '/class-slme-ajax.php';
			}
		}
	}
}
