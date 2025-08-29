<?php
/**
 * Plugin Name: Sensei Lesson Grid (Admin-Centric)
 * Description: Centrale grid-editor met modules en tiles (5 kolommen). Shortcode: [lesson_grid slug="..."]. Sensei-koppeling voor voortgang en lock. (v1.0.0 stable)
 * Version: 1.0.0
 * Author: Harold Otten
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Text Domain: sensei-lesson-grid-editor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
if ( ! defined( 'SLGE_VERSION' ) )     define( 'SLGE_VERSION', '1.0.0' );
if ( ! defined( 'SLGE_PLUGIN_FILE' ) ) define( 'SLGE_PLUGIN_FILE', __FILE__ );
if ( ! defined( 'SLGE_PLUGIN_PATH' ) ) define( 'SLGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'SLGE_PLUGIN_URL' ) )  define( 'SLGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class SLGE_Plugin {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );
        add_action( 'save_post_slg_grid', array( $this, 'save_grid_meta' ), 10, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

        add_shortcode( 'lesson_grid', array( $this, 'shortcode_lesson_grid' ) );

        // AJAX
        add_action( 'wp_ajax_slg_save_structure', array( $this, 'ajax_save_structure' ) );
        add_action( 'wp_ajax_slg_search_lessons', array( $this, 'ajax_search_lessons' ) );
    }

    /* ===== CPT ===== */
    public function register_cpt() {
        $labels = array(
            'name'               => __( 'Lesson Grids', 'sensei-lesson-grid-editor' ),
            'singular_name'      => __( 'Lesson Grid', 'sensei-lesson-grid-editor' ),
            'add_new'            => __( 'Add New Grid', 'sensei-lesson-grid-editor' ),
            'add_new_item'       => __( 'Add New Lesson Grid', 'sensei-lesson-grid-editor' ),
            'edit_item'          => __( 'Edit Lesson Grid', 'sensei-lesson-grid-editor' ),
            'new_item'           => __( 'New Lesson Grid', 'sensei-lesson-grid-editor' ),
            'view_item'          => __( 'View Lesson Grid', 'sensei-lesson-grid-editor' ),
            'search_items'       => __( 'Search Lesson Grids', 'sensei-lesson-grid-editor' ),
        );
        register_post_type( 'slg_grid', array(
            'labels'        => $labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-screenoptions',
            'supports'      => array( 'title' ),
        ) );
    }

    /* ===== Admin UI ===== */
    public function metaboxes() {
        add_meta_box( 'slg_modules', __( 'Modules & Tiles', 'sensei-lesson-grid-editor' ), array( $this, 'metabox_modules' ), 'slg_grid', 'normal', 'high' );
        add_meta_box( 'slg_shortcode', __( 'Shortcode', 'sensei-lesson-grid-editor' ), array( $this, 'metabox_shortcode' ), 'slg_grid', 'side', 'default' );
        add_meta_box( 'slg_options', __( 'Grid opties', 'sensei-lesson-grid-editor' ), array( $this, 'metabox_options' ), 'slg_grid', 'side', 'default' );
    }

    public function metabox_shortcode( $post ) {
        $slug = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
        echo '<p>' . esc_html__( 'Gebruik deze shortcode:', 'sensei-lesson-grid-editor' ) . '</p>';
        echo '<code>[lesson_grid slug="' . esc_attr( $slug ) . '"]</code>';
    }

    public function admin_assets( $hook ) {
        if ( 'slg_grid' !== get_post_type() ) { return; }
        wp_enqueue_style( 'slge-admin', SLGE_PLUGIN_URL . 'assets/admin.css', array(), SLGE_VERSION );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'slge-admin', SLGE_PLUGIN_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SLGE_VERSION, true );
        wp_localize_script( 'slge-admin', 'SLGE', array(
            'nonce'     => wp_create_nonce( 'slge_nonce' ),
            'ajax'      => admin_url( 'admin-ajax.php' ),
            'pluginUrl' => SLGE_PLUGIN_URL,
            'i18n'      => array(
                'addModule' => __( 'Add module', 'sensei-lesson-grid-editor' ),
                'addTile'   => __( 'Add tile', 'sensei-lesson-grid-editor' ),
            ),
        ) );
        wp_enqueue_media();
    }

    public function frontend_assets() {
        wp_enqueue_style( 'slge-frontend', SLGE_PLUGIN_URL . 'assets/frontend.css', array(), SLGE_VERSION );
        $vars = ':root{--slge-desktop-cols:5;--slge-tablet-cols:3;--slge-mobile-cols:2;}';
        wp_add_inline_style( 'slge-frontend', $vars );
    }

    public function metabox_modules( $post ) {
        $structure = get_post_meta( $post->ID, '_slg_structure', true );
        if ( ! is_array( $structure ) ) { $structure = array(); }
        if ( empty( $structure ) ) {
            $structure = array(
                array( 'name' => __( 'Module 1', 'sensei-lesson-grid-editor' ), 'tiles' => array() ),
            );
        }

        echo '<div id="slg-editor" class="slg-editor" data-post="' . intval( $post->ID ) . '">';
        echo '<div class="slg-controls">';
        echo '<button type="button" class="button button-secondary" id="slg-add-module">' . esc_html__( 'Add module', 'sensei-lesson-grid-editor' ) . '</button> ';
        echo '<button type="button" class="button button-primary" id="slg-save-structure">' . esc_html__( 'Save structure', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '</div>';

        echo '<div id="slg-modules" class="slg-modules">';
        foreach ( $structure as $mIndex => $module ) {
            $this->print_module_block( $mIndex, $module );
        }
        echo '</div>';

        echo '<textarea id="slg-structure" name="slg_structure" style="display:none;">' . esc_textarea( wp_json_encode( $structure ) ) . '</textarea>';

        // Lesson picker modal
        echo '<div id="slg-lesson-modal" class="slg-lesson-modal" style="display:none;">'
           . '<div class="slg-lesson-modal__backdrop"></div>'
           . '<div class="slg-lesson-modal__dialog">'
           .   '<div class="slg-lesson-modal__head"><strong>Pick Sensei lesson</strong><button type="button" class="button-link slg-lesson-modal__close">&times;</button></div>'
           .   '<div class="slg-lesson-modal__body">'
           .     '<input type="search" id="slg-lesson-search" placeholder="Zoek les..." style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:8px;" />'
           .     '<div id="slg-lesson-results" class="slg-lesson-results"></div>'
           .   '</div>'
           .   '<div class="slg-lesson-modal__foot"><button type="button" class="button slg-lesson-modal__close">Sluiten</button></div>'
           . '</div>'
           . '</div>';

        echo '</div>'; // #slg-editor
    }

    
public function metabox_options( $post ) {
    $hide = get_post_meta( $post->ID, '_slg_hide_sensei_default', true ) ? 1 : 0;
    echo '<p><label><input type="checkbox" name="slg_hide_sensei_default" value="1" ' . checked( 1, $hide, false ) . ' /> ' . esc_html__( 'Verberg standaard Sensei modules/lessen op course-pagina', 'sensei-lesson-grid-editor' ) . '</label></p>';
    echo '<p style="color:#6b7280;font-size:12px;">' . esc_html__( 'Alleen van toepassing wanneer deze grid op een course-pagina wordt gebruikt.', 'sensei-lesson-grid-editor' ) . '</p>';
}
private function print_module_block( $mIndex, $module ) {
        $mName = isset( $module['name'] ) ? $module['name'] : __( 'Module', 'sensei-lesson-grid-editor' );
        echo '<div class="slg-module" data-index="' . intval( $mIndex ) . '">';
        echo '  <div class="slg-module-head">';
        echo '    <span class="dashicons dashicons-move slg-handle" title="' . esc_attr__( 'Drag', 'sensei-lesson-grid-editor' ) . '"></span>';
        echo '    <input type="text" class="slg-module-name" value="' . esc_attr( $mName ) . '" placeholder="' . esc_attr__( 'Module name', 'sensei-lesson-grid-editor' ) . '" />';
        echo '    <button type="button" class="button-link-delete slg-remove-module">&times;</button>';
        echo '    <button type="button" class="button slg-add-tile">' . esc_html__( 'Add tile', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '  </div>';
        echo '  <ul class="slg-tiles">';
        if ( ! empty( $module['tiles'] ) && is_array( $module['tiles'] ) ) {
            foreach ( $module['tiles'] as $tIndex => $tile ) {
                $this->print_tile_item( $tIndex, $tile );
            }
        }
        echo '  </ul>';
        echo '</div>';
    }

    private function print_tile_item( $tIndex, $tile ) {
        $title     = isset( $tile['title'] ) ? $tile['title'] : '';
        $url       = isset( $tile['url'] ) ? $tile['url'] : '';
        $image     = isset( $tile['image'] ) ? intval( $tile['image'] ) : 0;
        $lesson_id = isset( $tile['lesson_id'] ) ? intval( $tile['lesson_id'] ) : 0;

        $img_src = $image ? wp_get_attachment_image_url( $image, 'thumbnail' ) : SLGE_PLUGIN_URL . 'assets/placeholder.png';

        echo '<li class="slg-tile">';
        echo '  <span class="dashicons dashicons-move slg-handle"></span>';
        echo '  <div class="slg-thumb"><img src="' . esc_url( $img_src ) . '" alt="" /></div>';
        echo '  <div class="slg-fields' . ( $lesson_id > 0 ? ' is-bound' : '' ) . '">';
        echo '      <input type="text" class="slg-title" placeholder="' . esc_attr__( 'Title', 'sensei-lesson-grid-editor' ) . '" value="' . esc_attr( $title ) . '" />';
        echo '      <input type="url" class="slg-url" placeholder="' . esc_attr__( 'URL (optional if Sensei lesson set)', 'sensei-lesson-grid-editor' ) . '" value="' . esc_attr( $url ) . '" />';
        echo '      <div class="slg-row">';
        echo '          <input type="number" class="slg-lesson-id" placeholder="' . esc_attr__( 'Sensei lesson ID (optional)', 'sensei-lesson-grid-editor' ) . '" value="' . intval( $lesson_id ) . '" min="0" step="1" />';
        echo '          <button type="button" class="button slg-pick-lesson">' . esc_html__( 'Pick Sensei lesson', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '          <button type="button" class="button slg-pick-media">' . esc_html__( 'Choose image', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '          <input type="hidden" class="slg-image-id" value="' . intval( $image ) . '" />';
        echo '          <button type="button" class="button-link-delete slg-remove-tile">' . esc_html__( 'Remove', 'sensei-lesson-grid-editor' ) . '</button>';
        if ( $lesson_id > 0 ) {
            echo '          <span class="slg-chip">Sensei les gekoppeld <button type="button" class="button button-small slg-unlink">Ontkoppelen</button></span>';
        }
        echo '      </div>';
        echo '  </div>';
        echo '</li>';
    }

    public function save_grid_meta( $post_id, $post ) {
        if ( ! isset( $_POST['slg_structure'] ) ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        $json = wp_unslash( $_POST['slg_structure'] );
        $arr  = json_decode( $json, true );
        if ( is_array( $arr ) ) {
            $hide = isset( $_POST['slg_hide_sensei_default'] ) ? 1 : 0;
            update_post_meta( $post_id, '_slg_hide_sensei_default', $hide );
            update_post_meta( $post_id, '_slg_structure', $arr );
            if ( function_exists( 'clean_post_cache' ) ) { clean_post_cache( $post_id ); }
            if ( function_exists( 'wp_cache_delete' ) ) { wp_cache_delete( $post_id, 'post_meta' ); }
            do_action( 'slg_grid_updated', $post_id );
        }
    }

    public function ajax_save_structure() {
        check_ajax_referer( 'slge_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( 'no_cap' ); }
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $payload = isset( $_POST['structure'] ) ? json_decode( stripslashes( $_POST['structure'] ), true ) : array();
        if ( ! $post_id || ! is_array( $payload ) ) { wp_send_json_error( 'bad_payload' ); }
        update_post_meta( $post_id, '_slg_structure', $payload );
        if ( function_exists( 'clean_post_cache' ) ) { clean_post_cache( $post_id ); }
        if ( function_exists( 'wp_cache_delete' ) ) { wp_cache_delete( $post_id, 'post_meta' ); }
        do_action( 'slg_grid_updated', $post_id );
        wp_send_json_success();
    }

    public function ajax_search_lessons() {
        check_ajax_referer( 'slge_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( 'no_cap' ); }
        $s = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
        $args = array(
            'post_type'      => 'lesson',
            's'              => $s,
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        );
        $q = new WP_Query( $args );
        $out = array();
        foreach ( $q->posts as $p ) {
            $thumb = get_the_post_thumbnail_url( $p->ID, 'thumbnail' );
            $out[] = array(
                'id'        => $p->ID,
                'title'     => get_the_title( $p->ID ),
                'permalink' => get_permalink( $p->ID ),
                'thumb'     => $thumb ? $thumb : '',
            );
        }
        wp_send_json_success( $out );
    }

    /* ===== Shortcode ===== */
    public function shortcode_lesson_grid( $atts ) {
        $atts = shortcode_atts( array( 'slug' => '', 'debug' => '' ), $atts );
        if ( empty( $atts['slug'] ) ) { return ''; }

        $q = new WP_Query( array(
            'post_type'      => 'slg_grid',
            'name'           => sanitize_title( $atts['slug'] ),
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ) );
        if ( ! $q->have_posts() ) { return ''; }
        $post      = $q->posts[0];
        $structure = get_post_meta( $post->ID, '_slg_structure', true );
        if ( ! is_array( $structure ) ) { return ''; }

        ob_start();
        echo '<div class="slge-lesson-grid-container">';
        foreach ( $structure as $module ) {
            $mname = isset( $module['name'] ) ? $module['name'] : '';
            $tiles = isset( $module['tiles'] ) ? $module['tiles'] : array();
            if ( empty( $tiles ) ) { continue; }
            echo '<div class="slge-module-section">';
            echo '  <h3 class="slge-module-title">' . esc_html( $mname ) . '</h3>';
            echo '  <div class="slge-lesson-grid">';
            foreach ( $tiles as $tile ) {
                echo $this->render_tile( $tile );
            }
            echo '  </div>';
            echo '</div>';
        }
        echo '</div>';
        // Hide Sensei default modules/lessons on single course if opted in
        $hide = get_post_meta( $post->ID, '_slg_hide_sensei_default', true );
        if ( $hide && function_exists('is_singular') && is_singular('course') ) {
            echo '<style>body.single-course .sensei-course-content, body.single-course .course-lessons, body.single-course .course-lesson-list{display:none!important}</style>';
        }
        if ( ! empty( $atts['debug'] ) ) {
            $hash = md5( wp_json_encode( $structure ) );
            echo '<div class="slg-debug" style="font-size:11px;color:#6b7280;margin-top:6px">Grid ID: ' . intval( $post->ID ) . ' • Modified: ' . esc_html( get_post_modified_time( 'c', false, $post ) ) . ' • Hash: ' . esc_html( $hash ) . '</div>';
        }
        return ob_get_clean();
    }

    private function render_tile( $tile ) {
        $title     = isset( $tile['title'] ) ? $tile['title'] : '';
        $url       = isset( $tile['url'] ) ? $tile['url'] : '';
        $image     = isset( $tile['image'] ) ? intval( $tile['image'] ) : 0;
        $lesson_id = isset( $tile['lesson_id'] ) ? intval( $tile['lesson_id'] ) : 0;

        // Prefer Sensei data when a lesson is linked (display only; editor never overwrites)
        $image_url = '';
        if ( $lesson_id > 0 && function_exists( 'get_post' ) ) {
            $p = get_post( $lesson_id );
            if ( $p && 'lesson' === $p->post_type ) {
                $title = get_the_title( $lesson_id );
                $url   = get_permalink( $lesson_id );
                $thumb = get_the_post_thumbnail_url( $lesson_id, 'medium' );
                if ( $thumb ) { $image_url = $thumb; }
            }
        }
        if ( ! $image_url ) {
            $image_url = $image ? wp_get_attachment_image_url( $image, 'medium' ) : '';
            if ( ! $image_url ) { $image_url = SLGE_PLUGIN_URL . 'assets/placeholder.png'; }
        }

        // Status for current user (done/lock) incl. guest lock only when Access Permissions enabled
        $show_done = false;
        $show_lock = false;
        $uid       = get_current_user_id();

        // Guests: lock only if "Access Permissions" is enabled
        if ( $lesson_id > 0 && ! $uid ) {
            $ap_enabled = 'yes';
            if ( function_exists( 'Sensei' ) && isset( Sensei()->settings ) && method_exists( Sensei()->settings, 'get' ) ) {
                $ap_enabled = Sensei()->settings->get( 'access_permissions' );
            } else {
                $ap_enabled = get_option( 'sensei_access_permissions', get_option( 'access_permissions', 'yes' ) );
            }
            if ( 'yes' === $ap_enabled || 1 === $ap_enabled || '1' === $ap_enabled || true === $ap_enabled ) {
                $show_lock = true;
            }
        }

        if ( $lesson_id > 0 && function_exists( 'Sensei' ) ) {
            if ( $uid ) {
                if ( isset( Sensei()->lesson ) && method_exists( Sensei()->lesson, 'is_user_complete' ) ) {
                    $show_done = (bool) Sensei()->lesson->is_user_complete( $lesson_id, $uid );
                } elseif ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_lesson' ) ) {
                    $show_done = (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $uid );
                } elseif ( isset( Sensei()->learner ) && method_exists( Sensei()->learner, 'has_completed_lesson' ) ) {
                    $show_done = (bool) Sensei()->learner->has_completed_lesson( $lesson_id, $uid );
                }
            }
            // prerequisites
            $prereq = get_post_meta( $lesson_id, '_lesson_prerequisite', true );
            if ( ! empty( $prereq ) ) {
                $has_pre = is_array( $prereq ) ? intval( reset( $prereq ) ) : intval( $prereq );
                if ( $has_pre ) {
                    $done_pre = false;
                    if ( $uid ) {
                        if ( isset( Sensei()->lesson ) && method_exists( Sensei()->lesson, 'is_user_complete' ) ) {
                            $done_pre = (bool) Sensei()->lesson->is_user_complete( $has_pre, $uid );
                        } elseif ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_lesson' ) ) {
                            $done_pre = (bool) Sensei_Utils::user_completed_lesson( $has_pre, $uid );
                        } elseif ( isset( Sensei()->learner ) && method_exists( Sensei()->learner, 'has_completed_lesson' ) ) {
                            $done_pre = (bool) Sensei()->learner->has_completed_lesson( $has_pre, $uid );
                        }
                    }
                    if ( ! $done_pre ) { $show_lock = true; }
                }
            }
        }

        ob_start();
        echo '<div class="slge-lesson-card' . ( $show_lock ? ' is-locked' : '' ) . '">';
        echo '  <div class="slge-lesson-image-container">';
        echo '    <img class="slge-lesson-image" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" />';
        echo '    <div class="slge-lesson-status">';
        echo '      <span class="slge-status-icon"><img src="' . esc_url( SLGE_PLUGIN_URL . 'assets/' . ( $show_done ? 'klaar.png' : 'niet.png' ) ) . '" alt="status"></span>';
        if ( $show_lock ) {
            $hint = is_user_logged_in() ? __( 'Les is vergrendeld', 'sensei-lesson-grid-editor' ) : __( 'Log in om deze les te openen', 'sensei-lesson-grid-editor' );
            echo '      <span class="slge-status-icon" title="' . esc_attr( $hint ) . '"><img src="' . esc_url( SLGE_PLUGIN_URL . 'assets/lock.png' ) . '" alt="lock"></span>';
            if ( ! $uid ) {
                echo '      <span class="slge-lock-hint">' . esc_html( __( 'Log in om deze les te openen', 'sensei-lesson-grid-editor' ) ) . '</span>';
            }
        }
        echo '    </div>';
        echo '  </div>';
        if ( $show_lock && ! $uid ) {
            $link_open  = '<span class="slge-locked-title" title="' . esc_attr__( 'Log in om deze les te openen', 'sensei-lesson-grid-editor' ) . '">';
            $link_close = '</span>';
        } else {
            $link_open  = $url ? '<a href="' . esc_url( $url ) . '">' : '<span>';
            $link_close = $url ? '</a>' : '</span>';
        }
        echo '  <div class="slge-lesson-title">' . $link_open . esc_html( $title ) . $link_close . '</div>';
        echo '</div>';
        return ob_get_clean();
    }
}

// Bootstrap
new SLGE_Plugin();
