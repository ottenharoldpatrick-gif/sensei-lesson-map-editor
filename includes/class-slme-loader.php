<?php
/**
 * SLME Loader – zorgt dat Frontend correct wordt geladen en geïnitialiseerd.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Loader {

	/**
	 * Wordt aangeroepen vanuit de hoofdplugin (sensei-lesson-map-editor.php).
	 */
	public static function init() : void {

		// Zorg dat de Frontend class beschikbaar is vóór we ::init() aanroepen.
		if ( ! class_exists( '\SLME\Frontend' ) ) {
			$frontend_file = __DIR__ . '/class-slme-frontend.php';
			if ( file_exists( $frontend_file ) ) {
				require_once $frontend_file;
			} else {
				// Vriendelijke melding in plaats van een fatale error.
				add_action( 'admin_notices', function () {
					echo '<div class="notice notice-error"><p>SLME: Bestand <code>includes/class-slme-frontend.php</code> niet gevonden.</p></div>';
				} );
				return;
			}
		}

		// Frontend bootstrap (static init aanwezig in class-slme-frontend.php).
		\SLME\Frontend::init();
	}
}
