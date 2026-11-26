<?php
/**
 * SizeScanner unit tests against a temp fixture.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Filesystem\SizeScanner;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Filesystem\SizeScanner
 */
final class SizeScannerTest extends TestCase {

	/** @var string */
	private $dir;

	protected function setUp(): void {
		parent::setUp();
		$this->dir = sys_get_temp_dir() . '/iwt-scan-' . uniqid( '', true );
		mkdir( $this->dir . '/keep', 0755, true );
		mkdir( $this->dir . '/updraft', 0755, true );
		file_put_contents( $this->dir . '/keep/a.txt', str_repeat( 'a', 2048 ) );
		file_put_contents( $this->dir . '/updraft/b.zip', str_repeat( 'b', 1024 ) );
		file_put_contents( $this->dir . '/big.sql', str_repeat( 'c', 11 * 1024 * 1024 ) );

		// Symlink loop (must be skipped).
		@symlink( $this->dir, $this->dir . '/loop' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		$unread = $this->dir . '/secret';
		mkdir( $unread, 0755, true );
		file_put_contents( $unread . '/x.txt', 'x' );
		chmod( $unread, 0000 );
	}

	protected function tearDown(): void {
		$unread = $this->dir . '/secret';
		if ( is_dir( $unread ) ) {
			chmod( $unread, 0755 );
		}
		$this->rrmdir( $this->dir );
		parent::tearDown();
	}

	public function test_scan_completes_and_skips_symlink() {
		$scanner = new SizeScanner();
		$state   = $scanner->start( array( 'root' => $this->dir ) );
		$guard   = 0;
		while ( empty( $state['done'] ) && $guard < 200 ) {
			$state = $scanner->step( $state, 2.0 );
			++$guard;
		}
		$this->assertTrue( ! empty( $state['done'] ) );
		$result = $scanner->finish( $state );
		$this->assertGreaterThan( 11 * 1024 * 1024, $result['total_bytes'] );
		$this->assertArrayHasKey( 'tree', $result );
		$this->assertArrayHasKey( 'top_files', $result );
		$this->assertIsArray( $result['unreadable'] );
		// Finished state must not retain working sets.
		$this->assertArrayNotHasKey( 'nodes', $state );
		$this->assertArrayNotHasKey( 'stack', $state );
	}

	public function test_large_fixture_keeps_serialized_state_small() {
		$root = sys_get_temp_dir() . '/iwt-scan-big-' . uniqid( '', true );
		mkdir( $root, 0755, true );
		$expected_bytes = 0;
		$expected_files = 0;
		for ( $d = 0; $d < 50; $d++ ) {
			$dir = $root . '/d' . $d;
			mkdir( $dir, 0755, true );
			for ( $f = 0; $f < 100; $f++ ) {
				$path = $dir . '/f' . $f . '.txt';
				$blob = str_repeat( 'x', 32 );
				file_put_contents( $path, $blob );
				$expected_bytes += strlen( $blob );
				++$expected_files;
			}
		}

		$scanner = new SizeScanner();
		$state   = $scanner->start( array( 'root' => $root ) );
		$guard   = 0;
		$max_ser = 0;
		while ( empty( $state['done'] ) && $guard < 5000 ) {
			$state   = $scanner->step( $state, 0.5 );
			$ser     = serialize( $state );
			$max_ser = max( $max_ser, strlen( $ser ) );
			$this->assertLessThan( 200 * 1024, strlen( $ser ), 'Serialized state exceeded 200 KB at step ' . $guard );
			++$guard;
		}
		$this->assertTrue( ! empty( $state['done'] ), 'Scan did not finish' );
		$result = $scanner->finish( $state );
		$this->assertSame( $expected_files, $result['total_files'] );
		$this->assertSame( $expected_bytes, $result['total_bytes'] );
		$this->assertLessThan( 200 * 1024, $max_ser );

		$this->rrmdir( $root );
	}

	public function test_path_is_under_roots_rejects_etc_and_dotdot() {
		$allowed = array( $this->dir );
		$this->assertTrue( SizeScanner::path_is_under_roots( $this->dir . '/keep', $allowed ) );
		$this->assertFalse( SizeScanner::path_is_under_roots( '/etc', $allowed ) );

		$etc = realpath( '/etc' );
		if ( false !== $etc ) {
			$sneaky = realpath( $this->dir . '/keep/../../../../../etc' );
			if ( false !== $sneaky ) {
				$this->assertFalse( SizeScanner::path_is_under_roots( $sneaky, $allowed ) );
			}
		}
	}

	/**
	 * @param string $dir Directory.
	 * @return void
	 */
	private function rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( false === $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_link( $path ) ) {
				unlink( $path );
			} elseif ( is_dir( $path ) ) {
				$this->rrmdir( $path );
			} else {
				unlink( $path );
			}
		}
		rmdir( $dir );
	}
}
