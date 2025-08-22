<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Ajax {

    public static function init() {
        add_action( 'wp_ajax_slme_save_map',  [ __CLASS__, 'save_map' ] );
        add_action( 'wp_ajax_slme_reset_map', [ __CLASS__, 'reset_map' ] );
    }

    public static function save_map() {
        check_ajax_referer( 'slme_editor_nonce', 'nonce' );

        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        if ( $course_id <= 0 ) {
            wp_send_json_error([ 'message' => 'Ongeldig course_id' ], 400 );
        }

        $raw = isset($_POST['payload']) ? wp_unslash( $_POST['payload'] ) : '';
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) {
            wp_send_json_error([ 'message' => 'Ongeldige payload' ], 400 );
        }

        // Normaliseer & overschrijfregels
        $mode = isset($data['mode']) && in_array($data['mode'], ['kolommen','vrij'], true) ? $data['mode'] : 'kolommen';

        $clean = [
            'mode' => $mode,
        ];

        if ( $mode === 'kolommen' ) {
            // géén achtergrondopties in kolommen
            $clean['columns'] = [
                'tileScale' => sanitize_text_field( $data['columns']['tileScale'] ?? 'auto' ),
            ];
        } else {
            // vrije kaart
            $bg_id = isset($data['free']['background_id']) ? (int)$data['free']['background_id'] : 0;

            $tiles = [];
            if ( ! empty( $data['free']['tiles'] ) && is_array( $data['free']['tiles'] ) ) {
                foreach ( $data['free']['tiles'] as $t ) {
                    $tiles[] = [
                        'lesson_id' => isset($t['lesson_id']) ? (int)$t['lesson_id'] : 0,
                        'x'         => isset($t['x']) ? (float)$t['x'] : 0,
                        'y'         => isset($t['y']) ? (float)$t['y'] : 0,
                        'w'         => isset($t['w']) ? (float)$t['w'] : 200,
                        'h'         => isset($t['h']) ? (float)$t['h'] : 140,
                        'z'         => isset($t['z']) ? (int)$t['z'] : 1,
                        'label'     => substr( sanitize_text_field( $t['label'] ?? '' ), 0, 3 ),
                        'size'      => in_array( ($t['size'] ?? 'm'), ['s','m','l','custom'], true ) ? $t['size'] : 'm',
                    ];
                }
            }

            $clean['free'] = [
                'background_id' => $bg_id,
                'tiles'         => $tiles,
                'updated_at'    => time(),
            ];
        }

        Utils::save_map_settings( $course_id, $clean );
        wp_send_json_success([ 'message' => 'Opgeslagen', 'data' => $clean ]);
    }

    public static function reset_map() {
        check_ajax_referer( 'slme_editor_nonce', 'nonce' );
        $course_id = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
        if ( $course_id <= 0 ) {
            wp_send_json_error([ 'message' => 'Ongeldig course_id' ], 400 );
        }
        Utils::reset_map_settings( $course_id );
        wp_send_json_success([ 'message' => 'Gerest', 'course_id' => $course_id ]);
    }
}
