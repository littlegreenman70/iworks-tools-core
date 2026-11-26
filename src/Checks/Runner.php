<?php
/**
 * Synchronous and AJAX check runner.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Checks;

use IWorksIE\Core\Core;
use IWorksIE\Core\Strings;

/**
 * Runs checks and exposes AJAX for one-at-a-time execution.
 */
final class Runner {

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		$prefix = (string) $this->core->config( 'prefix' );
		add_action( 'wp_ajax_' . $prefix . '_run_check', array( $this, 'ajax_run_check' ) );
	}

	/**
	 * Run every registered check synchronously.
	 *
	 * @return array<string,Result>
	 */
	public function run_all() {
		$out = array();
		foreach ( $this->core->checks()->all() as $id => $check ) {
			try {
				$out[ $id ] = $check->run();
			} catch ( \Throwable $e ) {
				$out[ $id ] = Result::critical(
					$check->label(),
					Strings::get( 'check_failed', array( 'message' => $e->getMessage() ) )
				);
			}
		}
		return $out;
	}

	/**
	 * AJAX: run a single check by id.
	 *
	 * @return void
	 */
	public function ajax_run_check() {
		$prefix = (string) $this->core->config( 'prefix' );
		check_ajax_referer( $prefix . '_checks', 'nonce' );

		if ( ! current_user_can( (string) $this->core->config( 'capability' ) ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$id    = isset( $_POST['check_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['check_id'] ) ) : '';
		$check = $this->core->checks()->get( $id );
		if ( null === $check ) {
			wp_send_json_error( array( 'message' => 'unknown_check' ), 404 );
		}

		try {
			$result = $check->run();
		} catch ( \Throwable $e ) {
			$result = Result::critical(
				$check->label(),
				Strings::get( 'check_failed', array( 'message' => $e->getMessage() ) )
			);
		}

		wp_send_json_success( $result->to_array() );
	}
}
