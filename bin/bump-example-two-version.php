<?php
/**
 * After Strauss, rewrite Core::VERSION in example-two's prefixed copy to the
 * next minor version above the real Core::VERSION so menu election still proves
 * "higher version wins".
 *
 * @package IWorksIE\Core
 */

$core_file = dirname( __DIR__ ) . '/src/Core.php';
$contents  = is_readable( $core_file ) ? file_get_contents( $core_file ) : false;
if ( false === $contents || ! preg_match( "/VERSION\s*=\s*'([^']+)'/", $contents, $m ) ) {
	fwrite( STDERR, "Could not read Core::VERSION\n" );
	exit( 1 );
}

$core_version = $m[1];
$parts        = array_map( 'intval', explode( '.', $core_version ) );
while ( count( $parts ) < 3 ) {
	$parts[] = 0;
}
// Next minor: 1.0.1 → 1.1.0
$bump_version = $parts[0] . '.' . ( $parts[1] + 1 ) . '.0';

$root = getcwd() . '/vendor-prefixed';
if ( ! is_dir( $root ) ) {
	fwrite( STDERR, "vendor-prefixed missing\n" );
	exit( 1 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

$replacements = 0;
$from_single  = "VERSION = '{$core_version}'";
$from_double  = 'VERSION = "' . $core_version . '"';
$to_single    = "VERSION = '{$bump_version}'";
$to_double    = 'VERSION = "' . $bump_version . '"';

foreach ( $iterator as $file ) {
	/** @var SplFileInfo $file */
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$path     = $file->getPathname();
	$php_file = file_get_contents( $path );
	if ( false === $php_file ) {
		continue;
	}
	if ( false === strpos( $php_file, $from_single ) && false === strpos( $php_file, $from_double ) ) {
		continue;
	}
	$updated = str_replace(
		array( $from_single, $from_double ),
		array( $to_single, $to_double ),
		$php_file
	);
	if ( $updated !== $php_file ) {
		file_put_contents( $path, $updated );
		++$replacements;
		echo "Bumped VERSION {$core_version} → {$bump_version} in {$path}\n";
	}
}

if ( 0 === $replacements ) {
	fwrite( STDERR, "WARNING: no VERSION constant bumped\n" );
	exit( 1 );
}
