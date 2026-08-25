<?php
/**
 * Runtime and activation dependency checks.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dependencies {
	public const MINIMUM_ACF = '6.6.0';
	public const MINIMUM_WP  = '6.9';

	/** Determine whether the supported ACF Pro runtime is available. */
	public static function has_acf_pro(): bool {
		if ( ! defined( 'ACF_VERSION' ) || version_compare( (string) ACF_VERSION, self::MINIMUM_ACF, '<' ) ) {
			return false;
		}

		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return false;
		}

		if ( defined( 'ACF_PRO' ) ) {
			return (bool) ACF_PRO;
		}

		if ( function_exists( 'acf_get_setting' ) && acf_get_setting( 'pro' ) ) {
			return true;
		}

		return class_exists( 'acf_pro' );
	}

	/** Validate requirements during activation. */
	public static function activate( bool $network_wide = false ): void {
		global $wp_version;

		$errors = array();

		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$errors[] = __( 'PHP 8.0 or newer is required.', 'dpi-blocks' );
		}

		if ( version_compare( (string) $wp_version, self::MINIMUM_WP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: minimum WordPress version. */
				__( 'WordPress %s or newer is required.', 'dpi-blocks' ),
				self::MINIMUM_WP
			);
		}

		if ( ! self::has_acf_pro() ) {
			$errors[] = sprintf(
				/* translators: %s: minimum ACF Pro version. */
				__( 'Advanced Custom Fields Pro %s or newer must be installed and active.', 'dpi-blocks' ),
				self::MINIMUM_ACF
			);
		}

		if ( $errors ) {
			if ( function_exists( 'deactivate_plugins' ) ) {
				deactivate_plugins( plugin_basename( DPI_BLOCKS_FILE ), true, $network_wide );
			}

			wp_die(
				wp_kses_post( implode( '<br>', array_map( 'esc_html', $errors ) ) ),
				esc_html__( 'DPI Blocks could not be activated', 'dpi-blocks' ),
				array( 'back_link' => true )
			);
		}

		if ( class_exists( Settings::class ) ) {
			Settings::ensure_defaults();
		}
		if ( class_exists( ContentTypes::class ) ) {
			( new ContentTypes() )->register_types();
		}

		flush_rewrite_rules();
	}

	/** Flush routes once when the plugin is deactivated. */
	public static function deactivate(): void {
		if ( class_exists( ContentTypes::class ) ) {
			if ( ContentTypes::owns( 'staff' ) ) {
				unregister_post_type( 'staff' );
				unregister_taxonomy( 'staff_group' );
			}
			if ( ContentTypes::owns( 'ministry' ) ) {
				unregister_post_type( 'ministry' );
				unregister_taxonomy( 'ministry_group' );
			}
		}

		flush_rewrite_rules();
	}

	/** Render a runtime notice when ACF Pro becomes unavailable. */
	public static function render_admin_notice(): void {
		if ( self::has_acf_pro() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: minimum ACF Pro version. */
					__( 'DPI Blocks requires Advanced Custom Fields Pro %s or newer. Its blocks and field groups are paused; enabled content types remain registered so their data stays accessible.', 'dpi-blocks' ),
					self::MINIMUM_ACF
				)
			)
		);
	}
}
