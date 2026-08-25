<?php
/** Shared Staff/Ministry/Office archive card. */

defined( 'ABSPATH' ) || exit;

$dpi_card_id       = $dpi_card_post->ID;
$dpi_card_url      = get_permalink( $dpi_card_id );
$dpi_card_is_staff = 'staff' === $dpi_directory['post_type'];
$dpi_dialog_id     = 'dpi-staff-dialog-' . $dpi_card_id;
$dpi_position      = $dpi_card_is_staff ? dpi_blocks_get_staff_field( $dpi_card_id, 'position' ) : '';
$dpi_email         = $dpi_card_is_staff ? sanitize_email( dpi_blocks_get_staff_field( $dpi_card_id, 'email' ) ) : '';
$dpi_phone         = $dpi_card_is_staff ? dpi_blocks_get_staff_field( $dpi_card_id, 'phone' ) : '';
$dpi_phone_number  = $dpi_phone ? preg_replace( '/[^0-9+]/', '', $dpi_phone ) : '';
$dpi_phone_href    = $dpi_phone_number ? 'tel:' . $dpi_phone_number : '';
$dpi_modal_attrs   = $dpi_directory['modal']
	? sprintf( ' aria-controls="%s" aria-haspopup="dialog" data-dpi-staff-modal-open', esc_attr( $dpi_dialog_id ) )
	: '';
?>
<article <?php post_class( 'dpi-directory-card', $dpi_card_id ); ?>>
	<?php if ( has_post_thumbnail( $dpi_card_id ) ) : ?>
		<a class="dpi-directory-card__media" href="<?php echo esc_url( $dpi_card_url ); ?>"<?php echo $dpi_modal_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed above from fixed attributes and an escaped ID. ?>>
			<?php
			echo get_the_post_thumbnail(
				$dpi_card_id,
				'large',
				array(
					'class'   => 'dpi-directory-card__image',
					'loading' => 'lazy',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated attachment markup.
			?>
		</a>
	<?php endif; ?>
	<div class="dpi-directory-card__content">
		<h3 class="dpi-directory-card__title"><a href="<?php echo esc_url( $dpi_card_url ); ?>"<?php echo $dpi_modal_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed above from fixed attributes and an escaped ID. ?>><?php echo esc_html( get_the_title( $dpi_card_id ) ); ?></a></h3>
		<?php if ( $dpi_position ) : ?>
			<p class="dpi-directory-card__position"><?php echo esc_html( $dpi_position ); ?></p>
		<?php endif; ?>
		<?php if ( ! $dpi_card_is_staff && has_excerpt( $dpi_card_id ) ) : ?>
			<div class="dpi-directory-card__excerpt"><?php echo wp_kses_post( wpautop( get_the_excerpt( $dpi_card_id ) ) ); ?></div>
		<?php endif; ?>
		<?php if ( $dpi_email || $dpi_phone_href ) : ?>
			<ul class="dpi-directory-card__contact">
				<?php if ( $dpi_email ) : ?>
					<li><a href="<?php echo esc_url( 'mailto:' . $dpi_email ); ?>"><?php echo esc_html( $dpi_email ); ?></a></li>
				<?php endif; ?>
				<?php if ( $dpi_phone_href ) : ?>
					<li><a href="<?php echo esc_url( $dpi_phone_href ); ?>"><?php echo esc_html( $dpi_phone ); ?></a></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
		<a class="dpi-directory-card__more" href="<?php echo esc_url( $dpi_card_url ); ?>"<?php echo $dpi_modal_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed above from fixed attributes and an escaped ID. ?>><?php echo esc_html( $dpi_card_is_staff && $dpi_directory['modal'] ? __( 'View bio', 'dpi-blocks' ) : __( 'View details', 'dpi-blocks' ) ); ?></a>
	</div>
</article>
