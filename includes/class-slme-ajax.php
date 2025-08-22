<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SLME_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_slme_save', [ $this, 'save_layout' ] );
        add_action( 'wp_ajax_slme_reset', [ $this, 'reset_layout' ] );
    }

    public function save_layout() {
        check_ajax_referer( 'slme_nonce', 'nonce' );

        $data = $_POST['layout'] ?? '';
        if ($data) {
            update_option( 'slme_layout', $data );
            wp_send_json_success(['message' => 'Layout opgeslagen']);
        }
        wp_send_json_error(['message' => 'Geen data ontvangen']);
    }

    public function reset_layout() {
        delete_option( 'slme_layout' );
        wp_send_json_success(['message' => 'Layout gereset']);
    }
}
