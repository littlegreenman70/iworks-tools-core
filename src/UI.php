<?php
/**
 * Escaped HTML render helpers for admin UI.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core;

use IWorksIE\Core\Checks\Result;

/**
 * Static UI helpers. All output is escaped.
 */
final class UI {

	/**
	 * Status badge matching Site Health wording and colours.
	 *
	 * @param string $status One of good|recommended|critical|info.
	 * @return string HTML.
	 */
	public static function status_badge( $status ) {
		$map    = array(
			'good'        => array(
				'class' => 'iwt-badge iwt-badge--good',
				'label' => Strings::get( 'status_good' ),
			),
			'recommended' => array(
				'class' => 'iwt-badge iwt-badge--recommended',
				'label' => Strings::get( 'status_recommended' ),
			),
			'critical'    => array(
				'class' => 'iwt-badge iwt-badge--critical',
				'label' => Strings::get( 'status_critical' ),
			),
			'info'        => array(
				'class' => 'iwt-badge iwt-badge--info',
				'label' => Strings::get( 'status_info' ),
			),
		);
		$status = isset( $map[ $status ] ) ? $status : 'info';
		$item   = $map[ $status ];
		return '<span class="' . esc_attr( $item['class'] ) . '"><span class="screen-reader-text">' . esc_html( $item['label'] ) . ': </span>' . esc_html( $item['label'] ) . '</span>';
	}

	/**
	 * Render a result accordion row (Site Health style).
	 *
	 * @param Result $result Check result.
	 * @param bool   $open   Whether the accordion starts expanded.
	 * @return string HTML.
	 */
	public static function result_card( Result $result, $open = false ) {
		$status   = $result->status();
		$open     = (bool) $open;
		$panel_id = 'iwt-result-' . ( function_exists( 'wp_unique_id' ) ? wp_unique_id() : uniqid( '', false ) );

		$html  = '<article class="iwt-result iwt-result--' . esc_attr( $status ) . '" data-status="' . esc_attr( $status ) . '">';
		$html .= '<h3 class="iwt-result__heading">';
		$html .= '<button type="button" class="iwt-result__toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $panel_id ) . '">';
		$html .= '<span class="iwt-result__title">' . esc_html( $result->label() ) . '</span>';
		$html .= '<span class="iwt-result__meta">';
		$html .= self::status_badge( $status );
		$html .= '<span class="iwt-result__chevron" aria-hidden="true"></span>';
		$html .= '</span></button></h3>';
		$html .= '<div class="iwt-result__body" id="' . esc_attr( $panel_id ) . '"' . ( $open ? '' : ' hidden' ) . '>';
		$html .= '<p>' . esc_html( $result->description() ) . '</p>';
		$html .= self::result_matches_html( $result->data() );
		if ( '' !== $result->why() ) {
			$html .= '<div class="iwt-result__section"><h4>' . esc_html( Strings::get( 'why_it_matters' ) ) . '</h4>';
			$html .= '<p>' . esc_html( $result->why() ) . '</p></div>';
		}
		if ( '' !== $result->fix() ) {
			$html .= '<div class="iwt-result__section"><h4>' . esc_html( Strings::get( 'how_to_fix' ) ) . '</h4>';
			$html .= '<p>' . esc_html( $result->fix() ) . '</p></div>';
		}
		$actions = $result->actions();
		if ( ! empty( $actions ) ) {
			$html .= '<ul class="iwt-result__actions">';
			foreach ( $actions as $action ) {
				if ( empty( $action['label'] ) || empty( $action['url'] ) ) {
					continue;
				}
				$html .= '<li><a class="button" href="' . esc_url( (string) $action['url'] ) . '">' . esc_html( (string) $action['label'] ) . '</a></li>';
			}
			$html .= '</ul>';
		}
		$html .= '</div></article>';
		return $html;
	}

