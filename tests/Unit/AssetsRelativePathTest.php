<?php
/**
 * Unit tests for Assets::relative_to_plugin().
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Assets;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class AssetsRelativePathTest extends TestCase {

	/** @var string */
	private $tmp;

	protected function setUp(): void {
		parent::setUp();
		$this->tmp = sys_get_temp_dir() . '/iwt-assets-' . uniqid( '', false );
		mkdir( $this->tmp . '/plugin/vendor-prefixed/iworksie/tools-core/assets/css', 0777, true );
		mkdir( $this->tmp . '/plugin/assets/css', 0777, true );
		file_put_contents( $this->tmp . '/plugin/plugin.php', "<?php\n" );
		file_put_contents( $this->tmp . '/plugin/assets/css/iwt-core.css', '/* x */' );
		file_put_contents(
			$this->tmp . '/plugin/vendor-prefixed/iworksie/tools-core/assets/css/iwt-core.css',
			'/* x */'
		);

		// Symlinked plugin dir pointing at a real tree outside "wp-content".
		mkdir( $this->tmp . '/outside/real-plugin/vendor-prefixed/assets/css', 0777, true );
		mkdir( $this->tmp . '/plugins-dir', 0777, true );
		file_put_contents( $this->tmp . '/outside/real-plugin/plugin.php', "<?php\n" );
		file_put_contents( $this->tmp . '/outside/real-plugin/vendor-prefixed/assets/css/iwt-core.css', '/* x */' );
		symlink( $this->tmp . '/outside/real-plugin', $this->tmp . '/plugins-dir/real-plugin' );
	}

	protected function tearDown(): void {
		$this->rmTree( $this->tmp );
		parent::tearDown();
	}

	public function test_relative_when_assets_live_in_plugin_root() {
		$rel = Assets::relative_to_plugin(
			$this->tmp . '/plugin',
			$this->tmp . '/plugin/plugin.php'
		);
		$this->assertSame( '', $rel );
	}

	public function test_relative_when_assets_live_under_vendor_prefixed() {
		$rel = Assets::relative_to_plugin(
			$this->tmp . '/plugin/vendor-prefixed/iworksie/tools-core',
			$this->tmp . '/plugin/plugin.php'
		);
		$this->assertSame( 'vendor-prefixed/iworksie/tools-core', $rel );
	}

	public function test_relative_through_symlinked_plugin() {
		$link_plugin = $this->tmp . '/plugins-dir/real-plugin/plugin.php';
		$asset_dir   = $this->tmp . '/plugins-dir/real-plugin/vendor-prefixed';
		$rel         = Assets::relative_to_plugin( $asset_dir, $link_plugin );
		$this->assertSame( 'vendor-prefixed', $rel );
	}

	public function test_unrelated_path_returns_null() {
		$rel = Assets::relative_to_plugin(
			$this->tmp . '/outside',
			$this->tmp . '/plugin/plugin.php'
		);
		$this->assertNull( $rel );
	}

	/**
	 * @param string $dir Directory.
	 * @return void
	 */
	private function rmTree( $dir ) {
		if ( ! is_dir( $dir ) && ! is_link( $dir ) ) {
			return;
		}
		if ( is_link( $dir ) ) {
			unlink( $dir );
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
			if ( is_link( $path ) || is_file( $path ) ) {
				unlink( $path );
			} else {
				$this->rmTree( $path );
			}
		}
		rmdir( $dir );
	}
}
