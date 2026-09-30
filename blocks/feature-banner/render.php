<?php
/**
 * Feature / CTA Banner block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow          = function_exists( 'get_field' ) ? trim( (string) get_field( 'eyebrow' ) ) : '';
$headline         = function_exists( 'get_field' ) ? trim( (string) get_field( 'headline' ) ) : '';
$content_text     = function_exists( 'get_field' ) ? (string) get_field( 'content' ) : '';
$image            = function_exists( 'get_field' ) ? get_field( 'image' ) : null;
$cta              = function_exists( 'get_field' ) ? get_field( 'cta' ) : null;
$image_layout     = function_exists( 'get_field' ) ? (string) get_field( 'image_layout' ) : 'background';
$visual_variant   = function_exists( 'get_field' ) ? (string) get_field( 'visual_variant' ) : 'dark';
$headline_element = function_exists( 'get_field' ) ? (string) get_field( 'headline_element' ) : 'heading';
$image_layout     = in_array( $image_layout, array( 'background', 'left', 'right' ), true ) ? $image_layout : 'background';
$visual_variant   = in_array( $visual_variant, array( 'dark', 'light' ), true ) ? $visual_variant : 'dark';
$headline_element = in_array( $headline_element, array( 'heading', 'quote' ), true ) ? $headline_element : 'heading';
$image_id         = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );
$cta              = is_array( $cta ) ? $cta : array();

if ( ! $eyebrow && ! $headline && ! trim( wp_strip_all_tags( $content_text ) ) && ! $image_id && empty( $cta['url'] ) ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-feature-banner dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add feature content, media, or a CTA.', 'dpi-blocks' )
		);
	}
	return;
}

$heading_id         = $headline && 'heading' === $headline_element ? wp_unique_id( 'dpi-feature-banner-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf( 'dpi-block dpi-feature-banner dpi-feature-banner--%1$s dpi-feature-banner--%2$s', $image_layout, $visual_variant ),
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>>
	<?php if ( $image_id ) : ?>
		<figure class="dpi-feature-banner__media">
			<?php echo wp_get_attachment_image( $image_id, 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</figure>
		<?php if ( 'background' === $image_layout ) : ?>
			<div class="dpi-feature-banner__overlay" aria-hidden="true"></div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="dpi-feature-banner__content">
		<?php if ( $eyebrow ) : ?>
			<p class="dpi-feature-banner__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( $headline ) : ?>
			<?php if ( 'quote' === $headline_element ) : ?>
				<blockquote class="dpi-feature-banner__quote"><p><?php echo esc_html( $headline ); ?></p></blockquote>
			<?php else : ?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-feature-banner__heading"><?php echo esc_html( $headline ); ?></h2>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( trim( wp_strip_all_tags( $content_text ) ) ) : ?>
			<div class="dpi-feature-banner__text"><?php echo wp_kses_post( wpautop( $content_text ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $cta['url'] ) && ! empty( $cta['title'] ) ) : ?>
			<?php
			$target = ! empty( $cta['target'] ) ? (string) $cta['target'] : '_self';
			$rel    = '_blank' === $target ? 'noopener noreferrer' : '';
			?>
			<?php echo dpi_blocks_link_open( $cta, 'dpi-feature-banner__cta', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $cta['title'] ); ?><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</div>
</section>
