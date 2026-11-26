<?php
/**
 * Integration: core assets resolve when the example plugin is loaded via symlink.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Integration;

use WP_UnitTestCase;

/**
 * @group integration
 */
final class SymlinkAssetsTest extends WP_UnitTestCase {

	/** @var string|null */
	private $link;

	/** @var string|null */
	private $original_plugin_file;

	public function setUp(): void {
		parent::setUp();
		$source = dirname( __DIR__, 2 ) . '/examples/iworksie-example-one';
		$this->assertDirectoryExists( $source );

		$plugins = WP_PLUGIN_DIR . '/iworksie-example-one-symlink-test';
		if ( file_exists( $plugins ) || is_link( $plugins ) ) {
			if ( is_link( $plugins ) || is_file( $plugins ) ) {
				unlink( $plugins );
			}
		}
		$this->assertTrue( symlink( $source, $plugins ), 'Could not create plugin symlink' );
		$this->link = $plugins;
	}

	public function tearDown(): void {
		if ( $this->link && ( is_link( $this->link ) || file_exists( $this->link ) ) ) {
			unlink( $this->link );
		}
		parent::tearDown();
	}

	public function test_assets_registered_via_plugins_url_under_symlink() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );

		$plugin_file = $this->link . '/iworksie-example-one.php';
		$this->assertFileExists( $plugin_file );

		// Boot a fresh Core against the symlinked plugin file.
		\IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Strings::set_text_domain( 'iworksie-example-one' );
		$core = \IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Core::boot(
			array(
				'plugin_file' => $plugin_file,
				'slug'        => 'iworksie-example-one-symlink-test',
				'prefix'      => 'iwieosl',
				'name'        => 'Example One Symlink',
				'version'     => '1.0.0',
				'text_domain' => 'iworksie-example-one',
			)
		);

		$hook = 'iworks-tools_page_iworksie-example-one-symlink-test';
		set_current_screen( $hook );
		do_action( 'admin_enqueue_scripts', $hook );

		$handle = 'iwieosl-iwt-core';
		$this->assertTrue( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) );
		$this->assertTrue( wp_script_is( $handle, 'registered' ) || wp_script_is( $handle, 'enqueued' ) );

		global $wp_styles, $wp_scripts;
		$style_src  = isset( $wp_styles->registered[ $handle ] ) ? $wp_styles->registered[ $handle ]->src : '';
		$script_src = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ]->src : '';

		$this->assertNotSame( '', $style_src, 'Style src must not be empty' );
		$this->assertNotSame( '', $script_src, 'Script src must not be empty' );

		$plugins_base = plugins_url( '', $plugin_file );
		$this->assertStringContainsString( 'wp-content/plugins/', $style_src );
		$this->assertStringStartsWith( $plugins_base, $style_src );

		// Local HTTP 200 against the test site URL when the file maps under plugins.
		$css_path = str_replace(
			trailingslashit( plugins_url( '', $plugin_file ) ),
			trailingslashit( dirname( $plugin_file ) ),
			$style_src
		);
		$css_path = preg_replace( '/\?.*$/', '', $css_path );
		$this->assertFileExists( $css_path );
		$this->assertGreaterThan( 0, filesize( $css_path ) );

		unset( $core );
	}
}
