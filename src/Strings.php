<?php
/**
 * Central catalogue of core user-facing strings.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

/**
 * Provides translated strings using the consuming plugin's text domain.
 */
final class Strings {

	/**
	 * Active text domain (set by Core::boot).
	 *
	 * @var string
	 */
	private static $text_domain = 'iworks-tools-core';

	/**
	 * Set the text domain used for translations.
	 *
	 * @param string $domain Plugin text domain.
	 * @return void
	 */
	public static function set_text_domain( $domain ) {
		self::$text_domain = (string) $domain;
	}

	/**
	 * Get the current text domain.
	 *
	 * @return string
	 */
	public static function text_domain() {
		return self::$text_domain;
	}

	/**
	 * Return a translated string by key.
	 *
	 * @param string               $key  Catalogue key.
	 * @param array<string,string> $vars Optional placeholders as {name} => value.
	 * @return string
	 */
	public static function get( $key, array $vars = array() ) {
		$catalogue = self::catalogue();
		$english   = isset( $catalogue[ $key ] ) ? $catalogue[ $key ] : $key;

		if ( function_exists( 'translate' ) ) {
			$translated = translate( $english, self::$text_domain ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
		} else {
			$translated = $english;
		}

		if ( empty( $vars ) ) {
			return $translated;
		}

		$search  = array();
		$replace = array();
		foreach ( $vars as $name => $value ) {
			$search[]  = '{' . $name . '}';
			$replace[] = (string) $value;
		}

		return str_replace( $search, $replace, $translated );
	}

	/**
	 * Default English catalogue. Keys are stable; values are the English source.
	 *
	 * @return array<string,string>
	 */
	private static function catalogue() {
		return array(
			'menu_title'          => 'iWorks Tools',
			'overview'            => 'Overview',
			'open'                => 'Open',
			'more_tools'          => 'More free tools from iWorks.ie',
			'need_custom'         => 'Need a custom plugin?',
			'builds_them'         => 'iWorks builds them →',
			'rate_plugin'         => 'Rate this plugin',
			'status_good'         => 'Good',
			'status_recommended'  => 'Recommended',
			'status_critical'     => 'Critical',
			'status_info'         => 'Info',
			'why_it_matters'      => 'Why it matters',
			'how_to_fix'          => 'How to fix',
			'copy'                => 'Copy',
			'copied'              => 'Copied',
			'copy_report'         => 'Copy report',
			'download_report'     => 'Download report',
			'run_checks'          => 'Run checks',
			'running'             => 'Running…',
			'checking_progress'   => 'Checking {current} of {total}…',
			'cancel'              => 'Cancel',
			'resume'              => 'Resume',
			'start'               => 'Start',
			'job_cancelled'       => 'Cancelled',
			'job_done'            => 'Done',
			'check_failed'        => 'Check failed: {message}',
			'no_plugins'          => 'No iWorks Tools plugins are active yet.',
			'version_label'       => 'Version {version}',
			'unreadable_dir'      => 'Directory could not be read',
			'dead_weight_backup'  => 'Looks like a backup folder that may not belong in production.',
			'dead_weight_cache'   => 'Cache directory that can usually be rebuilt.',
			'dead_weight_vcs'     => 'Version control metadata that should not ship to production.',
			'dead_weight_node'    => 'Node.js dependencies folder, usually not needed on production.',
			'dead_weight_log'     => 'Log file that can grow large and may contain sensitive data.',
			'dead_weight_archive' => 'Archive file that may be leftover from a migration or backup.',
			'dead_weight_sql'     => 'Large SQL dump that should not live in the web root.',
			'progress_label'      => 'Progress',
			'results_region'      => 'Check results',
			'summary_label'       => 'Health summary',
			'installed_plugins'   => 'Installed iWorks Tools plugins',
			'plugin_version'      => 'v{version}',
			'scan_starting'       => 'Starting scan…',
			'scanning'            => 'Scanning…',
			'scan_complete'       => 'Scan complete',
			'invalid_nonce'       => 'Invalid nonce.',
			'not_allowed'         => 'Sorry, you are not allowed to access this page.',
			'no_report'           => 'No report data.',
			'assets_failed'       => 'iWorks Tools styles could not be loaded; please reinstall the plugin',
			'empty_title'         => 'Run your first health check',
			'empty_body'          => 'Takes about 10 seconds and changes nothing on your site.',
			'run_first'           => 'Run checks',
			'passed_checks'       => 'Passed checks ({count})',
			'group_critical'      => 'Critical ({count})',
			'group_recommended'   => 'Recommended ({count})',
			'group_info'          => 'Info ({count})',
			'filter_all'          => 'All',
			'resume_prompt'       => 'A previous scan was interrupted. Resume where it left off?',
		);
	}
}
