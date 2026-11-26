<?php
/**
 * Checks registry tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Checks\Check;
use IWorksIE\Core\Checks\Registry;
use IWorksIE\Core\Checks\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Checks\Registry
 */
final class ChecksRegistryTest extends TestCase {

	public function test_add_get_group() {
		$reg = new Registry();
		$c   = new class() implements Check {
			public function id() {
				return 't1';
			}
			public function label() {
				return 'T1';
			}
			public function group() {
				return 'g';
			}
			public function run() {
				return Result::good( 'T1', 'ok' );
			}
		};
		$reg->add( $c );
		$this->assertSame( $c, $reg->get( 't1' ) );
		$this->assertArrayHasKey( 'g', $reg->by_group() );
		$this->assertCount( 1, $reg->all() );
	}
}
