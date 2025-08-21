<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele leskaart + admin editor voor Sensei LMS (vrije kaart & kolommen, drag & drop, mediabibliotheek achtergrond).
 * Version: 0.3.0
 * Author: Otten Harold Patrick + ChatGPT
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SLME_VERSION', '0.3.0' );
define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );
define( 'SLME_FILE', __FILE__ );

// Includes
require_once SLME_DIR . 'includes/class-slme-assets.php';
require_once SLME_DIR . 'includes/class-slme-admin.php';
require_once SLME_DIR . 'includes/class-slme-shortcode.php';

// Boot
add_action( 'plugins_loaded', function() {
	// Niets bijzonders hier; classes haken zelf in.
} );
