<?php
/**
 * Featured links block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_heading = function_exists( 'get_field' ) ? trim( (string) get_field( 'section_heading' ) ) : '';
$inner_heading   = function_exists( 'get_field' ) ? trim( (string) get_field( 'inner_heading' ) ) : '';
$text            = function_exists( 'get_field' ) ? (string) get_field( 'text' ) : '';
$background      = function_exists( 'get_field' ) ? get_field( 'background_image' ) : null;
$links           = function_exists( 'get_field' ) ? get_field( 'links' ) : array();
$layout          = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'split';
$layout          = in_array( $layout, array( 'split', 'stacked' ), true ) ? $layout : 'split';
$links           = is_array( $links ) ? $links : array();
$image_id        = is_array( $background ) ? absint( $background['ID'] ?? $background['id'] ?? 0 ) : absint( $background );
$items           = array();

foreach ( $links as $row ) {
	$featured_link = is_array( $row ) && isset( $row['link'] ) && is_array( $row['link'] ) ? $row['link'] : array();

	if ( empty( $featured_link['url'] ) || empty( $featured_link['title'] ) ) {
		continue;
	}

	$items[] = array(
		'icon'    => $row['icon'] ?? '',
		'link'    => $featured_link,
		'excerpt' => (string) ( $row['excerpt'] ?? '' ),
	);
}

if ( ! $section_heading && ! $inner_heading && ! $text && ! $image_id && ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-featured-links dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add content or links to this block.', 'dpi-blocks' )
		);
	}

	return;
}

$heading_id         = $section_heading ? wp_unique_id( 'dpi-featured-links-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-featured-links dpi-featured-links--' . $layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading_id ) :
	?>
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"<?php endif; ?>>
	<?php if ( $section_heading ) : ?>
		<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-featured-links__heading"><?php echo esc_html( $section_heading ); ?></h2>
	<?php endif; ?>

	<div class="dpi-featured-links__body">
		<?php if ( $image_id ) : ?>
			<figure class="dpi-featured-links__media">
				<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</figure>
		<?php endif; ?>

		<div class="dpi-featured-links__content">
			<?php if ( $inner_heading ) : ?>
				<h3><?php echo esc_html( $inner_heading ); ?></h3>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<div class="dpi-featured-links__text"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
			<?php endif; ?>

			<?php if ( $items ) : ?>
				<ul class="dpi-featured-links__list">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$featured_link = $item['link'];
						$target        = ! empty( $featured_link['target'] ) ? (string) $featured_link['target'] : '_self';
						$rel           = '_blank' === $target ? 'noopener noreferrer' : '';
						?>
						<li class="dpi-featured-links__item">
							<a href="<?php echo esc_url( $featured_link['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>"
							<?php
							if ( $rel ) :
								?>
								rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>>
								<?php if ( $item['icon'] && function_exists( 'dpi_blocks_render_icon' ) ) : ?>
									<span class="dpi-featured-links__icon" aria-hidden="true"><?php echo dpi_blocks_render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the plugin-owned icon renderer. ?></span>
								<?php endif; ?>
								<span class="dpi-featured-links__label"><?php echo esc_html( $featured_link['title'] ); ?></span>
								<?php if ( $item['excerpt'] ) : ?>
									<span class="dpi-featured-links__excerpt"><?php echo wp_kses_post( $item['excerpt'] ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
