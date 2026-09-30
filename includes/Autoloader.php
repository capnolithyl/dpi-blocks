<?php
/**
 * Minimal PSR-4-style autoloader for plugin classes.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {
	private const PREFIX = 'DPI\\Blocks\\';

	/** Register the class loader. */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load one plugin class.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	private static function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$file     = DPI_BLOCKS_DIR . 'includes/' . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
