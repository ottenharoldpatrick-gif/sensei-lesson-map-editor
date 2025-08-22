<?php
/**
 * SLME – Kolommenweergave
 * Pad: templates/grid-columns.php
 *
 * - Max 5 kolommen op groot scherm (responsive minder).
 * - Elke rij = één Sensei Module (titel 1x boven de rij).
 * - Tegel A: uitgelichte afbeelding (geschaald naar tegel).
 * - Tegel B: pictogram afgerond/niet afgerond.
 * - Tegel D: slotje als gebruiker niet is ingelogd.
 * - Kolombreedte blijft constant; bij minder lessen ontstaan lege plekken rechts (geen uitrekken).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post, $current_user;
wp_get_current_user();

/** Bepaal course_id: via shortcode-args of huidige cursus */
$course_id = 0;
if ( isset( $args['course_id'] ) ) {
	$course_id = intval( $args['course_id'] );
} elseif ( $post instanceof WP_Post && 'course' === $post->post_type ) {
	$course_id = $post->ID;
}
if ( ! $course_id ) {
	echo '<div class="slme-columns-wrap"><p>Geen cursus gevonden.</p></div>';
	return;
}

/** Enqueue front.css zonder structuur te wijzigen */
$plugin_url  = trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) ); // plugin root URL
$plugin_path = trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ); // plugin root PATH
if ( ! wp_style_is( 'slme-front', 'enqueued' ) ) {
	wp_enqueue_style( 'slme-front', $plugin_url . 'assets/front.css', array(), '1.0' );
}

/** Lessons ophalen in Sensei-volgorde (menu_order) */
$lessons_q = new WP_Query( array(
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
	'no_found_rows'  => true,
	'fields'         => 'ids',
) );

/** Groeperen per Module (taxonomy: module) */
$by_module = array();   // [term_id => [lesson_ids]]
$modules   = array();   // lijst met term-objecten

if ( $lessons_q->have_posts() ) {
	foreach ( $lessons_q->posts as $lesson_id ) {
		$terms = wp_get_post_terms( $lesson_id, 'module' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term = $terms[0];
			$modules[ $term->term_id ] = $term;
			$by_module[ $term->term_id ][] = $lesson_id;
		} else {
			$by_module[0][] = $lesson_id; // zonder module
		}
	}
	wp_reset_postdata();
}

/** Modules sorteren op naam (of pas hier aan naar Sensei-module-volgorde als gewenst) */
if ( ! empty( $modules ) ) {
	usort( $modules, function( $a, $b ) {
		return strnatcasecmp( $a->name, $b->name );
	} );
}

/** Helper: featured image URL met fallback */
$slme_thumb = function( $lesson_id ) {
	$url = get_the_post_thumbnail_url( $lesson_id, 'large' );
	if ( ! $url ) {
		// neutrale placeholder
		$svg = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360"><rect width="100%" height="100%" fill="%23eeeeee"/></svg>');
		return $svg;
	}
	return $url;
};

/** Helper: is les afgerond door huidige user? */
$slme_done = function( $lesson_id, $user_id ) {
	if ( class_exists( 'Sensei_Utils' ) && is_callable( array( 'Sensei_Utils', 'user_completed_lesson' ) ) ) {
		return (bool) Sensei_Utils::user_completed_lesson( $lesson_id, $user_id );
	}
	return false;
};

/**
 * Icon helpers – houden zich aan jouw assets:
 * 1) Probeer SVG in assets/ (klaar.svg, niet.svg, lock.svg)
 * 2) Dan PNG in assets/ (klaar.png, niet.png, lock.png)
 * 3) Anders fallback inline SVG (klein, altijd beschikbaar)
 */
