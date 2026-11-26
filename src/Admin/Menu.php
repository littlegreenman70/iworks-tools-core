<?php
/**
 * Submenu registration under the shared iWorks Tools menu.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Admin;

use IWorksIE\Core\Core;

/**
 * Registers plugin submenu pages under `iworks-tools`.
 */
final class Menu {

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * Pending submenu registrations.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $pending = array();

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
		add_action( 'admin_menu', array( $this, 'register_pending' ), 10 );
	}

	/**
	 * Queue a submenu page under iworks-tools.
	 *
	 * @param string   $page_title Browser / heading title.
	 * @param string   $menu_title Sidebar label.
	 * @param string   $slug       Page slug (usually the plugin slug).
	 * @param callable $callback   Render callback.
	 * @return void
	 */
	public function add_submenu( $page_title, $menu_title, $slug, $callback ) {
		$this->pending[] = array(
			'page_title' => (string) $page_title,
			'menu_title' => (string) $menu_title,
			'slug'       => (string) $slug,
			'callback'   => $callback,
		);
	}

	/**
	 * Register queued submenus and tidy the auto-generated first item.
	 *
	 * @return void
	 */
	public function register_pending() {
		$cap = (string) $this->core->config( 'capability' );
		foreach ( $this->pending as $item ) {
			add_submenu_page(
				'iworks-tools',
				$item['page_title'],
				$item['menu_title'],
				$cap,
				$item['slug'],
				$item['callback']
			);
		}

		// Ensure the first submenu label reads "Overview" (owner already added it).
		global $submenu;
		if ( isset( $submenu['iworks-tools'] ) && is_array( $submenu['iworks-tools'] ) ) {
			foreach ( $submenu['iworks-tools'] as $index => $entry ) {
				if ( isset( $entry[2] ) && 'iworks-tools' === $entry[2] && isset( $entry[0] ) ) {
					// Leave as Overview from register_top_menu; remove accidental duplicates.
					if ( $index > 0 && 'iworks-tools' === $entry[2] ) {
						unset( $submenu['iworks-tools'][ $index ] );
					}
				}
			}
		}
	}
}
