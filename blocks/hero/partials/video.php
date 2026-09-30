<?php

/**
 * Hero video presentation.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_video_file        = $dpi_get_field( 'video_file', null );
$dpi_video_source      = (string) $dpi_get_field( 'video_source', 'media' );
$dpi_youtube_url       = (string) $dpi_get_field( 'youtube_url', '' );
$dpi_video_poster      = $dpi_get_field( 'video_poster', null );
$dpi_video_heading     = $dpi_get_field( 'video_heading', '' );
$dpi_video_subheadings = $dpi_get_field( 'subheadings', array() );
$dpi_video_buttons     = $dpi_get_field( 'video_buttons', array() );
$dpi_video_autoplay    = $dpi_get_bool( 'video_autoplay', true );
$dpi_video_muted       = $dpi_get_bool( 'video_muted', true );
$dpi_video_loop        = $dpi_get_bool( 'video_loop', true );
$dpi_video_controls    = $dpi_get_bool( 'video_controls', false );
$dpi_video_url         = '';
$dpi_video_mime        = '';
$dpi_poster_url        = '';
$dpi_youtube_embed_url = '';

$dpi_video_source = in_array( $dpi_video_source, array( 'media', 'youtube' ), true ) ? $dpi_video_source : 'media';

if ( 'media' === $dpi_video_source ) {
	if ( is_array( $dpi_video_file ) ) {
		$dpi_video_url  = (string) ( $dpi_video_file['url'] ?? '' );
		$dpi_video_mime = (string) ( $dpi_video_file['mime_type'] ?? '' );
	} elseif ( is_numeric( $dpi_video_file ) ) {
		$dpi_video_url  = (string) wp_get_attachment_url( absint( $dpi_video_file ) );
		$dpi_video_mime = (string) get_post_mime_type( absint( $dpi_video_file ) );
	} elseif ( is_string( $dpi_video_file ) ) {
		$dpi_video_url = $dpi_video_file;
	}

	if ( ! $dpi_video_mime && $dpi_video_url ) {
		$dpi_file_type  = wp_check_filetype( $dpi_video_url );
		$dpi_video_mime = (string) ( $dpi_file_type['type'] ?? 'video/mp4' );
	}
} else {
	$dpi_youtube_id = $dpi_get_youtube_id( $dpi_youtube_url );
	if ( $dpi_youtube_id ) {
		$dpi_youtube_embed_url = add_query_arg(
			array(
				'autoplay'       => $dpi_video_autoplay ? 1 : 0,
				'controls'       => $dpi_video_controls ? 1 : 0,
				'enablejsapi'    => 1,
				'loop'           => $dpi_video_loop ? 1 : 0,
				'modestbranding' => 1,
				'mute'           => $dpi_video_muted ? 1 : 0,
				'playlist'       => $dpi_video_loop ? $dpi_youtube_id : false,
				'playsinline'    => 1,
				'rel'            => 0,
			),
			'https://www.youtube-nocookie.com/embed/' . rawurlencode( $dpi_youtube_id )
		);
	}
}

if ( is_array( $dpi_video_poster ) ) {
	$dpi_poster_url = (string) ( $dpi_video_poster['url'] ?? '' );
} elseif ( is_numeric( $dpi_video_poster ) ) {
	$dpi_poster_url = (string) wp_get_attachment_image_url( absint( $dpi_video_poster ), 'full' );
} elseif ( is_string( $dpi_video_poster ) ) {
	$dpi_poster_url = $dpi_video_poster;
}

if ( ! $dpi_video_subheadings ) {
	$dpi_legacy_subheading = $dpi_get_field( 'video_subheading', '' );
	$dpi_video_subheadings = $dpi_legacy_subheading ? array( array( 'video_subheading' => $dpi_legacy_subheading ) ) : array();
}
?>
<article class="dpi-hero__slide dpi-hero__slide--video">
	<div class="dpi-hero__media">
		<?php if ( 'youtube' === $dpi_video_source && $dpi_youtube_embed_url ) : ?>
		<iframe
			class="dpi-hero__video dpi-hero__video--youtube"
			src="<?php echo esc_url( $dpi_youtube_embed_url ); ?>"
			title="<?php echo esc_attr( $dpi_video_heading ? wp_strip_all_tags( (string) $dpi_video_heading ) : __( 'Hero video', 'dpi-blocks' ) ); ?>"
			allow="autoplay; encrypted-media; picture-in-picture"
			referrerpolicy="strict-origin-when-cross-origin"
			data-dpi-youtube-player
			data-dpi-youtube-autoplay="<?php echo $dpi_video_autoplay ? '1' : '0'; ?>"
			allowfullscreen
		></iframe>
		<?php elseif ( 'media' === $dpi_video_source && $dpi_video_url ) : ?>
		<video
			class="dpi-hero__video"
			playsinline
			preload="metadata"<?php echo $dpi_video_autoplay ? ' autoplay' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $dpi_video_muted ? ' muted' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $dpi_video_loop ? ' loop' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $dpi_video_controls ? ' controls' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $dpi_poster_url ? ' poster="' . esc_url( $dpi_poster_url ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<source src="<?php echo esc_url( $dpi_video_url ); ?>" type="<?php echo esc_attr( $dpi_video_mime ); ?>">
		</video>
		<?php elseif ( $dpi_poster_url ) : ?>
		<img src="<?php echo esc_url( $dpi_poster_url ); ?>" alt="">
		<?php elseif ( ! empty( $is_preview ) ) : ?>
		<p class="dpi-block--placeholder"><?php esc_html_e( 'Choose a valid YouTube URL or Media Library video.', 'dpi-blocks' ); ?>
		</p>
		<?php endif; ?>
		<span class="dpi-hero__overlay" aria-hidden="true"></span>
	</div>
	<?php $dpi_render_content( $dpi_video_heading, $dpi_video_subheadings, $dpi_video_buttons ); ?>
</article>
