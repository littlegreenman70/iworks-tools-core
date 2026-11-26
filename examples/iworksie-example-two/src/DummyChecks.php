<?php
/**
 * Dummy checks for example two.
 *
 * @package IWorksIE\ExampleTwo
 */

namespace IWorksIE\ExampleTwo;

use IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\Checks\Check;
use IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\Checks\Result;

/**
 * Always-good dummy check.
 */
final class DummyGoodCheck implements Check {

	/**
	 * Check id.
	 *
	 * @return string
	 */
	public function id() {
		return 'example_two_good';
	}

	/**
	 * Check label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Example Two baseline';
	}

	/**
	 * Check group.
	 *
	 * @return string
	 */
	public function group() {
		return 'demo';
	}

	/**
	 * Run the check.
	 *
	 * @return Result
	 */
	public function run() {
		return Result::good(
			'Example Two baseline',
			'Everything looks fine in this dummy check.',
			array(
				'why' => 'Demonstrates a good status card.',
				'fix' => 'No action needed.',
			)
		);
	}
}

/**
 * Recommended dummy check.
 */
final class DummyRecommendedCheck implements Check {

	/**
	 * Check id.
	 *
	 * @return string
	 */
	public function id() {
		return 'example_two_recommended';
	}

	/**
	 * Check label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Example Two suggestion';
	}

	/**
	 * Check group.
	 *
	 * @return string
	 */
	public function group() {
		return 'demo';
	}

	/**
	 * Run the check.
	 *
	 * @return Result
	 */
	public function run() {
		return Result::recommended(
			'Example Two suggestion',
			'This dummy check always recommends a review.',
			array(
				'why' => 'Shows the recommended badge and expandable help.',
				'fix' => 'Nothing real to fix; this is a demo.',
			)
		);
	}
}
