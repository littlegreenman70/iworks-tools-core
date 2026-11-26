<?php
/**
 * Integration: menu ownership with both example plugins.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Integration;

use WP_UnitTestCase;

/**
 * @group integration
 */
final class MenuElectionTest extends WP_UnitTestCase {

	public function test_single_top_level_menu_owned_by_example_two() {
		set_current_screen( 'dashboard' );
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );

		do_action( 'admin_menu' );

		global $menu, $submenu;
		$found = 0;
		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && 'iworks-tools' === $item[2] ) {
				++$found;
			}
		}
		$this->assertSame( 1, $found, 'Exactly one iworks-tools top-level menu' );
		$this->assertSame( 'iworksie-example-two', $GLOBALS['iworks_tools_menu_owner'] ?? '' );

		$this->assertArrayHasKey( 'iworks-tools', $submenu );
		$slugs = array();
		foreach ( $submenu['iworks-tools'] as $entry ) {
			$slugs[] = $entry[2];
		}
		$this->assertContains( 'iworks-tools', $slugs );
		$this->assertContains( 'iworksie-example-one', $slugs );
		$this->assertContains( 'iworksie-example-two', $slugs );
	}
}
