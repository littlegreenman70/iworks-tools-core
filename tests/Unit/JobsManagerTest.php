<?php
/**
 * Job manager time-budget / stale helpers (no WordPress).
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Jobs\Manager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \IWorksIE\Core\Jobs\Manager
 */
final class JobsManagerTest extends TestCase {

	/**
	 * @return Manager
	 */
	private function bare_manager() {
		$ref = new ReflectionClass( Manager::class );
		return $ref->newInstanceWithoutConstructor();
	}

	public function test_is_stale() {
		$mgr = $this->bare_manager();
		$this->assertFalse( $mgr->is_stale( array( 'updated_at' => time() ) ) );
		$this->assertTrue( $mgr->is_stale( array( 'updated_at' => time() - Manager::STALE_SECONDS - 10 ) ) );
	}

	public function test_time_budget_caps_at_ten() {
		$mgr    = $this->bare_manager();
		$budget = $mgr->time_budget();
		$this->assertGreaterThan( 0, $budget );
		$this->assertLessThanOrEqual( 10.0, $budget );
	}

	public function test_public_view_omits_working_data() {
		$mgr  = $this->bare_manager();
		$view = $mgr->public_view(
			'size_scanner',
			array(
				'progress'  => 40,
				'message'   => 'Scanning…',
				'done'      => false,
				'cancelled' => false,
				'updated_at'=> time(),
				'nodes'     => array( '/tmp' => array() ),
				'stack'     => array( '/tmp' ),
				'result'    => array( 'secret' => 1 ),
			)
		);
		$this->assertArrayHasKey( 'job_id', $view );
		$this->assertArrayHasKey( 'progress', $view );
		$this->assertArrayNotHasKey( 'nodes', $view );
		$this->assertArrayNotHasKey( 'stack', $view );
		$this->assertArrayNotHasKey( 'result', $view );

		$done = $mgr->public_view(
			'size_scanner',
			array(
				'progress'   => 100,
				'message'    => 'Done',
				'done'       => true,
				'cancelled'  => false,
				'updated_at' => time(),
				'result'     => array( 'total_bytes' => 9 ),
			)
		);
		$this->assertSame( array( 'total_bytes' => 9 ), $done['result'] );
	}
}
