<?php
/**
 * CSS/JS registration for core screens.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

/**
 * Registers and enqueues core assets only on suite screens belonging to this plugin / overview.
 *
 * Asset URLs use plugins_url() so symlinked plugins (dev and some hosts) resolve correctly.
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
	 * Whether the last asset URL resolution failed.
	 *
	 * @var bool
	 */
	private $load_failed = false;

	/**
	 * Whether the admin notice for a failed load was already printed.
	 *
	 * @var bool
	 */
	private static $notice_printed = false;

	/**
	 * @param Core $core Core instance.
	 */
	public function __construct( Core $core ) {
		$this->core = $core;
		$this->root = dirname( __DIR__ );
	}

	/**
	 * Hook register (early) and enqueue.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 10 );
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
	}

	/**
	 * Register style/script handles early so dependents can declare them.
	 *
	 * @param string $hook Screen hook.
	 * @return void
	 */
	public function register( $hook ) {
		if ( ! $this->core->is_our_screen( $hook ) ) {
			return;
		}

		$prefix  = (string) $this->core->config( 'prefix' );
		$version = Core::VERSION;
		$handle  = $prefix . '-iwt-core';
		$base    = $this->asset_url();

		if ( '' === $base ) {
			$this->load_failed = true;
			return;
		}

		$this->load_failed = false;
		wp_register_style( $handle, $base . 'assets/css/iwt-core.css', array(), $version );
		wp_register_script( $handle, $base . 'assets/js/iwt-core.js', array(), $version, true );
	}

	/**
	 * Enqueue registered assets and localise the script.
	 *
	 * @param string $hook Screen hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->core->is_our_screen( $hook ) ) {
			return;
		}

		$prefix = (string) $this->core->config( 'prefix' );
		$handle = $prefix . '-iwt-core';

		if ( $this->load_failed || ! wp_style_is( $handle, 'registered' ) ) {
			$this->load_failed = true;
			return;
		}

		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

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
					'running'          => Strings::get( 'running' ),
					'checkingProgress' => Strings::get( 'checking_progress' ),
					'copy'             => Strings::get( 'copy' ),
					'copied'           => Strings::get( 'copied' ),
					'copyReport'       => Strings::get( 'copy_report' ),
					'downloadReport'   => Strings::get( 'download_report' ),
					'cancel'           => Strings::get( 'cancel' ),
					'start'            => Strings::get( 'start' ),
					'resume'           => Strings::get( 'resume' ),
					'jobDone'          => Strings::get( 'job_done' ),
					'jobCancelled'     => Strings::get( 'job_cancelled' ),
					'good'             => Strings::get( 'status_good' ),
					'recommended'      => Strings::get( 'status_recommended' ),
					'critical'         => Strings::get( 'status_critical' ),
					'info'             => Strings::get( 'status_info' ),
					'why'              => Strings::get( 'why_it_matters' ),
					'fix'              => Strings::get( 'how_to_fix' ),
					'passedChecks'     => Strings::get( 'passed_checks' ),
					'groupCritical'    => Strings::get( 'group_critical' ),
					'groupRecommended' => Strings::get( 'group_recommended' ),
					'groupInfo'        => Strings::get( 'group_info' ),
					'filterAll'        => Strings::get( 'filter_all' ),
					'emptyTitle'       => Strings::get( 'empty_title' ),
					'emptyBody'        => Strings::get( 'empty_body' ),
					'runFirst'         => Strings::get( 'run_first' ),
					'assetsFailed'     => Strings::get( 'assets_failed' ),
				),
			)
		);
	}

	/**
	 * Admin notice when styles/scripts could not be resolved.
	 *
	 * @return void
	 */
	public function maybe_notice() {
		if ( ! $this->load_failed || self::$notice_printed ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$hook   = ( $screen instanceof \WP_Screen ) ? (string) $screen->id : '';
		if ( '' === $hook || ! $this->core->is_our_screen( $hook ) ) {
			return;
		}
		self::$notice_printed = true;
		echo UI::notice( 'error', Strings::get( 'assets_failed' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- UI::notice escapes.
	}

	/**
	 * Whether asset resolution failed on the current request.
	 *
	 * @return bool
	 */
	public function load_failed() {
		return $this->load_failed;
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

		$plugin_file = (string) $this->core->config( 'plugin_file' );
		$candidates  = $this->candidate_asset_dirs( $plugin_file );
		$tried       = array();

		foreach ( $candidates as $dir ) {
			$tried[] = $dir;
			$css     = $dir . '/assets/css/iwt-core.css';
			if ( ! is_readable( $css ) ) {
				continue;
			}
			$relative = self::relative_to_plugin( $dir, $plugin_file );
			if ( null === $relative ) {
				continue;
			}
			$url = plugins_url( $relative, $plugin_file );
			if ( is_string( $url ) && '' !== $url ) {
				return trailingslashit( $url );
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				'iWorks Tools: could not resolve asset URL. Tried: ' . implode( ', ', $tried )
			);
		}

		return '';
	}

	/**
	 * Candidate directories that may contain the assets/ folder.
	 *
	 * @param string $plugin_file Main plugin file.
	 * @return array<int,string>
	 */
	private function candidate_asset_dirs( $plugin_file ) {
		$plugin_dir = dirname( $plugin_file );
		$dirs       = array(
			$this->root,
			$plugin_dir . '/vendor-prefixed/iworksie/tools-core',
			$plugin_dir . '/vendor-prefixed',
		);
		$out        = array();
		foreach ( $dirs as $dir ) {
			$real  = realpath( $dir );
			$out[] = wp_normalize_path( false !== $real ? $real : $dir );
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Compute the path of $asset_dir relative to the plugin root, for plugins_url().
	 *
	 * Both paths are realpath()'d so a symlinked plugin matches the resolved asset dir.
	 * Returns '' when the asset dir is the plugin root itself; null when unrelated.
	 *
	 * @param string $asset_dir   Absolute directory containing assets/.
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return string|null Relative path (no leading slash) or null.
	 */
	public static function relative_to_plugin( $asset_dir, $plugin_file ) {
		$asset_real  = realpath( $asset_dir );
		$plugin_real = realpath( dirname( $plugin_file ) );
		if ( false === $asset_real || false === $plugin_real ) {
			return null;
		}
		$asset_real  = str_replace( '\\', '/', $asset_real );
		$plugin_real = str_replace( '\\', '/', $plugin_real );

		if ( $asset_real === $plugin_real ) {
			return '';
		}

		$prefix = rtrim( $plugin_real, '/' ) . '/';
		if ( 0 !== strpos( $asset_real, $prefix ) ) {
			return null;
		}

		return ltrim( substr( $asset_real, strlen( $prefix ) ), '/' );
	}
}
