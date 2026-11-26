<?php
/**
 * DeadWeight matcher tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use IWorksIE\Core\Filesystem\DeadWeight;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Filesystem\DeadWeight
 */
final class DeadWeightTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

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

	public function test_backup_prefix_matches_dir_basename() {
		$m = DeadWeight::match( '/var/www/wp-content/uploads/backup-2023', true, 100 );
		$this->assertNotNull( $m );
		$this->assertSame( 'backup_prefix', $m['id'] );

		$m2 = DeadWeight::match( '/var/www/wp-content/uploads/backups', true, 100 );
		$this->assertNotNull( $m2 );
		$this->assertSame( 'backup_prefix', $m2['id'] );
	}

	public function test_backup_prefix_ignores_files() {
		$m = DeadWeight::match( '/var/www/wp-content/uploads/backup-notes.txt', false, 100 );
		$this->assertNull( $m );
	}

	public function test_backup_prefix_ignores_plugin_and_theme_trees() {
		$plugin = DeadWeight::match( '/var/www/wp-content/plugins/backupbuddy', true, 100 );
		$this->assertNull( $plugin );
		$theme = DeadWeight::match( '/var/www/wp-content/themes/backup-theme', true, 100 );
		$this->assertNull( $theme );
	}

	public function test_backup_prefix_ignores_core_upgrade_folders() {
		$empty = DeadWeight::match( '/var/www/wp-content/upgrade-temp-backup', true, 0 );
		$this->assertNull( $empty );

		$with_files = DeadWeight::match( '/var/www/wp-content/upgrade-temp-backup', true, 4096 );
		$this->assertNull( $with_files );

		$nested = DeadWeight::match( '/var/www/wp-content/upgrade-temp-backup/plugins', true, 100 );
		$this->assertNull( $nested );

		$upgrade = DeadWeight::match( '/var/www/wp-content/upgrade', true, 100 );
		$this->assertNull( $upgrade );
	}

	public function test_backup_prefix_ignores_empty_folder() {
		$m = DeadWeight::match( '/var/www/wp-content/uploads/backup-old', true, 0 );
		$this->assertNull( $m );
	}

	public function test_backup_ignore_paths_filter() {
		Functions\when( 'apply_filters' )->alias(
			static function ( $tag, $value ) {
				if ( 'iworks_tools_backup_ignore_paths' === $tag && is_array( $value ) ) {
					$value[] = 'wp-content/custom-ignore';
				}
				return $value;
			}
		);

		$this->assertTrue(
			DeadWeight::is_ignored_backup_path( '/var/www/wp-content/custom-ignore/file.zip' )
		);
		$this->assertNull(
			DeadWeight::match( '/var/www/wp-content/custom-ignore/backup-stuff', true, 100 )
		);
		$this->assertContains(
			'wp-content/custom-ignore',
			DeadWeight::backup_ignore_paths()
		);
	}
}
