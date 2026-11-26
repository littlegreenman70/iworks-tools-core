<?php
/**
 * PHPUnit bootstrap.
 *
 * @package IWorksIE\Core
 */

$iwt_root = dirname( __DIR__ );

require_once $iwt_root . '/vendor/autoload.php';

$iwt_suite = getenv( 'IWT_TESTSUITE' );
if ( ! $iwt_suite ) {
	$iwt_suite = 'unit';
}

if ( 'integration' === $iwt_suite ) {
	$iwt_wp_tests = getenv( 'WP_TESTS_DIR' );
	if ( ! $iwt_wp_tests ) {
		$iwt_wp_tests = $iwt_root . '/tests/wordpress/wordpress-tests-lib';
	}
	if ( ! is_readable( $iwt_wp_tests . '/includes/functions.php' ) ) {
		fwrite( STDERR, "WP test suite not found at {$iwt_wp_tests}. Run bin/install-wp-tests.sh first.\n" );
		exit( 1 );
	}
	require_once $iwt_wp_tests . '/includes/functions.php';

	tests_add_filter(
		'muplugins_loaded',
		static function () use ( $iwt_root ) {
			$iwt_one = $iwt_root . '/examples/iworksie-example-one/iworksie-example-one.php';
			$iwt_two = $iwt_root . '/examples/iworksie-example-two/iworksie-example-two.php';
			if ( is_readable( $iwt_one ) ) {
				require $iwt_one;
			}
			if ( is_readable( $iwt_two ) ) {
				require $iwt_two;
			}
		}
	);

	require_once $iwt_wp_tests . '/includes/bootstrap.php';
}
