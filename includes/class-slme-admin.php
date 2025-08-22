<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( __NAMESPACE__ . '\Admin' ) ) {

	class Admin {

		public static function init() : void {
			add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		}

		public static function register_menu() : void {
			add_menu_page(
				__( 'Sensei Cursus Maps', 'slme' ),
				__( 'Sensei Cursus Maps', 'slme' ),
				'manage_options',
				'slme-maps',
				[ __CLASS__, 'render_page' ],
				'dashicons-screenoptions',
				56
			);
		}

		public static function render_page() : void {
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Sensei Cursus Maps', 'slme' ); ?></h1>
				<p>
					<?php echo esc_html__( 'Deze pagina is informatief. De weergave verschijnt automatisch op cursuspagina’s, of plaats handmatig:', 'slme' ); ?>
					<br>
					<code>[slme_grid_columns course_id="123"]</code>
				</p>
			</div>
			<?php
		}
	}
}
