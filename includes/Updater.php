<?php
/**
 * GitHub-backed WordPress plugin updates.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Plugin Update Checker against the public DPI Blocks GitHub repo.
 *
 * Release assets are preferred so WordPress always installs the purpose-built
 * dpi-blocks.zip artifact instead of a generic GitHub source archive.
 */
final class Updater {
	private const REPOSITORY_URL = 'https://github.com/capnolithyl/dpi-blocks/';
	private const PLUGIN_SLUG    = 'dpi-blocks';

	private ?object $checker = null;

	/** Register the GitHub update source when the bundled dependency is present. */
	public function register(): void {
		if ( ! class_exists( PucFactory::class ) ) {
			return;
		}

		$this->checker = PucFactory::buildUpdateChecker(
			self::REPOSITORY_URL,
			DPI_BLOCKS_FILE,
			self::PLUGIN_SLUG
		);

		$this->checker->setBranch( 'main' );

		$vcs_api = $this->checker->getVcsApi();
		if ( is_object( $vcs_api ) && method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
			$vcs_api->enableReleaseAssets( '/dpi-blocks\.zip(?:$|[?&#])/i' );
		}
	}
}
