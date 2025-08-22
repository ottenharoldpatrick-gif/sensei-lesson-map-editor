<?php
/**
 * Frontend bootstrap – levert [slme view="columns"] shortcode
 * Namespace en klassenaam zijn afgestemd op de loader: SLME\Frontend
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {

	/**
	 * Constructor: registreert assets en shortcode.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'slme', array( $this, 'shortcode' ) );
	}

	/**
	 * Laad frontend CSS (stabiele naam/locatie: assets/frontend.css).
	 */
	public function enqueue_assets() {
		$ver = defined( 'SLME_VERSION' ) ? SLME_VERSION : '1.0.0';
		$css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/frontend.css';
		wp_enqueue_style( 'slme-frontend', $css_url, array(), $ver );
	}

	/**
	 * Shortcode: [slme view="columns" course_id=""]
	 * We renderen altijd via templates/grid-columns.php (stabiele naam/locatie).
	 */
	public function shortcode( $atts = array(), $content = '' ) {
		$atts = shortcode_atts(
			array(
				'view'      => 'columns',
				'course_id' => 0,
			),
			$atts,
			'slme'
		);

		$course_id = absint( $atts['course_id'] );
		if ( ! $course_id ) {
			$course_id = get_the_ID();
		}

		// Veiligheid: render alleen op cursuspost of als expliciet course_id is gezet naar een cursus.
		if ( $course_id && 'course' !== get_post_type( $course_id ) ) {
			return '';
		}

		$plugin_root   = dirname( __DIR__ ); // map van de plugin-root
		$template_file = $plugin_root . '/templates/grid-columns.php';

		if ( ! file_exists( $template_file ) ) {
			return '<div class="slme-notice">SLME template niet gevonden (templates/grid-columns.php).</div>';
		}

		// Maak variabelen beschikbaar in de template indien nodig
		$slme_course_id = $course_id;

		ob_start();
		include $template_file;
		return ob_get_clean();
	}
}
