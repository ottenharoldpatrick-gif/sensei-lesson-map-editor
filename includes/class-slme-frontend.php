<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Frontend {

    public static function init() {
        add_shortcode( 'sensei_course_map', [ __CLASS__, 'shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
    }

    public static function assets() {
        wp_register_style( 'slme-frontend', SLME_URL . 'assets/frontend.css', [], SLME_VERSION );
        wp_register_script( 'slme-frontend', SLME_URL . 'assets/frontend.js', [], SLME_VERSION, true );
    }

    public static function shortcode( $atts = [] ) {
        $atts = shortcode_atts([
            'course_id' => 0,
            'mode'      => '',
        ], $atts, 'sensei_course_map' );

        $course_id = (int)$atts['course_id'];
        if ( $course_id <= 0 ) {
            return '<div class="slme-notice">Geen course_id opgegeven.</div>';
        }

        $settings = Utils::get_map_settings( $course_id );
        $mode = $atts['mode'] ?: ( $settings['mode'] ?? 'kolommen' );
        wp_enqueue_style( 'slme-frontend' );
        wp_enqueue_script( 'slme-frontend' );

        ob_start();
        if ( $mode === 'vrij' ) {
            $data = [
                'course_id' => $course_id,
                'free'      => $settings['free'] ?? ['background_id'=>0,'tiles'=>[]],
            ];
            include SLME_DIR . 'templates/free-canvas.php';
        } else {
            $modules = Utils::get_lessons_for_course_by_modules( $course_id );
            $data = [
                'course_id' => $course_id,
                'modules'   => $modules,
            ];
            include SLME_DIR . 'templates/grid-columns.php';
        }
        return ob_get_clean();
    }
}
