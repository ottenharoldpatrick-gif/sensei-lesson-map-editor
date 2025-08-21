<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLME_Admin {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'wp_ajax_slme_save', [ __CLASS__, 'ajax_save' ] );
	}

	public static function menu() {
		add_menu_page(
			'Lesson Map Editor',
			'Lesson Map',
			'manage_options',
			'slme',
			[ __CLASS__, 'screen' ],
			'dashicons-screenoptions',
			58
		);
	}

	public static function assets( $hook ) {
		if ( $hook !== 'toplevel_page_slme' ) return;

		wp_enqueue_media();

		wp_enqueue_style( 'slme-admin', SLME_URL . 'assets/css/slme-admin.css', [], SLME_VERSION );
		wp_enqueue_script( 'slme-admin', SLME_URL . 'assets/js/slme-admin.js', [ 'jquery' ], SLME_VERSION, true );

		wp_localize_script( 'slme-admin', 'SLME',
			[
				'nonce' => wp_create_nonce( 'slme_nonce' ),
				'ajax'  => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	private static function get_course_select_html() : string {
		$courses = get_posts( [
			'post_type'      => 'course',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => [ 'ids' ],
		] );

		$html = '<select id="slme-course" name="slme_course">';
		$html .= '<option value="">Kies een cursus…</option>';
		foreach ( $courses as $cid ) {
			$html .= sprintf( '<option value="%d">%s</option>', $cid, esc_html( get_the_title( $cid ) ) );
		}
		$html .= '</select>';
		return $html;
	}

	public static function screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang' );
		}

		?>
		<div class="wrap slme-admin">
			<h1>Lesson Map Editor</h1>

			<div class="slme-controls">
				<label>Cursus: <?php echo self::get_course_select_html(); ?></label>

				<div class="slme-row">
					<label>Weergave:</label>
					<label><input type="radio" name="slme_mode" value="free" checked> Vrije kaart (standaard)</label>
					<label><input type="radio" name="slme_mode" value="columns"> Kolommen</label>
				</div>

				<div class="slme-row">
					<label>Achtergrond:</label>
					<input type="hidden" id="slme-bg-id" value="">
					<button class="button" id="slme-bg-choose">Kies uit mediabibliotheek</button>
					<button class="button button-link-delete" id="slme-bg-clear">Verwijder</button>
					<span id="slme-bg-preview"></span>
				</div>

				<div class="slme-row">
					<label>Tile-formaat:</label>
					<select id="slme-tile-size">
						<option value="s">Klein</option>
						<option value="m" selected>Middel</option>
						<option value="l">Groot</option>
						<option value="custom">Eigen (px)</option>
					</select>
					<input type="number" id="slme-tile-w" value="180" min="60" step="10"> ×
					<input type="number" id="slme-tile-h" value="120" min="40" step="10"> px
				</div>

				<div class="slme-row">
					<label><input type="checkbox" id="slme-borders"> Module‑grenzen (borders) tonen</label>
				</div>

				<div class="slme-row">
					<button class="button button-primary" id="slme-save">Opslaan</button>
					<button class="button" id="slme-reset">Reset posities</button>
				</div>
			</div>

			<div class="slme-editor-wrap">
				<div class="slme-editor-canvas" id="slme-canvas" aria-live="polite">
					<div class="slme-editor-hint">Kies eerst een cursus hierboven.</div>
				</div>
			</div>

			<p class="description">Tip: sleep tegels; gebruik <kbd>[</kbd> en <kbd>]</kbd> om z‑index (voor/achter) te wijzigen; dubbelklik tegel om 3‑tekens label te zetten.</p>
		</div>
		<?php
	}

	public static function ajax_save() {
		check_ajax_referer( 'slme_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Geen rechten' ], 403 );
		}

		$course_id = isset( $_POST['course'] ) ? absint( $_POST['course'] ) : 0;
		if ( ! $course_id ) {
			wp_send_json_error( [ 'message' => 'Geen course_id' ], 400 );
		}

		$mode        = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'free';
		$bg_id       = isset( $_POST['bg_id'] ) ? absint( $_POST['bg_id'] ) : 0;
		$tile_size   = isset( $_POST['tile_size'] ) ? sanitize_text_field( $_POST['tile_size'] ) : 'm';
		$tile_w      = isset( $_POST['tile_w'] ) ? absint( $_POST['tile_w'] ) : 180;
		$tile_h      = isset( $_POST['tile_h'] ) ? absint( $_POST['tile_h'] ) : 120;
		$borders     = ! empty( $_POST['borders'] );
		$positions   = isset( $_POST['positions'] ) && is_array( $_POST['positions'] ) ? $_POST['positions'] : [];

		// Sanitize positions
		$clean = [];
		foreach ( $positions as $lesson_id => $p ) {
			$lesson_id = absint( $lesson_id );
			if ( ! $lesson_id ) continue;
			$clean[ $lesson_id ] = [
				'x'     => isset( $p['x'] ) ? intval( $p['x'] ) : 0,
				'y'     => isset( $p['y'] ) ? intval( $p['y'] ) : 0,
				'w'     => isset( $p['w'] ) ? max( 40, intval( $p['w'] ) ) : $tile_w,
				'h'     => isset( $p['h'] ) ? max( 40, intval( $p['h'] ) ) : $tile_h,
				'z'     => isset( $p['z'] ) ? max( 1, intval( $p['z'] ) ) : 1,
				'label' => isset( $p['label'] ) ? substr( sanitize_text_field( $p['label'] ), 0, 3 ) : '',
				'module'=> isset( $p['module'] ) ? sanitize_text_field( $p['module'] ) : '',
			];
		}

		update_post_meta( $course_id, '_slme_mode', $mode );
		update_post_meta( $course_id, '_slme_bg_id', $bg_id );
		update_post_meta( $course_id, '_slme_tile_size', $tile_size );
		update_post_meta( $course_id, '_slme_tile_w', $tile_w );
		update_post_meta( $course_id, '_slme_tile_h', $tile_h );
		update_post_meta( $course_id, '_slme_borders', $borders ? 1 : 0 );
		update_post_meta( $course_id, '_slme_positions', $clean );

		wp_send_json_success( [ 'message' => 'Opgeslagen' ] );
	}
}
