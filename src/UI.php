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
	 * Render a result card.
	 *
	 * @param Result $result Check result.
	 * @return string HTML.
	 */
	public static function result_card( Result $result ) {
		$html  = '<article class="iwt-result iwt-result--' . esc_attr( $result->status() ) . '" data-status="' . esc_attr( $result->status() ) . '">';
		$html .= '<header class="iwt-result__header">';
		$html .= '<h3 class="iwt-result__title">' . esc_html( $result->label() ) . '</h3>';
		$html .= self::status_badge( $result->status() );
		$html .= '</header>';
		$html .= '<div class="iwt-result__body">';
		$html .= '<p>' . esc_html( $result->description() ) . '</p>';
		if ( '' !== $result->why() ) {
			$html .= '<details class="iwt-result__details"><summary>' . esc_html( Strings::get( 'why_it_matters' ) ) . '</summary>';
			$html .= '<p>' . esc_html( $result->why() ) . '</p></details>';
		}
		if ( '' !== $result->fix() ) {
			$html .= '<details class="iwt-result__details"><summary>' . esc_html( Strings::get( 'how_to_fix' ) ) . '</summary>';
			$html .= '<p>' . esc_html( $result->fix() ) . '</p></details>';
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
	 * Site Health style summary bar with score circle.
	 *
	 * @param int $good        Good count.
	 * @param int $recommended Recommended count.
	 * @param int $critical    Critical count.
	 * @return string HTML.
	 */
	public static function summary_bar( $good, $recommended, $critical ) {
		$good        = max( 0, (int) $good );
		$recommended = max( 0, (int) $recommended );
		$critical    = max( 0, (int) $critical );
		$total       = $good + $recommended + $critical;
		$score       = ( 0 === $total ) ? 100 : (int) round( ( $good / $total ) * 100 );
		$class       = 'iwt-summary__score--good';
		if ( $critical > 0 ) {
			$class = 'iwt-summary__score--critical';
		} elseif ( $recommended > 0 ) {
			$class = 'iwt-summary__score--recommended';
		}

		$html  = '<div class="iwt-summary" role="status" aria-label="' . esc_attr( Strings::get( 'summary_label' ) ) . '">';
		$html .= '<div class="iwt-summary__score ' . esc_attr( $class ) . '" aria-hidden="true"><span>' . esc_html( (string) $score ) . '</span></div>';
		$html .= '<ul class="iwt-summary__counts">';
		$html .= '<li><span class="iwt-summary__count">' . esc_html( (string) $good ) . '</span> ' . esc_html( Strings::get( 'status_good' ) ) . '</li>';
		$html .= '<li><span class="iwt-summary__count">' . esc_html( (string) $recommended ) . '</span> ' . esc_html( Strings::get( 'status_recommended' ) ) . '</li>';
		$html .= '<li><span class="iwt-summary__count">' . esc_html( (string) $critical ) . '</span> ' . esc_html( Strings::get( 'status_critical' ) ) . '</li>';
		$html .= '</ul></div>';
		return $html;
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
		$html .= '<div class="iwt-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="' . esc_attr( Strings::get( 'progress_label' ) ) . '">';
		$html .= '<div class="iwt-progress__bar" style="width:0%"></div></div>';
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
	 * @param array<string,string>            $columns Column key => heading.
	 * @param array<int,array<string,string>> $rows Row maps.
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
					$cell  = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
					$html .= '<td>' . esc_html( $cell ) . '</td>';
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
