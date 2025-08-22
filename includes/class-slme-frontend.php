<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class SLME_Frontend {
  public static function init(){ add_shortcode('sensei_lesson_map',[__CLASS__,'shortcode']); }
  public static function shortcode($atts){
    $atts = shortcode_atts(['course_id'=>0], $atts); $course_id = intval($atts['course_id']) ?: get_the_ID(); if(!$course_id) return '';
    $state = get_post_meta($course_id,'_slme_state',true); $layout = $state['layout'] ?? 'free';
    $lessons = SLME_Templates::get_lessons_grouped($course_id);
    ob_start();
    if ($layout==='columns'){
      $mod_count=count($lessons);
      echo '<div class="slme-columns slme-frontend-map" data-modules style="--slme-mod-count:'.$mod_count.'">';
      foreach($lessons as $mid=>$arr){ echo '<section class="slme-module">';
        foreach($arr as $l){ $lid=$l['ID']; $img=$l['thumb']; $done=$l['completed']; $tag=$state['tags'][$lid] ?? '';
          echo '<div class="slme-tile" tabindex="0">';
          if($img){ echo '<img src="'.esc_url($img).'" alt="">'; }
          echo '<span class="slme-badge">'.($done?'✓':'✕').'</span>';
          if($tag){ echo '<span class="slme-tag">'.esc_html($tag).'</span>'; }
          echo '<div class="slme-hover"><div><strong>'.esc_html($l['title']).'</strong><br/>Voortgang: '.($done?'100%':'0%').'</div></div>';
          echo '</div>';
        } echo '</section>'; }
      echo '</div>';
    } else {
      $bgId = intval($state['bgId'] ?? 0); $bgUrl = $bgId ? wp_get_attachment_image_url($bgId,'large') : ''; $tileSize = $state['tileSize'] ?? 'md';
      echo '<div class="slme-free-canvas slme-frontend-map" style="background-image:url(\''.esc_url($bgUrl).'\');">';
      foreach($lessons as $mid=>$arr){ foreach($arr as $l){ $lid=$l['ID']; $pos=$state['positions'][$lid]??['x'=>10,'y'=>10,'z'=>1]; $tag=$state['tags'][$lid]??''; $img=$l['thumb']; $done=$l['completed'];
        echo '<div class="slme-tile slme-tile--'.esc_attr($tileSize).'" style="left:'.intval($pos['x']).'px; top:'.intval($pos['y']).'px; z-index:'.intval($pos['z']).';" tabindex="0">';
        if($img){ echo '<img src="'.esc_url($img).'" alt="">'; }
        echo '<span class="slme-badge">'.($done?'✓':'✕').'</span>';
        if($tag){ echo '<span class="slme-tag">'.esc_html($tag).'</span>'; }
        echo '<div class="slme-hover"><div><strong>'.esc_html($l['title']).'</strong><br/>Voortgang: '.($done?'100%':'0%').'</div></div>';
        echo '</div>';
      } }
      echo '</div>';
    }
    return ob_get_clean();
  }
}
SLME_Frontend::init();
