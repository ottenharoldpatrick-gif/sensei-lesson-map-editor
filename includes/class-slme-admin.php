<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class SLME_Admin {
  public static function init(){ add_action('admin_menu',[__CLASS__,'menu']); }
  public static function menu(){
    add_submenu_page('edit.php?post_type=course','Lesson Map Editor','Lesson Map Editor','edit_posts','slme-editor',[__CLASS__,'render_page']);
  }
  private static function get_course_list(): array {
    $q=new WP_Query(['post_type'=>'course','posts_per_page'=>50,'post_status'=>'publish','orderby'=>'title','order'=>'ASC']);
    $out=[]; foreach($q->posts as $p){ $out[]=['id'=>$p->ID,'title'=>$p->post_title]; } return $out;
  }
  public static function render_page(){
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $state = $course_id ? get_post_meta($course_id,'_slme_state',true) : [];
    $layout = $state['layout'] ?? 'free'; $tileSize = $state['tileSize'] ?? 'md'; $bgId = intval($state['bgId'] ?? 0);
    $bgUrl = $bgId ? wp_get_attachment_image_url($bgId,'large') : '';
    $lessons = $course_id ? SLME_Templates::get_lessons_grouped($course_id) : [];

    echo '<div class="wrap slme-wrap"><h1>Lesson Map Editor</h1>';
    echo '<form id="slme-form" method="post" onsubmit="return false;">';
    wp_nonce_field('slme_nonce');
    echo '<input type="hidden" id="slme-nonce" value="'.esc_attr(wp_create_nonce('slme_nonce')).'"/>';
    echo '<label>Cursus: <select id="slme-course" name="course_id" onchange="location.href=\'?post_type=course&page=slme-editor&course_id=\'+this.value;"><option value="">— kies cursus —</option>';
    foreach(self::get_course_list() as $c){ echo '<option value="'.esc_attr($c['id']).'" '.selected($course_id,$c['id'],false).'>'.esc_html($c['title']).'</option>'; } 
    echo '</select></label>';

    if($course_id){
      echo '<div class="slme-toolbar">';
      echo '<label><input type="radio" name="slme_layout" value="free" '.checked($layout,'free',false).'> Vrije kaart (standaard)</label>';
      echo '<label><input type="radio" name="slme_layout" value="columns" '.checked($layout,'columns',false).'> Kolommen (per module)</label>';
      echo '<label>Tile grootte: <select id="slme-size"><option value="sm" '.selected($tileSize,'sm',false).'>Klein</option><option value="md" '.selected($tileSize,'md',false).'>Midden</option><option value="lg" '.selected($tileSize,'lg',false).'>Groot</option></select></label>';
      echo '<span class="slme-bg-tools" '.($layout==='columns'?'style="display:none"':'').'><button id="slme-pick-bg" class="button">Achtergrond kiezen</button> <button id="slme-clear-bg" class="button">Leegmaken</button><input type="hidden" id="slme-bg-id" value="'.esc_attr($bgId).'"></span>';
      echo '<button id="slme-save" class="button button-primary">Opslaan</button> <button id="slme-reset" class="button">Reset</button>';
      echo '</div>';
      echo '<input type="hidden" id="slme-state" value="'.esc_attr(wp_json_encode($state)).'"/>';

      // Free layout
      echo '<div class="slme-layout slme-layout--free" '.($layout!=='free'?'style="display:none"':'').'>';
      echo '<div id="slme-bg-preview" class="slme-free-canvas" style="background-image:url(\''.(esc_url($bgUrl)).'\');">';
      foreach($lessons as $mid=>$arr){ foreach($arr as $l){ $lid=$l['ID']; $pos=$state['positions'][$lid]??['x'=>10,'y'=>10,'z'=>1]; $tag=$state['tags'][$lid]??''; $img=$l['thumb']; $done=$l['completed'];
        echo '<div class="slme-tile slme-tile--'.esc_attr($tileSize).'" data-lesson="'.esc_attr($lid).'" style="left:'.intval($pos['x']).'px; top:'.intval($pos['y']).'px; z-index:'.intval($pos['z']).';">';
        if($img){ echo '<img src="'.esc_url($img).'" alt="">'; }
        echo '<span class="slme-badge">'.($done?'✓':'✕').'</span><span class="slme-tag">'.esc_html($tag).'</span>';
        echo '<div class="slme-hover"><div><strong>'.esc_html($l['title']).'</strong><br/>Voortgang: '.($done?'100%':'0%').'</div></div>';
        echo '<div class="slme-z-buttons"><button class="slme-z-up" type="button">▲</button><button class="slme-z-down" type="button">▼</button></div>';
        echo '</div>';
      } }
      echo '</div>';
      echo '<p><em>Tip:</em> Sleep tegels, gebruik ▲/▼ om lagen te wijzigen. Tag (3 tekens) invullen hieronder.</p>';
      echo '<table class="widefat striped"><thead><tr><th>Les</th><th>Tag (max 3 tekens)</th></tr></thead><tbody>';
      foreach($lessons as $mid=>$arr){ foreach($arr as $l){ $lid=$l['ID']; $tag=$state['tags'][$lid]??'';
        echo '<tr><td>'.esc_html($l['title']).'</td><td><input class="slme-tag-input" data-lesson="'.esc_attr($lid).'" value="'.esc_attr($tag).'" maxlength="3"/></td></tr>';
      } }
      echo '</tbody></table>';
      echo '</div>'; // free

      // Columns layout
      echo '<div class="slme-layout slme-layout--columns" '.($layout!=='columns'?'style="display:none"':'').'>';
      $mod_count = count($lessons);
      echo '<div class="slme-columns slme-frontend-map" data-modules style="--slme-mod-count:'.$mod_count.'">';
      foreach($lessons as $mid=>$arr){
        echo '<section class="slme-module">';
        foreach($arr as $l){ $lid=$l['ID']; $img=$l['thumb']; $done=$l['completed']; $tag=$state['tags'][$lid]??'';
          echo '<div class="slme-tile" tabindex="0">';
          if($img){ echo '<img src="'.esc_url($img).'" alt="">'; }
          echo '<span class="slme-badge">'.($done?'✓':'✕').'</span>';
          if($tag){ echo '<span class="slme-tag">'.esc_html($tag).'</span>'; }
          echo '<div class="slme-hover"><div><strong>'.esc_html($l['title']).'</strong><br/>Voortgang: '.($done?'100%':'0%').'</div></div>';
          echo '</div>';
        }
        echo '</section>';
      }
      echo '</div><p><em>Opmerking:</em> Kolommen gebruiken <strong>geen</strong> achtergrondafbeelding. Tegelgrootte schaalt automatisch per aantal modules.</p></div>';
    } else {
      echo '<p>Kies hierboven eerst een cursus.</p>';
    }
    echo '</form></div>';
  }
}
SLME_Admin::init();
