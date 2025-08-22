<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var array $data */
/** $data['free'] = ['background_id'=>int,'tiles'=>[]] */

$bg = '';
if ( ! empty( $data['free']['background_id'] ) ) {
  $bg = wp_get_attachment_image_url( (int)$data['free']['background_id'], 'full' ) ?: '';
}
$tiles = $data['free']['tiles'] ?? [];
?>
<div class="slme-free-wrap" style="<?php echo $bg ? 'background-image:url('.esc_url($bg).')' : ''; ?>">
  <?php foreach ( $tiles as $t ):
    $w = isset($t['w']) ? (float)$t['w'] : 200;
    $h = isset($t['h']) ? (float)$t['h'] : 140;
    if ( isset($t['size']) && in_array($t['size'], ['s','m','l'], true) ) {
      if ( $t['size']==='s' ) { $w=150; $h=100; }
      elseif ( $t['size']==='l' ) { $w=260; $h=180; }
      else { $w=200; $h=140; }
    }
    $x = (float)($t['x'] ?? 0);
    $y = (float)($t['y'] ?? 0);
    $z = (int)($t['z'] ?? 1);
    $label = substr( sanitize_text_field( $t['label'] ?? '' ), 0, 3 );
    $lesson_id = (int)($t['lesson_id'] ?? 0);
    $title = $lesson_id ? get_the_title( $lesson_id ) : '';
    $link  = $lesson_id ? get_permalink( $lesson_id ) : '#';
    $thumb = $lesson_id ? ( get_the_post_thumbnail_url( $lesson_id, 'medium' ) ?: '' ) : '';
  ?>
    <div class="slme-free-tile" style="left:<?php echo $x; ?>px; top:<?php echo $y; ?>px; width:<?php echo $w; ?>px; height:<?php echo $h; ?>px; z-index:<?php echo $z; ?>;">
      <?php if ( $label ): ?><span class="slme-free-label"><?php echo esc_html( $label ); ?></span><?php endif; ?>
      <div class="slme-free-body" style="text-align:center; width:100%;">
        <?php if ( $thumb ): ?>
          <img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width:100%; height:auto; max-height:60%; object-fit:cover;" />
        <?php endif; ?>
        <div style="font-weight:600; margin-top:4px;"><?php echo esc_html( $title ?: ('#'.$lesson_id) ); ?></div>
        <?php if ( $lesson_id && $link ): ?>
          <a class="button" href="<?php echo esc_url( $link ); ?>">Open les</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
