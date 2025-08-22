<?php
/**
 * SLME – Kolommenweergave (max 5 kolommen)
 * - Iedere rij = 1 module
 * - Max 5 kolommen op grote schermen; bij kleiner scherm neemt het aantal kolommen af en wrapt de volgende “kolom” naar een nieuwe regel
 * - Als een module < 5 lessen heeft, tonen we alleen dat aantal kolommen (tegels worden niet breder/geschaald)
 * - A: Uitgelichte afbeelding
 * - B: Statuspictogram (afgerond / niet afgerond) rechtsboven
 * - C: Naam van de module (meerdere regels toegestaan) onderin
 * - D: Slotje linksboven als inloggen vereist (gebruiker niet ingelogd)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = get_the_ID();
if ( ! $course_id ) {
	// Probeer uit query var (editor kan ?course_id= toevoegen)
	$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
}
if ( ! $course_id ) {
	echo '<div class="slme-notice">Geen cursus gevonden om kolommen te renderen.</div>';
	return;
}

$current_user_id = get_current_user_id();
$is_logged_in    = is_user_logged_in();

/**
 * Modules ophalen (Sensei API met fallback)
 */
$modules = array();
if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_course_modules' ) ) {
	$modules = Sensei()->modules->get_course_modules( $course_id ); // verwacht array van term-objecten (module taxonomy)
} else {
	// Fallback: pak alle module-termen die aan lessen van deze cursus hangen
	$lesson_ids = array();
	if ( function_exists( 'Sensei' ) && isset( Sensei()->course ) && method_exists( Sensei()->course, 'course_lessons' ) ) {
		$lesson_ids = wp_list_pluck( Sensei()->course->course_lessons( $course_id ), 'ID' );
	} else {
		$q = new WP_Query( array(
			'post_type'      => 'lesson',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
			),
		) );
		$lesson_ids = $q->posts;
	}
	if ( $lesson_ids ) {
		$modules = wp_get_object_terms( $lesson_ids, 'module', array( 'fields' => 'all' ) );
		// Unieke volgorde op naam als Sensei volgorde niet beschikbaar is
		usort( $modules, function( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );
	}
}

// Als er geen modules zijn: toch alles als één “module” tonen met alle lessen
if ( empty( $modules ) ) {
	$modules = array( (object) array(
		'term_id' => 0,
		'name'    => __( 'Zonder module', 'slme' ),
	) );
}

/**
 * Hulpfunctie – lessen per module
 */
$fetch_lessons_for_module = function( $module_term_id ) use ( $course_id ) {
	$lessons = array();

	// Voorkeur: Sensei modules API
	if ( function_exists( 'Sensei' ) && isset( Sensei()->modules ) && method_exists( Sensei()->modules, 'get_lessons' ) && $module_term_id ) {
		$lessons = Sensei()->modules->get_lessons( $module_term_id, $course_id );
	}

	// Fallbacks
	if ( empty( $lessons ) ) {
		if ( $module_term_id ) {
			$q = new WP_Query( array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'module',
						'field'    => 'term_id',
						'terms'    => $module_term_id,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => '_lesson_course',
						'value' => $course_id,
					),
				),
			) );
		} else {
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
		}
		$lessons = $q->posts;
	}

	// Normaliseer naar array van WP_Post
	if ( $lessons && is_array( $lessons ) ) {
		// Sensei kan array met ID’s of WP_Posts teruggeven; converteer ID’s naar WP_Post
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

		// Kolommen = min(5, aantal lessen)
		$cols = min( 5, count( $lessons ) );
		?>
		<section class="slme-row" data-cols="<?php echo esc_attr( $cols ); ?>">
			<header class="slme-row-header">
				<h3 class="slme-row-title"><?php echo esc_html( $module_name ); ?></h3>
			</header>

			<div class="slme-row-tiles">
				<?php
				foreach ( $lessons as $lesson_post ) :
					$lesson_id   = is_object( $lesson_post ) ? $lesson_post->ID : intval( $lesson_post );
					$lesson_link = get_permalink( $lesson_id );

					// A: uitgelichte afbeelding (fallback effen)
					$thumb = get_the_post_thumbnail_url( $lesson_id, 'large' );

					// B: afgerond of niet (Sensei utils-API)
					$is_complete = false;
					if ( class_exists( 'Sensei_Utils' ) && $current_user_id ) {
						$is_complete = (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $current_user_id );
					}

					// D: lock als inloggen vereist en gebruiker is niet ingelogd
					$show_lock = ! $is_logged_in;

					// Titel (lesnaam)
					$lesson_title = get_the_title( $lesson_id );
					?>
					<article class="slme-tile<?php echo $is_complete ? ' is-complete' : ' is-incomplete'; ?><?php echo $show_lock ? ' is-locked' : ''; ?>">
						<a class="slme-tile-link" href="<?php echo esc_url( $lesson_link ); ?>" <?php echo $show_lock ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
							<div class="slme-tile-media" style="<?php echo $thumb ? 'background-image:url(' . esc_url( $thumb ) . ');' : ''; ?>"></div>

							<!-- B: status icoon (rechtsboven) -->
							<span class="slme-icon slme-icon-status" aria-hidden="true"></span>

							<!-- D: lock (linksboven wanneer niet ingelogd) -->
							<?php if ( $show_lock ) : ?>
								<span class="slme-icon slme-icon-lock" aria-hidden="true" title="<?php esc_attr_e( 'Inloggen vereist', 'slme' ); ?>"></span>
							<?php endif; ?>

							<!-- C: module-naam onderin (meerdere regels toegestaan) -->
							<footer class="slme-tile-footer">
								<div class="slme-tile-module-name"><?php echo esc_html( $module_name ); ?></div>
								<div class="slme-tile-lesson-title"><?php echo esc_html( $lesson_title ); ?></div>
							</footer>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
