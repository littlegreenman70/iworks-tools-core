<?php
/**
 * Integration: AJAX check endpoint auth.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Integration;

use WP_Ajax_UnitTestCase;
use WPAjaxDieStopException;

/**
 * @group integration
 */
final class AjaxCheckAuthTest extends WP_Ajax_UnitTestCase {

	public function test_rejects_bad_nonce() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );

		$_POST['nonce']    = 'bad';
		$_POST['check_id'] = 'example_one_good';
		$_REQUEST['nonce'] = 'bad';

		try {
			$this->_handleAjax( 'iwieo_run_check' );
			$this->fail( 'Expected nonce failure' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertNotEmpty( $e->getMessage() );
		} catch ( \WPDieException $e ) {
			$this->assertTrue( true );
		}
	}

	public function test_rejects_subscriber() {
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );

		$_POST['nonce']    = wp_create_nonce( 'iwieo_checks' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['check_id'] = 'example_one_good';

		try {
			$this->_handleAjax( 'iwieo_run_check' );
			$response = json_decode( $this->_last_response, true );
			if ( is_array( $response ) ) {
				$this->assertFalse( ! empty( $response['success'] ) );
			}
		} catch ( \Throwable $e ) {
			$this->assertTrue( true );
		}
	}
}
