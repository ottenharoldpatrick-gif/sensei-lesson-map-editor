<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SLME_Shortcode {
	public static function init() {
		add_shortcode( 'sensei_lesson_map', [ __CLASS__, 'render' ] );
	}
	private static function get_course_id_from_context( $atts ) {
		$atts = shortcode_atts( [ 'course_id' => 0 ], $atts, 'sensei_lesson_map' );
		$cid  = intval( $atts['course_id'] );
		if ( $cid ) return $cid;
		// Als we op een cursuspagina staan:
		if ( is_singular( 'course' ) ) {
			return get_the_ID();
		}
		return 0;
	}
	private static function get_course_lessons( $course_id ) {
		if ( function_exists( 'Sensei' ) && isset( Sensei()->course ) && method_exists( Sensei()->course, 'course_lessons' ) ) {
			$lessons = Sensei()->course->course_lessons( $course_id );
			if ( is_array( $lessons ) ) { return $lessons; }
		}
		$q = new WP_Query([
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'meta_query'     => [
				[ 'key' => '_lesson_course', 'value' => $course_id ],
			],
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		]);
		return $q->posts;
	}
	private static function is_completed( $lesson_id, $user_id ) {
		// Probeer Sensei API
		if ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_lesson' ) ) {
			return (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
		}
		return false;
	}
	public static function render( $atts ) {
		$course_id = self::get_course_id_from_context( $atts );
		if ( ! $course_id ) return '<em>Geen course_id gevonden</em>';

		$layout        = get_post_meta( $course_id, '_slme_layout', true ) ?: 'free';
		$background_id = intval( get_post_meta( $course_id, '_slme_background_id', true ) );
		$tile_size     = get_post_meta( $course_id, '_slme_tile_size', true ) ?: 'm';
		$tiles         = get_post_meta( $course_id, '_slme_tiles', true );
		if ( ! is_array( $tiles ) ) { $tiles = []; }
		$bg_url        = $background_id ? wp_get_attachment_image_url( $background_id, 'full' ) : '';

		$lessons = self::get_course_lessons( $course_id );
		$positions = [];
		foreach ( $tiles as $t ) {
			if ( empty( $t['lesson_id'] ) ) continue;
			$positions[ intval($t['lesson_id']) ] = $t;
		}

		ob_start();
		?>
		<div class="slme-frontend slme-size-<?php echo esc_attr( $tile_size ); ?> slme-<?php echo esc_attr( $layout ); ?>"
			<?php if ( $bg_url ): ?>style="background-image:url('<?php echo esc_url( $bg_url ); ?>');"<?php endif; ?>>
			<?php
			$user_id = get_current_user_id();
			foreach ( $lessons as $lesson ) {
				$lid = $lesson->ID;
				$pos = isset( $positions[ $lid ] ) ? $positions[ $lid ] : [];
				$x   = isset( $pos['x'] ) ? floatval($pos['x']) : 0;
				$y   = isset( $pos['y'] ) ? floatval($pos['y']) : 0;
				$z   = isset( $pos['z'] ) ? intval($pos['z']) : 0;
				$col = isset( $pos['col'] ) ? intval($pos['col']) : 0;
				$lab = isset( $pos['label'] ) ? substr( sanitize_text_field( $pos['label'] ), 0, 3 ) : '';

				$thumb = get_the_post_thumbnail_url( $lid, 'medium' );
				$link  = get_permalink( $lid );
				$done  = self::is_completed( $lid, $user_id );

				?>
				<a class="slme-card" href="<?php echo esc_url( $link ); ?>"
					data-col="<?php echo esc_attr($col); ?>"
					style="left:<?php echo esc_attr($x); ?>px; top:<?php echo esc_attr($y); ?>px; z-index:<?php echo esc_attr($z); ?>;"
					aria-label="<?php echo esc_attr( get_the_title($lid) ); ?>">
					<div class="slme-card-img" style="background-image:url('<?php echo esc_url($thumb); ?>');"></div>
					<span class="slme-corner <?php echo $done ? 'done' : 'todo'; ?>" aria-hidden="true"><?php echo $done ? '✅' : '❌'; ?></span>
					<?php if ( $lab !== '' ): ?><span class="slme-label"><?php echo esc_html( $lab ); ?></span><?php endif; ?>
					<div class="slme-hover">
						<strong><?php echo esc_html( get_the_title( $lid ) ); ?></strong>
						<em><?php echo $done ? esc_html__('Afgerond', 'slme') : esc_html__('Nog niet afgerond', 'slme'); ?></em>
					</div>
				</a>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
SLME_Shortcode::init();
