<?php
/**
 * Site Health bridge badge labelling.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use IWorksIE\Core\Checks\Check;
use IWorksIE\Core\Checks\Registry;
use IWorksIE\Core\Checks\Result;
use IWorksIE\Core\Checks\SiteHealthBridge;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Checks\SiteHealthBridge
 */
final class SiteHealthBridgeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'esc_html' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_badge_uses_plugin_label_and_blue() {
		$check = new class() implements Check {
			public function id() {
				return 'demo';
			}
			public function label() {
				return 'Demo check';
			}
			public function group() {
				return 'server';
			}
			public function run() {
				return Result::critical( 'Demo check', 'Something is wrong' );
			}
		};

		$registry = new Registry();
		$registry->add( $check );
		$bridge = new SiteHealthBridge( $registry, array( 'demo' ), 'iWorks.ie Health Check' );

		$ref    = new \ReflectionClass( $bridge );
		$method = $ref->getMethod( 'format_test_result' );
		$method->setAccessible( true );
		$out = $method->invoke( $bridge, $check );

		$this->assertSame( 'critical', $out['status'] );
		$this->assertSame( 'iWorks.ie Health Check', $out['badge']['label'] );
		$this->assertSame( 'blue', $out['badge']['color'] );
	}
}
