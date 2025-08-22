<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Centrale loader. Laadt bestanden één keer, start Frontend/Admin/Ajax.
 * Mapstructuur: includes/*.php  (geen submappen wijzigen)
 */
final class Loader {

	public static function init() : void {
		self::load_files();

		// Frontend altijd
		if ( class_exists( __NAMESPACE__ . '\Frontend' ) ) {
			Frontend::init();
		}

		// Admin alleen in dashboard
		if ( is_admin() && class_exists( __NAMESPACE__ . '\Admin' ) ) {
			Admin::init();
		}

		// AJAX hooks
		if ( class_exists( __NAMESPACE__ . '\Ajax' ) ) {
			Ajax::init();
		}
	}

	private static function load_files() : void {
		$base = plugin_dir_path( __FILE__ ); // .../includes/

		// Let op: require_once voorkomt "Cannot declare class ..." (dubbele include)
		require_once $base . 'class-slme-frontend.php';

		// Admin/Ajax zijn optioneel aanwezig; alleen laden als bestand bestaat.
		if ( file_exists( $base . 'class-slme-admin.php' ) ) {
			require_once $base . 'class-slme-admin.php';
		}
		if ( file_exists( $base . 'class-slme-ajax.php' ) ) {
			require_once $base . 'class-slme-ajax.php';
		}
	}
}
