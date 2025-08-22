<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Loader {
    public static function init() {
        require_once SLME_DIR . 'includes/class-slme-utils.php';
        require_once SLME_DIR . 'includes/class-slme-ajax.php';
        require_once SLME_DIR . 'includes/class-slme-frontend.php';
        require_once SLME_DIR . 'admin/class-slme-admin.php';

        Admin::init();
        Ajax::init();
        Frontend::init();
    }
}
