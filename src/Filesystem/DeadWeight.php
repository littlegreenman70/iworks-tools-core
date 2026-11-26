<?php
/**
 * Heuristics for leftover / heavy paths in a WordPress install.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Filesystem;

use IWorksIE\Core\Strings;

/**
 * Read-only matcher for common dead-weight paths. Never deletes.
 */
final class DeadWeight {

	/**
	 * Default rules. Filterable via `iworks_tools_dead_weight_rules`.
	 *
	 * Each rule:
	 * - id (string)
	 * - type: dir_name|path_suffix|extension|basename
	 * - pattern (string) compared case-insensitively where noted
	 * - min_bytes (int, optional)
	 * - reason_key (string) Strings catalogue key
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function rules() {
		$rules = array(
			array(
				'id'         => 'backup_updraft',
				'type'       => 'dir_name',
				'pattern'    => 'updraft',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'backup_ai1wm',
				'type'       => 'dir_name',
				'pattern'    => 'ai1wm-backups',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'backup_buddy',
				'type'       => 'dir_name',
				'pattern'    => 'backupbuddy_backups',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'backup_prefix',
				'type'       => 'dir_name_prefix',
				'pattern'    => 'backup',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'backup_wpvivid',
				'type'       => 'dir_name',
				'pattern'    => 'wpvividbackups',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'backup_dup_lite',
				'type'       => 'dir_name',
				'pattern'    => 'backups-dup-lite',
				'reason_key' => 'dead_weight_backup',
			),
			array(
				'id'         => 'cache_wp',
				'type'       => 'path_suffix',
				'pattern'    => '/wp-content/cache',
				'reason_key' => 'dead_weight_cache',
			),
			array(
				'id'         => 'cache_et',
				'type'       => 'dir_name',
				'pattern'    => 'et-cache',
				'reason_key' => 'dead_weight_cache',
			),
			array(
				'id'         => 'vcs_git',
				'type'       => 'dir_name',
				'pattern'    => '.git',
				'reason_key' => 'dead_weight_vcs',
			),
			array(
				'id'         => 'vcs_svn',
				'type'       => 'dir_name',
				'pattern'    => '.svn',
				'reason_key' => 'dead_weight_vcs',
			),
			array(
				'id'         => 'node_modules',
				'type'       => 'dir_name',
				'pattern'    => 'node_modules',
				'reason_key' => 'dead_weight_node',
			),
			array(
				'id'         => 'error_log',
				'type'       => 'basename',
				'pattern'    => 'error_log',
				'reason_key' => 'dead_weight_log',
			),
			array(
				'id'         => 'log_ext',
				'type'       => 'extension',
				'pattern'    => 'log',
				'reason_key' => 'dead_weight_log',
			),
			array(
				'id'         => 'archive',
				'type'       => 'extension',
				'pattern'    => array( 'zip', 'tar', 'gz', 'tgz', 'rar', '7z' ),
				'reason_key' => 'dead_weight_archive',
			),
			array(
				'id'         => 'sql_large',
				'type'       => 'extension',
				'pattern'    => 'sql',
				'min_bytes'  => 10 * 1024 * 1024,
				'reason_key' => 'dead_weight_sql',
			),
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'iworks_tools_dead_weight_rules', $rules );
			if ( is_array( $filtered ) ) {
				$rules = $filtered;
			}
		}

		return $rules;
	}

	/**
	 * Match a path against dead-weight rules.
	 *
	 * @param string $path   Absolute or relative path.
	 * @param bool   $is_dir Whether the path is a directory.
	 * @param int    $bytes  Size in bytes (files) or subtree size (dirs).
	 * @return array{id:string,reason:string}|null
	 */
	public static function match( $path, $is_dir, $bytes ) {
		$path       = str_replace( '\\', '/', (string) $path );
		$basename   = basename( $path );
		$lower      = strtolower( $basename );
		$ext        = strtolower( pathinfo( $basename, PATHINFO_EXTENSION ) );
		$path_lower = strtolower( $path );
		$bytes      = (int) $bytes;

		foreach ( self::rules() as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['id'] ) || empty( $rule['type'] ) ) {
				continue;
			}
			$min = isset( $rule['min_bytes'] ) ? (int) $rule['min_bytes'] : 0;
			if ( $min > 0 && $bytes < $min ) {
				continue;
			}

			$matched = false;
			$type    = (string) $rule['type'];
			$pattern = isset( $rule['pattern'] ) ? $rule['pattern'] : '';

			switch ( $type ) {
				case 'dir_name':
					$matched = $is_dir && 0 === strcasecmp( $basename, (string) $pattern );
					break;
				case 'dir_name_prefix':
					// Basename must start with the pattern; never files; never plugin/theme code trees.
					$matched = $is_dir
						&& 0 === stripos( $basename, (string) $pattern )
						&& ! self::is_under_plugins_or_themes( $path_lower );
					break;
				case 'path_suffix':
					$suffix  = strtolower( str_replace( '\\', '/', (string) $pattern ) );
					$matched = (bool) preg_match( '#' . preg_quote( $suffix, '#' ) . '$#', $path_lower );
					break;
				case 'basename':
					$matched = ! $is_dir && 0 === strcasecmp( $basename, (string) $pattern );
					break;
				case 'extension':
					if ( $is_dir ) {
						break;
					}
					$patterns = is_array( $pattern ) ? $pattern : array( $pattern );
					foreach ( $patterns as $p ) {
						if ( strtolower( (string) $p ) === $ext ) {
							$matched = true;
							break;
						}
					}
					break;
			}

			if ( $matched ) {
				$reason_key = isset( $rule['reason_key'] ) ? (string) $rule['reason_key'] : '';
				$reason     = '' !== $reason_key ? Strings::get( $reason_key ) : '';
				if ( isset( $rule['reason'] ) && is_string( $rule['reason'] ) && '' !== $rule['reason'] ) {
					$reason = $rule['reason'];
				}
				return array(
					'id'     => (string) $rule['id'],
					'reason' => $reason,
				);
			}
		}

		return null;
	}

	/**
	 * Whether a path sits under wp-content/plugins or wp-content/themes.
	 *
	 * @param string $path_lower Lowercased normalised path.
	 * @return bool
	 */
	private static function is_under_plugins_or_themes( $path_lower ) {
		$path_lower = str_replace( '\\', '/', (string) $path_lower );
		return (bool) preg_match( '#/wp-content/(plugins|themes)(/|$)#', $path_lower );
	}
}
