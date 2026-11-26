<?php
/**
 * Chunked job interface.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Jobs;

/**
 * Resumable background-style job executed in AJAX steps.
 */
interface Job {

	/**
	 * Stable job id.
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Begin a job and return the initial state.
	 *
	 * @param array<string,mixed> $params Start parameters.
	 * @return array<string,mixed>
	 */
	public function start( array $params );

	/**
	 * Advance the job for up to $time_budget seconds.
	 *
	 * Returned state must include:
	 * - done (bool)
	 * - progress (int 0-100)
	 * - message (string)
	 *
	 * @param array<string,mixed> $state       Current state.
	 * @param float               $time_budget Seconds available.
	 * @return array<string,mixed>
	 */
	public function step( array $state, $time_budget );

	/**
	 * Finalise and return a result payload.
	 *
	 * @param array<string,mixed> $state Final state.
	 * @return array<string,mixed>
	 */
	public function finish( array $state );
}
