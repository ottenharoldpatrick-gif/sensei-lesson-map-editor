<?php
/**
 * Kolommen-weergave: 1 rij per module, max 5 kolommen per rij (responsive).
 * Verwacht $course_id (int).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$course_id   = isset( $course_id ) ? intval( $course_id ) : get_the_ID();
$user_id     = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Sensei helper aanwezig?
if ( ! function_exists( 'Sensei' ) || ! Sensei()->modules ) {
	echo '<p>Sensei LMS modules niet beschikbaar.</p>';
	return;
}

// Modules voor deze cursus
$modules = Sensei()->modules->get_course_modules( $course_id );
if ( empty( $modules ) ) {
	echo '<p>Geen modules gevonden voor deze cursus.</p>';
	return;
}

// Pad naar iconen (SVG)
$icons_url = plugins_url( 'assets/img/', dirname( __FILE__, 1 ) );
$icon_done = $icons_url . 'klaar.svg';
$icon_not  = $icons_url . 'niet.svg';
$icon_lock = $icons_url . 'lock.svg';

// Helper: les voltooid?
$lesson_completed = function( $lesson_id, $user_id ) {
	if ( ! $user_id ) { return false; }
	if ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_lesson' ) ) {
		return (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
	}
	return false;
};
?>

<div class="slme-columns-wrap" data-max-cols="5">
	<?php foreach ( $modules as $module ) :
		// Lessen per module in cursusvolgorde
		$lessons = Sensei()->modules->get_lessons( intval( $module->term_id ), $course_id );
		$lesson_count = is_array( $lessons ) ? count( $lessons ) : 0;
		?>
		<section class="slme-module-row" data-lessons="<?php echo esc_attr( $lesson_count ); ?>">
			<header class="slme-module-header">
				<h3 class="slme-module-title"><?php echo esc_html( $module->name ); ?></h3>
			</header>

			<div class="slme-grid" aria-label="<?php echo esc_attr( $module->name ); ?>">
				<?php if ( $lesson_count ) :
					foreach ( $lessons as $lesson ) :
						$lesson_id = is_object( $lesson ) ? $lesson->ID : intval( $lesson );
						$permalink = get_permalink( $lesson_id );
						$title     = get_the_title( $lesson_id );
						$thumb     = get_the_post_thumbnail_url( $lesson_id, 'medium' );
						$thumb     = $thumb ?: wc_placeholder_img_src(); // Fallback afbeelding indien nodig

						$completed = $lesson_completed( $lesson_id, $user_id );
						$locked    = ! $is_logged_in; // “D” slotje: alleen zichtbaar als niet ingelogd
						?>
						<article class="slme-tile">
							<a class="slme-tile-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
								<!-- A: uitgelichte afbeelding -->
								<div class="slme-a">
									<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" />
								</div>

								<!-- B: afgerond / niet afgerond -->
								<div class="slme-b" title="<?php echo $completed ? esc_attr__( 'Afgerond', 'slme' ) : esc_attr__( 'Niet afgerond', 'slme' ); ?>">
									<img src="<?php echo esc_url( $completed ? $icon_done : $icon_not ); ?>" alt="" />
								</div>

								<!-- D: slotje indien niet ingelogd -->
								<?php if ( $locked ) : ?>
									<div class="slme-d" title="<?php esc_attr_e( 'Alleen zichtbaar na inloggen', 'slme' ); ?>">
										<img src="<?php echo esc_url( $icon_lock ); ?>" alt="" />
									</div>
								<?php endif; ?>
							</a>
						</article>
					<?php endforeach;
				else : ?>
					<p class="slme-no-lessons"><?php esc_html_e( 'Geen lessen in deze module.', 'slme' ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
