<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele editor (Vrije kaart & Kolommen) voor Sensei LMS cursussen.
 * Version: 0.2.7
 * Author: SLME
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'SLME_VERSION' ) ) {
    define( 'SLME_VERSION', '0.2.7' );
}
if ( ! defined( 'SLME_DIR' ) ) {
    define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SLME_URL' ) ) {
    define( 'SLME_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Belangrijk: laad alléén de nieuwe bestanden uit /includes.
 * Oude admin-bestanden NIET meer gebruiken.
 */

// Admin (menu, editor, AJAX save/reset/preview)
require_once SLME_DIR . 'includes/class-slme-admin.php';

// Frontend weergave
if ( file_exists( SLME_DIR . 'includes/class-slme-frontend.php' ) ) {
    require_once SLME_DIR . 'includes/class-slme-frontend.php';
}

// (Optioneel) Legacy placeholder voor oude include, doet niets.
// Laat staan om foutloos te blijven als iemand oude requires vergeet.
if ( file_exists( SLME_DIR . 'includes/class-slme-ajax.php' ) ) {
    require_once SLME_DIR . 'includes/class-slme-ajax.php';
}
