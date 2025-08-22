<?php
/**
 * Frontend bootstrap – levert [slme view="columns"] shortcode
 * Houdt de bestaande map-/bestandsstructuur aan (templates/grid-columns.php, assets/frontend.css).
 */

namespace SLME;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend {

	/**
	 * Static init die door de Loader wordt aangeroepen.
	 */
	public static function init() : void {
		new self();
	}

	/**
	 * Registreer assets en shortcode.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'slme', array( $this, 'shortcode' ) );
	}

	/**
	 * Laad frontend CSS (stabiele naam/locatie: assets/frontend.css).
	 */
	public function enqueue_assets() : void {
		$ver     = defined( 'SLME_VERSION' ) ? SLME_VERSION : '1.0.0';
		$css_url = plugin_dir_url( dirname( __FILE__ ) ) . 'assets/frontend.css';
		wp_enqueue_style( 'slme-frontend', $css_url, array(), $ver );
	}

	/**
	 * Shortcode: [slme view="columns" course_id=""]
	 * Render via templates/grid-columns.php (bestaande/stabiele bestandsnaam).
	 */
	public function shortcode( $atts = array(), $content = '' ) : string {
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

		// Alleen tonen op een Sensei cursus of een geldige course_id.
		if ( $course_id && 'course' !== get_post_type( $course_id ) ) {
			return '';
		}

		$template_file = dirname( __DIR__ ) . '/templates/grid-columns.php';
		if ( ! file_exists( $template_file ) ) {
			return '<div class="slme-notice">SLME template ontbreekt: <code>templates/grid-columns.php</code>.</div>';
		}

		// Variabele voor in de template
		$slme_course_id = $course_id;

		ob_start();
		include $template_file;
		return ob_get_clean();
	}
}
