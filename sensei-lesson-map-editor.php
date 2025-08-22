<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele cursuskaart voor Sensei LMS (Kolommen of Vrije kaart) met AJAX opslaan/reset.
 * Version: 0.3.0
 * Author: Harold & Team
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SLME_VERSION', '0.3.0' );
define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

require_once SLME_DIR . 'includes/class-slme-loader.php';

add_action( 'plugins_loaded', function() {
    // Laad alles via Loader (voorkomt double-declare).
    \SLME\Loader::init();
} );
