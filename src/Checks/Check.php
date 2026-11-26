<?php
/**
 * Check interface.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Checks;

/**
 * A single health / diagnostic check.
 */
interface Check {

	/**
	 * Stable identifier.
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Group key for grouping in the UI.
	 *
	 * @return string
	 */
	public function group();

	/**
	 * Run the check.
	 *
	 * @return Result
	 */
	public function run();
}
