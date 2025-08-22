<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
    }

    public static function menu() {
        add_menu_page(
            'Sensei Cursus Maps',
            'Sensei Cursus Maps',
            'manage_options',
            'slme-editor',
            [ __CLASS__, 'render_page' ],
            'dashicons-screenoptions',
            56
        );
    }

    public static function assets( $hook ) {
        if ( $hook !== 'toplevel_page_slme-editor' ) { return; }

        wp_enqueue_style( 'slme-admin', SLME_URL . 'assets/admin.css', [], SLME_VERSION );
        wp_enqueue_media();
        wp_enqueue_script( 'slme-admin-editor', SLME_URL . 'assets/admin-editor.js', [ 'jquery' ], SLME_VERSION, true );

        wp_localize_script( 'slme-admin-editor', 'SLME', [
            'ajax'   => admin_url( 'admin-ajax.php' ),
            'nonce'  => wp_create_nonce( 'slme_editor_nonce' ),
        ] );
    }

    public static function render_page() {
        $courses = Utils::get_courses();
        $course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

        $settings = $course_id ? Utils::get_map_settings( $course_id ) : [];

        include SLME_DIR . 'admin/views/page-editor.php';
    }
}
