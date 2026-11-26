<?php
/**
 * Standard admin screen layout.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Admin;

use IWorksIE\Core\Core;
use IWorksIE\Core\Strings;
use IWorksIE\Core\UI;

/**
 * Renders the shared page chrome for plugin screens and Overview.
 */
final class Page {

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
	 * Render a standard iWorks Tools screen.
	 *
	 * @param array<string,mixed> $args    Screen options (title, version, tagline, tabs).
	 * @param callable            $content Callback that echoes the body.
	 * @return void
	 */
	public function render( array $args, $content ) {
		$title   = isset( $args['title'] ) ? (string) $args['title'] : (string) $this->core->config( 'name' );
		$version = isset( $args['version'] ) ? (string) $args['version'] : (string) $this->core->config( 'version' );
		$tagline = isset( $args['tagline'] ) ? (string) $args['tagline'] : (string) $this->core->config( 'tagline' );
		$tabs    = isset( $args['tabs'] ) && is_array( $args['tabs'] ) ? $args['tabs'] : array();
		$allowed = array_keys( $tabs );
		$active  = '';
		if ( ! empty( $tabs ) ) {
			$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active    = in_array( $requested, $allowed, true ) ? $requested : (string) reset( $allowed );
		}

		echo '<div class="wrap iwt-page">';
		echo '<header class="iwt-page__header">';
		echo '<h1 class="iwt-page__title">' . esc_html( $title );
		if ( '' !== $version ) {
			echo ' <span class="iwt-page__version">' . esc_html( Strings::get( 'version_label', array( 'version' => $version ) ) ) . '</span>';
		}
		echo '</h1>';
		if ( '' !== $tagline ) {
			echo '<p class="iwt-page__tagline">' . esc_html( $tagline ) . '</p>';
		}
		echo '</header>';

		if ( ! empty( $tabs ) ) {
			echo '<nav class="nav-tab-wrapper iwt-tabs" aria-label="' . esc_attr( $title ) . '">';
			foreach ( $tabs as $key => $label ) {
				$url   = add_query_arg( 'tab', $key );
				$class = ( $key === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
				echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( (string) $label ) . '</a>';
			}
			echo '</nav>';
		}

		echo '<div class="iwt-notices" id="iwt-notices" aria-live="polite"></div>';
		echo '<div class="iwt-page__content">';
		call_user_func( $content, $active );
		echo '</div>';

		$this->render_footer();
		echo '</div>';
	}

	/**
	 * Shared footer with custom-plugin CTA and optional rating link.
	 *
	 * @return void
	 */
	private function render_footer() {
		$slug  = (string) $this->core->config( 'slug' );
		$utm   = 'https://www.iworks.ie/?utm_source=wp-plugin&utm_medium=' . rawurlencode( $slug );
		$wporg = (string) $this->core->config( 'wporg_url' );

		echo '<footer class="iwt-page__footer">';
		echo '<p>' . esc_html( Strings::get( 'need_custom' ) ) . ' ';
		echo '<a href="' . esc_url( $utm ) . '" rel="noopener noreferrer"><strong>' . esc_html( Strings::get( 'builds_them' ) ) . '</strong></a>';
		echo '</p>';
		if ( '' !== $wporg ) {
			$reviews = trailingslashit( $wporg ) . 'reviews/#new-post';
			echo '<p><a href="' . esc_url( $reviews ) . '" rel="noopener noreferrer">' . esc_html( Strings::get( 'rate_plugin' ) ) . '</a></p>';
		}
		echo '</footer>';
	}
}
