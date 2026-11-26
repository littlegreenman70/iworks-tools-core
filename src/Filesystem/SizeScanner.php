<?php
/**
 * Iterative directory size scanner as a resumable Job.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Filesystem;

use IWorksIE\Core\Jobs\Job;

/**
 * Walks a filesystem tree without recursion or symlink following.
 */
final class SizeScanner implements Job {

	/**
	 * Include children for directories at or above this size (bytes).
	 *
	 * @var int
	 */
	const CHILD_THRESHOLD = 1048576; // 1 MB.

	/**
	 * @return string
	 */
	public function id() {
		return 'size_scanner';
	}

	/**
	 * @param array<string,mixed> $params Start params; `root` optional.
	 * @return array<string,mixed>
	 */
	public function start( array $params ) {
		$root = isset( $params['root'] ) ? (string) $params['root'] : ( defined( 'ABSPATH' ) ? ABSPATH : getcwd() );
		$root = rtrim( $root, '/\\' );
		return array(
			'root'        => $root,
			'stack'       => array( $root ),
			'done'        => false,
			'progress'    => 0,
			'message'     => 'Starting scan…',
			'total_bytes' => 0,
			'total_files' => 0,
			'unreadable'  => array(),
			'nodes'       => array(),
			'visited'     => 0,
			'estimated'   => 1,
		);
	}

