<?php
/**
 * Suite-wide registration and menu version election.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Suite;

use IWorksIE\Core\Core;
use IWorksIE\Core\Strings;

/**
 * Registers this core copy and elects which copy owns the top-level menu.
 *
 * Public hook contract (do not rename or remove fields; add only):
 * - Filter `iworks_tools_register_core`: array of [ version, render_overview, register_menu ].
 * - Filter `iworks_tools_plugins`: array of plugin cards.
 * - Filter `iworks_tools_catalogue`: array of external tool links (default empty).
 */
final class Registry {

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
	}

	/**
	 * Wire suite hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'iworks_tools_register_core', array( $this, 'register_core' ), 10, 1 );
		add_filter( 'iworks_tools_plugins', array( $this, 'register_plugin' ), 10, 1 );
		add_action( 'admin_menu', array( $this, 'maybe_own_menu' ), 5 );
	}

	/**
	 * Add this copy to the core election list.
	 *
	 * @param array<int,array<string,mixed>> $cores Existing registrations.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_core( $cores ) {
		if ( ! is_array( $cores ) ) {
			$cores = array();
		}
		$cores[] = array(
			'version'         => Core::VERSION,
			'render_overview' => array( $this, 'render_overview' ),
			'register_menu'   => array( $this, 'register_top_menu' ),
			'slug'            => (string) $this->core->config( 'slug' ),
		);
		return $cores;
	}

	/**
	 * Add this plugin to the overview card list.
	 *
	 * @param array<int,array<string,mixed>> $plugins Existing plugins.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_plugin( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			$plugins = array();
		}
		$slug      = (string) $this->core->config( 'slug' );
		$plugins[] = array(
			'slug'        => $slug,
			'name'        => (string) $this->core->config( 'name' ),
			'version'     => (string) $this->core->config( 'version' ),
			'description' => (string) $this->core->config( 'description' ),
			'admin_url'   => admin_url( 'admin.php?page=' . $slug ),
			'wporg_url'   => (string) $this->core->config( 'wporg_url' ),
			'icon'        => (string) $this->core->config( 'icon' ),
		);
		return $plugins;
	}

	/**
	 * Elect the highest-version core and let it register the top-level menu once.
	 *
	 * @return void
	 */
	public function maybe_own_menu() {
		if ( ! empty( $GLOBALS['iworks_tools_menu_owner'] ) ) {
			return;
		}

		$cores = apply_filters( 'iworks_tools_register_core', array() );
		if ( ! is_array( $cores ) || empty( $cores ) ) {
			return;
		}

		$winner = self::elect( $cores );
		if ( empty( $winner['register_menu'] ) || ! is_callable( $winner['register_menu'] ) ) {
			return;
		}

		$GLOBALS['iworks_tools_menu_owner'] = isset( $winner['slug'] ) ? (string) $winner['slug'] : 'unknown';
		call_user_func( $winner['register_menu'], $winner );
	}

	/**
	 * Pick the highest version; ties keep the first registered.
	 *
	 * @param array<int,array<string,mixed>> $cores Registrations.
	 * @return array<string,mixed>
	 */
	public static function elect( array $cores ) {
		$winner = null;
		foreach ( $cores as $core ) {
			if ( ! is_array( $core ) || empty( $core['version'] ) ) {
				continue;
			}
			if ( null === $winner ) {
				$winner = $core;
				continue;
			}
			if ( version_compare( (string) $core['version'], (string) $winner['version'], '>' ) ) {
				$winner = $core;
			}
		}
		return null === $winner ? array() : $winner;
	}

	/**
	 * Register the shared top-level menu and Overview page.
	 *
	 * @param array<string,mixed> $winner Winning registration (unused beyond call).
	 * @return void
	 */
	public function register_top_menu( $winner = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$cores   = apply_filters( 'iworks_tools_register_core', array() );
		$elected = self::elect( is_array( $cores ) ? $cores : array() );
		$render  = isset( $elected['render_overview'] ) && is_callable( $elected['render_overview'] )
			? $elected['render_overview']
			: array( $this, 'render_overview' );

		add_menu_page(
			Strings::get( 'menu_title' ),
			Strings::get( 'menu_title' ),
			(string) $this->core->config( 'capability' ),
			'iworks-tools',
			$render,
			'dashicons-admin-tools',
			75
		);

		add_submenu_page(
			'iworks-tools',
			Strings::get( 'overview' ),
			Strings::get( 'overview' ),
			(string) $this->core->config( 'capability' ),
			'iworks-tools',
			$render
		);
	}

	/**
	 * Render the suite Overview page.
	 *
	 * @return void
	 */
	public function render_overview() {
		if ( ! current_user_can( (string) $this->core->config( 'capability' ) ) ) {
			wp_die( esc_html( Strings::get( 'not_allowed' ) ) );
		}

		$plugins = apply_filters( 'iworks_tools_plugins', array() );
		if ( ! is_array( $plugins ) ) {
			$plugins = array();
		}
		$catalogue = apply_filters( 'iworks_tools_catalogue', array() );
		if ( ! is_array( $catalogue ) ) {
			$catalogue = array();
		}

		$this->core->page()->render(
			array(
				'title'   => Strings::get( 'menu_title' ),
				'version' => Core::VERSION,
				'tagline' => Strings::get( 'installed_plugins' ),
			),
			function () use ( $plugins, $catalogue ) {
				echo '<div class="iwt-overview">';
				if ( empty( $plugins ) ) {
					echo '<p>' . esc_html( Strings::get( 'no_plugins' ) ) . '</p>';
				} else {
					echo '<div class="iwt-card-grid" role="list">';
					foreach ( $plugins as $plugin ) {
						if ( ! is_array( $plugin ) ) {
							continue;
						}
						$name = isset( $plugin['name'] ) ? (string) $plugin['name'] : '';
						$ver  = isset( $plugin['version'] ) ? (string) $plugin['version'] : '';
						$desc = isset( $plugin['description'] ) ? (string) $plugin['description'] : '';
						$url  = isset( $plugin['admin_url'] ) ? (string) $plugin['admin_url'] : '';
						$icon = isset( $plugin['icon'] ) ? (string) $plugin['icon'] : 'dashicons-admin-tools';
						echo '<article class="iwt-card" role="listitem">';
						echo '<div class="iwt-card__icon"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span></div>';
						echo '<div class="iwt-card__title-row">';
						echo '<h2 class="iwt-card__title">' . esc_html( $name ) . '</h2>';
						if ( '' !== $ver ) {
							echo '<span class="iwt-pill">' . esc_html( Strings::get( 'plugin_version', array( 'version' => $ver ) ) ) . '</span>';
						}
						echo '</div>';
						if ( '' !== $desc ) {
							echo '<p class="iwt-card__desc">' . esc_html( $desc ) . '</p>';
						}
						if ( '' !== $url ) {
							echo '<p class="iwt-card__action"><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( Strings::get( 'open' ) ) . '</a></p>';
						}
						echo '</article>';
					}
					echo '</div>';
				}

				if ( ! empty( $catalogue ) ) {
					echo '<section class="iwt-catalogue" aria-labelledby="iwt-catalogue-heading">';
					echo '<h2 id="iwt-catalogue-heading">' . esc_html( Strings::get( 'more_tools' ) ) . '</h2>';
					echo '<ul class="iwt-catalogue__list">';
					foreach ( $catalogue as $item ) {
						if ( ! is_array( $item ) || empty( $item['url'] ) || empty( $item['label'] ) ) {
							continue;
						}
						echo '<li><a href="' . esc_url( (string) $item['url'] ) . '" rel="noopener noreferrer">' . esc_html( (string) $item['label'] ) . '</a></li>';
					}
					echo '</ul></section>';
				}
				echo '</div>';
			}
		);
	}
}
