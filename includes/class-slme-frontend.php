<?php
/**
 * Frontend bootstrap – levert [slme view="columns"] shortcode
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SLME_Frontend' ) ) :

class SLME_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'slme', array( $this, 'shortcode' ) );
	}

	/**
	 * Laad frontend CSS altijd; lichtgewicht en voorkomt “geen stijl”-issues.
	 */
	public function enqueue_assets() {
		$ver = defined( 'SLME_VERSION' ) ? SLME_VERSION : '1.0.0';
		$css = plugins_url( 'assets/frontend.css', dirname( __FILE__ ) );
		wp_enqueue_style( 'slme-frontend', $css, array(), $ver );
	}

	/**
	 * [slme view="columns"]
	 */
	public function shortcode( $atts = array(), $content = '' ) {
		$atts = shortcode_atts( array(
			'view'      => 'columns', // we gebruiken 'columns' = grid-columns.php
			'course_id' => 0,
		), $atts, 'slme' );

		$course_id = absint( $atts['course_id'] );
		if ( ! $course_id ) {
			// probeer huidig bericht (cursus)
			$course_id = get_the_ID();
		}

		// Beperk rendering tot cursuspagina's (veiligheid + performance)
		if ( get_post_type( $course_id ) !== 'course' ) {
			return ''; // niks renderen als het geen cursus is
		}

		// Kies template – WE HOUDEN VAST AAN DE STABIELE NAAM:
		$template_file = plugin_dir_path( __DIR__ ) . 'templates/grid-columns.php';

		if ( ! file_exists( $template_file ) ) {
			return '<div class="slme-notice">SLME template niet gevonden (templates/grid-columns.php).</div>';
		}

		// Maak $course_id beschikbaar in template indien gewenst
		setup_postdata( get_post( $course_id ) );

		ob_start();
		include $template_file;
		return ob_get_clean();
	}
}

endif;

// Bootstrap
if ( class_exists( 'SLME_Frontend' ) ) {
	new SLME_Frontend();
}
