<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele leskaart + admin editor voor Sensei LMS (Vrije kaart & Kolommen), met mediabibliotheek-achtergrond, tile-formaten, labels en drag & drop.
 * Version: 0.3.0
 * Author: Harold Patrick Otten
 * Text Domain: slme
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SLME_VERSION', '0.3.0' );
define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

require_once SLME_DIR . 'includes/class-slme-frontend.php';
require_once SLME_DIR . 'includes/class-slme-admin.php';

// Frontend & shortcode
add_action( 'init', [ 'SLME_Frontend', 'init' ] );

// Admin
if ( is_admin() ) {
	add_action( 'init', [ 'SLME_Admin', 'init' ] );
}
