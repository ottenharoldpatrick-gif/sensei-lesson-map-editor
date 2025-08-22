<?php
/**
 * SLME Frontend helper (veilig om meerdere keren te includen)
 */
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) exit;

// Guard: voorkom "Cannot declare class ..." als dit bestand dubbel wordt ingeladen.
if ( ! class_exists( __NAMESPACE__ . '\Frontend', false ) ) {

	class Frontend {

		/**
		 * (optioneel) Singleton instance voor toekomstige uitbreidingen.
		 */
		public static function instance() {
			static $inst = null;
			if ( null === $inst ) {
				$inst = new self();
			}
			return $inst;
		}

		private function __construct() {
			// Hook-plek voor toekomstige filters/shortcodes indien nodig.
			// Momenteel bewust leeg; functionaliteit draait via Loader + template.
		}
	}
}
