<?php
/**
 * Frontend functionaliteit van de Sensei Cursus Maps plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLME_Frontend {

    public function __construct() {
        add_shortcode( 'sensei_course_map', array( $this, 'render_course_map' ) );
    }

    /**
     * Render de cursus map
     */
    public function render_course_map( $atts ) {
        $atts = shortcode_atts(
            array(
                'course_id' => get_the_ID(),
            ),
            $atts,
            'sensei_course_map'
        );

        $course_id = intval( $atts['course_id'] );

        if ( ! $course_id ) {
            return '<p>Geen cursus gevonden.</p>';
        }

        // Ophalen van layout uit de admin instellingen (via AJAX opgeslagen)
        $layout = get_post_meta( $course_id, '_slme_layout', true );
        if ( empty( $layout ) ) {
            $layout = 'columns'; // standaard
        }

        ob_start();

        if ( $layout === 'columns' ) {
            $this->render_columns_layout( $course_id );
        } else {
            $this->render_free_map_layout( $course_id );
        }

        return ob_get_clean();
    }

    /**
     * Kolommen layout: modules = kolommen, lessen onder elkaar
     */
    private function render_columns_layout( $course_id ) {
        $modules = Sensei()->modules->get_course_modules( $course_id );

        if ( empty( $modules ) ) {
            echo '<p>Geen modules gevonden voor deze cursus.</p>';
            return;
        }

        $module_count = count( $modules );
        $tile_size = $module_count > 0 ? floor(100 / $module_count) : 100;

        echo '<div class="slme-columns-layout" style="display:flex; gap:10px;">';

        foreach ( $modules as $module ) {
            $lessons = Sensei()->modules->get_lessons( $module->term_id, $course_id );

            echo '<div class="slme-column" style="flex:1; min-width:' . esc_attr( $tile_size ) . '%;">';
            echo '<h3>' . esc_html( $module->name ) . '</h3>';

            if ( ! empty( $lessons ) ) {
                foreach ( $lessons as $lesson_id ) {
                    $this->render_tile( $lesson_id );
                }
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Vrije kaart layout
     */
    private function render_free_map_layout( $course_id ) {
        $lessons = Sensei()->course->course_lessons( $course_id );

        if ( empty( $lessons ) ) {
            echo '<p>Geen lessen gevonden.</p>';
            return;
        }

        echo '<div class="slme-free-map" style="position:relative; width:100%; height:600px; border:1px solid #ddd;">';

        foreach ( $lessons as $lesson_id ) {
            $this->render_tile( $lesson_id, true );
        }

        echo '</div>';
    }

    /**
     * Render één tegel
     */
    private function render_tile( $lesson_id, $free = false ) {
        $completed = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );
        $class     = $completed ? 'slme-tile completed' : 'slme-tile not-completed';

        $style = '';
        if ( $free ) {
            // willekeurige positie bij free map, later uitbreidbaar met admin instellingen
            $top  = rand( 0, 500 );
            $left = rand( 0, 500 );
            $style = "position:absolute; top:{$top}px; left:{$left}px;";
        }

        echo '<div class="' . esc_attr( $class ) . '" style="width:120px; height:120px; margin:5px; ' . esc_attr( $style ) . '">';
        echo '<div class="slme-tile-inner" style="border:1px solid #ccc; padding:10px; text-align:center; font-size:14px;">';

        // uitgelichte afbeelding
        if ( has_post_thumbnail( $lesson_id ) ) {
            echo get_the_post_thumbnail( $lesson_id, 'thumbnail', array( 'style' => 'max-width:100%; height:auto;' ) );
        }

        // korte tekst (bijv. lesnummer of titel)
        echo '<p>' . esc_html( get_the_title( $lesson_id ) ) . '</p>';

        // vinkje of kruisje
        if ( $completed ) {
            echo '<span style="color:green; font-size:20px;">✔</span>';
        } else {
            echo '<span style="color:red; font-size:20px;">✘</span>';
        }

        echo '</div>';
        echo '</div>';
    }
}

new SLME_Frontend();
