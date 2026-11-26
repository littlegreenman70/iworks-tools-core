<?php
/**
 * Plugin Name:       iWorks.ie Example One
 * Description:       Demo consumer of iworks-tools-core (version 1.0.0).
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            iWorks.ie
 * License:           GPL-2.0-or-later
 * Text Domain:       iworksie-example-one
 *
 * @package IWorksIE\ExampleOne
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$iwieo_autoload = __DIR__ . '/vendor-prefixed/autoload.php';
if ( ! is_readable( $iwieo_autoload ) ) {
	return;
}
require_once $iwieo_autoload;

require_once __DIR__ . '/src/DummyChecks.php';
require_once __DIR__ . '/src/CountJob.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Core' ) ) {
			return;
		}

		$core = \IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Core::boot(
			array(
				'plugin_file' => __FILE__,
				'slug'        => 'iworksie-example-one',
				'prefix'      => 'iwieo',
				'name'        => 'Example One',
				'version'     => '1.0.0',
				'text_domain' => 'iworksie-example-one',
				'description' => 'Demo plugin bundling tools-core 1.0.0.',
				'icon'        => 'dashicons-yes-alt',
				'tagline'     => 'Dummy checks and a resumable job.',
			)
		);

		$core->checks()->add( new \IWorksIE\ExampleOne\DummyGoodCheck() );
		$core->checks()->add( new \IWorksIE\ExampleOne\DummyRecommendedCheck() );
		$core->jobs()->add( new \IWorksIE\ExampleOne\CountJob() );

		$core->menu()->add_submenu(
			'Example One',
			'Example One',
			'iworksie-example-one',
			static function () use ( $core ) {
				$ui       = '\IWorksIE\ExampleOne\Vendor\IWorksIE\Core\UI';
				$strings  = '\IWorksIE\ExampleOne\Vendor\IWorksIE\Core\Strings';
				$prefix   = (string) $core->config( 'prefix' );
				$download = wp_nonce_url(
					admin_url( 'admin-post.php?action=' . $prefix . '_download_report' ),
					$prefix . '_download_report'
				);

				$core->page()->render(
					array(
						'title'   => (string) $core->config( 'name' ),
						'version' => (string) $core->config( 'version' ),
						'tagline' => (string) $core->config( 'tagline' ),
					),
					static function () use ( $ui, $strings, $download ) {
						echo '<div class="iwt-toolbar">';
						echo '<button type="button" class="button button-primary" data-iwt-run-checks>' . esc_html( $strings::get( 'run_checks' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-copy="iwt-report-source">' . esc_html( $strings::get( 'copy_report' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-download-report data-url="' . esc_url( $download ) . '">' . esc_html( $strings::get( 'download_report' ) ) . '</button>';
						echo '</div>';
						echo '<div class="iwt-summary" data-iwt-summary role="status">' . $ui::summary_bar( 0, 0, 0 ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<div data-iwt-results aria-live="polite" aria-label="' . esc_attr( $strings::get( 'results_region' ) ) . '"></div>';
						echo '<h2>Dummy job</h2>';
						echo '<div class="iwt-toolbar">';
						echo '<button type="button" class="button button-primary" data-iwt-job-start data-job="count_to_100" data-progress="example-one-job">' . esc_html( $strings::get( 'start' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-job-resume data-job="count_to_100" data-progress="example-one-job">' . esc_html( $strings::get( 'resume' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-job-cancel>' . esc_html( $strings::get( 'cancel' ) ) . '</button>';
						echo '</div>';
						echo $ui::progress( 'example-one-job' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<pre id="iwt-report-source"></pre>';
					}
				);
			}
		);
	},
	5
);
