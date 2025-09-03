<?php
/**
 * PHPUnit bootstrap for SLME.
 */

define( 'WP_PHPUNIT__DIR', __DIR__ . '/../vendor/wp-phpunit/wp-phpunit' );
define( 'WP_TESTS_CONFIG_FILE_PATH', dirname( __DIR__ ) . '/wp-tests-config.php' );

require WP_PHPUNIT__DIR . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() {
    require dirname( __DIR__ ) . '/includes/class-slme-admin.php';
});

require WP_PHPUNIT__DIR . '/includes/bootstrap.php';
