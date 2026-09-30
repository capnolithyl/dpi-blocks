<?php
/**
 * Image buttons block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rows        = function_exists( 'get_field' ) ? get_field( 'buttons' ) : array();
$layout      = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'overlay';
$interaction = function_exists( 'get_field' ) ? (string) get_field( 'interaction' ) : 'static';
$layout      = in_array( $layout, array( 'overlay', 'captioned' ), true ) ? $layout : 'overlay';
$interaction = in_array( $interaction, array( 'static', 'reveal' ), true ) ? $interaction : 'static';
$interaction = 'overlay' === $layout ? $interaction : 'static';
$rows        = is_array( $rows ) ? $rows : array();
$items       = array();

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
		'link'         => $button_link,
		'image_id'     => $image_id,
		'heading'      => trim( (string) ( $row['heading'] ?? '' ) ),
		'subheading'   => trim( (string) ( $row['subheading'] ?? '' ) ),
		'description'  => trim( (string) ( $row['description'] ?? '' ) ),
		'action_label' => trim( (string) ( $row['action_label'] ?? '' ) ),
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
		'class' => 'dpi-block dpi-image-buttons dpi-image-buttons--' . $layout . ' dpi-image-buttons--interaction-' . $interaction,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="dpi-image-buttons__list">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$has_link       = ! empty( $item['link']['url'] );
			$has_reveal     = 'reveal' === $interaction && $has_link && ( $item['heading'] || $item['description'] || $item['action_label'] );
			$card_class     = 'dpi-image-buttons__card' . ( $has_reveal ? ' dpi-image-buttons__card--reveal' : '' );
			$target         = ! empty( $item['link']['target'] ) ? (string) $item['link']['target'] : '_self';
			$rel            = '_blank' === $target ? 'noopener noreferrer' : '';
			?>
			<li class="dpi-image-buttons__item">
				<?php if ( $has_link ) : ?>
					<a class="<?php echo esc_attr( $card_class ); ?>" href="<?php echo esc_url( $item['link']['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>"
					<?php
					if ( $rel ) :
						?>
						rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>>
				<?php else : ?>
					<div class="<?php echo esc_attr( $card_class ); ?>">
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

				<?php if ( $has_reveal ) : ?>
					<div class="dpi-image-buttons__reveal">
						<?php if ( $item['heading'] ) : ?>
							<span class="dpi-image-buttons__reveal-heading" aria-hidden="true"><?php echo esc_html( $item['heading'] ); ?></span>
						<?php endif; ?>
						<?php if ( $item['description'] ) : ?>
							<p class="dpi-image-buttons__description"><?php echo esc_html( $item['description'] ); ?></p>
						<?php endif; ?>
						<?php if ( $has_link && $item['action_label'] ) : ?>
							<span class="dpi-image-buttons__action" aria-hidden="true"><?php echo esc_html( $item['action_label'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

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
