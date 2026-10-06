<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VersionConsistencyTest extends TestCase {
	public function test_plugin_versions_stay_in_sync(): void {
		$root       = dirname( __DIR__, 2 );
		$plugin_php = (string) file_get_contents( $root . '/dpi-blocks.php' );
		$readme     = (string) file_get_contents( $root . '/readme.txt' );

		$this->assertMatchesRegularExpression( '/^ \* Version:\s+([^\s]+)$/m', $plugin_php );
		preg_match( '/^ \* Version:\s+([^\s]+)$/m', $plugin_php, $header_match );

		$this->assertMatchesRegularExpression( "/define\( 'DPI_BLOCKS_VERSION', '([^']+)' \);/", $plugin_php );
		preg_match( "/define\( 'DPI_BLOCKS_VERSION', '([^']+)' \);/", $plugin_php, $constant_match );

		$this->assertMatchesRegularExpression( '/^Stable tag:\s+([^\s]+)$/m', $readme );
		preg_match( '/^Stable tag:\s+([^\s]+)$/m', $readme, $readme_match );

		$this->assertSame( $header_match[1], $constant_match[1], 'Plugin header and DPI_BLOCKS_VERSION differ.' );
		$this->assertSame( $header_match[1], $readme_match[1], 'Plugin header and readme Stable tag differ.' );
	}
}
