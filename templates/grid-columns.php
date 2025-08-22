<?php
/**
 * SLME – Kolommenweergave (max 5 kolommen)
 * - Module-naam boven de rij
 * - In de tile alleen de LESNAAM
 * - Lock-icoon (D) in de rechterhoek van de titelbalk (footer) en alleen als inloggen vereist is
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = get_the_ID();
if ( ! $course_id ) {
	$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
}
if ( ! $course_id ) {
	echo '<div class="slme-notice">Geen cursus gevonden om kolommen te renderen.</div>';
	return;
}

$current_user_id = get_current_user_id();
$is_logged_in    = is_user_logged_in();

/**
 * Modules ophalen
 */
$modules = array();
if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_course_modules' ) ) {
	$modules = Sensei()->modules->get_course_modules( $course_id );
}

if ( empty( $modules ) ) {
	$modules = array( (object) array(
		'term_id' => 0,
		'name'    => __( 'Zonder module', 'slme' ),
	) );
}

/**
 * Lessen per module volgens Sensei-volgorde
 */
$fetch_lessons_for_module = function( $module_term_id ) use ( $course_id ) {
	$lessons = array();

	// Sensei-module volgorde wanneer beschikbaar
	if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_lessons' ) && $module_term_id ) {
		$lessons = Sensei()->modules->get_lessons( $module_term_id, $course_id );
	} else {
		// Fallback: menu_order binnen de cursus
		$q = new WP_Query( array(
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
			),
		) );
		$lessons = $q->posts;
	}

	if ( $lessons && is_array( $lessons ) ) {
		return array_map( function( $item ) {
			return is_numeric( $item ) ? get_post( $item ) : $item;
		}, $lessons );
	}

	return array();
};
?>
<div class="slme-grid-columns" aria-label="Kolommenweergave cursus">
	<?php foreach ( $modules as $module ) :
		$module_id   = isset( $module->term_id ) ? intval( $module->term_id ) : 0;
		$module_name = isset( $module->name ) ? $module->name : __( 'Module', 'slme' );

		$lessons = $fetch_lessons_for_module( $module_id );
		if ( empty( $lessons ) ) {
			continue;
		}

		$cols = min( 5, count( $lessons ) );
		?>
		<section class="slme-row" data-cols="<?php echo esc_attr( $cols ); ?>">
			<header class="slme-row-header">
				<h3 class="slme-row-title"><?php echo esc_html( $module_name ); ?></h3>
			</header>

			<div class="slme-row-tiles">
				<?php foreach ( $lessons as $lesson_post ) :
					$lesson_id     = is_object( $lesson_post ) ? $lesson_post->ID : intval( $lesson_post );
					$lesson_link   = get_permalink( $lesson_id );
					$thumb         = get_the_post_thumbnail_url( $lesson_id, 'large' );
					$lesson_title  = get_the_title( $lesson_id );

					// B: status (afgerond of niet)
					$is_complete = false;
					if ( class_exists( 'Sensei_Utils' ) && $current_user_id ) {
						$is_complete = (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $current_user_id );
					}

					// D: lock alleen tonen als niet ingelogd (les niet vrij toegankelijk)
					$show_lock = ! $is_logged_in;
					?>
					<article class="slme-tile<?php echo $is_complete ? ' is-complete' : ' is-incomplete'; ?><?php echo $show_lock ? ' is-locked' : ''; ?>">
						<a class="slme-tile-link" href="<?php echo esc_url( $lesson_link ); ?>" <?php echo $show_lock ? 'aria-disabled="true"' : ''; ?>>
							<!-- A: Uitgelichte afbeelding als achtergrond -->
							<div class="slme-tile-media" style="<?php echo $thumb ? 'background-image:url(' . esc_url( $thumb ) . ');' : ''; ?>"></div>

							<!-- B: Status icoon (linksboven op de afbeelding) -->
							<span class="slme-icon slme-icon-status" aria-hidden="true"></span>

							<!-- Footer met alleen LESNAAM, lock (D) naar rechterhoek van de footer -->
							<footer class="slme-tile-footer">
								<div class="slme-tile-lesson-title"><?php echo esc_html( $lesson_title ); ?></div>

								<?php if ( $show_lock ) : ?>
									<span class="slme-icon slme-icon-lock slme-icon-lock--in-footer" aria-hidden="true"></span>
								<?php endif; ?>
							</footer>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
