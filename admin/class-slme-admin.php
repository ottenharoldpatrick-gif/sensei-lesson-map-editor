<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLME_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu() {
        add_menu_page(
            'Lesson Map Editor',
            'Lesson Map Editor',
            'manage_options',
            'slme-editor',
            [ $this, 'render_editor_page' ],
            'dashicons-welcome-learn-more',
            6
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_slme-editor') return;

        wp_enqueue_style( 'slme-admin-css', SLME_URL . 'assets/css/frontend.css', [], '1.0.0' );
        wp_enqueue_script( 'slme-admin-js', SLME_URL . 'assets/js/admin-editor.js', ['jquery'], '1.0.0', true );

        wp_localize_script( 'slme-admin-js', 'slmeAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'slme_nonce' ),
        ]);
    }

    public function render_editor_page() {
        include SLME_PATH . 'templates/editor-page.php';
    }
}
