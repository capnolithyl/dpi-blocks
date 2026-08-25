<?php
/**
 * Plugin Name:       DPI Blocks
 * Plugin URI:        https://www.diocesan.com/
 * Description:       Portable ACF blocks, header utilities, and directory content types for custom WordPress sites.
 * Version:           1.2.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Requires Plugins:  advanced-custom-fields-pro
 * Author:            Diocesan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dpi-blocks
 * Update URI:        false
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DPI_BLOCKS_VERSION', '1.2.0' );
define( 'DPI_BLOCKS_FILE', __FILE__ );
define( 'DPI_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPI_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once DPI_BLOCKS_DIR . 'includes/Autoloader.php';

\DPI\Blocks\Autoloader::register();

require_once DPI_BLOCKS_DIR . 'includes/functions.php';

register_activation_hook( __FILE__, array( \DPI\Blocks\Dependencies::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \DPI\Blocks\Dependencies::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\DPI\Blocks\Plugin::instance()->register();
	},
	20
);
