<?php
/**
 * Metadata-backed WordPress plugin updates.
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
 * Register Plugin Update Checker against public static metadata.
 *
 * The metadata file is served from raw.githubusercontent.com, which avoids
 * GitHub API rate limits while release ZIPs continue to come from GitHub
 * Releases.
 */
final class Updater {
	private const METADATA_URL = 'https://raw.githubusercontent.com/capnolithyl/dpi-blocks/main/update.json';
	private const PLUGIN_SLUG  = 'dpi-blocks';

	private ?object $checker = null;

	/** Register the update source when the bundled dependency is present. */
	public function register(): void {
		if ( ! class_exists( PucFactory::class ) ) {
			return;
		}

		$this->checker = PucFactory::buildUpdateChecker(
			self::METADATA_URL,
			DPI_BLOCKS_FILE,
			self::PLUGIN_SLUG
		);
	}
}
