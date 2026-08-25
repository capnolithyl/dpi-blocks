<?php
/**
 * Alternative image buttons block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$text    = function_exists( 'get_field' ) ? (string) get_field( 'text' ) : '';
$rows    = function_exists( 'get_field' ) ? get_field( 'image_buttons' ) : array();
$layout  = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'grid';
$layout  = in_array( $layout, array( 'grid', 'list' ), true ) ? $layout : 'grid';
$rows    = is_array( $rows ) ? $rows : array();
$items   = array();

foreach ( $rows as $row ) {
	$image_link = is_array( $row ) && isset( $row['link'] ) && is_array( $row['link'] ) ? $row['link'] : array();
	$image      = is_array( $row ) ? ( $row['image'] ?? null ) : null;
	$image_id   = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );

	if ( $image_id && ! empty( $image_link['url'] ) && ! empty( $image_link['title'] ) ) {
		$items[] = array(
			'image_id' => $image_id,
			'link'     => $image_link,
		);
	}
}

if ( ! $heading && ! $text && ! $items ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-image-buttons-alt dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add content and image links to this block.', 'dpi-blocks' )
		);
	}

	return;
}

$heading_id         = $heading ? wp_unique_id( 'dpi-image-buttons-alt-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-image-buttons-alt dpi-image-buttons-alt--' . $layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading_id ) :
	?>
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"<?php endif; ?>>
	<?php if ( $heading || $text ) : ?>
		<header class="dpi-image-buttons-alt__header">
			<?php
			if ( $heading ) :
				?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php
			if ( $text ) :
				?>
				<div><?php echo wp_kses_post( wpautop( $text ) ); ?></div><?php endif; ?>
		</header>
	<?php endif; ?>

	<?php if ( $items ) : ?>
		<ul class="dpi-image-buttons-alt__list">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$image_link = $item['link'];
				$target     = ! empty( $image_link['target'] ) ? (string) $image_link['target'] : '_self';
				$rel        = '_blank' === $target ? 'noopener noreferrer' : '';
				?>
				<li>
					<a class="dpi-image-buttons-alt__card" href="<?php echo esc_url( $image_link['url'] ); ?>" target="<?php echo esc_attr( $target ); ?>"
					<?php
					if ( $rel ) :
						?>
						rel="<?php echo esc_attr( $rel ); ?>"<?php endif; ?>>
						<figure><?php echo wp_get_attachment_image( $item['image_id'], 'large', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
						<span><?php echo esc_html( $image_link['title'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