	/**
	 * @param array<string,mixed> $state       State.
	 * @param float               $time_budget Seconds.
	 * @return array<string,mixed>
	 */
	public function step( array $state, $time_budget ) {
		$deadline  = microtime( true ) + (float) $time_budget;
		$stack     = isset( $state['stack'] ) && is_array( $state['stack'] ) ? $state['stack'] : array();
		$nodes     = isset( $state['nodes'] ) && is_array( $state['nodes'] ) ? $state['nodes'] : array();
		$unread    = isset( $state['unreadable'] ) && is_array( $state['unreadable'] ) ? $state['unreadable'] : array();
		$total_b   = isset( $state['total_bytes'] ) ? (int) $state['total_bytes'] : 0;
		$total_f   = isset( $state['total_files'] ) ? (int) $state['total_files'] : 0;
		$visited   = isset( $state['visited'] ) ? (int) $state['visited'] : 0;
		$estimated = max( 1, isset( $state['estimated'] ) ? (int) $state['estimated'] : 1 );
		$root      = isset( $state['root'] ) ? (string) $state['root'] : '';

		while ( ! empty( $stack ) && microtime( true ) < $deadline ) {
			$path = array_pop( $stack );
			++$visited;

			if ( is_link( $path ) ) {
				continue;
			}

			if ( is_dir( $path ) ) {
				if ( ! is_readable( $path ) ) {
					$unread[] = $path;
					continue;
				}
				$entries = @scandir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( false === $entries ) {
					$unread[] = $path;
					continue;
				}
				$dir_bytes = 0;
				$dir_files = 0;
				$children  = array();
				foreach ( $entries as $entry ) {
					if ( '.' === $entry || '..' === $entry ) {
						continue;
					}
					$child = $path . DIRECTORY_SEPARATOR . $entry;
					if ( is_link( $child ) ) {
						continue;
					}
					if ( is_dir( $child ) ) {
						$stack[] = $child;
						++$estimated;
						$children[] = $child;
					} elseif ( is_file( $child ) ) {
						$size       = @filesize( $child ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						$size       = false === $size ? 0 : (int) $size;
						$dir_bytes += $size;
						++$dir_files;
						$total_b += $size;
						++$total_f;
						$match           = DeadWeight::match( $child, false, $size );
						$nodes[ $child ] = array(
							'path'     => $child,
							'bytes'    => $size,
							'files'    => 1,
							'is_dir'   => false,
							'dead'     => $match,
							'children' => array(),
						);
					}
				}
				// Dir node is approximate until children finish; store placeholder counts of immediate files.
				$nodes[ $path ] = array(
					'path'     => $path,
					'bytes'    => $dir_bytes,
					'files'    => $dir_files,
					'is_dir'   => true,
					'dead'     => DeadWeight::match( $path, true, $dir_bytes ),
					'children' => $children,
				);
			} elseif ( is_file( $path ) ) {
				$size     = @filesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$size     = false === $size ? 0 : (int) $size;
				$total_b += $size;
				++$total_f;
				$nodes[ $path ] = array(
					'path'     => $path,
					'bytes'    => $size,
					'files'    => 1,
					'is_dir'   => false,
					'dead'     => DeadWeight::match( $path, false, $size ),
					'children' => array(),
				);
			}
		}

		$done     = empty( $stack );
		$progress = $done ? 100 : (int) min( 99, floor( ( $visited / max( $estimated, $visited ) ) * 100 ) );

		$state['stack']       = array_values( $stack );
		$state['nodes']       = $nodes;
		$state['unreadable']  = $unread;
		$state['total_bytes'] = $total_b;
		$state['total_files'] = $total_f;
		$state['visited']     = $visited;
		$state['estimated']   = $estimated;
		$state['done']        = $done;
		$state['progress']    = $progress;
		$state['message']     = $done ? 'Scan complete' : 'Scanning…';
		$state['root']        = $root;

		if ( $done ) {
			$state['tree'] = self::build_tree( $root, $nodes );
		}

		return $state;
	}

	/**
	 * Build a nested tree keeping children only for dirs above threshold.
	 *
	 * @param string                            $root  Root path.
	 * @param array<string,array<string,mixed>> $nodes Flat nodes.
	 * @return array<string,mixed>
	 */
	public static function build_tree( $root, array $nodes ) {
		if ( ! isset( $nodes[ $root ] ) ) {
			return array(
				'path'     => $root,
				'bytes'    => 0,
				'files'    => 0,
				'children' => array(),
			);
		}
		return self::nest( $root, $nodes );
	}

	/**
	 * @param string                            $path  Path.
	 * @param array<string,array<string,mixed>> $nodes Nodes.
	 * @return array<string,mixed>
	 */
	private static function nest( $path, array $nodes ) {
		$node     = $nodes[ $path ];
		$out      = array(
			'path'   => $path,
			'bytes'  => isset( $node['bytes'] ) ? (int) $node['bytes'] : 0,
			'files'  => isset( $node['files'] ) ? (int) $node['files'] : 0,
			'is_dir' => ! empty( $node['is_dir'] ),
			'dead'   => isset( $node['dead'] ) ? $node['dead'] : null,
		);
		$children = array();
		if ( ! empty( $node['is_dir'] ) && ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
			$total = $out['bytes'];
			$files = $out['files'];
			foreach ( $node['children'] as $child ) {
				if ( ! isset( $nodes[ $child ] ) ) {
					continue;
				}
				$child_tree = self::nest( $child, $nodes );
				$total     += $child_tree['bytes'];
				$files     += $child_tree['files'];
				if ( ! empty( $child_tree['is_dir'] ) && $child_tree['bytes'] >= self::CHILD_THRESHOLD ) {
					$children[] = $child_tree;
				} elseif ( empty( $child_tree['is_dir'] ) && $child_tree['bytes'] >= self::CHILD_THRESHOLD ) {
					$children[] = $child_tree;
				} elseif ( ! empty( $child_tree['dead'] ) ) {
					$children[] = $child_tree;
				}
			}
			$out['bytes'] = $total;
			$out['files'] = $files;
		}
		$out['children'] = $children;
		return $out;
	}

	/**
	 * @param array<string,mixed> $state State.
	 * @return array<string,mixed>
	 */
	public function finish( array $state ) {
		return array(
			'total_bytes' => isset( $state['total_bytes'] ) ? (int) $state['total_bytes'] : 0,
			'total_files' => isset( $state['total_files'] ) ? (int) $state['total_files'] : 0,
			'unreadable'  => isset( $state['unreadable'] ) ? $state['unreadable'] : array(),
			'tree'        => isset( $state['tree'] ) ? $state['tree'] : array(),
		);
	}
}
