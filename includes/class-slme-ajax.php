<?php
/**
 * Ajax – handlers voor opslaan/reset van editorinstellingen.
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Ajax {

	public static function init() : void {
		add_action( 'wp_ajax_slme_save_map',  [ __CLASS__, 'save_map' ] );
		add_action( 'wp_ajax_slme_reset_map', [ __CLASS__, 'reset_map' ] );
	}

	/**
	 * Opslaan van instellingen uit de editor.
	 */
	public static function save_map() : void {
		check_ajax_referer( 'slme_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Geen rechten' ], 403 );
		}

		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';

		if ( empty( $data ) ) {
			wp_send_json_error( [ 'message' => 'Geen data ontvangen' ], 400 );
		}

		update_option( 'slme_map_settings', $data );
		wp_send_json_success( [ 'message' => 'Instellingen opgeslagen' ] );
	}

	/**
	 * Reset naar standaardinstellingen.
	 */
	public static function reset_map() : void {
		check_ajax_referer( 'slme_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Geen rechten' ], 403 );
		}

		delete_option( 'slme_map_settings' );
		wp_send_json_success( [ 'message' => 'Instellingen gereset' ] );
	}
}
