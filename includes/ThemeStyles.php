<?php
/**
 * Translate semantic Global Styles into optional block design tokens.
 *
 * A palette is deliberately not inspected: preset names and ordering do not
 * establish which colors or fonts a theme intends for a particular role.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ThemeStyles {
	/** Return CSS for the active theme, including user Global Styles edits. */
	public static function css(): string {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return '';
		}

		$styles = wp_get_global_styles();
		return is_array( $styles ) ? self::from_styles( $styles ) : '';
	}

	/** Convert only explicitly defined, semantic theme styles. */
	public static function from_styles( array $styles ): string {
		$paths = array(
			'text'               => array( 'color', 'text' ),
			'background'         => array( 'color', 'background' ),
			'body-font'          => array( 'typography', 'fontFamily' ),
			'body-size'          => array( 'typography', 'fontSize' ),
			'line-height'        => array( 'typography', 'lineHeight' ),
			'heading-font'       => array( 'elements', 'heading', 'typography', 'fontFamily' ),
			'heading-weight'     => array( 'elements', 'heading', 'typography', 'fontWeight' ),
			'button-background'  => array( 'elements', 'button', 'color', 'background' ),
			'button-text'        => array( 'elements', 'button', 'color', 'text' ),
			'button-font'        => array( 'elements', 'button', 'typography', 'fontFamily' ),
			'button-size'        => array( 'elements', 'button', 'typography', 'fontSize' ),
			'button-weight'      => array( 'elements', 'button', 'typography', 'fontWeight' ),
			'button-radius'      => array( 'elements', 'button', 'border', 'radius' ),
		);
		$declarations = array();

		foreach ( $paths as $name => $path ) {
			$value = $styles;
			foreach ( $path as $part ) {
				$value = is_array( $value ) ? ( $value[ $part ] ?? null ) : null;
			}
			$value = self::css_value( $value );
			if ( '' !== $value ) {
				$declarations[] = '--dpi-theme-' . $name . ':' . $value;
			}
		}

		$gap = $styles['spacing']['blockGap'] ?? null;
		$gap = is_array( $gap ) ? ( $gap['row'] ?? $gap['column'] ?? null ) : $gap;
		$gap = self::css_value( $gap );
		if ( '' !== $gap ) {
			$declarations[] = '--dpi-theme-gap:' . $gap;
		}

		return $declarations ? '@layer dpi-blocks.defaults{:where(.dpi-block){' . implode( ';', $declarations ) . ';}}' : '';
	}

	/** Resolve WordPress preset references without accepting CSS rule injection. */
	private static function css_value( mixed $value ): string {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( str_starts_with( $value, 'var:' ) ) {
			if ( ! preg_match( '/^var:(preset|custom)\|([a-zA-Z0-9_|-]+)$/', $value, $matches ) ) {
				return '';
			}
			$parts = explode( '|', $matches[2] );
			foreach ( $parts as &$part ) {
				if ( '' === $part ) {
					return '';
				}
				$part = strtolower( (string) preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $part ) );
				$part = str_replace( '_', '-', $part );
			}
			unset( $part );
			return 'var(--wp--' . $matches[1] . '--' . implode( '--', $parts ) . ')';
		}

		if ( preg_match( '~[{};<>\\\\\x00-\x1F]|/\*|\burl\s*\(|!important~i', $value ) ) {
			return '';
		}

		return $value;
	}
}
