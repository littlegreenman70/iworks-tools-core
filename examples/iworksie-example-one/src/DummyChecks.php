<?php
/**
 * Dummy checks for example one.
 *
 * @package IWorksIE\ExampleOne
 */

namespace IWorksIE\ExampleOne;

use IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Checks\Check;
use IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Checks\Result;

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
		return 'example_one_good';
	}

	/**
	 * Check label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Example One baseline';
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
			'Example One baseline',
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
		return 'example_one_recommended';
	}

	/**
	 * Check label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Example One suggestion';
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
			'Example One suggestion',
			'This dummy check always recommends a review.',
			array(
				'why' => 'Shows the recommended badge and expandable help.',
				'fix' => 'Nothing real to fix; this is a demo.',
			)
		);
	}
}
