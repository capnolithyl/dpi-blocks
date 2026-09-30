<?php
/**
 * Stats block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats  = function_exists( 'get_field' ) ? get_field( 'stats' ) : array();
$layout = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'row';
$layout = in_array( $layout, array( 'row', 'cards' ), true ) ? $layout : 'row';
$stats  = is_array( $stats ) ? $stats : array();
$items  = array();

foreach ( $stats as $stat ) {
	$value = is_array( $stat ) ? trim( (string) ( $stat['value'] ?? '' ) ) : '';
	$label = is_array( $stat ) ? trim( (string) ( $stat['label'] ?? '' ) ) : '';

	if ( $value || $label ) {
		$items[] = array(
			'value' => $value,
			'label' => $label,
		);
	}
}

if ( ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-stats dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one statistic.', 'dpi-blocks' )
		);
	}

	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-stats dpi-stats--' . $layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="dpi-stats__list">
		<?php foreach ( $items as $item ) : ?>
			<li class="dpi-stats__item">
				<?php
				if ( $item['value'] ) :
					?>
					<strong class="dpi-stats__value"><?php echo esc_html( $item['value'] ); ?></strong><?php endif; ?>
				<?php
				if ( $item['label'] ) :
					?>
					<span class="dpi-stats__label"><?php echo esc_html( $item['label'] ); ?></span><?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
