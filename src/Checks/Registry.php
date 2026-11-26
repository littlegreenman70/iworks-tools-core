<?php
/**
 * Registry of checks for one plugin.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Checks;

/**
 * Holds Check instances.
 */
final class Registry {

	/**
	 * @var array<string,Check>
	 */
	private $checks = array();

	/**
	 * Register a check.
	 *
	 * @param Check $check Check instance.
	 * @return void
	 */
	public function add( Check $check ) {
		$this->checks[ $check->id() ] = $check;
	}

	/**
	 * @param string $id Check id.
	 * @return Check|null
	 */
	public function get( $id ) {
		return isset( $this->checks[ $id ] ) ? $this->checks[ $id ] : null;
	}

	/**
	 * @return array<string,Check>
	 */
	public function all() {
		return $this->checks;
	}

	/**
	 * @return array<string,array<string,Check>>
	 */
	public function by_group() {
		$grouped = array();
		foreach ( $this->checks as $id => $check ) {
			$group = $check->group();
			if ( ! isset( $grouped[ $group ] ) ) {
				$grouped[ $group ] = array();
			}
			$grouped[ $group ][ $id ] = $check;
		}
		return $grouped;
	}
}
