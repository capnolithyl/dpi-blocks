<?php
/**
 * Image buttons block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rows   = function_exists( 'get_field' ) ? get_field( 'buttons' ) : array();
$layout = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'overlay';
$layout = in_array( $layout, array( 'overlay', 'captioned' ), true ) ? $layout : 'overlay';
$rows   = is_array( $rows ) ? $rows : array();
$items  = array();

foreach ( $rows as $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}

	$button_link = isset( $row['link'] ) && is_array( $row['link'] ) ? $row['link'] : array();
	$image       = $row['image'] ?? null;
	$image_id    = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );

	if ( ! $image_id && empty( $button_link['url'] ) && empty( $row['heading'] ) && empty( $row['subheading'] ) ) {
		continue;
	}

	$items[] = array(
		'link'       => $button_link,
		'image_id'   => $image_id,
		'heading'    => trim( (string) ( $row['heading'] ?? '' ) ),
		'subheading' => trim( (string) ( $row['subheading'] ?? '' ) ),
	);
}

if ( ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-image-buttons dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one image button.', 'dpi-blocks' )
		);
	}

	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-image-buttons dpi-image-buttons--' . $layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="dpi-image-buttons__list">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$has_link = ! empty( $item['link']['url'] );
			$target   = ! empty( $item['link']['target'] ) ? (string) $item['link']['target'] : '_self';
			$rel      = '_blank' === $target ? 'noopener noreferrer' : '';
			?>
			<li class="dpi-image-buttons__item">
				<?php if ( $has_link ) : ?>
					<a class="dpi-image-buttons__card" href="<?php echo esc_url( $item['link']['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>"
					<?php
					if ( $rel ) :
						?>
						rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>>
				<?php else : ?>
					<div class="dpi-image-buttons__card">
				<?php endif; ?>

				<?php if ( $item['image_id'] ) : ?>
					<figure class="dpi-image-buttons__media"><?php echo wp_get_attachment_image( $item['image_id'], 'large', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
				<?php endif; ?>
				<div class="dpi-image-buttons__caption">
					<?php
					if ( $item['heading'] ) :
						?>
						<h3><?php echo esc_html( $item['heading'] ); ?></h3><?php endif; ?>
					<?php
					if ( $item['subheading'] ) :
						?>
						<p><?php echo esc_html( $item['subheading'] ); ?></p><?php endif; ?>
				</div>

				<?php
				if ( $has_link ) :
					?>
					</a>
					<?php
else :
	?>
					</div><?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
