<?php
/**
 * Immutable check result.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Checks;

/**
 * Value object for a check outcome.
 */
final class Result {

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var string
	 */
	private $why;

	/**
	 * @var string
	 */
	private $fix;

	/**
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * @var array<int,array<string,string>>
	 */
	private $actions;

	/**
	 * @param string                          $status      good|recommended|critical|info.
	 * @param string                          $label       Title.
	 * @param string                          $description Short description.
	 * @param string                          $why         Why it matters.
	 * @param string                          $fix        How to fix.
	 * @param array<string,mixed>             $data        Extra data.
	 * @param array<int,array<string,string>> $actions     Actions with label+url.
	 */
	private function __construct( $status, $label, $description, $why, $fix, array $data, array $actions ) {
		$this->status      = $status;
		$this->label       = $label;
		$this->description = $description;
		$this->why         = $why;
		$this->fix         = $fix;
		$this->data        = $data;
		$this->actions     = $actions;
	}

	/**
	 * @param string              $label Label.
	 * @param string              $description Description.
	 * @param array<string,mixed> $extra Optional why/fix/data/actions.
	 * @return self
	 */
	public static function good( $label, $description, array $extra = array() ) {
		return self::make( 'good', $label, $description, $extra );
	}

	/**
	 * @param string              $label Label.
	 * @param string              $description Description.
	 * @param array<string,mixed> $extra Optional why/fix/data/actions.
	 * @return self
	 */
	public static function recommended( $label, $description, array $extra = array() ) {
		return self::make( 'recommended', $label, $description, $extra );
	}

	/**
	 * @param string              $label Label.
	 * @param string              $description Description.
	 * @param array<string,mixed> $extra Optional why/fix/data/actions.
	 * @return self
	 */
	public static function critical( $label, $description, array $extra = array() ) {
		return self::make( 'critical', $label, $description, $extra );
	}

	/**
	 * @param string              $label Label.
	 * @param string              $description Description.
	 * @param array<string,mixed> $extra Optional why/fix/data/actions.
	 * @return self
	 */
	public static function info( $label, $description, array $extra = array() ) {
		return self::make( 'info', $label, $description, $extra );
	}

	/**
	 * @param string              $status Status.
	 * @param string              $label Label.
	 * @param string              $description Description.
	 * @param array<string,mixed> $extra Extra fields.
	 * @return self
	 */
	private static function make( $status, $label, $description, array $extra ) {
		return new self(
			$status,
			(string) $label,
			(string) $description,
			isset( $extra['why'] ) ? (string) $extra['why'] : '',
			isset( $extra['fix'] ) ? (string) $extra['fix'] : '',
			isset( $extra['data'] ) && is_array( $extra['data'] ) ? $extra['data'] : array(),
			isset( $extra['actions'] ) && is_array( $extra['actions'] ) ? $extra['actions'] : array()
		);
	}

	/** @return string */
	public function status() {
		return $this->status;
	}

	/** @return string */
	public function label() {
		return $this->label;
	}

	/** @return string */
	public function description() {
		return $this->description;
	}

	/** @return string */
	public function why() {
		return $this->why;
	}

	/** @return string */
	public function fix() {
		return $this->fix;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function data() {
		return $this->data;
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function actions() {
		return $this->actions;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return array(
			'status'      => $this->status,
			'label'       => $this->label,
			'description' => $this->description,
			'why'         => $this->why,
			'fix'         => $this->fix,
			'data'        => $this->data,
			'actions'     => $this->actions,
		);
	}

	/**
	 * @param array<string,mixed> $data Serialized result.
	 * @return self
	 */
	public static function from_array( array $data ) {
		$status  = isset( $data['status'] ) ? (string) $data['status'] : 'info';
		$allowed = array( 'good', 'recommended', 'critical', 'info' );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'info';
		}
		return new self(
			$status,
			isset( $data['label'] ) ? (string) $data['label'] : '',
			isset( $data['description'] ) ? (string) $data['description'] : '',
			isset( $data['why'] ) ? (string) $data['why'] : '',
			isset( $data['fix'] ) ? (string) $data['fix'] : '',
			isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array(),
			isset( $data['actions'] ) && is_array( $data['actions'] ) ? $data['actions'] : array()
		);
	}
}
