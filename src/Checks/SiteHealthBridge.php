<?php
/**
 * Optional bridge from suite checks into core Site Health.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Checks;

/**
 * Exposes selected checks as Site Health direct tests and debug info.
 */
final class SiteHealthBridge {

	/**
	 * @var Registry
	 */
	private $registry;

	/**
	 * Check ids to expose.
	 *
	 * @var array<int,string>
	 */
	private $ids;

	/**
	 * Plugin label prefix for Site Health.
	 *
	 * @var string
	 */
	private $label_prefix;

	/**
	 * @param Registry          $registry     Checks registry.
	 * @param array<int,string> $ids          Check ids to expose.
	 * @param string            $label_prefix Prefix for test labels.
	 */
	public function __construct( Registry $registry, array $ids, $label_prefix = '' ) {
		$this->registry     = $registry;
		$this->ids          = $ids;
		$this->label_prefix = (string) $label_prefix;
	}

	/**
	 * Hook into Site Health.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'site_status_tests', array( $this, 'register_tests' ) );
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
	}

	/**
	 * @param array<string,mixed> $tests Existing tests.
	 * @return array<string,mixed>
	 */
	public function register_tests( $tests ) {
		if ( ! is_array( $tests ) ) {
			$tests = array();
		}
		if ( ! isset( $tests['direct'] ) || ! is_array( $tests['direct'] ) ) {
			$tests['direct'] = array();
		}
		foreach ( $this->ids as $id ) {
			$check = $this->registry->get( $id );
			if ( null === $check ) {
				continue;
			}
			$tests['direct'][ 'iworks_tools_' . $id ] = array(
				'label' => ( '' !== $this->label_prefix ? $this->label_prefix . ': ' : '' ) . $check->label(),
				'test'  => function () use ( $check ) {
					return $this->format_test_result( $check );
				},
			);
		}
		return $tests;
	}

	/**
	 * @param Check $check Check.
	 * @return array<string,mixed>
	 */
	private function format_test_result( Check $check ) {
		try {
			$result = $check->run();
		} catch ( \Throwable $e ) {
			$result = Result::critical( $check->label(), $e->getMessage() );
		}

		$map    = array(
			'good'        => 'good',
			'recommended' => 'recommended',
			'critical'    => 'critical',
			'info'        => 'good',
		);
		$status = isset( $map[ $result->status() ] ) ? $map[ $result->status() ] : 'recommended';

		return array(
			'label'       => $result->label(),
			'status'      => $status,
			'badge'       => array(
				'label' => $result->status(),
				'color' => ( 'critical' === $status ) ? 'red' : ( ( 'recommended' === $status ) ? 'orange' : 'blue' ),
			),
			'description' => '<p>' . esc_html( $result->description() ) . '</p>',
			'actions'     => '',
			'test'        => 'iworks_tools_' . $check->id(),
		);
	}

	/**
	 * @param array<string,mixed> $info Debug info.
	 * @return array<string,mixed>
	 */
	public function debug_information( $info ) {
		if ( ! is_array( $info ) ) {
			$info = array();
		}
		$fields = array();
		foreach ( $this->ids as $id ) {
			$check = $this->registry->get( $id );
			if ( null === $check ) {
				continue;
			}
			try {
				$result = $check->run();
				$value  = $result->status() . ': ' . $result->description();
			} catch ( \Throwable $e ) {
				$value = 'error: ' . $e->getMessage();
			}
			$fields[ $id ] = array(
				'label' => $check->label(),
				'value' => $value,
			);
		}
		$info['iworks-tools'] = array(
			'label'  => '' !== $this->label_prefix ? $this->label_prefix : 'iWorks Tools',
			'fields' => $fields,
		);
		return $info;
	}
}
