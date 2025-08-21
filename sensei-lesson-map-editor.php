<?php
/**
 * Plugin Name: Sensei Lesson Map Editor
 * Description: Visuele leskaart + admin editor voor Sensei LMS.
 * Version: 0.2.8-dev
 * Author: Harold Patrick Otten
 * Text Domain: slme
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SLME_VERSION', '0.2.8-dev' );
define( 'SLME_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLME_URL', plugin_dir_url( __FILE__ ) );

// Assets registreren
add_action( 'wp_enqueue_scripts', function() {
	wp_register_style( 'slme-front', SLME_URL . 'assets/css/slme-front.css', [], SLME_VERSION );
	wp_register_script( 'slme-front', SLME_URL . 'assets/js/slme-front.js', [ 'jquery' ], SLME_VERSION, true );
});

// Shortcode [sensei_lesson_map course_id="123"]
add_shortcode( 'sensei_lesson_map', function( $atts ) {
	$atts = shortcode_atts( [
		'course_id' => get_the_ID(),
	], $atts, 'sensei_lesson_map' );

	wp_enqueue_style( 'slme-front' );
	wp_enqueue_script( 'slme-front' );

	$course_id = intval( $atts['course_id'] );
	if ( ! $course_id ) return '<div class="slme-error">Geen course_id gevonden.</div>';

	ob_start(); ?>
	<div class="slme-map" data-course="<?php echo esc_attr( $course_id ); ?>">
		<div class="slme-columns">
			<div class="slme-col">Kolom 1 (voorbeeld)</div>
			<div class="slme-col">Kolom 2 (voorbeeld)</div>
			<div class="slme-col">Kolom 3 (voorbeeld)</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
});

// Eenvoudige admin-pagina placeholder
add_action( 'admin_menu', function() {
	add_menu_page(
		'Lesson Map Editor',
		'Lesson Map',
		'manage_options',
		'slme',
		function() {
			echo '<div class="wrap"><h1>Lesson Map Editor</h1><p>Plugin skeleton actief. Shortcode: <code>[sensei_lesson_map]</code></p></div>';
		},
		'dashicons-screenoptions',
		58
	);
});
