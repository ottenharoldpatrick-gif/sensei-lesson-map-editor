<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLME_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'sensei_single_course_content_inside_after', [ $this, 'render_layout' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'slme-frontend-css', SLME_URL . 'assets/css/frontend.css', [], '1.0.0' );
        wp_enqueue_script( 'slme-frontend-js', SLME_URL . 'assets/js/frontend.js', ['jquery'], '1.0.0', true );
    }

    public function render_layout() {
        $layout = get_option( 'slme_layout', 'free' );

        if ( $layout === 'columns' ) {
            include SLME_PATH . 'templates/layout-columns.php';
        } else {
            include SLME_PATH . 'templates/layout-free.php';
        }
    }
}
