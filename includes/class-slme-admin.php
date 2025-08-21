<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLME_Admin {
	public static function init() {
		// Metabox op cursus bewerken
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_metabox' ] );
		// AJAX save
		add_action( 'wp_ajax_slme_save_map', [ __CLASS__, 'handle_save_map' ] );
	}
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
	private static function get_course_lessons( $course_id ) {
		// Probeer Sensei API
		if ( function_exists( 'Sensei' ) && isset( Sensei()->course ) && method_exists( Sensei()->course, 'course_lessons' ) ) {
			$lessons = Sensei()->course->course_lessons( $course_id );
			if ( is_array( $lessons ) ) { return $lessons; }
		}
		// Fallback: WP_Query op "lesson" met course relatie
		$q = new WP_Query([
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
			],
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		]);
		return $q->posts;
	}
	public static function render_metabox( $post ) {
		$course_id     = $post->ID;
		$layout        = get_post_meta( $course_id, '_slme_layout', true ) ?: 'free';
		$background_id = intval( get_post_meta( $course_id, '_slme_background_id', true ) );
		$tile_size     = get_post_meta( $course_id, '_slme_tile_size', true ) ?: 'm';
		$tiles         = get_post_meta( $course_id, '_slme_tiles', true );
		if ( ! is_array( $tiles ) ) { $tiles = []; }

		$bg_url = $background_id ? wp_get_attachment_image_url( $background_id, 'full' ) : '';
		$lessons = self::get_course_lessons( $course_id );

		wp_nonce_field( 'slme_admin', 'slme_admin_nonce' );
		?>
		<div class="slme-controls">
			<label>
				<?php _e('Layout', 'slme'); ?>:
				<select id="slme-layout">
					<option value="free" <?php selected( $layout, 'free' ); ?>>Vrije kaart</option>
					<option value="columns" <?php selected( $layout, 'columns' ); ?>>Kolommen</option>
				</select>
			</label>

			<label>
				<?php _e('Tegelgrootte', 'slme'); ?>:
				<select id="slme-tile-size">
					<option value="s" <?php selected( $tile_size, 's' ); ?>>Klein</option>
					<option value="m" <?php selected( $tile_size, 'm' ); ?>>Middel</option>
					<option value="l" <?php selected( $tile_size, 'l' ); ?>>Groot</option>
					<option value="custom" <?php selected( $tile_size, 'custom' ); ?>>Custom (CSS)</option>
				</select>
			</label>

			<button type="button" class="button" id="slme-choose-bg"><?php _e('Achtergrond kiezen', 'slme'); ?></button>
			<button type="button" class="button" id="slme-clear-bg"><?php _e('Achtergrond verwijderen', 'slme'); ?></button>

			<button type="button" class="button button-primary" id="slme-save"><?php _e('Opslaan', 'slme'); ?></button>
			<button type="button" class="button" id="slme-reset"><?php _e('Reset (alle posities leeg)', 'slme'); ?></button>
		</div>

		<input type="hidden" id="slme-course-id" value="<?php echo esc_attr( $course_id ); ?>">
		<input type="hidden" id="slme-background-id" value="<?php echo esc_attr( $background_id ); ?>">

		<div id="slme-canvas" class="slme-canvas slme-<?php echo esc_attr( $layout ); ?>" style="<?php echo $bg_url ? 'background-image:url(' . esc_url( $bg_url ) . ');' : ''; ?>">
			<?php
			// Render tiles voor editor: absolute posities voor 'free', of in 3 kolommen voor 'columns'
			// Bepaal bestaande posities:
			$positions = [];
			foreach ( $tiles as $t ) {
				if ( empty( $t['lesson_id'] ) ) continue;
				$positions[ intval($t['lesson_id']) ] = $t;
			}
			// 3 kolommen container (ook bij free gebruiken we 1 canvas; CSS regelt columns vs free)
			$col_count = 3;
			echo '<div class="slme-columns" data-cols="' . intval($col_count) . '">';
			for ( $c = 0; $c < $col_count; $c++ ) {
				echo '<div class="slme-col" data-col="' . $c . '"></div>';
			}
			echo '</div>';

			// Plaats alle lessen als draggable tiles
			foreach ( $lessons as $lesson ) {
				$lid = $lesson->ID;
				$pos = isset( $positions[ $lid ] ) ? $positions[ $lid ] : [];
				$x   = isset( $pos['x'] ) ? floatval($pos['x']) : 0;
				$y   = isset( $pos['y'] ) ? floatval($pos['y']) : 0;
				$z   = isset( $pos['z'] ) ? intval($pos['z']) : 0;
				$col = isset( $pos['col'] ) ? intval($pos['col']) : 0;
				$lab = isset( $pos['label'] ) ? substr( sanitize_text_field( $pos['label'] ), 0, 3 ) : '';

				$thumb = get_the_post_thumbnail_url( $lid, 'medium' );
				if ( ! $thumb ) { $thumb = wc_placeholder_img_src(); } // fallback; als WooCommerce niet aanwezig is, negeren
				?>
				<div class="slme-tile" draggable="true"
					data-lesson-id="<?php echo esc_attr( $lid ); ?>"
					data-col="<?php echo esc_attr( $col ); ?>"
					data-z="<?php echo esc_attr( $z ); ?>"
					style="left:<?php echo esc_attr( $x ); ?>px; top:<?php echo esc_attr( $y ); ?>px; z-index:<?php echo esc_attr( $z ); ?>;">
					<div class="slme-tile-img" style="background-image:url('<?php echo esc_url( $thumb ); ?>');"></div>
					<span class="slme-label"><?php echo esc_html( $lab ); ?></span>
				</div>
				<?php
			}
			?>
		</div>

		<p class="description">
			<?php _e('Sleep tegels vrij over de kaart (Vrije kaart) of binnen de kolom (Kolommen). Opslaan bewaart posities per les (user-voortgang blijft user-specifiek).', 'slme'); ?>
		</p>
		<?php
	}

	public static function handle_save_map() {
		try {
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'slme_admin' ) ) {
				wp_send_json_error( [ 'message' => 'Ongeldige nonce' ], 403 );
			}
			$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
			if ( ! $course_id || 'course' !== get_post_type( $course_id ) ) {
				wp_send_json_error( [ 'message' => 'Ongeldige course_id' ], 400 );
			}
			if ( ! current_user_can( 'edit_post', $course_id ) ) {
				wp_send_json_error( [ 'message' => 'Geen rechten' ], 403 );
			}

			$layout        = isset($_POST['layout']) ? sanitize_text_field( $_POST['layout'] ) : 'free';
			$background_id = isset($_POST['background_id']) ? intval( $_POST['background_id'] ) : 0;
			$tile_size     = isset($_POST['tile_size']) ? sanitize_text_field( $_POST['tile_size'] ) : 'm';
			$tiles_raw     = isset($_POST['tiles']) ? wp_unslash( $_POST['tiles'] ) : '[]';
			$tiles         = json_decode( $tiles_raw, true );
			if ( ! is_array( $tiles ) ) { $tiles = []; }

			$clean = [];
			foreach ( $tiles as $t ) {
				$clean[] = [
					'lesson_id' => isset($t['lesson_id']) ? intval($t['lesson_id']) : 0,
					'x'         => isset($t['x']) ? floatval($t['x']) : 0,
					'y'         => isset($t['y']) ? floatval($t['y']) : 0,
					'col'       => array_key_exists('col',$t) ? intval($t['col']) : null,
					'z'         => isset($t['z']) ? intval($t['z']) : 0,
					'label'     => isset($t['label']) ? substr( sanitize_text_field( $t['label'] ), 0, 3 ) : '',
				];
			}

			update_post_meta( $course_id, '_slme_layout', in_array( $layout, ['free','columns'], true ) ? $layout : 'free' );
			update_post_meta( $course_id, '_slme_background_id', $background_id );
			update_post_meta( $course_id, '_slme_tile_size', in_array( $tile_size, ['s','m','l','custom'], true ) ? $tile_size : 'm' );
			update_post_meta( $course_id, '_slme_tiles', $clean );

			wp_send_json_success( [ 'updated' => true ] );

		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}
}
SLME_Admin::init();
