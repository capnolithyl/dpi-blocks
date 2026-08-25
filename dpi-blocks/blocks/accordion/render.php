<?php
/**
 * Accordion / FAQ block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow        = function_exists( 'get_field' ) ? trim( (string) get_field( 'eyebrow' ) ) : '';
$heading        = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$items          = function_exists( 'get_field' ) ? get_field( 'items' ) : array();
$allow_multiple = function_exists( 'get_field' ) ? (bool) get_field( 'allow_multiple' ) : false;
$first_open     = function_exists( 'get_field' ) ? (bool) get_field( 'first_item_open' ) : false;
$items          = is_array( $items ) ? $items : array();
$normalized     = array();

foreach ( $items as $item ) {
	$question = is_array( $item ) ? trim( (string) ( $item['question'] ?? '' ) ) : '';
	$answer   = is_array( $item ) ? (string) ( $item['answer'] ?? '' ) : '';
	if ( $question || trim( wp_strip_all_tags( $answer ) ) ) {
		$normalized[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}
}

if ( ! $eyebrow && ! $heading && ! $normalized ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-accordion dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one accordion item.', 'dpi-blocks' )
		);
	}
	return;
}

$instance_id        = wp_unique_id( 'dpi-accordion-' );
$heading_id         = $heading ? $instance_id . '-heading' : '';
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-accordion' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?> data-dpi-accordion data-dpi-allow-multiple="<?php echo $allow_multiple ? 'true' : 'false'; ?>">
	<?php if ( $eyebrow ) : ?>
		<p class="dpi-accordion__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
	<?php endif; ?>
	<?php if ( $heading ) : ?>
		<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-accordion__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="dpi-accordion__items">
		<?php foreach ( $normalized as $index => $item ) : ?>
			<?php
			$trigger_id = $instance_id . '-trigger-' . $index;
			$panel_id   = $instance_id . '-panel-' . $index;
			$open       = $first_open && 0 === $index;
			?>
			<div class="dpi-accordion__item" data-dpi-accordion-item data-dpi-initial-open="<?php echo $open ? 'true' : 'false'; ?>">
				<h3 class="dpi-accordion__item-heading">
					<button id="<?php echo esc_attr( $trigger_id ); ?>" class="dpi-accordion__trigger" type="button" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>" data-dpi-accordion-trigger>
						<span><?php echo esc_html( $item['question'] ); ?></span>
						<span class="dpi-accordion__indicator" aria-hidden="true"></span>
					</button>
				</h3>
				<div id="<?php echo esc_attr( $panel_id ); ?>" class="dpi-accordion__panel" role="region" aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"<?php echo $open ? '' : ' hidden'; ?> data-dpi-accordion-panel>
					<div class="dpi-accordion__answer"><?php echo wp_kses_post( $item['answer'] ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
