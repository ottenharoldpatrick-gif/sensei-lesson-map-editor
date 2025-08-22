<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele editor voor het indelen van lessen (vrije kaart of kolommen per module).
 * Version: 1.0.0
 * Author: Harold Otten
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SLME_PATH', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

// Load includes
require_once SLME_PATH . 'admin/class-slme-admin.php';
require_once SLME_PATH . 'includes/class-slme-ajax.php';
require_once SLME_PATH . 'public/class-slme-frontend.php';

// Init
add_action( 'plugins_loaded', function() {
    new SLME_Admin();
    new SLME_Ajax();
    new SLME_Frontend();
});
