<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( __NAMESPACE__ . '\Ajax' ) ) {

	class Ajax {

		public static function init() : void {
			add_action( 'wp_ajax_slme_save_layout', [ __CLASS__, 'save_layout' ] );
			add_action( 'wp_ajax_slme_reset_layout', [ __CLASS__, 'reset_layout' ] );
		}

		public static function save_layout() : void {
			check_ajax_referer( 'slme_nonce', 'nonce' );
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
			}
			$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
			$layout    = isset( $_POST['layout'] ) ? wp_unslash( $_POST['layout'] ) : '';

			if ( ! $course_id ) {
				wp_send_json_error( [ 'message' => 'missing course_id' ], 400 );
			}

			update_post_meta( $course_id, '_slme_layout_json', $layout );
			wp_send_json_success( [ 'saved' => true ] );
		}

		public static function reset_layout() : void {
			check_ajax_referer( 'slme_nonce', 'nonce' );
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
			}
			$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
			if ( ! $course_id ) {
				wp_send_json_error( [ 'message' => 'missing course_id' ], 400 );
			}
			delete_post_meta( $course_id, '_slme_layout_json' );
			wp_send_json_success( [ 'reset' => true ] );
		}
	}
}
