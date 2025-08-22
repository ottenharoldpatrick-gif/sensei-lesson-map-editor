<?php
/**
 * Admin – menu, pagina en assets voor de beheer‑editor.
 */
namespace SLME;

if ( ! class_exists( '\SLME\Admin' ) ) {

	class Admin {

		const MENU_SLUG = 'slme-editor';

		public function init() {
			add_action( 'admin_menu', [ $this, 'register_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		}

		public function register_menu() {
			// Titel zoals door jou gewenst:
			add_menu_page(
				__( 'Sensei Cursus Maps', 'slme' ),
				__( 'Sensei Cursus Maps', 'slme' ),
				'manage_options',
				self::MENU_SLUG,
				[ $this, 'render_page' ],
				'dashicons-screenoptions',
				59
			);
		}

		public function enqueue_admin_assets( $hook ) {
			// Alleen laden op onze eigen pagina.
			if ( strpos( $hook, self::MENU_SLUG ) === false ) {
				return;
			}

			$css = plugins_url( '../assets/admin.css', __FILE__ );
			$js  = plugins_url( '../assets/admin-editor.js', __FILE__ );

			wp_enqueue_style( 'slme-admin', $css, [], '1.0.0' );
			wp_enqueue_script( 'slme-admin', $js, [ 'jquery' ], '1.0.0', true );

			wp_localize_script( 'slme-admin', 'SLME_ADMIN', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'slme_admin' ),
				'msgs'     => [
					'saved' => __( 'Opgeslagen.', 'slme' ),
					'error' => __( 'Er ging iets mis bij opslaan.', 'slme' ),
					'reset' => __( 'Instellingen zijn gereset.', 'slme' ),
				],
			] );
		}

		public function render_page() {
			// Eenvoudige beheerpagina: cursus select + opslaan/reset.
			$courses = get_posts( [
				'post_type'      => [ 'course', 'sensei_course' ],
				'posts_per_page' => -1,
				'post_status'    => [ 'publish', 'draft' ],
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );
			?>
			<div class="wrap slme-admin">
				<h1><?php esc_html_e( 'Sensei Cursus Maps', 'slme' ); ?></h1>

				<div class="slme-admin__controls">
					<label for="slme-course"><?php esc_html_e( 'Kies cursus', 'slme' ); ?></label>
					<select id="slme-course">
						<option value=""><?php esc_html_e( '— Selecteer —', 'slme' ); ?></option>
						<?php foreach ( $courses as $c ) : ?>
							<option value="<?php echo esc_attr( $c->ID ); ?>">
								<?php echo esc_html( get_the_title( $c ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<button id="slme-save" class="button button-primary">
						<?php esc_html_e( 'Opslaan', 'slme' ); ?>
					</button>
					<button id="slme-reset" class="button">
						<?php esc_html_e( 'Reset', 'slme' ); ?>
					</button>
				</div>

				<p class="description">
					<?php esc_html_e( 'Gebruik de editor om de kaartweergave voor de gekozen cursus op te slaan. Opslaan/Reset werken via AJAX.', 'slme' ); ?>
				</p>

				<div id="slme-admin-feedback" class="notice is-dismissible" style="display:none;"></div>
			</div>
			<?php
		}
	}
}
