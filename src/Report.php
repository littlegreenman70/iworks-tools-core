<?php
/**
 * Plain-text / Markdown report builder and download handler.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

use IWorksIE\Core\Checks\Result;

/**
 * Builds Markdown reports and handles secure downloads.
 */
final class Report {

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
	 * Register download handler.
	 *
	 * @return void
	 */
	public function hooks() {
		$prefix = (string) $this->core->config( 'prefix' );
		add_action( 'admin_post_' . $prefix . '_download_report', array( $this, 'download' ) );
	}

	/**
	 * Build a Markdown report from results.
	 *
	 * @param string                   $title   Report title.
	 * @param array<int|string,Result> $results Results.
	 * @param array<string,string>     $meta    Extra metadata.
	 * @return string
	 */
	public static function from_results( $title, array $results, array $meta = array() ) {
		$lines   = array();
		$lines[] = '# ' . $title;
		$lines[] = '';
		$site    = isset( $meta['site'] ) ? $meta['site'] : ( function_exists( 'home_url' ) ? home_url( '/' ) : '' );
		$wp      = isset( $meta['wp'] ) ? $meta['wp'] : ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '' );
		$php     = isset( $meta['php'] ) ? $meta['php'] : PHP_VERSION;
		$date    = isset( $meta['date'] ) ? $meta['date'] : gmdate( 'c' );

		$lines[] = '- Site: ' . $site;
		$lines[] = '- WordPress: ' . $wp;
		$lines[] = '- PHP: ' . $php;
		$lines[] = '- Date: ' . $date;
		$lines[] = '';

		$grouped = array();
		foreach ( $results as $result ) {
			if ( ! $result instanceof Result ) {
				continue;
			}
			$status = $result->status();
			if ( ! isset( $grouped[ $status ] ) ) {
				$grouped[ $status ] = array();
			}
			$grouped[ $status ][] = $result;
		}

		$order = array( 'critical', 'recommended', 'good', 'info' );
		foreach ( $order as $status ) {
			if ( empty( $grouped[ $status ] ) ) {
				continue;
			}
			$lines[] = '## ' . ucfirst( $status );
			$lines[] = '';
			foreach ( $grouped[ $status ] as $result ) {
				$lines[] = '### ' . $result->label();
				$lines[] = $result->description();
				if ( '' !== $result->why() ) {
					$lines[] = '';
					$lines[] = '**Why it matters:** ' . $result->why();
				}
				if ( '' !== $result->fix() ) {
					$lines[] = '';
					$lines[] = '**How to fix:** ' . $result->fix();
				}
				$lines[] = '';
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * admin-post download handler.
	 *
	 * @return void
	 */
	public function download() {
		$prefix = (string) $this->core->config( 'prefix' );
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), $prefix . '_download_report' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'default' ), 403 );
		}
		if ( ! current_user_can( (string) $this->core->config( 'capability' ) ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		$raw = isset( $_POST['report'] ) ? wp_unslash( (string) $_POST['report'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- downloaded as attachment text.
		if ( '' === $raw && isset( $_GET['report'] ) ) {
			$raw = wp_unslash( (string) $_GET['report'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		// Prefer transient keyed download for large reports.
		$key = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( (string) $_GET['key'] ) ) : '';
		if ( '' !== $key ) {
			$stored = get_transient( $prefix . '_report_' . $key );
			if ( is_string( $stored ) ) {
				$raw = $stored;
			}
		}
		if ( '' === $raw ) {
			wp_die( esc_html__( 'No report data.', 'default' ), 400 );
		}

		$filename = sanitize_file_name( (string) $this->core->config( 'slug' ) . '-report.md' );
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $raw; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file download body.
		exit;
	}

	/**
	 * Store report content briefly and return a download URL.
	 *
	 * @param string $markdown Report body.
	 * @return string
	 */
	public function store_for_download( $markdown ) {
		$prefix = (string) $this->core->config( 'prefix' );
		$key    = function_exists( 'wp_generate_password' ) ? wp_generate_password( 12, false, false ) : bin2hex( random_bytes( 6 ) );
		$ttl    = defined( 'MINUTE_IN_SECONDS' ) ? 10 * MINUTE_IN_SECONDS : 600;
		set_transient( $prefix . '_report_' . $key, (string) $markdown, $ttl );
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . $prefix . '_download_report&key=' . rawurlencode( $key ) ),
			$prefix . '_download_report'
		);
	}
}
