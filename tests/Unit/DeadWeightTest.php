<?php
/**
 * DeadWeight matcher tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Filesystem\DeadWeight;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Filesystem\DeadWeight
 */
final class DeadWeightTest extends TestCase {

	public function test_matches_backup_dir() {
		$m = DeadWeight::match( '/var/www/wp-content/updraft', true, 100 );
		$this->assertNotNull( $m );
		$this->assertSame( 'backup_updraft', $m['id'] );
	}

	public function test_matches_node_modules() {
		$m = DeadWeight::match( '/var/www/node_modules', true, 1 );
		$this->assertNotNull( $m );
		$this->assertSame( 'node_modules', $m['id'] );
	}

	public function test_matches_large_sql() {
		$m = DeadWeight::match( '/var/www/dump.sql', false, 11 * 1024 * 1024 );
		$this->assertNotNull( $m );
		$this->assertSame( 'sql_large', $m['id'] );
	}

	public function test_ignores_small_sql() {
		$m = DeadWeight::match( '/var/www/dump.sql', false, 100 );
		$this->assertNull( $m );
	}

	public function test_matches_zip() {
		$m = DeadWeight::match( '/var/www/backup.zip', false, 10 );
		$this->assertNotNull( $m );
		$this->assertSame( 'archive', $m['id'] );
	}
}
