<?php
/**
 * Admin: Sensei Cursus Maps (editor + opslag per cursus)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SLME_Admin {

    const CAPABILITY     = 'manage_options';
    const MENU_SLUG      = 'slme-editor';
    const NONCE_ACTION   = 'slme_nonce_action';
    const NONCE_NAME     = 'slme_nonce';

    // Meta keys (per cursus)
    const META_LAYOUT    = '_slme_layout';        // 'free' | 'columns'
    const META_BG_ID     = '_slme_bg_id';         // attachment id (alleen bij free)
    const META_POSITIONS = '_slme_positions';     // array: [ lesson_id => ['x'=>..,'y'=>..,'z'=>..], ... ]
    const META_TILE_SIZE = '_slme_tile_size';     // 'sm' | 'md' | 'lg' | 'custom'
    const META_TILE_WH   = '_slme_tile_wh';       // ['w'=>int,'h'=>int] voor 'custom'

    public function __construct() {
        // Menu
        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        // Assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX
        add_action( 'wp_ajax_slme_save_layout', array( $this, 'ajax_save_layout' ) );
        add_action( 'wp_ajax_slme_reset_layout', array( $this, 'ajax_reset_layout' ) );
        add_action( 'wp_ajax_slme_preview', array( $this, 'ajax_preview' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Sensei Cursus Maps', 'slme' ),
            __( 'Sensei Cursus Maps', 'slme' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_admin_page' ),
            'dashicons-grid-view',
            58
        );
    }

    public function enqueue_admin_assets( $hook ) {
        // Alleen op onze pagina
        if ( $hook !== 'toplevel_page_' . self::MENU_SLUG ) {
            return;
        }

        // Media (voor achtergrond kiezen)
        wp_enqueue_media();

        // JS
        wp_register_script(
            'slme-admin-editor',
            plugins_url( '../assets/js/admin-editor.js', __FILE__ ),
            array( 'jquery' ),
            defined('SLME_VERSION') ? SLME_VERSION : '0.0.0',
            true
        );

        // Nonce + vertalingen + ajax url
        wp_localize_script( 'slme-admin-editor', 'slmeEditor', array(
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
            'i18n'         => array(
                'saved'     => __( 'Opgeslagen', 'slme' ),
                'error'     => __( 'Fout', 'slme' ),
                'reset_ok'  => __( 'Gerest', 'slme' ),
            ),
        ) );

        wp_enqueue_script( 'slme-admin-editor' );

        // Eenvoudige inline styles (bewust minimaal)
        $css = '
        .slme-wrap { max-width: 1200px; }
        .slme-row { display:flex; gap:20px; align-items:flex-start; }
        .slme-col { flex:1; min-width: 320px; }
        .slme-field { margin-bottom:12px; }
        .slme-preview { border:1px solid #ddd; min-height: 420px; background:#fafafa; position:relative; overflow:auto; }
        .slme-toolbar { display:flex; gap:10px; margin-top:10px; }
        .slme-note { color:#555; font-size:12px; }
        .slme-tile { border:1px solid #ccc; background:#fff; position:absolute; box-shadow:0 1px 2px rgba(0,0,0,.06); display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .slme-tile img { width:100%; height:100%; object-fit:cover; }
        .slme-tile .slme-badge { position:absolute; top:4px; right:6px; font-size:14px; }
        .slme-draggable { cursor:move; }
        .hidden { display:none; }
        ';
        wp_add_inline_style( 'wp-admin', $css );
    }

    public function render_admin_page() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_die( __( 'Je hebt geen rechten om dit te bekijken.', 'slme' ) );
        }

        // Huidige selectie (optioneel via query arg)
        $selected_course = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : 0;

        // Cursuslijst (Sensei -> post_type=course)
        $courses = get_posts( array(
            'post_type'      => 'course',
            'posts_per_page' => 100,
            'post_status'    => array( 'publish', 'draft', 'private' ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <div class="wrap slme-wrap">
            <h1><?php esc_html_e( 'Sensei Cursus Maps', 'slme' ); ?></h1>

            <div class="slme-row" id="slme-editor-root" data-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
                <div class="slme-col">
                    <div class="slme-field">
                        <label for="slme-course"><strong><?php esc_html_e( 'Kies cursus', 'slme' ); ?></strong></label><br>
                        <select id="slme-course" style="min-width:280px;">
                            <option value="0"><?php esc_html_e( '— Selecteer —', 'slme' ); ?></option>
                            <?php foreach ( $courses as $c ): ?>
                                <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $selected_course, $c->ID ); ?>>
                                    <?php echo esc_html( get_the_title( $c->ID ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="slme-field">
                        <label for="slme-layout"><strong><?php esc_html_e( 'Layout', 'slme' ); ?></strong></label><br>
                        <select id="slme-layout">
                            <option value="free"><?php esc_html_e( 'Vrije kaart (drag & drop)', 'slme' ); ?></option>
                            <option value="columns"><?php esc_html_e( 'Kolommen (modules -> kolommen)', 'slme' ); ?></option>
                        </select>
                        <div class="slme-note">
                            <?php esc_html_e( 'Bij Kolommen wordt geen achtergrond gebruikt en de Sensei-lesvolgorde per module aangehouden.', 'slme' ); ?>
                        </div>
                    </div>

                    <div id="slme-free-only" class="slme-field">
                        <label><strong><?php esc_html_e( 'Achtergrond (alleen Vrije kaart)', 'slme' ); ?></strong></label><br>
                        <input type="hidden" id="slme-bg-id" value="">
                        <button class="button" id="slme-choose-bg"><?php esc_html_e( 'Kies/Verander afbeelding', 'slme' ); ?></button>
                        <button class="button-link-delete" id="slme-remove-bg"><?php esc_html_e( 'Verwijder', 'slme' ); ?></button>
                        <div id="slme-bg-preview" style="margin-top:8px;"></div>
                        <div class="slme-note">
                            <?php esc_html_e( 'Achtergrond wordt éénmaal gebruikt en virtueel in 3 verticale stukken gedeeld.', 'slme' ); ?>
                        </div>
                    </div>

                    <div class="slme-field">
                        <label for="slme-tile-size"><strong><?php esc_html_e( 'Tegelgrootte', 'slme' ); ?></strong></label><br>
                        <select id="slme-tile-size">
                            <option value="sm"><?php esc_html_e( 'Klein', 'slme' ); ?></option>
                            <option value="md" selected><?php esc_html_e( 'Middel', 'slme' ); ?></option>
                            <option value="lg"><?php esc_html_e( 'Groot', 'slme' ); ?></option>
                            <option value="custom"><?php esc_html_e( 'Aangepast (px)', 'slme' ); ?></option>
                        </select>
                        <div id="slme-tile-custom" class="slme-field hidden" style="display:flex; gap:8px; align-items:center;">
                            <label><?php esc_html_e( 'Breedte', 'slme' ); ?> <input type="number" id="slme-tile-w" min="40" max="600" value="140" style="width:90px;"></label>
                            <label><?php esc_html_e( 'Hoogte', 'slme' ); ?> <input type="number" id="slme-tile-h" min="40" max="600" value="140" style="width:90px;"></label>
                        </div>
                    </div>

                    <div class="slme-toolbar">
                        <button class="button button-primary" id="slme-save"><?php esc_html_e( 'Opslaan', 'slme' ); ?></button>
                        <button class="button" id="slme-preview-btn"><?php esc_html_e( 'Voorbeeld verversen', 'slme' ); ?></button>
                        <button class="button button-link-delete" id="slme-reset"><?php esc_html_e( 'Reset cursusinstellingen', 'slme' ); ?></button>
                    </div>
                </div>

                <div class="slme-col">
                    <label><strong><?php esc_html_e( 'Inline preview', 'slme' ); ?></strong></label>
                    <div id="slme-preview" class="slme-preview">
                        <div style="padding:20px;color:#666;"><?php esc_html_e( 'Kies eerst een cursus…', 'slme' ); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: opslaan
     */
    public function ajax_save_layout() {
        $this->check_ajax_security();

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        if ( ! $course_id ) {
            wp_send_json_error( array( 'message' => 'Geen geldige cursus.' ) );
        }

        $layout    = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'free';
        $bg_id     = isset($_POST['bg_id']) ? intval($_POST['bg_id']) : 0;
        $tile_size = isset($_POST['tile_size']) ? sanitize_text_field($_POST['tile_size']) : 'md';

        $tile_w = isset($_POST['tile_w']) ? intval($_POST['tile_w']) : 140;
        $tile_h = isset($_POST['tile_h']) ? intval($_POST['tile_h']) : 140;

        // posities: JSON array
        $positions = array();
        if ( isset($_POST['positions']) ) {
            $raw = wp_unslash( $_POST['positions'] );
            $arr = json_decode( $raw, true );
            if ( is_array( $arr ) ) {
                // force ints
                foreach ( $arr as $lesson_id => $pos ) {
                    $lesson_id = intval( $lesson_id );
                    $x = isset($pos['x']) ? intval($pos['x']) : 0;
                    $y = isset($pos['y']) ? intval($pos['y']) : 0;
                    $z = isset($pos['z']) ? intval($pos['z']) : 1;
                    $positions[ $lesson_id ] = array( 'x'=>$x, 'y'=>$y, 'z'=>$z );
                }
            }
        }

        // Opslag
        update_post_meta( $course_id, self::META_LAYOUT, $layout );
        update_post_meta( $course_id, self::META_TILE_SIZE, $tile_size );

        if ( $tile_size === 'custom' ) {
            update_post_meta( $course_id, self::META_TILE_WH, array( 'w'=>$tile_w, 'h'=>$tile_h ) );
        } else {
            delete_post_meta( $course_id, self::META_TILE_WH );
        }

        if ( $layout === 'free' ) {
            update_post_meta( $course_id, self::META_POSITIONS, $positions );
            update_post_meta( $course_id, self::META_BG_ID, $bg_id );
        } else {
            // Kolommen: geen bg/posities
            delete_post_meta( $course_id, self::META_POSITIONS );
            delete_post_meta( $course_id, self::META_BG_ID );
        }

        wp_send_json_success( array( 'message' => 'Opgeslagen' ) );
    }

    /**
     * AJAX: reset
     */
    public function ajax_reset_layout() {
        $this->check_ajax_security();

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        if ( ! $course_id ) {
            wp_send_json_error( array( 'message' => 'Geen geldige cursus.' ) );
        }

        delete_post_meta( $course_id, self::META_LAYOUT );
        delete_post_meta( $course_id, self::META_BG_ID );
        delete_post_meta( $course_id, self::META_POSITIONS );
        delete_post_meta( $course_id, self::META_TILE_SIZE );
        delete_post_meta( $course_id, self::META_TILE_WH );

        wp_send_json_success( array( 'message' => 'Gerest' ) );
    }

    /**
     * AJAX: preview html ophalen
     */
    public function ajax_preview() {
        $this->check_ajax_security();

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        if ( ! $course_id ) {
            wp_send_json_error( array( 'message' => 'Geen geldige cursus.' ) );
        }

        $layout    = get_post_meta( $course_id, self::META_LAYOUT, true );
        if ( ! $layout ) $layout = 'free';

        $bg_id     = intval( get_post_meta( $course_id, self::META_BG_ID, true ) );
        $tile_size = get_post_meta( $course_id, self::META_TILE_SIZE, true );
        if ( ! in_array( $tile_size, array('sm','md','lg','custom'), true ) ) $tile_size = 'md';

        $tile_wh   = get_post_meta( $course_id, self::META_TILE_WH, true );
        $w = 140; $h = 140;
        if ( $tile_size === 'sm' ) { $w=110; $h=110; }
        if ( $tile_size === 'md' ) { $w=140; $h=140; }
        if ( $tile_size === 'lg' ) { $w=180; $h=180; }
        if ( $tile_size === 'custom' && is_array($tile_wh) ) {
            $w = max(40, intval($tile_wh['w'] ?? 140));
            $h = max(40, intval($tile_wh['h'] ?? 140));
        }

        // Lessen ophalen
        $html  = '';
        $style = '';

        if ( $layout === 'columns' ) {
            $modules = function_exists('Sensei') ? Sensei()->modules->get_course_modules( $course_id ) : array();
            if ( empty( $modules ) ) {
                $html .= '<div style="padding:20px;">' . esc_html__( 'Geen modules gevonden voor deze cursus.', 'slme' ) . '</div>';
            } else {
                // Kolommen container
                $html .= '<div class="slme-columns-container" style="display:flex; gap:10px;">';
                $module_count = count( $modules );
                foreach ( $modules as $module ) {
                    $lessons = Sensei()->modules->get_lessons( $module->term_id, $course_id );
                    $html .= '<div class="slme-column" style="flex:1; min-width:' . esc_attr( floor(100/$module_count) ) . '%;">';
                    $html .= '<div style="font-weight:600; margin:6px 0;">' . esc_html( $module->name ) . '</div>';
                    if ( ! empty( $lessons ) ) {
                        foreach ( $lessons as $lesson_id ) {
                            $thumb = has_post_thumbnail( $lesson_id ) ? get_the_post_thumbnail( $lesson_id, 'medium' ) : '';
                            $completed = class_exists('Sensei_Utils') ? Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() ) : false;
                            $badge = $completed ? '✔' : '✘';
                            $html .= '<div class="slme-tile" style="position:relative; width:'.$w.'px; height:'.$h.'px; margin:6px; static;">';
                            $html .= $thumb ? '<div style="width:100%;height:100%;">'.$thumb.'</div>' : '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:#f5f5f5;">' . esc_html( get_the_title($lesson_id) ) . '</div>';
                            $html .= '<div class="slme-badge">'.esc_html($badge).'</div>';
                            $html .= '</div>';
                        }
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        } else {
            // Vrije kaart
            $positions = get_post_meta( $course_id, self::META_POSITIONS, true );
            if ( ! is_array( $positions ) ) $positions = array();

            $lessons = function_exists('Sensei') ? Sensei()->course->course_lessons( $course_id ) : array();

            $bg_style = '';
            if ( $bg_id ) {
                $bg_url   = wp_get_attachment_image_url( $bg_id, 'full' );
                if ( $bg_url ) {
                    // Eenmaal als background, no-repeat, cover. (Splitsing in 3 kolommen gebeurt virtueel op front via CSS grid)
                    $bg_style = "background-image:url('".esc_url($bg_url)."'); background-repeat:no-repeat; background-size:cover; background-position:center;";
                }
            }

            $html .= '<div id="slme-free-stage" class="slme-free-stage" style="position:relative; width:100%; min-height:600px; '.$bg_style.'">';

            if ( ! empty( $lessons ) ) {
                foreach ( $lessons as $lesson_id ) {
                    $x=20; $y=20; $z=1;
                    if ( isset($positions[$lesson_id]) ) {
                        $x = intval($positions[$lesson_id]['x'] ?? 20);
                        $y = intval($positions[$lesson_id]['y'] ?? 20);
                        $z = intval($positions[$lesson_id]['z'] ?? 1);
                    }
                    $thumb = has_post_thumbnail( $lesson_id ) ? get_the_post_thumbnail( $lesson_id, 'medium' ) : '';
                    $completed = class_exists('Sensei_Utils') ? Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() ) : false;
                    $badge = $completed ? '✔' : '✘';

                    $html .= '<div class="slme-tile slme-draggable" data-lesson-id="'.esc_attr($lesson_id).'" data-x="'.$x.'" data-y="'.$y.'" data-z="'.$z.'" style="left:'.$x.'px; top:'.$y.'px; z-index:'.$z.'; width:'.$w.'px; height:'.$h.'px;">';
                    $html .= $thumb ? '<img src="'.esc_url( wp_get_attachment_image_url( get_post_thumbnail_id($lesson_id), 'medium' ) ).'" alt="">' : '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:#f5f5f5;">' . esc_html( get_the_title($lesson_id) ) . '</div>';
                    $html .= '<div class="slme-badge">'.$badge.'</div>';
                    $html .= '</div>';
                }
            } else {
                $html .= '<div style="padding:20px;">' . esc_html__( 'Geen lessen gevonden voor deze cursus.', 'slme' ) . '</div>';
            }

            $html .= '</div>';
        }

        wp_send_json_success( array(
            'html'     => $html,
            'layout'   => $layout,
            'bg_id'    => $bg_id,
            'tile_w'   => $w,
            'tile_h'   => $h,
            'tileSize' => $tile_size,
        ) );
    }

    private function check_ajax_security() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            wp_send_json_error( array( 'message' => 'Geen rechten.' ) );
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json_error( array( 'message' => 'Ongeldige nonce.' ) );
        }
    }
}

new SLME_Admin();
