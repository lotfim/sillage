<?php
/**
 * Build sillage-1.0.0.zip (no-dev Composer, .distignore exclusions).
 *
 * Usage (in Docker):
 *   php tools/make-dist-zip.php
 *
 * @package Sillage
 */

$plugin_root = dirname( __DIR__ );
$distignore  = $plugin_root . '/.distignore';
$stage       = sys_get_temp_dir() . '/sillage-build';
$staged      = $stage . '/sillage';
$zip_path    = $plugin_root . '/sillage-1.0.0.zip';

$exclude = array( '.git', '.wordpress-org', 'sillage-1.0.0.zip', 'vendor' );
if ( is_readable( $distignore ) ) {
	foreach ( file( $distignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || str_starts_with( $line, '#' ) ) {
			continue;
		}
		$exclude[] = $line;
	}
}

$exclude = array_values( array_unique( $exclude ) );

passthru( 'rm -rf ' . escapeshellarg( $stage ), $rm );
if ( 0 !== $rm ) {
	fwrite( STDERR, "Failed to clear stage.\n" );
	exit( 1 );
}

mkdir( $staged, 0777, true );

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $plugin_root, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ( $iterator as $file ) {
	$rel = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $plugin_root ) + 1 ) );
	$skip = false;
	foreach ( $exclude as $pattern ) {
		$pattern = trim( $pattern, '/' );
		if ( $rel === $pattern || str_starts_with( $rel, $pattern . '/' ) ) {
			$skip = true;
			break;
		}
	}
	if ( $skip ) {
		continue;
	}
	$dest = $staged . '/' . $rel;
	if ( $file->isDir() ) {
		if ( ! is_dir( $dest ) ) {
			mkdir( $dest, 0777, true );
		}
		continue;
	}
	$dir = dirname( $dest );
	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0777, true );
	}
	copy( $file->getPathname(), $dest );
}

$composer = 'composer install --no-dev --optimize-autoloader --no-interaction --working-dir=' . escapeshellarg( $staged );
passthru( $composer, $code );
if ( 0 !== $code ) {
	fwrite( STDERR, "Composer failed.\n" );
	exit( $code );
}

if ( file_exists( $zip_path ) ) {
	unlink( $zip_path );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Cannot create zip.\n" );
	exit( 1 );
}

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $staged, FilesystemIterator::SKIP_DOTS )
);
foreach ( $files as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$local = 'sillage/' . str_replace( '\\', '/', substr( $file->getPathname(), strlen( $staged ) + 1 ) );
	$zip->addFile( $file->getPathname(), $local );
}
$zip->close();

echo $zip_path . ' (' . number_format( filesize( $zip_path ) / 1048576, 2 ) . " MB)\n";
