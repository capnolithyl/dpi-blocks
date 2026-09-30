<?php
/**
 * Mission block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image      = function_exists( 'get_field' ) ? get_field( 'image' ) : null;
$heading    = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$subheading = function_exists( 'get_field' ) ? trim( (string) get_field( 'subheading' ) ) : '';
$text       = function_exists( 'get_field' ) ? (string) get_field( 'text' ) : '';
$buttons    = function_exists( 'get_field' ) ? get_field( 'buttons' ) : array();
$layout     = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'media-left';
$layout     = in_array( $layout, array( 'media-left', 'media-right', 'stacked' ), true ) ? $layout : 'media-left';
$image_id   = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );
$buttons    = is_array( $buttons ) ? $buttons : array();

if ( ! $image_id && ! $heading && ! $subheading && ! $text && ! $buttons ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-mission dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add mission content.', 'dpi-blocks' )
		);
	}

	return;
}

$heading_id         = $subheading || $heading ? wp_unique_id( 'dpi-mission-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-mission dpi-mission--' . $layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading_id ) :
	?>
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"<?php endif; ?>>
	<div class="dpi-mission__inner">
		<?php if ( $image_id ) : ?>
			<figure class="dpi-mission__media"><?php echo wp_get_attachment_image( $image_id, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
		<?php endif; ?>

		<div class="dpi-mission__content">
			<?php if ( $heading && $subheading ) : ?>
				<p class="dpi-mission__eyebrow"><?php echo esc_html( $heading ); ?></p>
			<?php endif; ?>
			<?php if ( $subheading ) : ?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $subheading ); ?></h2>
			<?php elseif ( $heading ) : ?>
				<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-mission__eyebrow"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php
			if ( $text ) :
				?>
				<div class="dpi-mission__text"><?php echo wp_kses_post( wpautop( $text ) ); ?></div><?php endif; ?>
			<?php if ( $buttons ) : ?>
				<div class="dpi-mission__actions">
					<?php foreach ( $buttons as $row ) : ?>
						<?php $action_link = is_array( $row ) ? ( $row['link'] ?? null ) : null; ?>
						<?php
						if ( is_array( $action_link ) && function_exists( 'dpi_blocks_render_link' ) ) :
							?>
							<?php echo wp_kses_post( dpi_blocks_render_link( $action_link, 'dpi-button', ! empty( $is_preview ) ) ); ?><?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
