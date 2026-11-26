<?php
/**
 * After Strauss, rewrite Core::VERSION in example-two's prefixed copy to 1.0.1.
 *
 * @package IWorksIE\Core
 */

$root = getcwd() . '/vendor-prefixed';
if ( ! is_dir( $root ) ) {
	fwrite( STDERR, "vendor-prefixed missing\n" );
	exit( 1 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

$replacements = 0;
foreach ( $iterator as $file ) {
	/** @var SplFileInfo $file */
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$path    = $file->getPathname();
	$contents = file_get_contents( $path );
	if ( false === $contents ) {
		continue;
	}
	if ( false === strpos( $contents, "VERSION = '1.0.0'" ) && false === strpos( $contents, 'VERSION = "1.0.0"' ) ) {
		continue;
	}
	$updated = str_replace(
		array( "VERSION = '1.0.0'", 'VERSION = "1.0.0"' ),
		array( "VERSION = '1.0.1'", 'VERSION = "1.0.1"' ),
		$contents
	);
	if ( $updated !== $contents ) {
		file_put_contents( $path, $updated );
		++$replacements;
		echo "Bumped VERSION in {$path}\n";
	}
}

if ( 0 === $replacements ) {
	fwrite( STDERR, "WARNING: no VERSION constant bumped\n" );
	exit( 1 );
}
