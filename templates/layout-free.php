<div class="slme-container">
    <?php
    $lessons = Sensei()->course->course_lessons( get_the_ID() );
    foreach ( $lessons as $lesson ) {
        echo '<div class="slme-tile">' . esc_html( get_the_title($lesson->ID) ) . '</div>';
    }
    ?>
</div>
