<?php
/**
 * Frontend – shortcode en assets voor de cursusweergave.
 */
namespace SLME;

if ( ! class_exists( '\SLME\Frontend' ) ) {

	class Frontend {

		/**
		 * Hooks registreren.
		 */
		public function init() {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

			// Shortcode blijft gelijk aan de stabiele versie:
			add_shortcode( 'slme_course_map', [ $this, 'render_shortcode' ] );
		}

		/**
		 * CSS/JS laden voor voorkant.
		 */
		public function enqueue_assets() {
			// Gebruik plugins_url met relatief pad vanaf dit bestand (blijft robuust).
			$css = plugins_url( '../assets/frontend.css', __FILE__ );
			$js  = plugins_url( '../assets/frontend.js', __FILE__ );

			wp_register_style( 'slme-frontend', $css, [], '1.0.0' );
			wp_enqueue_style( 'slme-frontend' );

			wp_register_script( 'slme-frontend', $js, [ 'jquery' ], '1.0.0', true );

			// Iconen — blijven in /assets/ (GEEN submap), zoals je hebt aangegeven.
			$icons = [
				'klaar' => plugins_url( '../assets/klaar.svg', __FILE__ ),
				'niet'  => plugins_url( '../assets/niet.svg', __FILE__ ),
				'lock'  => plugins_url( '../assets/lock.svg', __FILE__ ),
			];

			wp_localize_script( 'slme-frontend', 'SLME_FRONTEND', [
				'icons' => $icons,
			] );

			wp_enqueue_script( 'slme-frontend' );
		}

		/**
		 * Shortcode output.
		 * Voorbeeld: [slme_course_map] of [slme_course_map course_id="123"]
		 */
		public function render_shortcode( $atts = [] ) {
			$atts = shortcode_atts( [
				'course_id' => 0,
			], $atts, 'slme_course_map' );

			$course_id = absint( $atts['course_id'] );
			if ( ! $course_id ) {
				$maybe = $this->detect_course_id();
				if ( $maybe ) {
					$course_id = $maybe;
				}
			}

			if ( ! $course_id ) {
				// Toon een kleine melding i.p.v. leeg scherm, maar breek niet hard af.
				return '<div class="slme-notice">Geen cursus gevonden voor de kaartweergave.</div>';
			}

			// Laad template /templates/grid-columns.php (blijft exact zo heten).
			$template = $this->get_template_path( 'grid-columns.php' );
			if ( ! file_exists( $template ) ) {
				return '<div class="slme-notice">Template grid-columns.php ontbreekt in /templates/.</div>';
			}

			// Maak data beschikbaar voor de template.
			$context = [
				'course_id' => $course_id,
				// Als je layout of opties per cursus in meta opslaat:
				'layout'    => get_post_meta( $course_id, '_slme_layout', true ),
			];

			ob_start();
			// In de template kun je $context gebruiken.
			$slme = $context; // kortere alias in template.
			include $template;
			return ob_get_clean();
		}

		/**
		 * Bepaal course_id op een cursuspagina.
		 */
		private function detect_course_id() : int {
			if ( is_singular() ) {
				$post = get_post();
				if ( $post && in_array( $post->post_type, [ 'course', 'sensei_course' ], true ) ) {
					return (int) $post->ID;
				}
			}
			return 0;
		}

		/**
		 * Absolute pad naar templatebestand.
		 */
		private function get_template_path( string $file ) : string {
			// /includes -> terug naar plugin root -> /templates/...
			return dirname( __DIR__ ) . '/templates/' . ltrim( $file, '/' );
		}
	}
}
