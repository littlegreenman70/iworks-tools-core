<?php
/**
 * Iterative directory size scanner as a resumable Job.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Filesystem;

use IWorksIE\Core\Jobs\Job;
use IWorksIE\Core\Strings;

/**
 * Walks a filesystem tree without recursion or symlink following.
 *
 * State keeps directory aggregates only (never one node per file) plus capped
 * top-file and dead-weight file lists so option/AJAX payloads stay small.
 */
final class SizeScanner implements Job {

	/**
	 * Include children for directories at or above this size (bytes).
	 *
	 * @var int
	 */
	const CHILD_THRESHOLD = 1048576; // 1 MB.

	/**
	 * Max paths kept in the top-files list.
	 *
	 * @var int
	 */
	const TOP_FILES_CAP = 50;

	/**
	 * Max dead-weight file matches kept in state.
	 *
	 * @var int
	 */
	const DEAD_FILES_CAP = 500;

	/**
	 * Max unreadable paths listed (extras counted only).
	 *
	 * @var int
	 */
	const UNREADABLE_CAP = 100;

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
		$requested = isset( $params['root'] ) ? (string) $params['root'] : '';
		$root      = self::resolve_allowed_root( $requested );
		return array(
			'root'                      => $root,
			'stack'                     => array( $root ),
			'done'                      => false,
			'progress'                  => 0,
			'message'                   => Strings::get( 'scan_starting' ),
			'total_bytes'               => 0,
			'total_files'               => 0,
			'unreadable'                => array(),
			'unreadable_count'          => 0,
			'nodes'                     => array(),
			'top_files'                 => array(),
			'dead_files'                => array(),
			'dead_files_overflow'       => 0,
			'dead_files_overflow_bytes' => 0,
			'visited'                   => 0,
			'estimated'                 => 1,
		);
	}

	/**
	 * Resolve a scan root that must lie under ABSPATH (or WP_CONTENT_DIR).
	 *
	 * Outside WordPress (unit tests / CLI without ABSPATH), any real directory
	 * is accepted so fixtures can be scanned; WordPress loads always enforce
	 * the allow-list.
	 *
	 * @param string $requested Requested path.
	 * @return string Absolute allowed root.
	 */
	public static function resolve_allowed_root( $requested ) {
		$fallback  = defined( 'ABSPATH' ) ? rtrim( (string) ABSPATH, '/\\' ) : getcwd();
		$candidate = '' !== trim( (string) $requested ) ? (string) $requested : $fallback;
		$real      = realpath( $candidate );
		if ( false === $real || ! is_dir( $real ) ) {
			$fb = realpath( $fallback );
			return false !== $fb ? self::norm( $fb ) : self::norm( $fallback );
		}
		$real = self::norm( $real );

		if ( ! defined( 'ABSPATH' ) ) {
			return $real;
		}

		if ( self::path_is_under_roots( $real, self::allowed_roots() ) ) {
			return $real;
		}

		$fb = realpath( $fallback );
		return false !== $fb ? self::norm( $fb ) : self::norm( $fallback );
	}

	/**
	 * Whether $path is equal to or under one of the allowed roots.
	 *
	 * @param string            $path  Absolute normalised path.
	 * @param array<int,string> $roots Allowed roots.
	 * @return bool
	 */
	public static function path_is_under_roots( $path, array $roots ) {
		$path_real = realpath( (string) $path );
		$path_n    = self::norm( rtrim( false !== $path_real ? $path_real : (string) $path, '/\\' ) );
		foreach ( $roots as $base ) {
			$base_real = realpath( (string) $base );
			if ( false === $base_real ) {
				continue;
			}
			$base_n = self::norm( rtrim( $base_real, '/\\' ) );
			if ( $path_n === $base_n || 0 === strpos( $path_n . '/', $base_n . '/' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Allowed scan roots. Filterable via `iworks_tools_scan_roots`.
	 *
	 * @return array<int,string>
	 */
	public static function allowed_roots() {
		$roots = array();
		if ( defined( 'ABSPATH' ) ) {
			$roots[] = (string) ABSPATH;
		}
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$roots[] = (string) WP_CONTENT_DIR;
		}
		if ( empty( $roots ) ) {
			$roots[] = getcwd();
		}
		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'iworks_tools_scan_roots', $roots );
			if ( is_array( $filtered ) && ! empty( $filtered ) ) {
				$roots = $filtered;
			}
		}
		$out = array();
		foreach ( $roots as $root ) {
			$real = realpath( (string) $root );
			if ( false !== $real ) {
				$out[] = $real;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string $path Path.
	 * @return string
	 */
	private static function norm( $path ) {
		if ( function_exists( 'wp_normalize_path' ) ) {
			return wp_normalize_path( $path );
		}
		return str_replace( '\\', '/', (string) $path );
	}

	/**
	 * @param array<string,mixed> $state       State.
	 * @param float               $time_budget Seconds.
	 * @return array<string,mixed>
	 */
	public function step( array $state, $time_budget ) {
		$deadline    = microtime( true ) + (float) $time_budget;
		$stack       = isset( $state['stack'] ) && is_array( $state['stack'] ) ? $state['stack'] : array();
		$nodes       = isset( $state['nodes'] ) && is_array( $state['nodes'] ) ? $state['nodes'] : array();
		$unread      = isset( $state['unreadable'] ) && is_array( $state['unreadable'] ) ? $state['unreadable'] : array();
		$unread_n    = isset( $state['unreadable_count'] ) ? (int) $state['unreadable_count'] : count( $unread );
		$total_b     = isset( $state['total_bytes'] ) ? (int) $state['total_bytes'] : 0;
		$total_f     = isset( $state['total_files'] ) ? (int) $state['total_files'] : 0;
		$visited     = isset( $state['visited'] ) ? (int) $state['visited'] : 0;
		$estimated   = max( 1, isset( $state['estimated'] ) ? (int) $state['estimated'] : 1 );
		$root        = isset( $state['root'] ) ? (string) $state['root'] : '';
		$top_files   = isset( $state['top_files'] ) && is_array( $state['top_files'] ) ? $state['top_files'] : array();
		$dead_files  = isset( $state['dead_files'] ) && is_array( $state['dead_files'] ) ? $state['dead_files'] : array();
		$dead_over   = isset( $state['dead_files_overflow'] ) ? (int) $state['dead_files_overflow'] : 0;
		$dead_obytes = isset( $state['dead_files_overflow_bytes'] ) ? (int) $state['dead_files_overflow_bytes'] : 0;

		while ( ! empty( $stack ) && microtime( true ) < $deadline ) {
			$path = array_pop( $stack );
			++$visited;

			if ( is_link( $path ) ) {
				continue;
			}

			if ( is_dir( $path ) ) {
				if ( ! is_readable( $path ) ) {
					list( $unread, $unread_n ) = self::push_unreadable( $unread, $unread_n, $path );
					continue;
				}
				$entries = @scandir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( false === $entries ) {
					list( $unread, $unread_n ) = self::push_unreadable( $unread, $unread_n, $path );
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
						$top_files = self::push_top_file( $top_files, $child, $size );
						$match     = DeadWeight::match( $child, false, $size );
						if ( null !== $match ) {
							list( $dead_files, $dead_over, $dead_obytes ) = self::push_dead_file(
								$dead_files,
								$dead_over,
								$dead_obytes,
								$child,
								$size,
								$match
							);
						}
					}
				}
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
				$top_files = self::push_top_file( $top_files, $path, $size );
				$match     = DeadWeight::match( $path, false, $size );
				if ( null !== $match ) {
					list( $dead_files, $dead_over, $dead_obytes ) = self::push_dead_file(
						$dead_files,
						$dead_over,
						$dead_obytes,
						$path,
						$size,
						$match
					);
				}
			}
		}

		$done     = empty( $stack );
		$progress = $done ? 100 : (int) min( 99, floor( ( $visited / max( $estimated, $visited ) ) * 100 ) );

		$state['stack']                     = array_values( $stack );
		$state['nodes']                     = $nodes;
		$state['unreadable']                = $unread;
		$state['unreadable_count']          = $unread_n;
		$state['total_bytes']               = $total_b;
		$state['total_files']               = $total_f;
		$state['visited']                   = $visited;
		$state['estimated']                 = $estimated;
		$state['done']                      = $done;
		$state['progress']                  = $progress;
		$state['message']                   = $done ? Strings::get( 'scan_complete' ) : Strings::get( 'scanning' );
		$state['root']                      = $root;
		$state['top_files']                 = $top_files;
		$state['dead_files']                = $dead_files;
		$state['dead_files_overflow']       = $dead_over;
		$state['dead_files_overflow_bytes'] = $dead_obytes;

		if ( $done ) {
			$state['tree'] = self::build_tree( $root, $nodes );
			// Drop working sets from finished state so the option stays small.
			unset( $state['nodes'], $state['stack'] );
			$state['nodes'] = array();
			$state['stack'] = array();
		}

		return $state;
	}

	/**
	 * @param array<int,string> $items Listed paths.
	 * @param int               $count Total count including overflow.
	 * @param string            $path  Path.
	 * @return array{0:array<int,string>,1:int}
	 */
	private static function push_unreadable( array $items, $count, $path ) {
		++$count;
		if ( count( $items ) < self::UNREADABLE_CAP ) {
			$items[] = $path;
		}
		return array( $items, $count );
	}

	/**
	 * Maintain a bounded largest-files list.
	 *
	 * @param array<int,array{path:string,bytes:int}> $items Existing.
	 * @param string                                  $path Path.
	 * @param int                                     $bytes Size.
	 * @return array<int,array{path:string,bytes:int}>
	 */
	private static function push_top_file( array $items, $path, $bytes ) {
		$bytes   = (int) $bytes;
		$items[] = array(
			'path'  => (string) $path,
			'bytes' => $bytes,
		);
		usort(
			$items,
			static function ( $a, $b ) {
				return $b['bytes'] <=> $a['bytes'];
			}
		);
		if ( count( $items ) > self::TOP_FILES_CAP ) {
			$items = array_slice( $items, 0, self::TOP_FILES_CAP );
		}
		return $items;
	}

	/**
	 * @param array<int,array<string,mixed>> $items      Listed dead files.
	 * @param int                            $over       Overflow count.
	 * @param int                            $obytes     Overflow bytes.
	 * @param string                         $path       Path.
	 * @param int                            $bytes      Size.
	 * @param array{id:string,reason:string} $dead_match Match.
	 * @return array{0:array<int,array<string,mixed>>,1:int,2:int}
	 */
	private static function push_dead_file( array $items, $over, $obytes, $path, $bytes, array $dead_match ) {
		if ( count( $items ) < self::DEAD_FILES_CAP ) {
			$items[] = array(
				'path'   => (string) $path,
				'bytes'  => (int) $bytes,
				'dead'   => $dead_match,
				'is_dir' => false,
			);
			return array( $items, $over, $obytes );
		}
		return array( $items, $over + 1, $obytes + (int) $bytes );
	}

	/**
	 * Build a nested tree keeping children only for dirs above threshold.
	 *
	 * @param string                            $root  Root path.
	 * @param array<string,array<string,mixed>> $nodes Directory nodes only.
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
			'is_dir' => true,
			'dead'   => isset( $node['dead'] ) ? $node['dead'] : null,
		);
		$children = array();
		if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
			$total = $out['bytes'];
			$files = $out['files'];
			foreach ( $node['children'] as $child ) {
				if ( ! isset( $nodes[ $child ] ) ) {
					continue;
				}
				$child_tree = self::nest( $child, $nodes );
				$total     += $child_tree['bytes'];
				$files     += $child_tree['files'];
				if ( $child_tree['bytes'] >= self::CHILD_THRESHOLD || ! empty( $child_tree['dead'] ) ) {
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
	 * Collect dead-weight directory matches from the finished tree.
	 *
	 * @param array<string,mixed> $tree Tree.
	 * @return array<int,array<string,mixed>>
	 */
	public static function dead_dirs_from_tree( array $tree ) {
		$out = array();
		self::walk_dead_dirs( $tree, $out );
		return $out;
	}

	/**
	 * @param array<string,mixed>            $node Node.
	 * @param array<int,array<string,mixed>> $out  Accumulator.
	 * @return void
	 */
	private static function walk_dead_dirs( array $node, array &$out ) {
		if ( ! empty( $node['dead'] ) && is_array( $node['dead'] ) ) {
			$out[] = array(
				'path'   => isset( $node['path'] ) ? (string) $node['path'] : '',
				'bytes'  => isset( $node['bytes'] ) ? (int) $node['bytes'] : 0,
				'dead'   => $node['dead'],
				'is_dir' => true,
			);
		}
		if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
			foreach ( $node['children'] as $child ) {
				if ( is_array( $child ) ) {
					self::walk_dead_dirs( $child, $out );
				}
			}
		}
	}

	/**
	 * @param array<string,mixed> $state State.
	 * @return array<string,mixed>
	 */
	public function finish( array $state ) {
		$tree       = isset( $state['tree'] ) && is_array( $state['tree'] ) ? $state['tree'] : array();
		$dead_files = isset( $state['dead_files'] ) && is_array( $state['dead_files'] ) ? $state['dead_files'] : array();
		$dead_dirs  = self::dead_dirs_from_tree( $tree );
		$dead       = array_merge( $dead_dirs, $dead_files );

		return array(
			'total_bytes'               => isset( $state['total_bytes'] ) ? (int) $state['total_bytes'] : 0,
			'total_files'               => isset( $state['total_files'] ) ? (int) $state['total_files'] : 0,
			'unreadable'                => isset( $state['unreadable'] ) ? $state['unreadable'] : array(),
			'unreadable_count'          => isset( $state['unreadable_count'] ) ? (int) $state['unreadable_count'] : 0,
			'top_files'                 => isset( $state['top_files'] ) ? $state['top_files'] : array(),
			'dead_weight'               => $dead,
			'dead_files_overflow'       => isset( $state['dead_files_overflow'] ) ? (int) $state['dead_files_overflow'] : 0,
			'dead_files_overflow_bytes' => isset( $state['dead_files_overflow_bytes'] ) ? (int) $state['dead_files_overflow_bytes'] : 0,
			'tree'                      => $tree,
		);
	}
}
