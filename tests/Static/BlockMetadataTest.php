<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BlockMetadataTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname( __DIR__, 2 );
	}

	public function test_every_block_has_valid_canonical_metadata_and_renderer(): void {
		$metadata_files = glob( $this->root . '/blocks/*/block.json' ) ?: array();
		$this->assertNotEmpty( $metadata_files, 'No block metadata files were discovered.' );

		$names = array();

		foreach ( $metadata_files as $file ) {
			$slug = basename( dirname( $file ) );
			$data = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );

			$this->assertSame( 'dpi/' . $slug, $data['name'] ?? null, $file . ' must use its directory slug as the canonical block name.' );
			$this->assertSame( 3, $data['apiVersion'] ?? null, $file . ' must use Block API v3.' );
			$this->assertSame( 'dpi-blocks', $data['textdomain'] ?? null, $file . ' must use the plugin textdomain.' );
			$this->assertSame( 'dpi_blocks_render_acf_block', $data['acf']['renderCallback'] ?? null, $file . ' must use the shared renderer callback.' );
			$this->assertSame( 3, $data['acf']['blockVersion'] ?? null, $file . ' must use ACF blockVersion 3.' );
			$this->assertFileExists( dirname( $file ) . '/render.php', $slug . ' is missing render.php.' );
			$this->assertNotContains( $data['name'], $names, 'Duplicate canonical block name: ' . $data['name'] );
			$names[] = $data['name'];

			foreach ( array( 'style', 'script', 'viewScript', 'viewStyle' ) as $asset_key ) {
				$asset = $data[ $asset_key ] ?? null;
				if ( is_string( $asset ) && str_starts_with( $asset, 'file:' ) ) {
					$this->assertFileExists(
						dirname( $file ) . '/' . substr( $asset, 5 ),
						sprintf( '%s references missing %s asset %s.', $slug, $asset_key, $asset )
					);
				}
			}
		}
	}

	public function test_renderers_keep_direct_access_guard(): void {
		foreach ( glob( $this->root . '/blocks/*/render.php' ) ?: array() as $renderer ) {
			$source = (string) file_get_contents( $renderer );
			$this->assertStringContainsString(
				"defined( 'ABSPATH' )",
				$source,
				basename( dirname( $renderer ) ) . '/render.php must guard direct access.'
			);
		}
	}
}
