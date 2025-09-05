<?php
/**
 * Plugin Name: Sensei Lesson Grid (Admin-Centric)
 * Description: Centrale grid-editor met modules en tiles. Per module instelbare kolommen (3-6). Shortcode: [lesson_grid slug="..."]. Sensei-koppeling voor voortgang en lock. (v1.1.0)
 * Version: 1.1.0
 * Author: Harold Otten
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Text Domain: sensei-lesson-grid-editor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
if ( ! defined( 'SLGE_VERSION' ) )     define( 'SLGE_VERSION', '1.1.0' );
if ( ! defined( 'SLGE_PLUGIN_FILE' ) ) define( 'SLGE_PLUGIN_FILE', __FILE__ );
if ( ! defined( 'SLGE_PLUGIN_PATH' ) ) define( 'SLGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'SLGE_PLUGIN_URL' ) )  define( 'SLGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class SLGE_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );
        add_action( 'save_post_slg_grid', array( $this, 'save_grid_meta' ), 10, 2 );

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

        add_shortcode( 'lesson_grid', array( $this, 'shortcode_lesson_grid' ) );

        // AJAX
        add_action( 'wp_ajax_slg_save_structure', array( $this, 'ajax_save_structure' ) );
        add_action( 'wp_ajax_slg_search_lessons', array( $this, 'ajax_search_lessons' ) );
        add_action( 'wp_ajax_slg_get_lesson_data', array( $this, 'ajax_get_lesson_data' ) );
        
        // Security improvements
        add_action( 'wp_ajax_nopriv_slg_search_lessons', array( $this, 'ajax_no_priv_handler' ) );
        add_action( 'wp_ajax_nopriv_slg_save_structure', array( $this, 'ajax_no_priv_handler' ) );
        add_action( 'wp_ajax_nopriv_slg_get_lesson_data', array( $this, 'ajax_no_priv_handler' ) );
    }

    /* ===== Security ===== */
    public function ajax_no_priv_handler() {
        wp_send_json_error( array( 'message' => __( 'Unauthorized access', 'sensei-lesson-grid-editor' ) ) );
    }

    private function sanitize_tile_data( $tile ) {
        return array(
            'title'     => isset( $tile['title'] ) ? sanitize_text_field( $tile['title'] ) : '',
            'url'       => isset( $tile['url'] ) ? esc_url_raw( $tile['url'] ) : '',
            'image'     => isset( $tile['image'] ) ? absint( $tile['image'] ) : 0,
            'lesson_id' => isset( $tile['lesson_id'] ) ? absint( $tile['lesson_id'] ) : 0,
        );
    }

    private function sanitize_structure_data( $structure ) {
        if ( ! is_array( $structure ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $structure as $module ) {
            if ( ! is_array( $module ) ) {
                continue;
            }

            $sanitized_module = array(
                'name'    => isset( $module['name'] ) ? sanitize_text_field( $module['name'] ) : '',
                'columns' => isset( $module['columns'] ) ? absint( $module['columns'] ) : 5,
                'tiles'   => array(),
            );

            // Validate columns value
            if ( ! in_array( $sanitized_module['columns'], array( 3, 4, 5, 6 ) ) ) {
                $sanitized_module['columns'] = 5;
            }

            if ( isset( $module['tiles'] ) && is_array( $module['tiles'] ) ) {
                foreach ( $module['tiles'] as $tile ) {
                    $sanitized_module['tiles'][] = $this->sanitize_tile_data( $tile );
                }
            }

            $sanitized[] = $sanitized_module;
        }

        return $sanitized;
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
            'not_found'          => __( 'No lesson grids found', 'sensei-lesson-grid-editor' ),
            'not_found_in_trash' => __( 'No lesson grids found in trash', 'sensei-lesson-grid-editor' ),
        );

        $args = array(
            'labels'        => $labels,
            'public'        => false,
            'show_ui'       => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-screenoptions',
            'supports'      => array( 'title' ),
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post'          => 'edit_posts',
                'read_post'          => 'read_posts',
                'delete_post'        => 'delete_posts',
                'edit_posts'         => 'edit_posts',
                'edit_others_posts'  => 'edit_others_posts',
                'delete_posts'       => 'delete_posts',
                'publish_posts'      => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
            ),
        );

        register_post_type( 'slg_grid', $args );
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
        echo '<p><small>' . esc_html__( 'Of gebruik in PHP:', 'sensei-lesson-grid-editor' ) . '<br>';
        echo '<code>echo do_shortcode(\'[lesson_grid slug="' . esc_attr( $slug ) . '"]\');</code></small></p>';
    }

    public function admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || 'slg_grid' !== $screen->post_type ) { 
            return; 
        }

        wp_enqueue_style( 'slge-admin', SLGE_PLUGIN_URL . 'assets/admin.css', array(), SLGE_VERSION );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'slge-admin', SLGE_PLUGIN_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SLGE_VERSION, true );
        
        wp_localize_script( 'slge-admin', 'SLGE', array(
            'nonce'     => wp_create_nonce( 'slge_nonce' ),
            'ajax'      => admin_url( 'admin-ajax.php' ),
            'pluginUrl' => SLGE_PLUGIN_URL,
            'i18n'      => array(
                'addModule'     => __( 'Add module', 'sensei-lesson-grid-editor' ),
                'addTile'       => __( 'Add tile', 'sensei-lesson-grid-editor' ),
                'confirmDelete' => __( 'Are you sure you want to delete this?', 'sensei-lesson-grid-editor' ),
                'saveSuccess'   => __( 'Structure saved successfully', 'sensei-lesson-grid-editor' ),
                'saveError'     => __( 'Error saving structure', 'sensei-lesson-grid-editor' ),
            ),
        ) );
        wp_enqueue_media();
    }

    public function frontend_assets() {
        wp_enqueue_style( 'slge-frontend', SLGE_PLUGIN_URL . 'assets/frontend.css', array(), SLGE_VERSION );
    }

    public function metabox_modules( $post ) {
        wp_nonce_field( 'slg_save_grid_meta', 'slg_grid_nonce' );
        
        $structure = get_post_meta( $post->ID, '_slg_structure', true );
        if ( ! is_array( $structure ) ) { 
            $structure = array(); 
        }
        
        if ( empty( $structure ) ) {
            $structure = array(
                array( 
                    'name'    => __( 'Module 1', 'sensei-lesson-grid-editor' ), 
                    'columns' => 5,
                    'tiles'   => array() 
                ),
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
        $this->render_lesson_modal();

        echo '</div>'; // #slg-editor
    }

    private function render_lesson_modal() {
        ?>
        <div id="slg-lesson-modal" class="slg-lesson-modal" style="display:none;">
            <div class="slg-lesson-modal__backdrop"></div>
            <div class="slg-lesson-modal__dialog">
                <div class="slg-lesson-modal__head">
                    <strong><?php esc_html_e( 'Pick Sensei lesson', 'sensei-lesson-grid-editor' ); ?></strong>
                    <button type="button" class="button-link slg-lesson-modal__close">&times;</button>
                </div>
                <div class="slg-lesson-modal__body">
                    <input type="search" id="slg-lesson-search" placeholder="<?php esc_attr_e( 'Search lessons...', 'sensei-lesson-grid-editor' ); ?>" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:8px;" />
                    <div id="slg-lesson-results" class="slg-lesson-results"></div>
                </div>
                <div class="slg-lesson-modal__foot">
                    <button type="button" class="button slg-lesson-modal__close"><?php esc_html_e( 'Close', 'sensei-lesson-grid-editor' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    public function metabox_options( $post ) {
        wp_nonce_field( 'slg_save_options_meta', 'slg_options_nonce' );
        
        $hide = get_post_meta( $post->ID, '_slg_hide_sensei_default', true ) ? 1 : 0;
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<td><label><input type="checkbox" name="slg_hide_sensei_default" value="1" ' . checked( 1, $hide, false ) . ' /> ';
        echo esc_html__( 'Verberg standaard Sensei modules/lessen op course-pagina', 'sensei-lesson-grid-editor' ) . '</label></td>';
        echo '</tr>';
        echo '</table>';
        
        echo '<p class="description">' . esc_html__( 'Alleen van toepassing wanneer deze grid op een course-pagina wordt gebruikt.', 'sensei-lesson-grid-editor' ) . '</p>';
    }

    private function print_module_block( $mIndex, $module ) {
        $mName    = isset( $module['name'] ) ? sanitize_text_field( $module['name'] ) : __( 'Module', 'sensei-lesson-grid-editor' );
        $mColumns = isset( $module['columns'] ) ? absint( $module['columns'] ) : 5;
        
        // Validate columns
        if ( ! in_array( $mColumns, array( 3, 4, 5, 6 ) ) ) {
            $mColumns = 5;
        }
        
        echo '<div class="slg-module" data-index="' . intval( $mIndex ) . '">';
        echo '  <div class="slg-module-head">';
        echo '    <span class="dashicons dashicons-move slg-handle" title="' . esc_attr__( 'Drag to reorder', 'sensei-lesson-grid-editor' ) . '"></span>';
        echo '    <input type="text" class="slg-module-name" value="' . esc_attr( $mName ) . '" placeholder="' . esc_attr__( 'Module name', 'sensei-lesson-grid-editor' ) . '" />';
        
        // Column selector
        echo '    <select class="slg-module-columns" title="' . esc_attr__( 'Number of columns', 'sensei-lesson-grid-editor' ) . '">';
        echo '      <option value="3"' . selected( $mColumns, 3, false ) . '>3 ' . esc_html__( 'columns', 'sensei-lesson-grid-editor' ) . '</option>';
        echo '      <option value="4"' . selected( $mColumns, 4, false ) . '>4 ' . esc_html__( 'columns', 'sensei-lesson-grid-editor' ) . '</option>';
        echo '      <option value="5"' . selected( $mColumns, 5, false ) . '>5 ' . esc_html__( 'columns', 'sensei-lesson-grid-editor' ) . '</option>';
        echo '      <option value="6"' . selected( $mColumns, 6, false ) . '>6 ' . esc_html__( 'columns', 'sensei-lesson-grid-editor' ) . '</option>';
        echo '    </select>';
        
        echo '    <button type="button" class="button-link-delete slg-remove-module" title="' . esc_attr__( 'Remove module', 'sensei-lesson-grid-editor' ) . '">&times;</button>';
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
        $tile = $this->sanitize_tile_data( $tile );
        
        $img_src = $tile['image'] ? wp_get_attachment_image_url( $tile['image'], 'thumbnail' ) : null;
        if ( ! $img_src ) {
            $img_src = SLGE_PLUGIN_URL . 'assets/placeholder.png';
        }

        $is_bound = $tile['lesson_id'] > 0;
        
        // Get lesson data if bound to prefill fields  
        $lesson_title = $tile['title'];
        $lesson_url = $tile['url'];
        
        if ( $is_bound ) {
            $lesson_data = $this->get_lesson_data( $tile['lesson_id'] );
            if ( $lesson_data ) {
                $lesson_title = $lesson_data['title'];
                $lesson_url = $lesson_data['url'];
                if ( $lesson_data['thumb'] ) {
                    $img_src = $lesson_data['thumb'];
                }
            }
        }
        
        echo '<li class="slg-tile">';
        echo '  <span class="dashicons dashicons-move slg-handle" title="' . esc_attr__( 'Drag to reorder', 'sensei-lesson-grid-editor' ) . '"></span>';
        echo '  <div class="slg-thumb"><img src="' . esc_url( $img_src ) . '" alt="" /></div>';
        echo '  <div class="slg-fields' . ( $is_bound ? ' is-bound' : '' ) . '">';
        echo '      <input type="text" class="slg-title" placeholder="' . esc_attr__( 'Title', 'sensei-lesson-grid-editor' ) . '" value="' . esc_attr( $lesson_title ) . '" ' . ( $is_bound ? 'readonly' : '' ) . ' />';
        echo '      <input type="url" class="slg-url" placeholder="' . esc_attr__( 'URL (optional if Sensei lesson set)', 'sensei-lesson-grid-editor' ) . '" value="' . esc_attr( $lesson_url ) . '" ' . ( $is_bound ? 'readonly' : '' ) . ' />';
        echo '      <div class="slg-row">';
        echo '          <input type="number" class="slg-lesson-id" placeholder="' . esc_attr__( 'Sensei lesson ID (optional)', 'sensei-lesson-grid-editor' ) . '" value="' . intval( $tile['lesson_id'] ) . '" min="0" step="1" />';
        echo '          <button type="button" class="button slg-pick-lesson">' . esc_html__( 'Pick Sensei lesson', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '          <button type="button" class="button slg-pick-media">' . esc_html__( 'Choose image', 'sensei-lesson-grid-editor' ) . '</button>';
        echo '          <input type="hidden" class="slg-image-id" value="' . intval( $tile['image'] ) . '" />';
        echo '          <button type="button" class="button-link-delete slg-remove-tile" title="' . esc_attr__( 'Remove tile', 'sensei-lesson-grid-editor' ) . '">' . esc_html__( 'Remove', 'sensei-lesson-grid-editor' ) . '</button>';
        
        if ( $is_bound ) {
            echo '          <span class="slg-chip">' . esc_html__( 'Sensei lesson linked', 'sensei-lesson-grid-editor' ) . ' ';
            echo '<button type="button" class="button button-small slg-unlink">' . esc_html__( 'Unlink', 'sensei-lesson-grid-editor' ) . '</button></span>';
        }
        
        echo '      </div>';
        echo '  </div>';
        echo '</li>';
    }

    public function save_grid_meta( $post_id, $post ) {
        // Security checks
        if ( ! isset( $_POST['slg_grid_nonce'] ) || ! wp_verify_nonce( $_POST['slg_grid_nonce'], 'slg_save_grid_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save structure
        if ( isset( $_POST['slg_structure'] ) ) {
            $json = wp_unslash( $_POST['slg_structure'] );
            $arr  = json_decode( $json, true );
            $arr  = $this->sanitize_structure_data( $arr );
            update_post_meta( $post_id, '_slg_structure', $arr );
        }

        // Save options
        if ( isset( $_POST['slg_options_nonce'] ) && wp_verify_nonce( $_POST['slg_options_nonce'], 'slg_save_options_meta' ) ) {
            $hide = isset( $_POST['slg_hide_sensei_default'] ) ? 1 : 0;
            update_post_meta( $post_id, '_slg_hide_sensei_default', $hide );
        }

        // Clear caches
        $this->clear_post_cache( $post_id );
        do_action( 'slg_grid_updated', $post_id );
    }

    private function clear_post_cache( $post_id ) {
        if ( function_exists( 'clean_post_cache' ) ) { 
            clean_post_cache( $post_id ); 
        }
        if ( function_exists( 'wp_cache_delete' ) ) { 
            wp_cache_delete( $post_id, 'post_meta' ); 
        }
    }

    public function ajax_save_structure() {
        // Security checks
        if ( ! check_ajax_referer( 'slge_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sensei-lesson-grid-editor' ) ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'sensei-lesson-grid-editor' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $payload = isset( $_POST['structure'] ) ? json_decode( stripslashes( $_POST['structure'] ), true ) : array();

        if ( ! $post_id || ! is_array( $payload ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data', 'sensei-lesson-grid-editor' ) ) );
        }

        // Verify user can edit this specific post
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Cannot edit this post', 'sensei-lesson-grid-editor' ) ) );
        }

        $payload = $this->sanitize_structure_data( $payload );
        update_post_meta( $post_id, '_slg_structure', $payload );
        
        $this->clear_post_cache( $post_id );
        do_action( 'slg_grid_updated', $post_id );
        
        wp_send_json_success( array( 'message' => __( 'Structure saved', 'sensei-lesson-grid-editor' ) ) );
    }

    public function ajax_search_lessons() {
        // Security checks
        if ( ! check_ajax_referer( 'slge_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sensei-lesson-grid-editor' ) ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'sensei-lesson-grid-editor' ) ) );
        }

        $search_term = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
        
        $args = array(
            'post_type'      => 'lesson',
            's'              => $search_term,
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
            'fields'         => 'ids', // Only get IDs for better performance
        );

        // Allow filtering the search query
        $args = apply_filters( 'slge_lesson_search_args', $args, $search_term );

        $query = new WP_Query( $args );
        $results = array();

        foreach ( $query->posts as $post_id ) {
            $thumb_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
            $results[] = array(
                'id'        => $post_id,
                'title'     => get_the_title( $post_id ),
                'permalink' => get_permalink( $post_id ),
                'thumb'     => $thumb_url ? $thumb_url : '',
            );
        }

        if ( empty( $results ) ) {
            wp_send_json_error( array( 'message' => __( 'No lessons found', 'sensei-lesson-grid-editor' ) ) );
        }

        wp_send_json_success( $results );
    }

    public function ajax_get_lesson_data() {
        // Security checks
        if ( ! check_ajax_referer( 'slge_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'sensei-lesson-grid-editor' ) ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'sensei-lesson-grid-editor' ) ) );
        }

        $lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
        
        if ( ! $lesson_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid lesson ID', 'sensei-lesson-grid-editor' ) ) );
        }

        $lesson_data = $this->get_lesson_data( $lesson_id );
        
        if ( ! $lesson_data ) {
            wp_send_json_error( array( 'message' => __( 'Lesson not found or not published', 'sensei-lesson-grid-editor' ) ) );
        }

        wp_send_json_success( $lesson_data );
    }

    /* ===== Shortcode ===== */
    public function shortcode_lesson_grid( $atts ) {
        $atts = shortcode_atts( array( 
            'slug'  => '', 
            'debug' => '' 
        ), $atts, 'lesson_grid' );

        if ( empty( $atts['slug'] ) ) { 
            return $this->get_error_message( __( 'No slug provided', 'sensei-lesson-grid-editor' ) );
        }

        $grid_post = $this->get_grid_by_slug( $atts['slug'] );
        if ( ! $grid_post ) {
            return $this->get_error_message( __( 'Grid not found', 'sensei-lesson-grid-editor' ) );
        }

        $structure = get_post_meta( $grid_post->ID, '_slg_structure', true );
        if ( ! is_array( $structure ) || empty( $structure ) ) { 
            return $this->get_error_message( __( 'No grid data found', 'sensei-lesson-grid-editor' ) );
        }

        return $this->render_grid( $grid_post, $structure, $atts );
    }

    private function get_grid_by_slug( $slug ) {
        static $cache = array();
        
        if ( isset( $cache[ $slug ] ) ) {
            return $cache[ $slug ];
        }

        $query = new WP_Query( array(
            'post_type'      => 'slg_grid',
            'name'           => sanitize_title( $slug ),
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
        ) );

        $cache[ $slug ] = $query->have_posts() ? $query->posts[0] : false;
        return $cache[ $slug ];
    }

    private function get_error_message( $message ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<div class="slge-error" style="color:#d63384;padding:8px;border:1px solid #f5c2c7;background:#f8d7da;border-radius:4px;">' . 
                   esc_html( $message ) . '</div>';
        }
        return ''; // Don't show errors to non-admins
    }

    private function render_grid( $grid_post, $structure, $atts ) {
        ob_start();
        
        echo '<div class="slge-lesson-grid-container">';
        
        foreach ( $structure as $module ) {
            $module_name = isset( $module['name'] ) ? sanitize_text_field( $module['name'] ) : '';
            $module_columns = isset( $module['columns'] ) ? absint( $module['columns'] ) : 5;
            $tiles = isset( $module['tiles'] ) && is_array( $module['tiles'] ) ? $module['tiles'] : array();
            
            // Validate columns
            if ( ! in_array( $module_columns, array( 3, 4, 5, 6 ) ) ) {
                $module_columns = 5;
            }
            
            if ( empty( $tiles ) ) { 
                continue; 
            }

            echo '<div class="slge-module-section">';
            
            if ( ! empty( $module_name ) ) {
                echo '<h3 class="slge-module-title">' . esc_html( $module_name ) . '</h3>';
            }
            
            // Add data-columns attribute for CSS targeting
            echo '<div class="slge-lesson-grid" data-columns="' . esc_attr( $module_columns ) . '">';
            
            foreach ( $tiles as $tile ) {
                echo $this->render_tile( $tile );
            }
            
            echo '</div>'; // .slge-lesson-grid
            echo '</div>'; // .slge-module-section
        }
        
        echo '</div>'; // .slge-lesson-grid-container

        // Hide Sensei default modules/lessons on single course if opted in
        $hide_default = get_post_meta( $grid_post->ID, '_slg_hide_sensei_default', true );
        if ( $hide_default && function_exists( 'is_singular' ) && is_singular( 'course' ) ) {
            echo '<style>.course .modules-title, .course .module{display:none!important}</style>';
        }

        // Debug information for admins
        if ( ! empty( $atts['debug'] ) && current_user_can( 'edit_posts' ) ) {
            $this->render_debug_info( $grid_post, $structure );
        }

        return ob_get_clean();
    }

    private function render_debug_info( $grid_post, $structure ) {
        $hash = md5( wp_json_encode( $structure ) );
        echo '<div class="slg-debug" style="font-size:11px;color:#6b7280;margin-top:6px;padding:8px;background:#f9fafb;border-radius:4px;">';
        echo 'Grid ID: ' . intval( $grid_post->ID ) . ' • ';
        echo 'Modified: ' . esc_html( get_post_modified_time( 'c', false, $grid_post ) ) . ' • ';
        echo 'Hash: ' . esc_html( $hash ) . ' • ';
        echo 'Modules: ' . count( $structure ) . ' • ';
        $total_tiles = array_sum( array_map( function( $m ) { return count( $m['tiles'] ?? array() ); }, $structure ) );
        echo 'Tiles: ' . $total_tiles;
        echo '</div>';
    }

    private function render_tile( $tile ) {
        $tile = $this->sanitize_tile_data( $tile );
        
        // Get lesson data if linked
        $lesson_data = $this->get_lesson_data( $tile['lesson_id'] );
        
        // Use lesson data if available, otherwise use tile data
        $title = isset( $lesson_data['title'] ) ? $lesson_data['title'] : $tile['title'];
        $url = isset( $lesson_data['url'] ) ? $lesson_data['url'] : $tile['url'];
        $image_url = $this->get_tile_image_url( $tile, $lesson_data );

        // Status for current user (done/lock)
        $status = $this->get_lesson_status( $tile['lesson_id'] );

        ob_start();
        
        // Check if we have a clickable URL and user is allowed to click
        $is_clickable = ! empty( $url ) && ( ! $status['is_locked'] || is_user_logged_in() );
        
        if ( $is_clickable ) {
            echo '<a href="' . esc_url( $url ) . '" class="slge-lesson-card-link">';
        }
        
        echo '<div class="slge-lesson-card' . ( $status['is_locked'] ? ' is-locked' : '' ) . ( $is_clickable ? ' is-clickable' : '' ) . '">';
        echo '  <div class="slge-lesson-image-container">';
        echo '    <img class="slge-lesson-image" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" />';
        
        if ( $tile['lesson_id'] > 0 ) {
            echo '    <div class="slge-lesson-status">';
            
            // Status icon (complete/incomplete)
            echo '      <span class="slge-status-icon">';
            $status_icon = $status['is_complete'] ? 'klaar.png' : 'niet.png';
            $status_alt = $status['is_complete'] ? __( 'Complete', 'sensei-lesson-grid-editor' ) : __( 'Incomplete', 'sensei-lesson-grid-editor' );
            
            // Check if PNG file exists, otherwise fallback to dashicons
            $status_icon_url = SLGE_PLUGIN_URL . 'assets/' . $status_icon;
            if ( $this->file_exists_in_assets( $status_icon ) ) {
                echo '        <img src="' . esc_url( $status_icon_url ) . '" alt="' . esc_attr( $status_alt ) . '" />';
            } else {
                // Fallback to dashicons
                $dashicon_class = $status['is_complete'] ? 'dashicons-yes-alt' : 'dashicons-minus';
                $dashicon_color = $status['is_complete'] ? '#10b981' : '#f59e0b';
                echo '        <span class="dashicons ' . esc_attr( $dashicon_class ) . '" style="color: ' . esc_attr( $dashicon_color ) . '; font-size: 18px;"></span>';
            }
            echo '      </span>';
            
            // Lock icon if locked
            if ( $status['is_locked'] ) {
                echo '      <span class="slge-status-icon" title="' . esc_attr( $status['lock_reason'] ) . '">';
                
                if ( $this->file_exists_in_assets( 'lock.png' ) ) {
                    echo '        <img src="' . esc_url( SLGE_PLUGIN_URL . 'assets/lock.png' ) . '" alt="' . esc_attr__( 'Locked', 'sensei-lesson-grid-editor' ) . '" />';
                } else {
                    // Fallback to dashicon
                    echo '        <span class="dashicons dashicons-lock" style="color: #ef4444; font-size: 18px;"></span>';
                }
                echo '      </span>';
                
                if ( ! is_user_logged_in() ) {
                    echo '      <span class="slge-lock-hint">' . esc_html( $status['lock_reason'] ) . '</span>';
                }
            }
            echo '    </div>';
        }
        
        echo '  </div>';

        // Title (no longer needs its own link since whole card is clickable)
        $title_content = ! empty( $title ) ? esc_html( $title ) : esc_html__( 'Untitled', 'sensei-lesson-grid-editor' );
        if ( $status['is_locked'] && ! is_user_logged_in() ) {
            echo '  <div class="slge-lesson-title slge-locked-title" title="' . esc_attr( $status['lock_reason'] ) . '">' . $title_content . '</div>';
        } else {
            echo '  <div class="slge-lesson-title">' . $title_content . '</div>';
        }
        
        echo '</div>';
        
        if ( $is_clickable ) {
            echo '</a>';
        }
        
        return ob_get_clean();
    }

    private function file_exists_in_assets( $filename ) {
        return file_exists( SLGE_PLUGIN_PATH . 'assets/' . $filename );
    }

    private function get_lesson_data( $lesson_id ) {
        if ( $lesson_id <= 0 || ! function_exists( 'get_post' ) ) {
            return null;
        }

        static $cache = array();
        
        if ( isset( $cache[ $lesson_id ] ) ) {
            return $cache[ $lesson_id ];
        }

        $post = get_post( $lesson_id );
        if ( ! $post || 'lesson' !== $post->post_type || 'publish' !== $post->post_status ) {
            $cache[ $lesson_id ] = null;
            return null;
        }

        $cache[ $lesson_id ] = array(
            'title' => get_the_title( $lesson_id ),
            'url'   => get_permalink( $lesson_id ),
            'thumb' => get_the_post_thumbnail_url( $lesson_id, 'medium' ),
        );

        return $cache[ $lesson_id ];
    }

    private function get_tile_image_url( $tile, $lesson_data ) {
        // Priority: Lesson thumbnail > Custom image > Placeholder
        if ( isset( $lesson_data['thumb'] ) && ! empty( $lesson_data['thumb'] ) ) {
            return $lesson_data['thumb'];
        }

        if ( $tile['image'] > 0 ) {
            $custom_img = wp_get_attachment_image_url( $tile['image'], 'medium' );
            if ( $custom_img ) {
                return $custom_img;
            }
        }

        return SLGE_PLUGIN_URL . 'assets/placeholder.png';
    }

    private function get_lesson_status( $lesson_id ) {
        $status = array(
            'is_complete' => false,
            'is_locked'   => false,
            'lock_reason' => '',
        );

        if ( $lesson_id <= 0 || ! function_exists( 'Sensei' ) ) {
            return $status;
        }

        $user_id = get_current_user_id();

        // Check if lesson is complete for logged-in users
        if ( $user_id && $this->is_lesson_complete( $lesson_id, $user_id ) ) {
            $status['is_complete'] = true;
        }

        // Check lock status
        $lock_check = $this->is_lesson_locked( $lesson_id, $user_id );
        $status['is_locked'] = $lock_check['locked'];
        $status['lock_reason'] = $lock_check['reason'];

        return $status;
    }

    private function is_lesson_complete( $lesson_id, $user_id ) {
        if ( ! $user_id || ! function_exists( 'Sensei' ) ) {
            return false;
        }

        // Try multiple Sensei methods for compatibility
        if ( isset( Sensei()->lesson ) && method_exists( Sensei()->lesson, 'is_user_complete' ) ) {
            return (bool) Sensei()->lesson->is_user_complete( $lesson_id, $user_id );
        }

        if ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_lesson' ) ) {
            return (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
        }

        if ( isset( Sensei()->learner ) && method_exists( Sensei()->learner, 'has_completed_lesson' ) ) {
            return (bool) Sensei()->learner->has_completed_lesson( $lesson_id, $user_id );
        }

        return false;
    }

    private function is_lesson_locked( $lesson_id, $user_id ) {
        $result = array( 'locked' => false, 'reason' => '' );

        if ( $lesson_id <= 0 ) {
            return $result;
        }

        // For guests: only lock if Access Permissions is enabled
        if ( ! $user_id ) {
            $ap_enabled = $this->is_access_permissions_enabled();
            if ( $ap_enabled ) {
                $result['locked'] = true;
                $result['reason'] = __( 'Log in om deze les te openen', 'sensei-lesson-grid-editor' );
            }
            return $result;
        }

        // Check prerequisites for logged-in users
        $prereq = get_post_meta( $lesson_id, '_lesson_prerequisite', true );
        if ( ! empty( $prereq ) ) {
            $prereq_id = is_array( $prereq ) ? absint( reset( $prereq ) ) : absint( $prereq );
            
            if ( $prereq_id > 0 && ! $this->is_lesson_complete( $prereq_id, $user_id ) ) {
                $prereq_title = get_the_title( $prereq_id );
                $result['locked'] = true;
                $result['reason'] = sprintf( 
                    __( 'Complete "%s" first', 'sensei-lesson-grid-editor' ), 
                    $prereq_title 
                );
            }
        }

        return $result;
    }

    private function is_access_permissions_enabled() {
        static $enabled = null;
        
        if ( null !== $enabled ) {
            return $enabled;
        }

        // Try Sensei settings first
        if ( function_exists( 'Sensei' ) && isset( Sensei()->settings ) && method_exists( Sensei()->settings, 'get' ) ) {
            $setting = Sensei()->settings->get( 'access_permissions' );
        } else {
            // Fallback to direct option check
            $setting = get_option( 'sensei_access_permissions', get_option( 'access_permissions', 'yes' ) );
        }

        $enabled = in_array( $setting, array( 'yes', '1', 1, true ), true );
        return $enabled;
    }

    /* ===== Utility Functions ===== */
    public function get_all_grids() {
        return get_posts( array(
            'post_type'      => 'slg_grid',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
    }

    public function get_grid_structure( $grid_id ) {
        $structure = get_post_meta( $grid_id, '_slg_structure', true );
        return is_array( $structure ) ? $structure : array();
    }

    /* ===== Hooks for extensibility ===== */
    public function __call( $method, $args ) {
        // Allow external extensions to hook into this class
        if ( has_action( "slge_call_{$method}" ) ) {
            return apply_filters( "slge_call_{$method}", null, $args, $this );
        }
        
        trigger_error( "Call to undefined method " . __CLASS__ . "::{$method}()", E_USER_ERROR );
    }
}

// Helper function for template usage
function slge_grid_by_slug( $slug ) {
    return do_shortcode( '[lesson_grid slug="' . sanitize_title( $slug ) . '"]' );
}

// Plugin activation/deactivation hooks
register_activation_hook( __FILE__, 'slge_activation' );
register_deactivation_hook( __FILE__, 'slge_deactivation' );

function slge_activation() {
    // Flush rewrite rules to ensure CPT URLs work
    SLGE_Plugin::get_instance();
    flush_rewrite_rules();
}

function slge_deactivation() {
    flush_rewrite_rules();
}

// Initialize plugin
add_action( 'plugins_loaded', function() {
    SLGE_Plugin::get_instance();
} );

// Prevent direct access to plugin files
if ( ! function_exists( 'slge_prevent_direct_access' ) ) {
    function slge_prevent_direct_access() {
        if ( ! defined( 'ABSPATH' ) ) {
            status_header( 403 );
            exit( 'Direct access forbidden.' );
        }
    }
}

slge_prevent_direct_access();