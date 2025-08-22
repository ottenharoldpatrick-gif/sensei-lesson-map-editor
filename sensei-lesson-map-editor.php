<?php
/*
Plugin Name: Sensei Lesson Map Editor
Description: Visual "Lesson Map" editor for Sensei LMS. Free Canvas (default) + Columns layout. Admin-only editor with AJAX save/reset. Shortcode: [sensei_lesson_map course_id=""]
Version: 0.3.0
Author: SLME
Requires PHP: 8.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SLME_VERSION', '0.3.0' );
define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

require_once SLME_DIR . 'includes/class-slme-assets.php';
require_once SLME_DIR . 'includes/class-slme-ajax.php';
require_once SLME_DIR . 'includes/class-slme-admin.php';
require_once SLME_DIR . 'includes/class-slme-frontend.php';
require_once SLME_DIR . 'includes/class-slme-templates.php';

register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'slme_options' ) ) {
        add_option( 'slme_options', ['default_layout' => 'free'] );
    }
});
