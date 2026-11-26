<?php
/**
 * Entry point for the iWorks Tools core library.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

use IWorksIE\Core\Admin\Menu;
use IWorksIE\Core\Admin\Page;
use IWorksIE\Core\Checks\Registry as ChecksRegistry;
use IWorksIE\Core\Checks\Runner as ChecksRunner;
use IWorksIE\Core\Jobs\Manager as JobsManager;
use IWorksIE\Core\Suite\Registry as SuiteRegistry;

/**
 * Boots and holds one plugin's core instance.
 */
final class Core {

	/**
	 * Library semver.
	 *
	 * @var string
	 */
	const VERSION = '1.1.2';

	/**
	 * Plugin configuration.
	 *
	 * @var array<string,mixed>
	 */
	private $config;

	/**
	 * Admin menu helper.
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Page layout helper.
	 *
	 * @var Page
	 */
	private $page;

	/**
	 * Checks registry.
	 *
	 * @var ChecksRegistry
	 */
	private $checks;

	/**
	 * Checks AJAX runner.
	 *
	 * @var ChecksRunner
	 */
	private $checks_runner;

	/**
	 * Jobs manager.
	 *
	 * @var JobsManager
	 */
	private $jobs;

	/**
	 * Assets registrar.
	 *
	 * @var Assets
	 */
	private $assets;

	/**
	 * Report helper.
	 *
	 * @var Report
	 */
	private $report;

	/**
	 * Suite registry (shared menu / version election).
	 *
	 * @var SuiteRegistry
	 */
	private $suite;

	/**
	 * Boot a core instance for one plugin.
	 *
	 * Required keys: plugin_file, slug, prefix, name, version, text_domain.
	 * Optional: menu_title, capability, wporg_url, description, icon, tagline.
	 *
	 * @param array<string,mixed> $args Boot configuration.
	 * @return Core
	 * @throws \InvalidArgumentException When a required boot argument is missing.
	 */
	public static function boot( array $args ) {
		$required = array( 'plugin_file', 'slug', 'prefix', 'name', 'version', 'text_domain' );
		foreach ( $required as $key ) {
			if ( empty( $args[ $key ] ) ) {
				throw new \InvalidArgumentException( esc_html( 'Core::boot missing required arg: ' . $key ) );
			}
		}

		$defaults = array(
			'menu_title'  => '',
			'capability'  => 'manage_options',
			'wporg_url'   => '',
			'description' => '',
			'icon'        => 'dashicons-admin-tools',
			'tagline'     => '',
		);
		$args     = array_merge( $defaults, $args );
		if ( '' === $args['menu_title'] ) {
			$args['menu_title'] = $args['name'];
		}

		Strings::set_text_domain( (string) $args['text_domain'] );

		$instance = new self( $args );
		$instance->suite->register();
		$instance->assets->hooks();
		$instance->checks_runner->hooks();
		$instance->jobs->hooks();
		$instance->report->hooks();

		return $instance;
	}

	/**
	 * @param array<string,mixed> $config Boot arguments.
	 */
	private function __construct( array $config ) {
		$this->config        = $config;
		$this->menu          = new Menu( $this );
		$this->page          = new Page( $this );
		$this->checks        = new ChecksRegistry();
		$this->checks_runner = new ChecksRunner( $this );
		$this->jobs          = new JobsManager( $this );
		$this->assets        = new Assets( $this );
		$this->report        = new Report( $this );
		$this->suite         = new SuiteRegistry( $this );
	}

	/**
	 * Configuration value.
	 *
	 * @param string $key           Config key.
	 * @param mixed  $fallback_value Default when missing.
	 * @return mixed
	 */
	public function config( $key, $fallback_value = null ) {
		return array_key_exists( $key, $this->config ) ? $this->config[ $key ] : $fallback_value;
	}

	/**
	 * Full config array.
	 *
	 * @return array<string,mixed>
	 */
	public function all_config() {
		return $this->config;
	}

	/**
	 * @return Menu
	 */
	public function menu() {
		return $this->menu;
	}

	/**
	 * @return Page
	 */
	public function page() {
		return $this->page;
	}

	/**
	 * @return ChecksRegistry
	 */
	public function checks() {
		return $this->checks;
	}

	/**
	 * @return ChecksRunner
	 */
	public function checks_runner() {
		return $this->checks_runner;
	}

	/**
	 * @return JobsManager
	 */
	public function jobs() {
		return $this->jobs;
	}

	/**
	 * @return Assets
	 */
	public function assets() {
		return $this->assets;
	}

	/**
	 * @return Report
	 */
	public function report() {
		return $this->report;
	}

	/**
	 * @return SuiteRegistry
	 */
	public function suite() {
		return $this->suite;
	}

	/**
	 * Whether the current admin screen belongs to this plugin or the shared overview.
	 *
	 * @param string $hook Current screen hook suffix.
	 * @return bool
	 */
	public function is_our_screen( $hook ) {
		$slug   = (string) $this->config( 'slug' );
		$prefix = 'iworks-tools_page_';
		if ( false !== strpos( (string) $hook, $prefix . $slug ) ) {
			return true;
		}
		if ( 'toplevel_page_iworks-tools' === $hook ) {
			return true;
		}
		return false;
	}
}
