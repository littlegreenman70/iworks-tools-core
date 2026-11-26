<?php
/**
 * Plugin Name:       iWorks.ie Example Two
 * Description:       Demo consumer of iworks-tools-core pretending to ship core 1.0.1.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            iWorks.ie
 * License:           GPL-2.0-or-later
 * Text Domain:       iworksie-example-two
 *
 * @package IWorksIE\ExampleTwo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$iwiet_autoload = __DIR__ . '/vendor-prefixed/autoload.php';
if ( ! is_readable( $iwiet_autoload ) ) {
	return;
}
require_once $iwiet_autoload;

require_once __DIR__ . '/src/DummyChecks.php';
require_once __DIR__ . '/src/CountJob.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\Core' ) ) {
			return;
		}

		$core = \IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\Core::boot(
			array(
				'plugin_file' => __FILE__,
				'slug'        => 'iworksie-example-two',
				'prefix'      => 'iwiet',
				'name'        => 'Example Two',
				'version'     => '1.0.0',
				'text_domain' => 'iworksie-example-two',
				'description' => 'Demo plugin bundling tools-core 1.0.1 (version election winner).',
				'icon'        => 'dashicons-star-filled',
				'tagline'     => 'Higher core version owns the Overview menu.',
			)
		);

		$core->checks()->add( new \IWorksIE\ExampleTwo\DummyGoodCheck() );
		$core->checks()->add( new \IWorksIE\ExampleTwo\DummyRecommendedCheck() );
		$core->jobs()->add( new \IWorksIE\ExampleTwo\CountJob() );

		$core->menu()->add_submenu(
			'Example Two',
			'Example Two',
			'iworksie-example-two',
			static function () use ( $core ) {
				$ui       = '\IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\UI';
				$strings  = '\IWorksIE\ExampleTwo\Vendor\IWorksIE\Core\Strings';
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
						echo '<button type="button" class="button button-primary" data-iwt-job-start data-job="count_to_100" data-progress="example-two-job">' . esc_html( $strings::get( 'start' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-job-resume data-job="count_to_100" data-progress="example-two-job">' . esc_html( $strings::get( 'resume' ) ) . '</button>';
						echo '<button type="button" class="button" data-iwt-job-cancel>' . esc_html( $strings::get( 'cancel' ) ) . '</button>';
						echo '</div>';
						echo $ui::progress( 'example-two-job' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<pre id="iwt-report-source"></pre>';
					}
				);
			}
		);
	},
	5
);
