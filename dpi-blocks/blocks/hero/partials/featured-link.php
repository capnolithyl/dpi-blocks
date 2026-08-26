<?php

/**
 * Hero featured link presentation.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside class="dpi-hero__featured-link" aria-label="<?php esc_attr_e( 'Featured link', 'dpi-blocks' ); ?>">
	<?php if ( $dpi_featured_link_heading ) : ?>
	<span><?php echo esc_html( $dpi_featured_link_heading ); ?></span>
	<?php endif; ?>
	<?php $dpi_render_links( array( $dpi_featured_link ), 'dpi-hero__featured-link-action' ); ?>
</aside>
