<?php
/**
 * Job state storage and AJAX endpoints.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Jobs;

use IWorksIE\Core\Core;
use IWorksIE\Core\Strings;

/**
 * Manages one running job per id with option-backed state.
 */
final class Manager {

	/**
	 * Stale threshold in seconds (1 hour).
	 *
	 * @var int
	 */
	const STALE_SECONDS = 3600;

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * @var array<string,Job>
	 */
	private $jobs = array();

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
	}

	/**
	 * Register a job implementation.
	 *
	 * @param Job $job Job.
	 * @return void
	 */
	public function add( Job $job ) {
		$this->jobs[ $job->id() ] = $job;
	}

	/**
	 * @param string $id Job id.
	 * @return Job|null
	 */
	public function get( $id ) {
		return isset( $this->jobs[ $id ] ) ? $this->jobs[ $id ] : null;
	}

	/**
	 * Wire AJAX actions.
	 *
	 * @return void
	 */
	public function hooks() {
		$prefix = (string) $this->core->config( 'prefix' );
		add_action( 'wp_ajax_' . $prefix . '_job_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_' . $prefix . '_job_step', array( $this, 'ajax_step' ) );
		add_action( 'wp_ajax_' . $prefix . '_job_cancel', array( $this, 'ajax_cancel' ) );
		add_action( 'wp_ajax_' . $prefix . '_job_status', array( $this, 'ajax_status' ) );
	}

	/**
	 * Default time budget: min(10, 40% of max_execution_time) or 10 when unlimited.
	 *
	 * @return float
	 */
	public function time_budget() {
		$max = (int) ini_get( 'max_execution_time' );
		if ( $max <= 0 ) {
			return 10.0;
		}
		$forty = $max * 0.4;
		return (float) min( 10.0, max( 1.0, $forty ) );
	}

	/**
	 * Option key for a job.
	 *
	 * @param string $id Job id.
	 * @return string
	 */
	public function option_key( $id ) {
		return (string) $this->core->config( 'prefix' ) . '_job_' . sanitize_key( $id );
	}

	/**
	 * @param string $id Job id.
	 * @return array<string,mixed>|null
	 */
	public function load_state( $id ) {
		$state = get_option( $this->option_key( $id ), null );
		return is_array( $state ) ? $state : null;
	}

	/**
	 * @param string              $id    Job id.
	 * @param array<string,mixed> $state State.
	 * @return void
	 */
	public function save_state( $id, array $state ) {
		$state['updated_at'] = time();
		update_option( $this->option_key( $id ), $state, false );
	}

	/**
	 * @param string $id Job id.
	 * @return void
	 */
	public function delete_state( $id ) {
		delete_option( $this->option_key( $id ) );
	}

	/**
	 * Whether stored state is stale.
	 *
	 * @param array<string,mixed> $state State.
	 * @return bool
	 */
	public function is_stale( array $state ) {
		$updated = isset( $state['updated_at'] ) ? (int) $state['updated_at'] : 0;
		return ( time() - $updated ) > self::STALE_SECONDS;
	}

	/**
	 * Public AJAX view of job state (no working internals).
	 *
	 * @param string              $id     Job id.
	 * @param array<string,mixed> $state  Full state.
	 * @param bool                $exists Whether state exists.
	 * @return array<string,mixed>
	 */
	public function public_view( $id, array $state, $exists = true ) {
		$view = array(
			'job_id'    => (string) $id,
			'progress'  => isset( $state['progress'] ) ? (int) $state['progress'] : 0,
			'message'   => isset( $state['message'] ) ? (string) $state['message'] : '',
			'done'      => ! empty( $state['done'] ),
			'cancelled' => ! empty( $state['cancelled'] ),
			'exists'    => (bool) $exists,
			'stale'     => $exists ? $this->is_stale( $state ) : false,
		);
		if ( ! empty( $state['done'] ) && isset( $state['result'] ) ) {
			$view['result'] = $state['result'];
		}
		return $view;
	}

	/**
	 * Guard AJAX requests.
	 *
	 * @return void
	 */
	private function guard() {
		$prefix = (string) $this->core->config( 'prefix' );
		check_ajax_referer( $prefix . '_jobs', 'nonce' );
		if ( ! current_user_can( (string) $this->core->config( 'capability' ) ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
	}

	/**
	 * AJAX: start a job.
	 *
	 * @return void
	 */
	public function ajax_start() {
		$this->guard();
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in guard().
		$id  = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['job_id'] ) ) : '';
		$job = $this->get( $id );
		if ( null === $job ) {
			wp_send_json_error( array( 'message' => 'unknown_job' ), 404 );
		}

		$existing = $this->load_state( $id );
		if ( is_array( $existing ) && empty( $existing['done'] ) && empty( $existing['cancelled'] ) && ! $this->is_stale( $existing ) ) {
			wp_send_json_success( $this->public_view( $id, $existing ) );
		}

		$params = array();
		if ( isset( $_POST['params'] ) && is_array( $_POST['params'] ) ) {
			$params = wp_unslash( $_POST['params'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
		} elseif ( isset( $_POST['params'] ) && is_string( $_POST['params'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['params'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_array( $decoded ) ) {
				$params = $decoded;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( function_exists( 'map_deep' ) ) {
			$params = map_deep( $params, 'sanitize_text_field' );
		}

		$state              = $job->start( $params );
		$state['done']      = ! empty( $state['done'] );
		$state['progress']  = isset( $state['progress'] ) ? (int) $state['progress'] : 0;
		$state['message']   = isset( $state['message'] ) ? (string) $state['message'] : '';
		$state['cancelled'] = false;
		$state['job_id']    = $id;
		$this->save_state( $id, $state );
		wp_send_json_success( $this->public_view( $id, $state ) );
	}

	/**
	 * AJAX: advance a job one step.
	 *
	 * @return void
	 */
	public function ajax_step() {
		$this->guard();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		$id  = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['job_id'] ) ) : '';
		$job = $this->get( $id );
		if ( null === $job ) {
			wp_send_json_error( array( 'message' => 'unknown_job' ), 404 );
		}
		$state = $this->load_state( $id );
		if ( ! is_array( $state ) ) {
			wp_send_json_error( array( 'message' => 'no_state' ), 400 );
		}
		if ( ! empty( $state['cancelled'] ) || ! empty( $state['done'] ) ) {
			wp_send_json_success( $this->public_view( $id, $state ) );
		}

		$state             = $job->step( $state, $this->time_budget() );
		$state['done']     = ! empty( $state['done'] );
		$state['progress'] = isset( $state['progress'] ) ? (int) $state['progress'] : 0;
		$state['message']  = isset( $state['message'] ) ? (string) $state['message'] : '';
		$state['job_id']   = $id;

		if ( $state['done'] ) {
			$finished          = $job->finish( $state );
			$state['result']   = $finished;
			$state['message']  = Strings::get( 'job_done' );
			$state['progress'] = 100;
			// Keep finished option lean: drop working keys jobs may leave behind.
			unset( $state['nodes'], $state['stack'] );
		}

		$this->save_state( $id, $state );
		wp_send_json_success( $this->public_view( $id, $state ) );
	}

	/**
	 * AJAX: cancel a running job.
	 *
	 * @return void
	 */
	public function ajax_cancel() {
		$this->guard();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		$id = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['job_id'] ) ) : '';
		if ( null === $this->get( $id ) ) {
			wp_send_json_error( array( 'message' => 'unknown_job' ), 404 );
		}
		$state = $this->load_state( $id );
		if ( ! is_array( $state ) ) {
			$state = array(
				'job_id'   => $id,
				'progress' => 0,
			);
		}
		$state['cancelled'] = true;
		$state['done']      = true;
		$state['message']   = Strings::get( 'job_cancelled' );
		unset( $state['nodes'], $state['stack'] );
		$this->save_state( $id, $state );
		wp_send_json_success( $this->public_view( $id, $state ) );
	}

	/**
	 * AJAX: return stored job state.
	 *
	 * @return void
	 */
	public function ajax_status() {
		$this->guard();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
		$id    = isset( $_POST['job_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['job_id'] ) ) : '';
		$state = $this->load_state( $id );
		if ( ! is_array( $state ) ) {
			wp_send_json_success(
				array(
					'job_id'    => $id,
					'exists'    => false,
					'progress'  => 0,
					'message'   => '',
					'done'      => false,
					'cancelled' => false,
					'stale'     => false,
				)
			);
		}
		wp_send_json_success( $this->public_view( $id, $state, true ) );
	}
}
