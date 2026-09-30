<?php
/**
 * Anchor Navigation block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$links      = function_exists( 'get_field' ) ? get_field( 'links' ) : array();
$sticky     = function_exists( 'get_field' ) ? (bool) get_field( 'sticky' ) : true;
$aria_label = function_exists( 'get_field' ) ? trim( (string) get_field( 'aria_label' ) ) : '';
$aria_label = $aria_label ?: __( 'On this page', 'dpi-blocks' );
$links      = is_array( $links ) ? $links : array();
$items      = array();

foreach ( $links as $row ) {
	$label  = is_array( $row ) ? trim( (string) ( $row['label'] ?? '' ) ) : '';
	$target = is_array( $row ) ? trim( (string) ( $row['target'] ?? '' ) ) : '';
	$target = sanitize_title( ltrim( $target, '#' ) );
	if ( $label && $target ) {
		$items[] = array(
			'label'  => $label,
			'target' => $target,
		);
	}
}

if ( ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-anchor-navigation dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one anchor link.', 'dpi-blocks' )
		);
	}
	return;
}

$classes            = 'dpi-block dpi-anchor-navigation' . ( $sticky ? ' dpi-anchor-navigation--sticky' : '' );
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $aria_label ); ?>" data-dpi-anchor-navigation>
	<ul class="dpi-anchor-navigation__list">
		<?php foreach ( $items as $item ) : ?>
			<li class="dpi-anchor-navigation__item">
				<?php
				$anchor_link  = array( 'url' => '#' . $item['target'], 'title' => $item['label'], 'target' => '' );
				$anchor_attrs = empty( $is_preview ) ? array( 'data-dpi-anchor-link' => 'true' ) : array();
				echo dpi_blocks_link_open( $anchor_link, 'dpi-anchor-navigation__link', ! empty( $is_preview ), $anchor_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo esc_html( $item['label'] );
				echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
