<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class SLME_Assets {
  public static function init(){ add_action('admin_enqueue_scripts',[__CLASS__,'admin']); add_action('wp_enqueue_scripts',[__CLASS__,'front']); }
  public static function admin($hook){
    if (isset($_GET['page']) && $_GET['page']==='slme-editor'){
      wp_enqueue_style('slme-frontend', SLME_URL.'assets/css/frontend.css',[],SLME_VERSION);
      wp_enqueue_media();
      wp_enqueue_script('slme-editor', SLME_URL.'assets/js/editor-ui.js',['jquery'],SLME_VERSION,true);
    }
  }
  public static function front(){
    if ( is_singular('course') || is_page() ){
      wp_enqueue_style('slme-frontend', SLME_URL.'assets/css/frontend.css',[],SLME_VERSION);
    }
  }
}
SLME_Assets::init();
