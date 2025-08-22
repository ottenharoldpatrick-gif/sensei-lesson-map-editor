<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class SLME_Ajax {
  public static function init(){ add_action('wp_ajax_slme_save',[__CLASS__,'save']); add_action('wp_ajax_slme_reset',[__CLASS__,'reset']); }
  private static function can_edit_course(int $course_id): bool { return current_user_can('edit_post',$course_id); }
  public static function save(){
    check_ajax_referer('slme_nonce');
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    if (!$course_id || !self::can_edit_course($course_id)){ wp_send_json_error(['message'=>'Geen rechten of ongeldig course_id'],403); }
    $state_raw = wp_unslash($_POST['state'] ?? '');
    $state = json_decode($state_raw,true);
    if (!is_array($state)){ wp_send_json_error(['message'=>'Ongeldige state'],400); }
    $clean = ['layout'=> in_array($state['layout']??'free',['free','columns'],true)?$state['layout']:'free',
              'tileSize'=> in_array($state['tileSize']??'md',['sm','md','lg'],true)?$state['tileSize']:'md',
              'bgId'=> isset($state['bgId'])?absint($state['bgId']):0, 'positions'=>[], 'tags'=>[] ];
    if (!empty($state['positions']) && is_array($state['positions'])){
      foreach($state['positions'] as $lid=>$pos){
        $lid=intval($lid);
        $clean['positions'][$lid]=['x'=>isset($pos['x'])?intval($pos['x']):0,'y'=>isset($pos['y'])?intval($pos['y']):0,'z'=>isset($pos['z'])?intval($pos['z']):1,'module'=>isset($pos['module'])?intval($pos['module']):0];
      }
    }
    if (!empty($state['tags']) && is_array($state['tags'])){
      foreach($state['tags'] as $lid=>$tag){ $lid=intval($lid); $clean['tags'][$lid]=substr(sanitize_text_field($tag),0,3); }
    }
    update_post_meta($course_id,'_slme_state',$clean);
    wp_send_json_success(['message'=>'Opgeslagen']);
  }
  public static function reset(){
    check_ajax_referer('slme_nonce');
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    if (!$course_id || !self::can_edit_course($course_id)){ wp_send_json_error(['message'=>'Geen rechten of ongeldig course_id'],403); }
    delete_post_meta($course_id,'_slme_state');
    wp_send_json_success(['message'=>'Gerest']);
  }
}
SLME_Ajax::init();
