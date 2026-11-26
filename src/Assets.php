<?php
/**
 * CSS/JS registration for core screens.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

/**
 * Enqueues core assets only on suite screens belonging to this plugin / overview.
 */
final class Assets {

	/**
	 * @var Core
	 */
	private $core;

	/**
	 * Absolute path to this library root (parent of src/).
	 *
	 * @var string
	 */
	private $root;

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
		$this->root = dirname( __DIR__ );
	}

	/**
	 * Hook enqueue.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * @param string $hook Screen hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->core->is_our_screen( $hook ) ) {
			return;
		}

		$prefix  = (string) $this->core->config( 'prefix' );
		$version = Core::VERSION;
		$handle  = $prefix . '-iwt-core';
		$base    = $this->asset_url();
		if ( '' === $base ) {
			return;
		}

		wp_enqueue_style( $handle, $base . 'assets/css/iwt-core.css', array(), $version );
		wp_enqueue_script( $handle, $base . 'assets/js/iwt-core.js', array(), $version, true );

		$checks = array();
		foreach ( $this->core->checks()->all() as $id => $check ) {
			$checks[] = array(
				'id'    => $id,
				'label' => $check->label(),
				'group' => $check->group(),
			);
		}

		wp_localize_script(
			$handle,
			'iwtCore',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'prefix'      => $prefix,
				'checksNonce' => wp_create_nonce( $prefix . '_checks' ),
				'jobsNonce'   => wp_create_nonce( $prefix . '_jobs' ),
				'checks'      => $checks,
				'strings'     => array(
					'running'        => Strings::get( 'running' ),
					'copy'           => Strings::get( 'copy' ),
					'copied'         => Strings::get( 'copied' ),
					'copyReport'     => Strings::get( 'copy_report' ),
					'downloadReport' => Strings::get( 'download_report' ),
					'cancel'         => Strings::get( 'cancel' ),
					'start'          => Strings::get( 'start' ),
					'resume'         => Strings::get( 'resume' ),
					'jobDone'        => Strings::get( 'job_done' ),
					'jobCancelled'   => Strings::get( 'job_cancelled' ),
					'good'           => Strings::get( 'status_good' ),
					'recommended'    => Strings::get( 'status_recommended' ),
					'critical'       => Strings::get( 'status_critical' ),
					'info'           => Strings::get( 'status_info' ),
					'why'            => Strings::get( 'why_it_matters' ),
					'fix'            => Strings::get( 'how_to_fix' ),
				),
			)
		);
	}

	/**
	 * Public URL to the library package root (directory that contains assets/).
	 *
	 * @return string Trailing-slash URL or empty string.
	 */
	private function asset_url() {
		$filtered = apply_filters( 'iworks_tools_core_asset_url', '', $this->core );
		if ( is_string( $filtered ) && '' !== $filtered ) {
			return trailingslashit( $filtered );
		}

		$roots      = array( $this->root );
		$plugin_dir = dirname( (string) $this->core->config( 'plugin_file' ) );
		$roots[]    = $plugin_dir . '/vendor-prefixed/iworksie/tools-core';
		$roots[]    = $plugin_dir . '/vendor-prefixed';

		foreach ( $roots as $dir ) {
			$dir = wp_normalize_path( $dir );
			if ( ! is_readable( $dir . '/assets/css/iwt-core.css' ) ) {
				continue;
			}
			$url = $this->path_to_url( $dir );
			if ( '' !== $url ) {
				return trailingslashit( $url );
			}
		}

		return '';
	}

	/**
	 * Map a filesystem path under wp-content to a URL.
	 *
	 * @param string $dir Absolute directory.
	 * @return string
	 */
	private function path_to_url( $dir ) {
		$dir = wp_normalize_path( $dir );
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$content = wp_normalize_path( WP_CONTENT_DIR );
			if ( 0 === strpos( $dir, $content ) ) {
				$rel = ltrim( substr( $dir, strlen( $content ) ), '/' );
				return trailingslashit( WP_CONTENT_URL ) . $rel;
			}
		}
		if ( defined( 'ABSPATH' ) && function_exists( 'site_url' ) ) {
			$abs = wp_normalize_path( ABSPATH );
			if ( 0 === strpos( $dir, $abs ) ) {
				$rel = ltrim( substr( $dir, strlen( $abs ) ), '/' );
				return trailingslashit( site_url( '/' ) ) . $rel;
			}
		}
		return '';
	}
}
