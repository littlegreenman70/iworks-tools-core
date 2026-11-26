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
}
