<?php
/**
 * AJAX – opslaan en resetten van cursus-specifieke layout/instellingen.
 */
namespace SLME;

if ( ! class_exists( '\SLME\Ajax' ) ) {

	class Ajax {

		public function init() {
			add_action( 'wp_ajax_slme_save_map',  [ $this, 'save_map' ] );
			add_action( 'wp_ajax_slme_reset_map', [ $this, 'reset_map' ] );
			// Geen nopriv nodig: dit is beheer‑functionaliteit.
		}

		/**
		 * Opslaan van layout (JSON string) per course_id in post meta _slme_layout.
		 * Verwacht: POST[nonce], POST[course_id], POST[layout]
		 */
		public function save_map() {
			check_ajax_referer( 'slme_admin', 'nonce' );

			$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
			$layout    = isset( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : '';

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Geen rechten.', 'slme' ) ], 403 );
			}
			if ( ! $course_id ) {
				wp_send_json_error( [ 'message' => __( 'Ongeldige cursus.', 'slme' ) ], 400 );
			}

			// Optioneel: validatie dat $layout geldige JSON is.
			$decoded = json_decode( $layout, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_send_json_error( [ 'message' => __( 'Ongeldige data (JSON).', 'slme' ) ], 400 );
			}

			update_post_meta( $course_id, '_slme_layout', wp_slash( $layout ) );

			wp_send_json_success( [
				'message' => __( 'Opgeslagen.', 'slme' ),
			] );
		}

		/**
		 * Verwijder opgeslagen layout voor course_id.
		 */
		public function reset_map() {
			check_ajax_referer( 'slme_admin', 'nonce' );

			$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Geen rechten.', 'slme' ) ], 403 );
			}
			if ( ! $course_id ) {
				wp_send_json_error( [ 'message' => __( 'Ongeldige cursus.', 'slme' ) ], 400 );
			}

			delete_post_meta( $course_id, '_slme_layout' );

			wp_send_json_success( [
				'message' => __( 'Instellingen gereset.', 'slme' ),
			] );
		}
	}
}
