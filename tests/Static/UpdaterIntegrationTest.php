<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UpdaterIntegrationTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname( __DIR__, 2 );
	}

	public function test_plugin_update_checker_is_a_production_dependency(): void {
		$composer = json_decode(
			(string) file_get_contents( $this->root . '/composer.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$this->assertSame(
			'~5.7.0',
			$composer['require']['yahnis-elsts/plugin-update-checker'] ?? null
		);
	}

	public function test_main_plugin_bootstraps_composer_and_declares_external_update_uri(): void {
		$source = (string) file_get_contents( $this->root . '/dpi-blocks.php' );

		$this->assertStringContainsString( 'vendor/autoload.php', $source );
		$this->assertStringContainsString(
			'Update URI:        https://github.com/capnolithyl/dpi-blocks',
			$source
		);
	}

	public function test_updater_uses_static_public_metadata(): void {
		$source = (string) file_get_contents( $this->root . '/includes/Updater.php' );

		$this->assertStringContainsString(
			'https://raw.githubusercontent.com/capnolithyl/dpi-blocks/main/update.json',
			$source
		);
		$this->assertStringNotContainsString( 'getVcsApi', $source );
		$this->assertStringNotContainsString( 'setBranch', $source );
	}

	public function test_static_metadata_matches_plugin_version_and_release_asset(): void {
		$metadata = json_decode(
			(string) file_get_contents( $this->root . '/update.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$plugin_source = (string) file_get_contents( $this->root . '/dpi-blocks.php' );
		preg_match( '/^ \\* Version:\\s+([^\\s]+)$/m', $plugin_source, $matches );
		$version = $matches[1] ?? '';

		$this->assertNotSame( '', $version );
		$this->assertSame( $version, $metadata['version'] ?? null );
		$this->assertSame(
			'https://github.com/capnolithyl/dpi-blocks/releases/download/v' . $version . '/dpi-blocks.zip',
			$metadata['download_url'] ?? null
		);
	}

	public function test_release_workflow_builds_the_expected_plugin_archive(): void {
		$source = (string) file_get_contents( $this->root . '/.github/workflows/release.yml' );

		$this->assertStringContainsString( 'dpi-blocks.zip', $source );
		$this->assertStringContainsString( 'vendor/autoload.php', $source );
		$this->assertStringContainsString( 'gh release create', $source );
		$this->assertStringContainsString( 'update.json', $source );
	}
}
