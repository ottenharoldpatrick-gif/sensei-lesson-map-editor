<?php
// Verwijder alle opgeslagen opties bij uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

global $wpdb;
$like = $wpdb->esc_like('slme_course_') . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