	/**
	 * Optional match list from result data (relative path + size).
	 *
	 * @param array<string,mixed> $data Result data.
	 * @return string HTML.
	 */
	private static function result_matches_html( array $data ) {
		$matches = array();
		if ( ! empty( $data['matches'] ) && is_array( $data['matches'] ) ) {
			$matches = $data['matches'];
		} elseif ( ! empty( $data['files'] ) && is_array( $data['files'] ) ) {
			$matches = $data['files'];
		}
		if ( empty( $matches ) ) {
			return '';
		}

		$html  = '<ul class="iwt-result__matches">';
		$shown = 0;
		foreach ( $matches as $match ) {
			if ( $shown >= 10 ) {
				break;
			}
			if ( is_string( $match ) ) {
				$path = $match;
				$size = '';
				$kind = '';
			} elseif ( is_array( $match ) ) {
				$path = isset( $match['relative'] ) ? (string) $match['relative'] : ( isset( $match['path'] ) ? (string) $match['path'] : '' );
				$size = isset( $match['size'] ) ? (string) $match['size'] : '';
				$kind = isset( $match['kind'] ) ? (string) $match['kind'] : '';
			} else {
				continue;
			}
			if ( '' === $path ) {
				continue;
			}
			$label = $path;
			if ( '' !== $kind ) {
				$label = $kind . ': ' . $path;
			}
			if ( '' !== $size ) {
				$label .= ' (' . $size . ')';
			}
			$html .= '<li>' . esc_html( $label ) . '</li>';
			++$shown;
		}
		$html .= '</ul>';
		$more  = count( $matches ) - $shown;
		if ( $more > 0 ) {
			$html .= '<p class="iwt-result__matches-more">' . esc_html( Strings::get( 'and_n_more', array( 'count' => (string) $more ) ) ) . '</p>';
		}
		if ( ! empty( $data['scan_label'] ) ) {
			$html .= '<p class="iwt-result__scan-source">' . esc_html( (string) $data['scan_label'] ) . '</p>';
		}
		return $html;
	}

