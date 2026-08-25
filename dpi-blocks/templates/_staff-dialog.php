<?php
/** Progressive-enhancement biography dialog. */

defined( 'ABSPATH' ) || exit;

$dpi_dialog_id       = 'dpi-staff-dialog-' . $dpi_dialog_post->ID;
$dpi_dialog_title    = $dpi_dialog_id . '-title';
$dpi_dialog_url      = get_permalink( $dpi_dialog_post );
$dpi_dialog_content  = apply_filters( 'the_content', $dpi_dialog_post->post_content );
$dpi_dialog_position = dpi_blocks_get_staff_field( $dpi_dialog_post->ID, 'position' );
$dpi_dialog_email    = sanitize_email( dpi_blocks_get_staff_field( $dpi_dialog_post->ID, 'email' ) );
$dpi_dialog_phone    = dpi_blocks_get_staff_field( $dpi_dialog_post->ID, 'phone' );
$dpi_dialog_number   = $dpi_dialog_phone ? preg_replace( '/[^0-9+]/', '', $dpi_dialog_phone ) : '';
$dpi_dialog_tel      = $dpi_dialog_number ? 'tel:' . $dpi_dialog_number : '';
?>
<dialog id="<?php echo esc_attr( $dpi_dialog_id ); ?>" class="dpi-staff-dialog" aria-labelledby="<?php echo esc_attr( $dpi_dialog_title ); ?>" data-dpi-staff-dialog>
	<div class="dpi-staff-dialog__inner">
		<?php /* translators: %s: staff member name. */ ?>
		<button class="dpi-staff-dialog__close" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Close biography for %s', 'dpi-blocks' ), get_the_title( $dpi_dialog_post ) ) ); ?>" data-dpi-staff-modal-close>&times;</button>
		<div class="dpi-staff-dialog__profile">
			<?php if ( has_post_thumbnail( $dpi_dialog_post ) ) : ?>
				<?php
				echo get_the_post_thumbnail(
					$dpi_dialog_post,
					'large',
					array(
						'class'   => 'dpi-staff-dialog__image',
						'loading' => 'lazy',
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup.
				?>
			<?php endif; ?>
			<h2 id="<?php echo esc_attr( $dpi_dialog_title ); ?>"><?php echo esc_html( get_the_title( $dpi_dialog_post ) ); ?></h2>
			<?php if ( $dpi_dialog_position ) : ?>
				<p><?php echo esc_html( $dpi_dialog_position ); ?></p>
			<?php endif; ?>
			<?php if ( $dpi_dialog_email ) : ?>
				<p><a href="<?php echo esc_url( 'mailto:' . $dpi_dialog_email ); ?>"><?php echo esc_html( $dpi_dialog_email ); ?></a></p>
			<?php endif; ?>
			<?php if ( $dpi_dialog_tel ) : ?>
				<p><a href="<?php echo esc_url( $dpi_dialog_tel ); ?>"><?php echo esc_html( $dpi_dialog_phone ); ?></a></p>
			<?php endif; ?>
		</div>
		<div class="dpi-staff-dialog__bio">
			<?php echo $dpi_dialog_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered post content. ?>
			<p><a href="<?php echo esc_url( $dpi_dialog_url ); ?>"><?php esc_html_e( 'View full profile', 'dpi-blocks' ); ?></a></p>
		</div>
	</div>
</dialog>
