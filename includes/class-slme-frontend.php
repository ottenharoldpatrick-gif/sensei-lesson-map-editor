<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLME_Frontend {

	public static function init() {
		add_shortcode( 'sensei_lesson_map', [ __CLASS__, 'shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function assets() {
		wp_register_style( 'slme-front', SLME_URL . 'assets/css/slme-front.css', [], SLME_VERSION );
		wp_register_script( 'slme-front', SLME_URL . 'assets/js/slme-front.js', [ 'jquery' ], SLME_VERSION, true );
	}

	// (stub) Bepaal voortgang van les voor huidige gebruiker (Sensei API kan afwijken per versie)
	private static function is_lesson_completed( $lesson_id, $user_id ) : bool {
		// Probeer Sensei helper indien beschikbaar
		if ( function_exists( 'Sensei' ) && method_exists( Sensei()->lesson_progress_repository, 'get' ) ) {
			$progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
			if ( $progress && isset( $progress->status ) ) {
				return ( 'complete' === $progress->status );
			}
		}
		// Fallback: niet afgerond
		return false;
	}

	private static function get_course_meta_defaults( $course_id ) : array {
		return [
			'mode'          => get_post_meta( $course_id, '_slme_mode', true ) ?: 'free', // free | columns
			'bg_id'         => absint( get_post_meta( $course_id, '_slme_bg_id', true ) ),
			'tile_size'     => get_post_meta( $course_id, '_slme_tile_size', true ) ?: 'm', // s|m|l|custom
			'tile_w'        => absint( get_post_meta( $course_id, '_slme_tile_w', true ) ) ?: 180,
			'tile_h'        => absint( get_post_meta( $course_id, '_slme_tile_h', true ) ) ?: 120,
			'positions'     => (array) get_post_meta( $course_id, '_slme_positions', true ), // [ lesson_id => [x,y,w,h,z,label,module] ]
			'show_borders'  => (bool) get_post_meta( $course_id, '_slme_borders', true ),
		];
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( [
			'course_id' => 0,
		], $atts, 'sensei_lesson_map' );

		$course_id = $atts['course_id'] ? absint( $atts['course_id'] ) : get_the_ID();
		if ( ! $course_id ) return '<div class="slme-error">Geen course_id.</div>';

		wp_enqueue_style( 'slme-front' );
		wp_enqueue_script( 'slme-front' );

		$meta = self::get_course_meta_defaults( $course_id );

		// Achtergrond
		$bg_style = '';
		if ( $meta['bg_id'] ) {
			$bg_url = wp_get_attachment_image_url( $meta['bg_id'], 'full' );
			if ( $bg_url ) {
				// Eén achtergrond, niet herhalen, volledig passen
				$bg_style = 'background-image:url(' . esc_url( $bg_url ) . ');background-size:cover;background-repeat:no-repeat;background-position:center;';
			}
		}

		// Haal modules + lessen op (Sensei gebruikt 'module' taxonomy)
		$lessons = self::get_course_lessons_grouped( $course_id );

		ob_start(); ?>

		<div class="slme-map <?php echo $meta['show_borders'] ? 'slme-has-borders' : ''; ?>" data-mode="<?php echo esc_attr( $meta['mode'] ); ?>" style="<?php echo esc_attr( $bg_style ); ?>">
			<?php if ( 'columns' === $meta['mode'] ): ?>
				<div class="slme-columns">
					<?php foreach ( $lessons as $module_name => $module_lessons ): ?>
						<div class="slme-col">
							<div class="slme-col-title"><?php echo esc_html( $module_name ); ?></div>
							<div class="slme-col-inner">
								<?php foreach ( $module_lessons as $lesson_id ): ?>
									<?php echo self::render_tile( $lesson_id, $meta, $course_id ); ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<div class="slme-free-canvas">
					<?php
					// Vrije kaart: absolute posities
					foreach ( $lessons as $module_name => $module_lessons ) {
						foreach ( $module_lessons as $lesson_id ) {
							echo self::render_tile( $lesson_id, $meta, $course_id, true );
						}
					}
					?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		return ob_get_clean();
	}

	private static function render_tile( $lesson_id, $meta, $course_id, $free = false ) : string {
		$title   = get_the_title( $lesson_id );
		$img_id  = get_post_thumbnail_id( $lesson_id );
		$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';
		$user_id = get_current_user_id();
		$done    = $user_id ? self::is_lesson_completed( $lesson_id, $user_id ) : false;

		$pos = $meta['positions'][ $lesson_id ] ?? [];
		$w = $pos['w'] ?? $meta['tile_w'];
		$h = $pos['h'] ?? $meta['tile_h'];
		$x = $pos['x'] ?? 0;
		$y = $pos['y'] ?? 0;
		$z = $pos['z'] ?? 1;
		$label = isset( $pos['label'] ) ? substr( sanitize_text_field( $pos['label'] ), 0, 3 ) : '';

		$style = '';
		if ( $free ) {
			$style = sprintf( 'left:%dpx;top:%dpx;width:%dpx;height:%dpx;z-index:%d;', $x, $y, $w, $h, $z );
		}

		ob_start(); ?>
		<a class="slme-tile <?php echo $done ? 'is-done':'is-todo'; ?>" href="<?php echo esc_url( get_permalink( $lesson_id ) ); ?>"
		   data-lesson="<?php echo esc_attr( $lesson_id ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<span class="slme-status" aria-hidden="true"><?php echo $done ? '✔' : '✖'; ?></span>
			<?php if ( $label ): ?>
				<span class="slme-label"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<span class="slme-img" aria-hidden="true" style="<?php echo $img_url ? 'background-image:url('.esc_url($img_url).')':''; ?>"></span>
			<span class="slme-hover">
				<span class="slme-title"><?php echo esc_html( $title ); ?></span>
				<span class="slme-progress"><?php echo $done ? 'Afgerond' : 'Nog niet afgerond'; ?></span>
			</span>
		</a>
		<?php
		return ob_get_clean();
	}

	// Groepeer lessen op module; fallback "Zonder module"
	private static function get_course_lessons_grouped( $course_id ) : array {
		$out = [];
		$lessons = get_posts( [
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
			],
			'fields'         => 'ids',
		] );

		foreach ( $lessons as $lesson_id ) {
			$terms = wp_get_post_terms( $lesson_id, 'module' );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				$out['Zonder module'][] = $lesson_id;
			} else {
				foreach ( $terms as $t ) {
					$out[ $t->name ][] = $lesson_id;
				}
			}
		}
		return $out;
	}
}
