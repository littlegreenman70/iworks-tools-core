<?php
/**
 * Dummy job that counts to 100 in steps.
 *
 * @package IWorksIE\ExampleOne
 */

namespace IWorksIE\ExampleOne;

use IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Jobs\Job;

/**
 * Increments a counter toward 100 within the time budget.
 */
final class CountJob implements Job {

	/**
	 * Job id.
	 *
	 * @return string
	 */
	public function id() {
		return 'count_to_100';
	}

	/**
	 * Start counting from zero.
	 *
	 * @param array<string,mixed> $params Params.
	 * @return array<string,mixed>
	 */
	public function start( array $params ) {
		return array(
			'count'    => 0,
			'target'   => 100,
			'done'     => false,
			'progress' => 0,
			'message'  => 'Counting…',
		);
	}

	/**
	 * Advance the counter within the time budget.
	 *
	 * @param array<string,mixed> $state       State.
	 * @param float               $time_budget Budget.
	 * @return array<string,mixed>
	 */
	public function step( array $state, $time_budget ) {
		$deadline = microtime( true ) + max( 0.05, min( (float) $time_budget, 0.25 ) );
		$count    = isset( $state['count'] ) ? (int) $state['count'] : 0;
		$target   = isset( $state['target'] ) ? (int) $state['target'] : 100;

		while ( $count < $target && microtime( true ) < $deadline ) {
			++$count;
			usleep( 5000 );
		}

		$state['count']    = $count;
		$state['progress'] = (int) min( 100, floor( ( $count / $target ) * 100 ) );
		$state['done']     = $count >= $target;
		$state['message']  = $state['done'] ? 'Counted to ' . $target : 'Count: ' . $count;
		return $state;
	}

	/**
	 * Return the final count.
	 *
	 * @param array<string,mixed> $state State.
	 * @return array<string,mixed>
	 */
	public function finish( array $state ) {
		return array(
			'count' => isset( $state['count'] ) ? (int) $state['count'] : 0,
		);
	}
}
