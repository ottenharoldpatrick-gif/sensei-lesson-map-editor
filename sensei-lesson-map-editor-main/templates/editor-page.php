<div class="wrap">
    <h1>Sensei Cursus Maps</h1>

    <form id="slme-form">
        <label for="slme-course">Kies cursus:</label>
        <select id="slme-course" name="course_id">
            <?php
            $courses = get_posts([
                'post_type' => 'course',
                'numberposts' => -1
            ]);
            foreach ( $courses as $course ) {
                echo '<option value="' . esc_attr($course->ID) . '">' . esc_html($course->post_title) . '</option>';
            }
            ?>
        </select>

        <br><br>

        <label for="slme-layout">Kies layout:</label>
        <select id="slme-layout" name="layout">
            <option value="free">Vrije kaart</option>
            <option value="columns">Kolommen per module</option>
        </select>

        <br><br>
        <button id="slme-save" class="button button-primary">Opslaan</button>
        <button id="slme-reset" class="button">Reset</button>
    </form>
</div>
