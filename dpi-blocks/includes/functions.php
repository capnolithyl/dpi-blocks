<?php
/**
 * Stable public integration functions and renderer helpers.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

use DPI\Blocks\BlockTemplates;
use DPI\Blocks\IconRegistry;
use DPI\Blocks\Plugin;
use DPI\Blocks\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'dpi_blocks_render_top_bar' ) ) {
	/**
	 * Render the configured DPI top bar.
	 *
	 * @param array<string,mixed> $args Optional renderer arguments.
	 */
	function dpi_blocks_render_top_bar( array $args = array() ): void {
		Plugin::instance()->header()->render_top_bar( $args );
	}
}

if ( ! function_exists( 'dpi_blocks_render_acf_block' ) ) {
	/**
	 * Render a canonical DPI ACF block through the theme override resolver.
	 *
	 * Parameter types intentionally remain broad to match ACF's callback API.
	 *
	 * @param mixed $block      ACF block settings and attributes.
	 * @param mixed $content    Inner block content.
	 * @param mixed $is_preview Whether this is an editor preview.
	 * @param mixed $post_id    Current post ID.
	 * @param mixed $wp_block   WordPress block instance.
	 * @param mixed $context    Provided block context.
	 */
	function dpi_blocks_render_acf_block( $block, $content = '', $is_preview = false, $post_id = 0, $wp_block = null, $context = null ): void {
		BlockTemplates::render( $block, $content, $is_preview, $post_id, $wp_block, $context );
	}
}

if ( ! function_exists( 'dpi_blocks_render_search_trigger' ) ) {
	/**
	 * Render a configured search trigger and its search surface.
	 *
	 * @param array<string,mixed> $args Optional renderer arguments.
	 */
	function dpi_blocks_render_search_trigger( array $args = array() ): void {
		Plugin::instance()->header()->render_search_trigger( $args );
	}
}

if ( ! function_exists( 'dpi_blocks_render_icon' ) ) {
	/**
	 * Return safe, plugin-owned Font Awesome SVG markup.
	 *
	 * @param mixed               $icon  Saved icon identifier.
	 * @param array<string,mixed> $attrs Optional SVG attributes.
	 */
	function dpi_blocks_render_icon( mixed $icon, array $attrs = array() ): string {
		return IconRegistry::render( is_string( $icon ) ? $icon : '', $attrs );
	}
}

if ( ! function_exists( 'dpi_blocks_render_link' ) ) {
	/**
	 * Render an ACF link value as an escaped anchor.
	 *
	 * @param mixed  $link  ACF link array or URL.
	 * @param string $class_name CSS class list.
	 */
	function dpi_blocks_render_link( mixed $link, string $class_name = '' ): string {
		if ( is_string( $link ) ) {
			$link = array(
				'url'    => $link,
				'title'  => $link,
				'target' => '',
			);
		}

		if ( ! is_array( $link ) || empty( $link['url'] ) ) {
			return '';
		}

		$url = esc_url( (string) $link['url'] );
		if ( '' === $url ) {
			return '';
		}

		$title  = isset( $link['title'] ) ? (string) $link['title'] : $url;
		$target = isset( $link['target'] ) && '_blank' === $link['target'] ? '_blank' : '';
		$rel    = '_blank' === $target ? ' rel="noopener noreferrer"' : '';

		return sprintf(
			'<a class="%1$s" href="%2$s"%3$s%4$s>%5$s</a>',
			esc_attr( $class_name ),
			$url,
			$target ? ' target="_blank"' : '',
			$rel,
			esc_html( $title )
		);
	}
}

