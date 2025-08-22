<?php
/**
 * Shortcode output: vrije kaart + kolommen met Sensei-modules.
 * - Mobiel: altijd fallback-lijst.
 * - Kolommen: achtergrond genegeerd (en niet gerenderd).
 * - Tegel: 3-char label + voortgangsicoon (✓/×) als Sensei-API beschikbaar.
 *
 * @package SLME
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SLME_Shortcode' ) ) :

class SLME_Shortcode {

	public static function init() {
		add_shortcode( 'sensei_lesson_map', [ __CLASS__, 'render' ] );
	}

	public static function render( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'course_id' => 0,
			],
			$atts,
			'sensei_lesson_map'
		);

		$course_id = (int) $atts['course_id'];
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}
		if ( ! $course_id || 'course' !== get_post_type( $course_id ) ) {
			return '';
		}

		// Instellingen per cursus.
		$layout_mode    = get_post_meta( $course_id, '_slme_layout_mode', true );
		$layout_mode    = $layout_mode ? $layout_mode : 'free'; // standaard: vrije kaart
		$background_id  = (int) get_post_meta( $course_id, '_slme_background_id', true );
		$tile_size      = get_post_meta( $course_id, '_slme_tile_size', true );
		$tile_size      = $tile_size ? $tile_size : 'm';

		// Posities (en z-index e.d.) voor vrije kaart.
		$positions_json = get_post_meta( $course_id, '_slme_positions', true );
		if ( empty( $positions_json ) ) {
			$positions_json = '{}';
		}

		// Alle lessen (mobiele fallback).
		$all_lessons = self::get_course_lessons( $course_id );

		// Modules + lessen per module (voor kolommen).
		$modules             = self::get_course_modules( $course_id );
		$module_lessons_map  = [];
		if ( ! empty( $modules ) ) {
			foreach ( $modules as $mod ) {
				$module_lessons_map[ $mod->term_id ] = self::get_module_lessons_in_course_order( $course_id, $mod->term_id );
			}
		}

		ob_start();
		?>
		<div class="slme-responsive-wrap" data-course-id="<?php echo esc_attr( $course_id ); ?>">
			<?php if ( 'columns' === $layout_mode && ! empty( $modules ) ) : ?>
				<?php $col_count = count( $modules ); ?>
				<div class="slme-columns"
				     style="--slme-col-count: <?php echo (int) $col_count; ?>;"
				     role="region"
				     aria-label="<?php echo esc_attr__( 'Leskaart – kolommen', 'slme' ); ?>">

					<?php foreach ( $modules as $module ) : ?>
						<div class="slme-col" data-module-id="<?php echo esc_attr( $module->term_id ); ?>">
							<div class="slme-col-header">
								<span class="slme-col-title"><?php echo esc_html( $module->name ); ?></span>
							</div>

							<div class="slme-col-lessons">
								<?php
								$lessons = isset( $module_lessons_map[ $module->term_id ] ) ? $module_lessons_map[ $module->term_id ] : [];
								if ( empty( $lessons ) ) :
									?>
									<div class="slme-tile slme-tile--empty" aria-hidden="true">
										<span class="slme-tile-empty-label"><?php esc_html_e( 'Geen lessen', 'slme' ); ?></span>
									</div>
								<?php
								else :
									$idx = 0;
									foreach ( $lessons as $lesson_post ) :
										$lesson_id   = $lesson_post->ID;
										$thumb_id    = get_post_thumbnail_id( $lesson_id );
										$thumb_src   = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
										$lesson_url  = get_permalink( $lesson_id );
										$lesson_titl = get_the_title( $lesson_id );

										// 3-char label: gebruik positie binnen module (1..n). Kan later vervangen worden door custom label-meta.
										$idx++;
										$chip = (string) $idx;
										if ( strlen( $chip ) > 3 ) {
											$chip = substr( $chip, 0, 3 );
										}

										// Voortgang per user (✓/×) als Sensei helpers bestaan.
										$progress_state = self::is_lesson_done_for_current_user( $lesson_id ) ? 'done' : 'open';
										$icon_label     = ( 'done' === $progress_state ) ? __( 'Afgerond', 'slme' ) : __( 'Niet afgerond', 'slme' );
										?>
										<a class="slme-tile"
										   href="<?php echo esc_url( $lesson_url ); ?>"
										   aria-label="<?php echo esc_attr( $lesson_titl ); ?>">
											<span class="slme-tile-media">
												<?php if ( $thumb_src ) : ?>
													<img src="<?php echo esc_url( $thumb_src ); ?>" alt="">
												<?php else : ?>
													<span class="slme-tile-media-placeholder" aria-hidden="true"></span>
												<?php endif; ?>
											</span>

											<span class="slme-tile-chip" aria-hidden="true"><?php echo esc_html( $chip ); ?></span>

											<span class="slme-tile-badge slme-tile-badge--<?php echo esc_attr( $progress_state ); ?>"
											      title="<?php echo esc_attr( $icon_label ); ?>"
												  aria-hidden="true"></span>

											<span class="slme-tile-hover">
												<span class="slme-tile-title"><?php echo esc_html( $lesson_titl ); ?></span>
											</span>
										</a>
										<?php
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<!-- Vrije kaart (desktop/tablet). Achtergrond en posities alleen hier. -->
				<div class="slme-map"
					data-layout="<?php echo esc_attr( $layout_mode ); ?>"
					data-bg-id="<?php echo esc_attr( $background_id ); ?>"
					data-size="<?php echo esc_attr( $tile_size ); ?>"
					data-positions="<?php echo esc_attr( $positions_json ); ?>"
					role="region"
					aria-label="<?php echo esc_attr__( 'Leskaart – vrije kaart', 'slme' ); ?>">
				</div>
			<?php endif; ?>

			<!-- Mobiele fallback: gewone lijst -->
			<div class="slme-mobile-fallback" role="region" aria-label="<?php echo esc_attr__( 'Leslijst (mobiel)', 'slme' ); ?>">
				<?php if ( ! empty( $all_lessons ) ) : ?>
					<ul class="slme-mobile-list">
						<?php foreach ( $all_lessons as $lsn ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $lsn->ID ) ); ?>"><?php echo esc_html( get_the_title( $lsn->ID ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'Geen lessen gevonden.', 'slme' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ===== Helpers ===== */

	private static function get_course_lessons( $course_id ) {
		$q = new WP_Query( [
			'post_type'      => 'lesson',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_query'     => [
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
			],
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'no_found_rows'  => true,
		] );
		$posts = $q->have_posts() ? $q->posts : [];
		wp_reset_postdata();
		return $posts;
	}

	private static function get_course_modules( $course_id ) {
		$args  = [
			'taxonomy'   => 'module',
			'hide_empty' => false,
			'orderby'    => 'meta_value_num name',
			'order'      => 'ASC',
			'meta_key'   => 'module_order_' . (int) $course_id,
		];
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}
		$filtered = [];
		foreach ( $terms as $t ) {
			if ( self::module_has_lessons_for_course( $course_id, $t->term_id ) ) {
				$filtered[] = $t;
			}
		}
		return $filtered;
	}

	private static function module_has_lessons_for_course( $course_id, $module_id ) {
		$q = new WP_Query( [
			'post_type'      => 'lesson',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
				[
					'key'   => '_lesson_module',
					'value' => $module_id,
				],
			],
			'no_found_rows'  => true,
		] );
		$has = $q->have_posts();
		wp_reset_postdata();
		return $has;
	}

	private static function get_module_lessons_in_course_order( $course_id, $module_id ) {
		$q = new WP_Query( [
			'post_type'      => 'lesson',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_query'     => [
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
				[
					'key'   => '_lesson_module',
					'value' => $module_id,
				],
			],
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'no_found_rows'  => true,
		] );
		$posts = $q->have_posts() ? $q->posts : [];
		wp_reset_postdata();
		return $posts;
	}

	/**
	 * Check of de huidige gebruiker deze les heeft afgerond.
	 * Probeert meerdere Sensei varianten; valt terug op 'false'.
	 */
	private static function is_lesson_done_for_current_user( $lesson_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// Klassieke helper?
		if ( function_exists( 'sensei_has_user_completed_lesson' ) ) {
			return (bool) sensei_has_user_completed_lesson( $lesson_id, $user_id );
		}

		// Nieuwere API via klasse?
		if ( class_exists( 'Sensei' ) && isset( \Sensei()->lesson ) && method_exists( \Sensei()->lesson, 'is_lesson_complete' ) ) {
			return (bool) \Sensei()->lesson->is_lesson_complete( $lesson_id, $user_id );
		}

		// Geen API beschikbaar -> niet afgerond.
		return false;
	}
}

endif;

SLME_Shortcode::init();
