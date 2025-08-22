<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var array $data */
/** structure: ['course_id'=>int,'modules'=>[ module_id => ['module'=>['id','name'],'lessons'=>[...]] ]] */

$modules = $data['modules'] ?? [];
$cols = max( 1, min( 6, count( $modules ) ) );
?>
<div class="slme-grid columns-<?php echo (int)$cols; ?>">
  <?php foreach ( $modules as $mod ): ?>
    <div class="slme-column">
      <div class="slme-module-title"><?php echo esc_html( $mod['module']['name'] ); ?></div>
      <?php foreach ( $mod['lessons'] as $lesson ): ?>
        <div class="slme-card">
          <?php if ( ! empty( $lesson['thumb'] ) ): ?>
            <img class="slme-thumb" src="<?php echo esc_url( $lesson['thumb'] ); ?>" alt="">
          <?php endif; ?>
          <div class="slme-title"><?php echo esc_html( $lesson['title'] ); ?></div>
          <a href="<?php echo esc_url( $lesson['link'] ); ?>" class="button">Open les</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
