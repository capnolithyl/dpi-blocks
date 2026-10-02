<?php
/**
 * Community Slider card.
 *
 * Expects $community_post, $show_date, and $is_preview from the parent renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_link = array(
	'url'    => get_permalink( $community_post ),
	'title'  => get_the_title( $community_post ),
	'target' => '',
);
?>
<article class="dpi-community-slider__item">
	<?php if ( has_post_thumbnail( $community_post ) ) : ?>
		<?php echo dpi_blocks_link_open( $post_link, 'dpi-community-slider__image', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo get_the_post_thumbnail( $community_post, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
	<h3><?php echo dpi_blocks_link_open( $post_link, 'dpi-community-slider__title-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( get_the_title( $community_post ) ); ?><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
	<?php if ( $show_date ) : ?>
		<time class="dpi-community-slider__date" datetime="<?php echo esc_attr( get_the_date( 'c', $community_post ) ); ?>">
			<?php echo esc_html( get_the_date( 'M j', $community_post ) ); ?>
		</time>
	<?php endif; ?>
</article>
