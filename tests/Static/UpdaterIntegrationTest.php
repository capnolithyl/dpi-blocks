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

	public function test_updater_prefers_the_release_zip(): void {
		$source = (string) file_get_contents( $this->root . '/includes/Updater.php' );

		$this->assertStringContainsString( 'https://github.com/capnolithyl/dpi-blocks/', $source );
		$this->assertStringContainsString( "setBranch( 'main' )", $source );
		$this->assertStringContainsString( 'enableReleaseAssets', $source );
		$this->assertStringContainsString( 'dpi-blocks\\.zip', $source );
	}

	public function test_release_workflow_builds_the_expected_plugin_archive(): void {
		$source = (string) file_get_contents( $this->root . '/.github/workflows/release.yml' );

		$this->assertStringContainsString( 'dpi-blocks.zip', $source );
		$this->assertStringContainsString( 'vendor/autoload.php', $source );
		$this->assertStringContainsString( 'gh release create', $source );
	}
}
