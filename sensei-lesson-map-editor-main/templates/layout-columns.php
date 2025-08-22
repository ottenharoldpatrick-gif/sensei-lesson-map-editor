<div class="slme-container">
    <?php
    $modules = Sensei()->modules->get_course_modules( get_the_ID() );
    if ( $modules ) {
        foreach ( $modules as $module ) {
            echo '<div class="slme-column">';
            echo '<h3>' . esc_html( $module->title ) . '</h3>';

            $lessons = Sensei()->modules->get_lessons( $module->term_id );
            foreach ( $lessons as $lesson ) {
                echo '<div class="slme-tile">' . esc_html( get_the_title($lesson->ID) ) . '</div>';
            }
            echo '</div>';
        }
    }
    ?>
</div>
