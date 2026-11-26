<?php
/**
 * Result unit tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Checks\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Checks\Result
 */
final class ResultTest extends TestCase {

	public function test_named_constructors_and_roundtrip() {
		$r = Result::recommended(
			'Label',
			'Desc',
			array(
				'why'     => 'Why',
				'fix'     => 'Fix',
				'data'    => array( 'a' => 1 ),
				'actions' => array(
					array(
						'label' => 'Open',
						'url'   => 'https://example.com',
					),
				),
			)
		);

		$this->assertSame( 'recommended', $r->status() );
		$this->assertSame( 'Label', $r->label() );
		$this->assertSame( 'Desc', $r->description() );
		$this->assertSame( 'Why', $r->why() );
		$this->assertSame( 'Fix', $r->fix() );
		$this->assertSame( array( 'a' => 1 ), $r->data() );

		$again = Result::from_array( $r->to_array() );
		$this->assertSame( $r->to_array(), $again->to_array() );
	}

	public function test_from_array_sanitises_status() {
		$r = Result::from_array(
			array(
				'status' => 'nope',
				'label'  => 'X',
			)
		);
		$this->assertSame( 'info', $r->status() );
	}
}
