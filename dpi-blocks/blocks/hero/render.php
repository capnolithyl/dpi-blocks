<?php

/**
 * Hero block renderer.
 *
 * Coordinates the Hero's data and delegates presentation to focused partials.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_helpers = require __DIR__ . '/includes/helpers.php';

$dpi_get_field      = $dpi_helpers['get_field'];
$dpi_get_bool       = $dpi_helpers['get_bool'];
$dpi_get_youtube_id = $dpi_helpers['get_youtube_id'];
$dpi_render_links   = $dpi_helpers['render_links'];

// The parameters become local variables for the required content partial.
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
$dpi_render_content = static function ( $dpi_heading, $dpi_subheadings, $dpi_buttons ) use ( $dpi_render_links ): void {
	require __DIR__ . '/partials/content.php';
};
// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

$dpi_media_type     = $dpi_get_bool( 'media_type', false ) ? 'video' : 'slider';
$dpi_layout         = (string) $dpi_get_field( 'layout', 'overlay' );
$dpi_layout         = in_array( $dpi_layout, array( 'overlay', 'split' ), true ) ? $dpi_layout : 'overlay';
$dpi_section_height = (string) $dpi_get_field( 'section_height', 'medium' );
$dpi_section_height = in_array( $dpi_section_height, array( 'auto', 'small', 'medium', 'large', 'viewport' ), true ) ? $dpi_section_height : 'medium';
$dpi_opacity        = min( 100, max( 0, (float) $dpi_get_field( 'overlay_opacity', 50 ) ) );
$dpi_slick          = array(
	'arrows'         => $dpi_get_bool( 'show_arrows', true ),
	'dots'           => $dpi_get_bool( 'show_dots', true ),
	'infinite'       => $dpi_get_bool( 'infinite', true ),
	'autoplay'       => $dpi_get_bool( 'autoplay', true ),
	'autoplaySpeed'  => max( 1000, absint( $dpi_get_field( 'autoplay_delay', 6000 ) ) ),
	'speed'          => max( 0, absint( $dpi_get_field( 'transition_speed', 600 ) ) ),
	'pauseOnHover'   => $dpi_get_bool( 'pause_on_hover', true ),
	'pauseOnFocus'   => $dpi_get_bool( 'pause_on_focus', true ),
	'slidesToShow'   => 1,
	'slidesToScroll' => 1,
	'rows'           => 0,
);

$dpi_featured_link         = $dpi_get_field( 'featured_link', null );
$dpi_featured_link_heading = trim( (string) $dpi_get_field( 'featured_link_heading', '' ) );
$dpi_show_featured_link    = $dpi_get_bool( 'show_featured_link', true )
	&& is_array( $dpi_featured_link )
	&& ! empty( $dpi_featured_link['url'] )
	&& ! empty( $dpi_featured_link['title'] );

$dpi_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf( 'dpi-block dpi-hero dpi-hero--%s dpi-hero--height-%s', $dpi_layout, $dpi_section_height ),
		'style' => '--dpi-hero-overlay-opacity:' . ( $dpi_opacity / 100 ) . ';',
	)
);

printf( '<section %s>', $dpi_wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( 'video' === $dpi_media_type ) {
	require __DIR__ . '/partials/video.php';
} else {
	require __DIR__ . '/partials/slides.php';
}

if ( $dpi_show_featured_link ) {
	require __DIR__ . '/partials/featured-link.php';
}
?>
</section>