	/**
	 * Empty state before the first health check run.
	 *
	 * @return string HTML.
	 */
	public static function empty_state() {
		$html  = '<div class="iwt-empty" data-iwt-empty>';
		$html .= '<div class="iwt-empty__icon" aria-hidden="true"><span class="dashicons dashicons-heart"></span></div>';
		$html .= '<h2 class="iwt-empty__title">' . esc_html( Strings::get( 'empty_title' ) ) . '</h2>';
		$html .= '<p class="iwt-empty__body">' . esc_html( Strings::get( 'empty_body' ) ) . '</p>';
		$html .= '<p class="iwt-empty__action"><button type="button" class="button button-primary button-hero" data-iwt-run-checks>' . esc_html( Strings::get( 'run_first' ) ) . '</button></p>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Site Health style summary bar with score circle and filterable counts.
	 *
	 * @param int $good        Good count.
	 * @param int $recommended Recommended count.
	 * @param int $critical    Critical count.
	 * @param int $info        Optional info count.
	 * @return string HTML.
	 */
	public static function summary_bar( $good, $recommended, $critical, $info = 0 ) {
		$good        = max( 0, (int) $good );
		$recommended = max( 0, (int) $recommended );
		$critical    = max( 0, (int) $critical );
		$info        = max( 0, (int) $info );
		$total       = $good + $recommended + $critical;
		$score       = ( 0 === $total ) ? 100 : (int) round( ( $good / $total ) * 100 );
		// Score ring: 90+ green, 60-89 orange (Site Health "recommended"), under 60 red.
		if ( $score >= 90 ) {
			$class = 'iwt-summary__score--good';
		} elseif ( $score >= 60 ) {
			$class = 'iwt-summary__score--recommended';
		} else {
			$class = 'iwt-summary__score--critical';
		}

		$html  = '<div class="iwt-summary" role="status" aria-label="' . esc_attr( Strings::get( 'summary_label' ) ) . '" data-iwt-summary data-score="' . esc_attr( (string) $score ) . '">';
		$html .= '<div class="iwt-summary__score ' . esc_attr( $class ) . '" style="--iwt-score:' . esc_attr( (string) $score ) . '" aria-hidden="true">';
		$html .= '<span class="iwt-summary__score-value">' . esc_html( (string) $score ) . '</span></div>';
		$html .= '<ul class="iwt-summary__counts">';
		$html .= '<li><button type="button" class="iwt-summary__filter is-active" data-iwt-filter="all">' . esc_html( Strings::get( 'filter_all' ) ) . '</button></li>';
		$html .= '<li><button type="button" class="iwt-summary__filter" data-iwt-filter="critical"><span class="iwt-summary__count" data-count="critical">' . esc_html( (string) $critical ) . '</span> ' . esc_html( Strings::get( 'status_critical' ) ) . '</button></li>';
		$html .= '<li><button type="button" class="iwt-summary__filter" data-iwt-filter="recommended"><span class="iwt-summary__count" data-count="recommended">' . esc_html( (string) $recommended ) . '</span> ' . esc_html( Strings::get( 'status_recommended' ) ) . '</button></li>';
		$html .= '<li><button type="button" class="iwt-summary__filter" data-iwt-filter="good"><span class="iwt-summary__count" data-count="good">' . esc_html( (string) $good ) . '</span> ' . esc_html( Strings::get( 'status_good' ) ) . '</button></li>';
		if ( $info > 0 ) {
			$html .= '<li><button type="button" class="iwt-summary__filter" data-iwt-filter="info"><span class="iwt-summary__count" data-count="info">' . esc_html( (string) $info ) . '</span> ' . esc_html( Strings::get( 'status_info' ) ) . '</button></li>';
		}
		$html .= '</ul></div>';
		return $html;
	}

	/**
	 * Group heading with count, e.g. "Critical (1)".
	 *
	 * @param string $status Status key.
	 * @param int    $count  Count.
	 * @return string HTML.
	 */
	public static function group_heading( $status, $count ) {
		$map   = array(
			'critical'    => 'group_critical',
			'recommended' => 'group_recommended',
			'info'        => 'group_info',
			'good'        => 'passed_checks',
		);
		$key   = isset( $map[ $status ] ) ? $map[ $status ] : 'group_info';
		$label = Strings::get( $key, array( 'count' => (string) (int) $count ) );
		return '<h2 class="iwt-group__heading">' . esc_html( $label ) . '</h2>';
	}

	/**
	 * Progress bar with ARIA live region.
	 *
	 * @param string $id Element id prefix.
	 * @return string HTML.
	 */
	public static function progress( $id ) {
		$id    = sanitize_key( $id );
		$html  = '<div class="iwt-progress" id="' . esc_attr( $id ) . '" data-iwt-progress="' . esc_attr( $id ) . '">';
		$html .= '<div class="iwt-progress__row">';
		$html .= '<div class="iwt-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="' . esc_attr( Strings::get( 'progress_label' ) ) . '">';
		$html .= '<div class="iwt-progress__bar" style="width:0%"></div></div>';
		$html .= '<span class="iwt-progress__pct" aria-hidden="true">0%</span>';
		$html .= '</div>';
		$html .= '<p class="iwt-progress__message" aria-live="polite"></p>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Copy-to-clipboard button targeting an element id.
	 *
	 * @param string $target_id Element whose textContent is copied.
	 * @return string HTML.
	 */
	public static function copy_button( $target_id ) {
		$target_id = sanitize_key( $target_id );
		return '<button type="button" class="button iwt-copy" data-iwt-copy="' . esc_attr( $target_id ) . '">' . esc_html( Strings::get( 'copy' ) ) . '</button>';
	}

	/**
	 * Simple data table.
	 *
	 * Cell values may be a string or an array with `text` and optional `title`.
	 *
	 * @param array<string,string>           $columns Column key => heading.
	 * @param array<int,array<string,mixed>> $rows Row maps.
	 * @return string HTML.
	 */
	public static function table( array $columns, array $rows ) {
		$html = '<table class="widefat striped iwt-table"><thead><tr>';
		foreach ( $columns as $heading ) {
			$html .= '<th scope="col">' . esc_html( (string) $heading ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		if ( empty( $rows ) ) {
			$html .= '<tr><td colspan="' . esc_attr( (string) count( $columns ) ) . '">—</td></tr>';
		} else {
			foreach ( $rows as $row ) {
				$html .= '<tr>';
				foreach ( array_keys( $columns ) as $key ) {
					$raw = isset( $row[ $key ] ) ? $row[ $key ] : '';
					if ( is_array( $raw ) ) {
						$text  = array_key_exists( 'text', $raw ) ? (string) $raw['text'] : '';
						$title = array_key_exists( 'title', $raw ) ? (string) $raw['title'] : '';
						$html .= '<td' . ( '' !== $title ? ' title="' . esc_attr( $title ) . '"' : '' ) . '>' . esc_html( $text ) . '</td>';
					} else {
						$html .= '<td>' . esc_html( (string) $raw ) . '</td>';
					}
				}
				$html .= '</tr>';
			}
		}
		$html .= '</tbody></table>';
		return $html;
	}

	/**
	 * Admin notice markup.
	 *
	 * @param string $type    success|warning|error|info.
	 * @param string $message Notice text.
	 * @return string HTML.
	 */
	public static function notice( $type, $message ) {
		$allowed = array( 'success', 'warning', 'error', 'info' );
		$type    = in_array( $type, $allowed, true ) ? $type : 'info';
		return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible iwt-notice"><p>' . esc_html( $message ) . '</p></div>';
	}
}