if ( ! function_exists( 'slme_icon_html' ) ) {
	function slme_icon_html( $name, $plugin_url, $plugin_path, $alt ) {
		$svg_path = $plugin_path . 'assets/' . $name . '.svg';
		$png_path = $plugin_path . 'assets/' . $name . '.png';
		if ( file_exists( $svg_path ) ) {
			return '<img class="slme-ico" src="' . esc_url( $plugin_url . 'assets/' . $name . '.svg' ) . '" alt="' . esc_attr( $alt ) . '" />';
		}
		if ( file_exists( $png_path ) ) {
			return '<img class="slme-ico" src="' . esc_url( $plugin_url . 'assets/' . $name . '.png' ) . '" alt="' . esc_attr( $alt ) . '" />';
		}
		// Fallbacks (compacte inline SVG’s)
		switch ( $name ) {
			case 'klaar':
				return '<svg class="slme-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-label="' . esc_attr( $alt ) . '"><path fill="#63b241" d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zM232.485 373.657l-95.2-95.2 45.255-45.255 49.945 49.945 127.23-127.23 45.255 45.255-172.485 172.485z"/></svg>';
			case 'niet':
				return '<svg class="slme-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-label="' . esc_attr( $alt ) . '"><path fill="#c0392b" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm95 313.6L321.6 352 256 286.4 190.4 352 161 321.6 226.6 256 161 190.4 190.4 161 256 226.6 321.6 161 351 190.4 285.4 256 351 321.6z"/></svg>';
			case 'lock':
			default:
				return '<svg class="slme-ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-label="' . esc_attr( $alt ) . '"><path d="M12 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm6-7h-1V7a5 5 0 0 0-10 0v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2zm-7 0V7a3 3 0 0 1 6 0v3h-6z"/></svg>';
		}
	}
}
if ( ! function_exists( 'slme_icon_done' ) ) {
	function slme_icon_done( $plugin_url, $plugin_path ) {
		return slme_icon_html( 'klaar', $plugin_url, $plugin_path, __( 'Les afgerond', 'slme' ) );
	}
}
if ( ! function_exists( 'slme_icon_not' ) ) {
	function slme_icon_not( $plugin_url, $plugin_path ) {
		return slme_icon_html( 'niet', $plugin_url, $plugin_path, __( 'Nog niet afgerond', 'slme' ) );
	}
}
if ( ! function_exists( 'slme_icon_lock' ) ) {
	function slme_icon_lock( $plugin_url, $plugin_path ) {
		return slme_icon_html( 'lock', $plugin_url, $plugin_path, __( 'Alleen zichtbaar na inloggen', 'slme' ) );
	}
}
?>
<div class="slme-columns-wrap">

	<?php if ( ! empty( $modules ) ) :
		foreach ( $modules as $term ) :
			$lesson_ids = isset( $by_module[ $term->term_id ] ) ? $by_module[ $term->term_id ] : array();
			if ( empty( $lesson_ids ) ) { continue; }
			$lessons_count = count( $lesson_ids ); ?>
			<section class="slme-module-row" data-lessons="<?php echo esc_attr( $lessons_count ); ?>">
				<header class="slme-module-header">
					<h3 class="slme-module-title"><?php echo esc_html( $term->name ); ?></h3>
				</header>

				<div class="slme-grid">
					<?php foreach ( $lesson_ids as $lesson_id ) :
						$permalink   = get_permalink( $lesson_id );
						$is_done     = $slme_done( $lesson_id, $current_user->ID );
						$needs_login = ! is_user_logged_in(); ?>
						<article class="slme-tile">
							<a href="<?php echo esc_url( $permalink ); ?>">
								<!-- A: Uitgelichte afbeelding, geschaald naar tegel -->
								<div class="slme-a"><img src="<?php echo esc_url( $slme_thumb( $lesson_id ) ); ?>" alt=""></div>

								<!-- B: Afgerond / Niet afgerond -->
								<div class="slme-b">
									<?php echo $is_done ? slme_icon_done( $plugin_url, $plugin_path ) : slme_icon_not( $plugin_url, $plugin_path ); ?>
								</div>

								<!-- D: Slotje als niet ingelogd -->
								<?php if ( $needs_login ) : ?>
									<div class="slme-d"><?php echo slme_icon_lock( $plugin_url, $plugin_path ); ?></div>
								<?php endif; ?>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach;
	endif; ?>

	<?php if ( ! empty( $by_module[0] ) ) : ?>
		<section class="slme-module-row" data-lessons="<?php echo esc_attr( count( $by_module[0] ) ); ?>">
			<header class="slme-module-header">
				<h3 class="slme-module-title"><?php esc_html_e( 'Zonder module', 'slme' ); ?></h3>
			</header>

			<div class="slme-grid">
				<?php foreach ( $by_module[0] as $lesson_id ) :
					$permalink   = get_permalink( $lesson_id );
					$is_done     = $slme_done( $lesson_id, $current_user->ID );
					$needs_login = ! is_user_logged_in(); ?>
					<article class="slme-tile">
						<a href="<?php echo esc_url( $permalink ); ?>">
							<div class="slme-a"><img src="<?php echo esc_url( $slme_thumb( $lesson_id ) ); ?>" alt=""></div>
							<div class="slme-b">
								<?php echo $is_done ? slme_icon_done( $plugin_url, $plugin_path ) : slme_icon_not( $plugin_url, $plugin_path ); ?>
							</div>
							<?php if ( $needs_login ) : ?>
								<div class="slme-d"><?php echo slme_icon_lock( $plugin_url, $plugin_path ); ?></div>
							<?php endif; ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( empty( $modules ) && empty( $by_module[0] ) ) : ?>
		<div class="slme-no-lessons"><?php esc_html_e( 'Geen lessen gevonden.', 'slme' ); ?></div>
	<?php endif; ?>

</div>
