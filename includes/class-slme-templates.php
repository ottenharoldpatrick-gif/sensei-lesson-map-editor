<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class SLME_Templates {
  public static function get_lessons_grouped(int $course_id): array {
    $out=[]; if(!class_exists('Sensei')) return $out;
    $modules = Sensei()->modules->get_course_modules($course_id);
    if(empty($modules)){ $lessons = Sensei()->course->course_lessons($course_id); $out[0] = array_map(fn($l)=>self::map_lesson($l->ID), $lessons); return $out; }
    foreach($modules as $m){ $module_id=intval($m->term_id); $lessons = Sensei()->modules->get_lessons($module_id,$course_id); $out[$module_id] = array_map(fn($l)=>self::map_lesson($l->ID), $lessons); }
    return $out;
  }
  private static function map_lesson(int $lesson_id): array {
    $thumb = get_the_post_thumbnail_url($lesson_id,'medium_large'); if(!$thumb){ $thumb = get_the_post_thumbnail_url($lesson_id,'medium'); }
    $user_id = get_current_user_id(); $completed=false;
    if ( method_exists('Sensei_Utils','user_completed_lesson') ){ $completed = Sensei_Utils::user_completed_lesson($lesson_id,$user_id); }
    return ['ID'=>$lesson_id,'title'=>get_the_title($lesson_id),'thumb'=>$thumb?:'','completed'=>(bool)$completed];
  }
}
