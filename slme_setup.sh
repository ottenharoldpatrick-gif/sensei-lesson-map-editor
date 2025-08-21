#!/bin/bash
set -e

# Variabelen
PLUGIN_NAME="sensei-lesson-map-editor"
REPO_URL="https://github.com/ottenharoldpatrick-gif/sensei-lesson-map-editor.git"

# Maak plugin structuur
mkdir -p $PLUGIN_NAME/{includes,assets/css,assets/js}
cd $PLUGIN_NAME

# Hoofd plugin bestand
cat > $PLUGIN_NAME.php <<'PHP'
<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Drag & Drop editor voor lessen in vrije kaart of kolom-layout met achtergrondopties.
 * Version: 0.2.7d
 * Author: Harold Otten
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

require_once SLME_DIR . 'includes/class-slme-admin.php';
require_once SLME_DIR . 'includes/class-slme-frontend.php';
PHP

# Admin class
cat > includes/class-slme-admin.php <<'PHP'
<?php
class SLME_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }
    public function add_menu() {
        add_menu_page('Lesson Map Editor', 'Lesson Map Editor', 'manage_options', 'slme', [$this, 'render']);
    }
    public function enqueue() {
        wp_enqueue_style('slme-admin', SLME_URL . 'assets/css/admin.css');
        wp_enqueue_script('slme-admin', SLME_URL . 'assets/js/admin.js', ['jquery'], false, true);
    }
    public function render() {
        echo '<div class="wrap"><h1>Lesson Map Editor</h1><div id="slme-app"></div></div>';
    }
}
new SLME_Admin();
PHP

# Frontend class
cat > includes/class-slme-frontend.php <<'PHP'
<?php
class SLME_Frontend {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }
    public function enqueue() {
        wp_enqueue_style('slme-frontend', SLME_URL . 'assets/css/frontend.css');
        wp_enqueue_script('slme-frontend', SLME_URL . 'assets/js/frontend.js', ['jquery'], false, true);
    }
}
new SLME_Frontend();
PHP

# Dummy assets
echo "/* admin css */" > assets/css/admin.css
echo "/* frontend css */" > assets/css/frontend.css
echo "console.log('SLME admin loaded');" > assets/js/admin.js
echo "console.log('SLME frontend loaded');" > assets/js/frontend.js

# Git init & push
git init
git add .
git commit -m "Initial commit v0.2.7d"
git branch -M main
git remote add origin $REPO_URL
git push -u origin main --force
