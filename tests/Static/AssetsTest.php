<?php

declare(strict_types=1);

use DPI\Blocks\Assets;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'DPI_BLOCKS_DIR' ) ) {
	define( 'DPI_BLOCKS_DIR', ABSPATH );
}
if ( ! defined( 'DPI_BLOCKS_URL' ) ) {
	define( 'DPI_BLOCKS_URL', 'https://example.test/wp-content/plugins/dpi-blocks/' );
}
if ( ! defined( 'DPI_BLOCKS_VERSION' ) ) {
	define( 'DPI_BLOCKS_VERSION', 'test' );
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		unset( $domain );
		return $text;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook_name, mixed $value, mixed ...$args ): mixed {
		unset( $hook_name, $args );
		return $value;
	}
}
if ( ! function_exists( 'wp_get_global_styles' ) ) {
	function wp_get_global_styles(): array {
		return array();
	}
}
if ( ! function_exists( 'wp_json_file_decode' ) ) {
	function wp_json_file_decode( string $filename, array $options = array() ): mixed {
		unset( $options );
		return json_decode( (string) file_get_contents( $filename ), true, 512, JSON_THROW_ON_ERROR );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return (string) preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( mixed $value ): string {
		return (string) $value;
	}
}
if ( ! function_exists( 'wp_register_style' ) ) {
	function wp_register_style( string $handle, string|false $src, array $deps = array(), string|bool|null $ver = false, string $media = 'all' ): bool {
		$GLOBALS['dpi_assets_test_styles'][ $handle ] = compact( 'src', 'deps', 'ver', 'media' );
		return true;
	}
}
if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( string $handle, string $data ): bool {
		$GLOBALS['dpi_assets_test_inline_styles'][ $handle ][] = $data;
		return true;
	}
}
if ( ! function_exists( 'wp_register_script' ) ) {
	function wp_register_script( string $handle, string|false $src, array $deps = array(), string|bool|null $ver = false, bool|array $args = false ): bool {
		$GLOBALS['dpi_assets_test_scripts'][ $handle ] = compact( 'src', 'deps', 'ver', 'args' );
		return true;
	}
}
if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $object_name, array $l10n ): bool {
		$GLOBALS['dpi_assets_test_localizations'][ $handle ] = compact( 'object_name', 'l10n' );
		return true;
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/ThemeStyles.php';
require_once dirname( __DIR__, 2 ) . '/includes/IconRegistry.php';
require_once dirname( __DIR__, 2 ) . '/includes/Assets.php';

final class AssetsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['dpi_assets_test_styles']        = array();
		$GLOBALS['dpi_assets_test_inline_styles'] = array();
		$GLOBALS['dpi_assets_test_scripts']       = array();
		$GLOBALS['dpi_assets_test_localizations'] = array();
	}

	public function test_frontend_layer_order_is_a_registered_inline_style_dependency(): void {
		( new Assets() )->register_assets();

		$this->assertSame( false, $GLOBALS['dpi_assets_test_styles']['dpi-blocks-layer-order']['src'] );
		$this->assertSame(
			array( '@layer dpi-blocks; @layer dpi-blocks.structure, dpi-blocks.defaults;' ),
			$GLOBALS['dpi_assets_test_inline_styles']['dpi-blocks-layer-order']
		);
		$this->assertSame(
			array( 'dpi-blocks-layer-order' ),
			$GLOBALS['dpi_assets_test_styles']['dpi-blocks']['deps']
		);
	}
}
