<?php
class SLME_Assets {
  public function __construct(){
    add_action('admin_enqueue_scripts', [$this,'admin']);
    add_action('wp_enqueue_scripts', [$this,'front']);
  }
  public function admin($hook){
    if($hook!=='sensei-lms_page_slme-editor') return;
    wp_enqueue_script('slme-admin', plugins_url('assets/admin.js', __FILE__), ['jquery'], '1.0', true);
  }
  public function front(){
    wp_enqueue_style('slme-front', plugins_url('assets/front.css', __FILE__), [], '1.0');
  }
}
new SLME_Assets();
