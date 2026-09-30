<?php
/**
 * Font Awesome Free icon registry and SVG sprite renderer.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IconRegistry {
	/** @var array<string,string>|null */
	private static ?array $icons = null;

	/** Return searchable ACF select choices. */
	public static function choices(): array {
		if ( null === self::$icons ) {
			$file = DPI_BLOCKS_DIR . 'assets/vendor/fontawesome/icons.json';
			$data = is_readable( $file ) ? wp_json_file_decode( $file, array( 'associative' => true ) ) : array();

			self::$icons = is_array( $data ) ? array_filter( $data, 'is_string' ) : array();
		}

		/**
		 * Filter the bundled Font Awesome Free icon choices.
		 *
		 * Keys use `style:icon-name`; values are human-readable labels.
		 *
		 * @param array<string,string> $icons Icon choices.
		 */
		$icons = apply_filters( 'dpi_blocks/font_awesome_icons', self::$icons );

		return is_array( $icons ) ? $icons : self::$icons;
	}

	/**
	 * Render an icon from the local SVG sprite.
	 *
	 * @param string              $icon  `style:icon-name` identifier.
	 * @param array<string,mixed> $attrs Safe SVG attributes.
	 */
	public static function render( string $icon, array $attrs = array() ): string {
		if ( ! isset( self::choices()[ $icon ] ) ) {
			return '';
		}

		$parts = explode( ':', $icon, 2 );
		if ( 2 !== count( $parts ) ) {
			return '';
		}

		$style = sanitize_key( $parts[0] );
		$name  = sanitize_key( $parts[1] );

		if ( ! in_array( $style, array( 'brands', 'regular', 'solid' ), true ) || '' === $name ) {
			return '';
		}

		$defaults = array(
			'class'       => 'dpi-icon',
			'aria-hidden' => 'true',
			'focusable'   => 'false',
			'width'       => '1em',
			'height'      => '1em',
			'fill'        => 'currentColor',
		);
		$allowed  = array( 'class', 'aria-hidden', 'aria-label', 'focusable', 'role', 'width', 'height' );
		$attrs    = array_merge( $defaults, array_intersect_key( $attrs, array_flip( $allowed ) ) );

		if ( ! empty( $attrs['aria-label'] ) ) {
			$attrs['aria-hidden'] = 'false';
			$attrs['role']        = 'img';
		}

		$attribute_html = '';
		foreach ( $attrs as $attribute => $value ) {
			$attribute_html .= sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( (string) $value ) );
		}

		$href = DPI_BLOCKS_URL . 'assets/vendor/fontawesome/sprites/' . $style . '.svg#' . $name;

		return sprintf(
			'<svg%1$s><use href="%2$s"></use></svg>',
			$attribute_html,
			esc_url( $href )
		);
	}
}
