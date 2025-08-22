<?php
/**
 * Template: grid-columns.php
 * Weergave per module (rij), max 5 kolommen op grote schermen.
 * Onder de afbeelding: ALLEEN de LESNAAM.
 * Boven de rij: de modulenaam (meerdere regels toegestaan).
 * Badge (klaar/niet) linksboven op beeld; lock rechts naast lesnaam, alleen als inloggen vereist.
 *
 * Vereist in scope:
 * - $course_id (int)
 * - $modules (array uit Sensei()->modules->get_course_modules($course_id))
 * - $assets_url (url naar /assets/)
 * - $is_logged_in (bool)
 * - $user_id (int)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Helper: bepaal of les “voltooid” is door huidige gebruiker (Sensei)
function slme_is_lesson_completed( $lesson_id, $user_id ) {
	if ( ! $user_id ) return false;
	// Sensei <= 4.x: Sensei_Utils::user_completed_lesson( $lesson_id, $user_id )
	if ( class_exists( '\Sensei_Utils' ) && method_exists( '\Sensei_Utils', 'user_completed_lesson' ) ) {
		return (bool) \Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
	}
	// fallback: false
	return false;
}

// Helper: of les vergrendeld is voor niet-ingelogden
function slme_is_lesson_locked_for_guests( $lesson_id, $is_logged_in ) {
	if ( $is_logged_in ) return false;

	// Sensei kent "Preview" les (open voor gasten). Metadata verschilt per versie:
	// - _lesson_preview == 'open' of 'preview'
	$preview = get_post_meta( $lesson_id, '_lesson_preview', true );
	if ( in_array( $preview, [ 'open', 'preview', '1', 1, true ], true ) ) {
		return false; // open voor gasten
	}
	return true; // login vereist
}

// Badge-iconen (PNG, zoals aangeleverd)
$icon_done  = esc_url( $assets_url . 'klaar.png' );
$icon_todo  = esc_url( $assets_url . 'niet.png' );
$icon_lock  = esc_url( $assets_url . 'lock.png' );

// Als er geen modules zijn, toon niets (stil falen zoals gevraagd)
if ( empty( $modules ) ) {
	echo '<div class="slme-modules-wrap"></div>';
	return;
}

echo '<div class="slme-modules-wrap">';

foreach ( $modules as $module ) {

	$module_id    = isset( $module->term_id ) ? intval( $module->term_id ) : 0;
	$module_name  = isset( $module->name ) ? $module->name : '';
	$module_name  = esc_html( $module_name );

	// Lessen binnen deze module in Sensei-volgorde
	$lessons = [];
	if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_lessons' ) ) {
		$lessons = Sensei()->modules->get_lessons( $module_id, $course_id );
	}
	if ( ! is_array( $lessons ) ) $lessons = [];

	// Module header (C) 1x boven de rij
	echo '<div class="slme-module-header">' . $module_name . '</div>';

	// Grid-rij (vaste 5 kolommen op groot scherm via CSS)
	echo '<div class="slme-module-grid">';

	foreach ( $lessons as $lesson ) {
		$lesson_id    = is_object( $lesson ) ? $lesson->ID : intval( $lesson );
		if ( ! $lesson_id ) continue;

		$lesson_title = get_the_title( $lesson_id );
		$lesson_title = esc_html( $lesson_title );
		$lesson_url   = get_permalink( $lesson_id );
		$thumb        = get_the_post_thumbnail_url( $lesson_id, 'medium_large' );

		// status (B)
		$is_done = slme_is_lesson_completed( $lesson_id, $user_id );

		// lock (D)
		$show_lock = slme_is_lesson_locked_for_guests( $lesson_id, $is_logged_in );

		echo '<article class="slme-lesson-tile">';

			// A: thumbnail + statusbadge linksboven
			echo '<div class="slme-thumb-wrap">';
				if ( $thumb ) {
					echo '<img src="' . esc_url( $thumb ) . '" alt="">';
				} else {
					// veilige fallback zonder WooCommerce
					$placeholder = 'data:image/svg+xml;utf8,' . rawurlencode(
						'<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500">
						   <rect width="100%" height="100%" fill="#f3f4f6"/>
						   <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#9ca3af" font-size="20">Geen afbeelding</text>
						 </svg>'
					);
					echo '<img src="' . esc_url( $placeholder ) . '" alt="">';
				}
				// B: afgerond / niet afgerond icoon
				$badge = $is_done ? $icon_done : $icon_todo;
				echo '<img class="slme-badge" src="' . $badge . '" alt="">';
			echo '</div>';

			// Lesinfo: alleen LESNAAM, met lock-icoon rechts indien nodig
			echo '<div class="slme-lesson-body">';
				echo '<a class="slme-lesson-link" href="' . esc_url( $lesson_url ) . '">';
					echo '<div class="slme-lesson-title">';
						echo $lesson_title;
						if ( $show_lock ) {
							echo '<img class="slme-lock" src="' . $icon_lock . '" alt="">';
						}
					echo '</div>';
				echo '</a>';
			echo '</div>';

		echo '</article>';
	}

	echo '</div>'; // .slme-module-grid
}

echo '</div>'; // .slme-modules-wrap
