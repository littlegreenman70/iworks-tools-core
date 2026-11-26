<?php
/**
 * Suite version election tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Suite\Registry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Suite\Registry::elect
 */
final class SuiteRegistryTest extends TestCase {

	public function test_elect_picks_highest_version() {
		$winner = Registry::elect(
			array(
				array(
					'version' => '1.0.0',
					'slug'    => 'a',
				),
				array(
					'version' => '1.0.1',
					'slug'    => 'b',
				),
				array(
					'version' => '1.0.0',
					'slug'    => 'c',
				),
			)
		);
		$this->assertSame( 'b', $winner['slug'] );
	}

	public function test_elect_keeps_first_on_tie() {
		$winner = Registry::elect(
			array(
				array(
					'version' => '1.0.0',
					'slug'    => 'first',
				),
				array(
					'version' => '1.0.0',
					'slug'    => 'second',
				),
			)
		);
		$this->assertSame( 'first', $winner['slug'] );
	}

	public function test_elect_empty() {
		$this->assertSame( array(), Registry::elect( array() ) );
	}
}
