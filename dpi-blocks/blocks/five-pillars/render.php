<?php
/**
 * Pillars block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow       = function_exists( 'get_field' ) ? trim( (string) get_field( 'eyebrow' ) ) : '';
$heading       = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$counter_label = function_exists( 'get_field' ) ? trim( (string) get_field( 'counter_label' ) ) : '';
$pillars       = function_exists( 'get_field' ) ? get_field( 'pillars' ) : array();
$pillars       = is_array( $pillars ) ? $pillars : array();
$items         = array();

foreach ( $pillars as $pillar ) {
	if ( ! is_array( $pillar ) ) {
		continue;
	}

	$name        = trim( (string) ( $pillar['name'] ?? '' ) );
	$description = trim( (string) ( $pillar['description'] ?? '' ) );
	$link        = isset( $pillar['link'] ) && is_array( $pillar['link'] ) ? $pillar['link'] : array();
	$images      = isset( $pillar['images'] ) && is_array( $pillar['images'] ) ? $pillar['images'] : array();
	$image_ids   = array();

	foreach ( $images as $image ) {
		$image_id = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );
		if ( $image_id ) {
			$image_ids[] = $image_id;
		}
	}
	$image_ids = array_slice( array_values( array_unique( $image_ids ) ), 0, 3 );

	if ( $name || $description || ! empty( $link['url'] ) || $image_ids ) {
		$items[] = array(
			'name'        => $name,
			'description' => $description,
			'link'        => $link,
			'images'      => $image_ids,
		);
	}
}

if ( ! $eyebrow && ! $heading && ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-five-pillars dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one pillar.', 'dpi-blocks' )
		);
	}
	return;
}

$instance_id        = wp_unique_id( 'dpi-five-pillars-' );
$heading_id         = $heading ? $instance_id . '-heading' : '';
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-five-pillars' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?> data-dpi-five-pillars>
	<?php if ( $eyebrow || $heading ) : ?>
		<header class="dpi-five-pillars__header">
			<?php if ( $eyebrow ) : ?><p class="dpi-five-pillars__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-five-pillars__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		</header>
	<?php endif; ?>

	<?php if ( $items ) : ?>
		<div class="dpi-five-pillars__layout">
			<div class="dpi-five-pillars__visuals">
				<?php foreach ( $items as $index => $item ) : ?>
					<div class="dpi-five-pillars__media" data-dpi-pillar-media data-dpi-pillar-index="<?php echo esc_attr( (string) $index ); ?>">
						<?php foreach ( $item['images'] as $image_index => $image_id ) : ?>
							<figure class="dpi-five-pillars__image dpi-five-pillars__image--<?php echo esc_attr( (string) ( $image_index + 1 ) ); ?>">
								<?php echo wp_get_attachment_image( $image_id, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</figure>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dpi-five-pillars__content">
				<div class="dpi-five-pillars__count-wrap" aria-hidden="true">
					<span class="dpi-five-pillars__count"><?php echo esc_html( (string) count( $items ) ); ?></span>
					<?php if ( $counter_label ) : ?><span class="dpi-five-pillars__count-label"><?php echo esc_html( $counter_label ); ?></span><?php endif; ?>
				</div>

				<div class="dpi-five-pillars__items" role="list">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php
						$trigger_id = $instance_id . '-trigger-' . $index;
						$panel_id   = $instance_id . '-panel-' . $index;
						$link       = $item['link'];
						?>
						<div class="dpi-five-pillars__item" role="listitem" data-dpi-pillar-item>
							<h3 class="dpi-five-pillars__item-heading">
								<button id="<?php echo esc_attr( $trigger_id ); ?>" class="dpi-five-pillars__trigger" type="button" aria-expanded="true" aria-controls="<?php echo esc_attr( $panel_id ); ?>" data-dpi-pillar-trigger data-dpi-pillar-index="<?php echo esc_attr( (string) $index ); ?>">
									<?php echo esc_html( $item['name'] ); ?>
								</button>
							</h3>
							<div id="<?php echo esc_attr( $panel_id ); ?>" class="dpi-five-pillars__panel" role="region" aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>" data-dpi-pillar-panel data-dpi-pillar-index="<?php echo esc_attr( (string) $index ); ?>">
								<?php if ( $item['description'] ) : ?><p class="dpi-five-pillars__description"><?php echo esc_html( $item['description'] ); ?></p><?php endif; ?>
								<?php if ( ! empty( $link['url'] ) && ! empty( $link['title'] ) ) : ?>
									<?php
									$target = ! empty( $link['target'] ) ? (string) $link['target'] : '_self';
									$rel    = '_blank' === $target ? 'noopener noreferrer' : '';
									?>
									<a class="dpi-five-pillars__link" href="<?php echo esc_url( $link['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo $rel ? ' rel="' . esc_attr( $rel ) . '"' : ''; ?>><?php echo esc_html( $link['title'] ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</section>
