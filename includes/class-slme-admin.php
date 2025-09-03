<?php
/**
 * Admin: Lesson Map Editor metabox + opslaan (robuust).
 *
 * @package SLME
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SLME_Admin' ) ) :

class SLME_Admin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Metabox op Sensei 'course' post type.
		add_action( 'add_meta_boxes_course', [ __CLASS__, 'add_metabox' ] );

		// Fallback: sommige setups roepen alleen 'add_meta_boxes' aan.
		add_action( 'add_meta_boxes', function( $post_type ) {
			if ( 'course' === $post_type ) {
				self::add_metabox();
			}
		}, 10, 1 );

		// Opslaan via AJAX.
		add_action( 'wp_ajax_slme_save_map', [ __CLASS__, 'handle_save_map' ] );
	}

	/**
	 * Metabox registreren.
	 */
	public static function add_metabox() {
		add_meta_box(
			'slme_metabox',
			__( 'Lesson Map Editor', 'slme' ),
			[ __CLASS__, 'render_metabox' ],
			'course',
			'normal',
			'high'
		);
	}

	/**
	 * Metabox HTML.
	 *
	 * - Toont editor UI + inline preview scaffold
	 * - Media button voor achtergrond
	 * - Layout toggle (Vrije kaart / Kolommen)
	 * - Opslaan & Reset knoppen
	 */
	public static function render_metabox( $post ) {
		wp_nonce_field( 'slme_admin', 'slme_admin_nonce' );

		$course_id        = (int) $post->ID;
		$layout_mode      = get_post_meta( $course_id, '_slme_layout_mode', true ); // 'free' | 'columns'
		$layout_mode      = $layout_mode ? $layout_mode : 'free';
		$bg_id            = (int) get_post_meta( $course_id, '_slme_background_id', true );
		$tile_size        = get_post_meta( $course_id, '_slme_tile_size', true );   // 's' | 'm' | 'l' | custom (px)
		$tile_size        = $tile_size ? $tile_size : 'm';
		$positions_json   = get_post_meta( $course_id, '_slme_positions', true );   // JSON met posities
		$positions_json   = $positions_json ? $positions_json : '{}';

		?>
		<div class="slme-metabox-wrap" data-course-id="<?php echo esc_attr( $course_id ); ?>">
			<div class="slme-controls">
				<div class="slme-control">
					<label><strong><?php esc_html_e( 'Weergave', 'slme' ); ?></strong></label>
					<label style="margin-right:10px;">
						<input type="radio" name="slme_layout_mode" value="free" <?php checked( $layout_mode, 'free' ); ?> />
						<?php esc_html_e( 'Vrije kaart (standaard)', 'slme' ); ?>
					</label>
					<label>
						<input type="radio" name="slme_layout_mode" value="columns" <?php checked( $layout_mode, 'columns' ); ?> />
						<?php esc_html_e( 'Kolommen', 'slme' ); ?>
					</label>
				</div>

				<div class="slme-control">
					<label><strong><?php esc_html_e( 'Achtergrond', 'slme' ); ?></strong></label>
					<div class="slme-bg-row">
						<input type="hidden" id="slme_background_id" value="<?php echo esc_attr( $bg_id ); ?>" />
						<button type="button" class="button" id="slme_pick_bg"><?php esc_html_e( 'Kies uit Media', 'slme' ); ?></button>
						<button type="button" class="button" id="slme_clear_bg"><?php esc_html_e( 'Verwijder', 'slme' ); ?></button>
						<span id="slme_bg_label" style="margin-left:8px; opacity:.8;">
							<?php echo $bg_id ? esc_html( 'ID: ' . $bg_id ) : esc_html__( 'Geen gekozen', 'slme' ); ?>
						</span>
					</div>
				</div>

				<div class="slme-control">
					<label><strong><?php esc_html_e( 'Tegelgrootte', 'slme' ); ?></strong></label>
					<select id="slme_tile_size">
						<option value="s" <?php selected( $tile_size, 's' ); ?>><?php esc_html_e( 'Klein', 'slme' ); ?></option>
						<option value="m" <?php selected( $tile_size, 'm' ); ?>><?php esc_html_e( 'Midden', 'slme' ); ?></option>
						<option value="l" <?php selected( $tile_size, 'l' ); ?>><?php esc_html_e( 'Groot', 'slme' ); ?></option>
					</select>
					<input type="number" id="slme_tile_custom" placeholder="<?php esc_attr_e( 'of px (bijv. 120)', 'slme' ); ?>" min="60" step="10" style="width:140px;margin-left:6px;" />
				</div>

				<div class="slme-actions">
					<button type="button" class="button button-primary" id="slme_save"><?php esc_html_e( 'Opslaan', 'slme' ); ?></button>
					<button type="button" class="button" id="slme_reset"><?php esc_html_e( 'Reset', 'slme' ); ?></button>
					<span id="slme_save_status" style="margin-left:10px;"></span>
				</div>
			</div>

			<hr/>

			<div class="slme-preview-note" style="margin:6px 0 12px;opacity:.8;">
				<?php esc_html_e( 'Inline preview van de kaart. Sleep tegels (HTML5 drag & drop). Tekstlabel (3 tekens) kun je per tegel invullen.', 'slme' ); ?>
			</div>

			<!-- Scaffold voor de preview; JS voegt hier de echte tegels in -->
			<div id="slme_canvas"
				data-layout="<?php echo esc_attr( $layout_mode ); ?>"
				data-bg-id="<?php echo esc_attr( $bg_id ); ?>"
				data-size="<?php echo esc_attr( $tile_size ); ?>"
				data-positions="<?php echo esc_attr( $positions_json ); ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: opslaan van layout, achtergrond en posities.
	 */
	public static function handle_save_map() {
		check_ajax_referer( 'slme_admin', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'Geen permissie' ], 403 );
		}

		$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
		if ( ! $course_id || 'course' !== get_post_type( $course_id ) ) {
			wp_send_json_error( [ 'message' => 'Ongeldige cursus' ], 400 );
		}

		$layout_mode = isset( $_POST['layout_mode'] ) ? sanitize_key( wp_unslash( $_POST['layout_mode'] ) ) : 'free';
		if ( ! in_array( $layout_mode, [ 'free', 'columns' ], true ) ) {
			$layout_mode = 'free';
		}

		$bg_id = isset( $_POST['background_id'] ) ? intval( $_POST['background_id'] ) : 0;

		$tile_size = isset( $_POST['tile_size'] ) ? sanitize_text_field( wp_unslash( $_POST['tile_size'] ) ) : 'm';
		// Toestaan: s|m|l of puur getal (custom px).
		if ( ! in_array( $tile_size, [ 's', 'm', 'l' ], true ) ) {
			$tile_size = preg_replace( '/[^0-9]/', '', $tile_size );
		}

		$positions = isset( $_POST['positions'] ) ? wp_unslash( $_POST['positions'] ) : '{}';
		// Laat basis JSON toe; extra sanitatie: verwijder alle tags & control chars.
		$positions = wp_kses( $positions, [] );
		$positions = preg_replace( '/[^\{\}\[\]\":,0-9a-zA-Z\.\-\_\s]/', '', $positions );

		update_post_meta( $course_id, '_slme_layout_mode', $layout_mode );
		update_post_meta( $course_id, '_slme_background_id', $bg_id );
		update_post_meta( $course_id, '_slme_tile_size', $tile_size );
		update_post_meta( $course_id, '_slme_positions', $positions );

		wp_send_json_success( [
			'message' => __( 'Opgeslagen', 'slme' ),
			'saved'   => [
				'layout_mode'   => $layout_mode,
				'background_id' => $bg_id,
				'tile_size'     => $tile_size,
			],
		] );
	}
}

endif;

// Boot.
SLME_Admin::init();