if ( ! function_exists( 'dpi_blocks_get_social_profiles' ) ) {
	/** Return normalized global social profile rows. */
	function dpi_blocks_get_social_profiles(): array {
		$profiles = Settings::get( 'social_profiles', array() );
		$labels   = array(
			'facebook'  => array( __( 'Facebook', 'dpi-blocks' ), 'brands:facebook' ),
			'instagram' => array( __( 'Instagram', 'dpi-blocks' ), 'brands:instagram' ),
			'youtube'   => array( __( 'YouTube', 'dpi-blocks' ), 'brands:youtube' ),
			'x-twitter' => array( __( 'X', 'dpi-blocks' ), 'brands:x-twitter' ),
			'linkedin'  => array( __( 'LinkedIn', 'dpi-blocks' ), 'brands:linkedin' ),
		);
		$rows     = array();

		foreach ( $labels as $network => $details ) {
			$url = is_array( $profiles ) && isset( $profiles[ $network ] ) ? esc_url_raw( (string) $profiles[ $network ] ) : '';
			if ( $url ) {
				$rows[] = array(
					'icon'  => $details[1],
					'label' => $details[0],
					'url'   => $url,
				);
			}
		}

		return $rows;
	}
}

if ( ! function_exists( 'dpi_blocks_get_social_feed_shortcode' ) ) {
	/** Return the administrator-configured global social feed shortcode. */
	function dpi_blocks_get_social_feed_shortcode(): string {
		return trim( (string) Settings::get( 'social_feed_shortcode', '' ) );
	}
}

if ( ! function_exists( 'dpi_blocks_get_social_feed_adapter' ) ) {
	/**
	 * Resolve a shortcode-feed adapter used for optional carousel enhancement.
	 *
	 * Adapter callbacks receive the shortcode and return either null or an array
	 * with a stable `name` and a CSS `track_selector`. Unknown shortcodes return
	 * null and are rendered without assumptions about their markup.
	 */
	function dpi_blocks_get_social_feed_adapter( string $shortcode ): ?array {
		$adapters = array(
			'smash-balloon-instagram' => static function ( string $candidate ): ?array {
				if ( ! has_shortcode( $candidate, 'instagram-feed' ) ) {
					return null;
				}

				return array(
					'name'           => 'smash-balloon-instagram',
					'track_selector' => '[id="sbi_images"], .sbi_images',
				);
			},
		);

		/**
		 * Filter available social feed adapter callbacks.
		 *
		 * @param array<string,callable> $adapters  Adapter callbacks.
		 * @param string                 $shortcode Shortcode being resolved.
		 */
		$adapters = apply_filters( 'dpi_blocks/social_feed_adapters', $adapters, $shortcode );

		foreach ( is_array( $adapters ) ? $adapters : array() as $adapter ) {
			if ( ! is_callable( $adapter ) ) {
				continue;
			}

			try {
				$result = $adapter( $shortcode );
			} catch ( \Throwable $error ) {
				unset( $error );
				continue;
			}
			if ( ! is_array( $result ) || empty( $result['name'] ) || empty( $result['track_selector'] ) ) {
				continue;
			}

			return array(
				'name'           => sanitize_key( (string) $result['name'] ),
				'track_selector' => (string) $result['track_selector'],
			);
		}

		return null;
	}
}

if ( ! function_exists( 'dpi_blocks_get_staff_mode' ) ) {
	/** Return the validated global Staff interaction mode. */
	function dpi_blocks_get_staff_mode(): string {
		$mode = (string) Settings::get( 'staff_mode', 'single' );
		return in_array( $mode, array( 'single', 'modal' ), true ) ? $mode : 'single';
	}
}

if ( ! function_exists( 'dpi_blocks_get_staff_field' ) ) {
	/** Retrieve one normalized staff field. */
	function dpi_blocks_get_staff_field( int $post_id, string $name ): string {
		if ( ! in_array( $name, array( 'position', 'email', 'phone' ), true ) || 'staff' !== get_post_type( $post_id ) ) {
			return '';
		}

		$value = function_exists( 'get_field' ) ? get_field( 'staff_' . $name, $post_id ) : get_post_meta( $post_id, 'staff_' . $name, true );
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

if ( ! function_exists( 'dpi_blocks_staff_url' ) ) {
	/** Return a durable staff URL; modal behavior progressively enhances it. */
	function dpi_blocks_staff_url( int $post_id, string $mode = 'single' ): string {
		unset( $mode );
		$url = get_permalink( $post_id );
		return $url ? $url : '';
	}
}
