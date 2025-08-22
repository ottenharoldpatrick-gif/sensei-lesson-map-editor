<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var array $courses */
/** @var int $course_id */
/** @var array $settings */
$mode = $settings['mode'] ?? 'kolommen';
?>
<div class="wrap slme-wrap">
  <h1>Sensei Cursus Maps</h1>

  <form method="get" action="">
    <input type="hidden" name="page" value="slme-editor" />
    <label for="slme-course">Kies cursus:</label>
    <select id="slme-course" name="course_id">
      <option value="0">— Selecteer —</option>
      <?php foreach ( $courses as $c ): ?>
        <option value="<?php echo esc_attr($c['id']); ?>" <?php selected( $course_id, $c['id'] ); ?>>
            <?php echo esc_html( $c['title'] . " (#{$c['id']})" ); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button class="button button-primary">Open</button>
  </form>

  <?php if ( $course_id ): ?>
  <hr/>
  <div id="slme-editor"
       data-course="<?php echo esc_attr($course_id); ?>"
       data-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>">
    <h2>Editor voor cursus #<?php echo (int)$course_id; ?></h2>

    <div class="slme-mode-toggle">
      <label><input type="radio" name="slme-mode" value="kolommen" <?php checked($mode, 'kolommen'); ?> /> Kolommen</label>
      <label><input type="radio" name="slme-mode" value="vrij" <?php checked($mode, 'vrij'); ?> /> Vrije kaart</label>
    </div>

    <div class="slme-section slme-kolommen" style="<?php echo $mode==='kolommen'?'':'display:none'; ?>">
      <p><strong>Kaders:</strong> aantal kolommen = aantal Sensei Modules. Lessen per module van boven naar beneden in Sensei volgorde. Tile-grootte schaalt automatisch.
      </p>
      <label>Tile schaal:
        <select id="slme-col-scale">
          <option value="auto" <?php selected( $settings['columns']['tileScale'] ?? 'auto', 'auto' ); ?>>Automatisch</option>
          <option value="s" <?php selected( $settings['columns']['tileScale'] ?? 'auto', 's' ); ?>>Klein</option>
          <option value="m" <?php selected( $settings['columns']['tileScale'] ?? 'auto', 'm' ); ?>>Midden</option>
          <option value="l" <?php selected( $settings['columns']['tileScale'] ?? 'auto', 'l' ); ?>>Groot</option>
        </select>
      </label>
    </div>

    <div class="slme-section slme-vrij" style="<?php echo $mode==='vrij'?'':'display:none'; ?>">
      <div class="slme-bg">
        <button class="button" id="slme-pick-bg">Kies achtergrond</button>
        <span id="slme-bg-info"></span>
      </div>
      <p>Klik/versleep tegels. Elk blokje: formaat (S/M/L/Custom), 3‑tekens label, en Z‑index (voor/achter).</p>
      <div id="slme-canvas" class="slme-canvas"></div>
      <div class="slme-toolbar">
        <button class="button" id="slme-add-lessons">Voeg alle lessen toe</button>
        <button class="button" id="slme-clear-tiles">Leeg canvas</button>
      </div>
    </div>

    <div class="slme-actions">
      <button class="button button-primary" id="slme-save">Opslaan</button>
      <button class="button" id="slme-reset">Reset</button>
      <span id="slme-status"></span>
    </div>

    <div class="slme-help">
      <p><strong>Frontend weergave:</strong> gebruik shortcode<br/>
        <code>[sensei_course_map course_id="<?php echo (int)$course_id; ?>" mode="kolommen"]</code> of
        <code>[sensei_course_map course_id="<?php echo (int)$course_id; ?>" mode="vrij"]</code>.
      </p>
    </div>
  </div>
  <?php endif; ?>
</div>
