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
		// Unreadable dir should be recorded when permissions prevent scandir.
		$this->assertIsArray( $result['unreadable'] );
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
