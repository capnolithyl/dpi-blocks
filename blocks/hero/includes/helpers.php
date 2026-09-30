<?php

/**
 * Hero renderer helpers.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_get_field = static function ( $name, $fallback = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	$value = get_field( $name );

	return null === $value || '' === $value ? $fallback : $value;
};

$dpi_get_bool = static function ( $name, $fallback ) use ( $dpi_get_field ) {
	$value = $dpi_get_field( $name, null );

	return null === $value ? (bool) $fallback : (bool) $value;
};

$dpi_get_youtube_id = static function ( $url ): string {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return '';
	}

	$host = strtolower( (string) $parts['host'] );
	$host = (string) preg_replace( '/^(?:www\.|m\.)/', '', $host );
	$path = trim( (string) ( $parts['path'] ?? '' ), '/' );
	$id   = '';

	if ( 'youtu.be' === $host ) {
		$id = explode( '/', $path )[0] ?? '';
	} elseif (
		'youtube.com' === $host
		|| str_ends_with( $host, '.youtube.com' )
		|| 'youtube-nocookie.com' === $host
		|| str_ends_with( $host, '.youtube-nocookie.com' )
	) {
		$query = array();
		parse_str( (string) ( $parts['query'] ?? '' ), $query );

		if ( isset( $query['v'] ) && is_string( $query['v'] ) ) {
			$id = $query['v'];
		} else {
			$segments = array_values( array_filter( explode( '/', $path ) ) );
			if (
				isset( $segments[0], $segments[1] )
				&& in_array( $segments[0], array( 'embed', 'live', 'shorts' ), true )
			) {
				$id = $segments[1];
			}
		}
	}

	$id = trim( (string) $id );

	return preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $id ) ? $id : '';
};

$dpi_is_preview = ! empty( $is_preview );

$dpi_render_links = static function ( $rows, $class_name = '' ) use ( $dpi_is_preview ) {
	if ( ! is_array( $rows ) ) {
		return;
	}

	foreach ( $rows as $row ) {
		$link = is_array( $row ) && ! empty( $row['url'] ) ? $row : ( $row['link'] ?? null );

		if ( ! is_array( $link ) || empty( $link['url'] ) || empty( $link['title'] ) ) {
			continue;
		}

		if ( function_exists( 'dpi_blocks_render_link' ) ) {
			echo wp_kses_post( dpi_blocks_render_link( $link, $class_name, $dpi_is_preview ) );
			continue;
		}

		$target = ! empty( $link['target'] ) ? (string) $link['target'] : '_self';
		$rel    = '_blank' === $target ? 'noopener noreferrer' : '';

		if ( $dpi_is_preview ) {
			printf(
				'<span class="%1$s dpi-editor-link">%2$s</span>',
				esc_attr( $class_name ),
				esc_html( $link['title'] )
			);
			continue;
		}

		printf(
			'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
			esc_attr( $class_name ),
			esc_url( $link['url'] ),
			esc_attr( $target ),
			$rel ? ' rel="' . esc_attr( $rel ) . '"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $link['title'] )
		);
	}
};

return array(
	'get_field'      => $dpi_get_field,
	'get_bool'       => $dpi_get_bool,
	'get_youtube_id' => $dpi_get_youtube_id,
	'render_links'   => $dpi_render_links,
);
