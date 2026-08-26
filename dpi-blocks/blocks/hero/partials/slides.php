<?php

/**
 * Hero image slides presentation.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_slides = $dpi_get_field( 'slides', array() );
?>
<?php if ( is_array( $dpi_slides ) && $dpi_slides ) : ?>
<div class="dpi-hero__slides" <?php if ( count( $dpi_slides ) > 1 ) : ?>
	data-dpi-slick="<?php echo esc_attr( wp_json_encode( $dpi_slick ) ); ?>" <?php endif; ?>>
	<?php foreach ( $dpi_slides as $dpi_slide ) : ?>
		<?php
		$dpi_image    = is_array( $dpi_slide ) ? ( $dpi_slide['image'] ?? null ) : null;
		$dpi_image_id = is_array( $dpi_image ) ? absint( $dpi_image['ID'] ?? $dpi_image['id'] ?? 0 ) : absint( $dpi_image );
		?>
	<article class="dpi-hero__slide">
		<div class="dpi-hero__media">
			<?php if ( $dpi_image_id ) : ?>
				<?php
				echo wp_get_attachment_image( $dpi_image_id, 'full', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php elseif ( ! empty( $is_preview ) ) : ?>
			<p class="dpi-block--placeholder">
				<?php esc_html_e( 'Choose an image for this slide.', 'dpi-blocks' ); ?></p>
			<?php endif; ?>
			<span class="dpi-hero__overlay" aria-hidden="true"></span>
		</div>
		<?php $dpi_render_content( $dpi_slide['heading'] ?? '', $dpi_slide['subheadings'] ?? array(), $dpi_slide['buttons'] ?? array() ); ?>
	</article>
	<?php endforeach; ?>
</div>
<?php elseif ( ! empty( $is_preview ) ) : ?>
<p class="dpi-block--placeholder"><?php esc_html_e( 'Add at least one hero slide.', 'dpi-blocks' ); ?></p>
<?php endif; ?>
