<?php
/**
 * Staff card block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$staff_value = function_exists( 'get_field' ) ? get_field( 'staff_member' ) : null;
$staff_id    = $staff_value instanceof WP_Post ? $staff_value->ID : absint( $staff_value );

if ( ! $staff_id || 'staff' !== get_post_type( $staff_id ) ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-staff-card dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Choose a staff member.', 'dpi-blocks' )
		);
	}

	return;
}

$get_bool = static function ( $name, $fallback ) {
	if ( ! function_exists( 'get_field' ) ) {
		return (bool) $fallback;
	}

	$value = get_field( $name );

	return null === $value || '' === $value ? (bool) $fallback : (bool) $value;
};

$layout             = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'vertical';
$layout             = in_array( $layout, array( 'vertical', 'horizontal' ), true ) ? $layout : 'vertical';
$interaction_mode   = function_exists( 'get_field' ) ? (string) get_field( 'interaction_mode' ) : 'inherit';
$interaction_mode   = in_array( $interaction_mode, array( 'inherit', 'single', 'modal' ), true ) ? $interaction_mode : 'inherit';
$interaction_mode   = 'inherit' === $interaction_mode && function_exists( 'dpi_blocks_get_staff_mode' ) ? (string) dpi_blocks_get_staff_mode() : $interaction_mode;
$interaction_mode   = in_array( $interaction_mode, array( 'single', 'modal' ), true ) ? $interaction_mode : 'single';
$name               = get_the_title( $staff_id );
$field              = static function ( $field_name ) use ( $staff_id ) {
	return function_exists( 'dpi_blocks_get_staff_field' ) ? trim( (string) dpi_blocks_get_staff_field( $staff_id, $field_name ) ) : '';
};
$position           = $get_bool( 'show_position', true ) ? $field( 'position' ) : '';
$email              = $get_bool( 'show_email', true ) ? sanitize_email( $field( 'email' ) ) : '';
$phone              = $get_bool( 'show_phone', true ) ? $field( 'phone' ) : '';
$phone_uri          = $phone ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) : '';
$bio                = (string) get_post_field( 'post_content', $staff_id );
$url                = function_exists( 'dpi_blocks_staff_url' ) ? dpi_blocks_staff_url( $staff_id, $interaction_mode ) : get_permalink( $staff_id );
$dialog_id          = wp_unique_id( 'dpi-staff-dialog-' . $staff_id . '-' );
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-staff-card dpi-staff-card--' . $layout,
	)
);
?>
<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( has_post_thumbnail( $staff_id ) ) : ?>
		<figure class="dpi-staff-card__media"><?php echo get_the_post_thumbnail( $staff_id, 'large', array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
	<?php endif; ?>

	<div class="dpi-staff-card__content">
		<h3><?php echo esc_html( $name ); ?></h3>
		<?php
		if ( $position ) :
			?>
			<p class="dpi-staff-card__position"><?php echo esc_html( $position ); ?></p><?php endif; ?>
		<?php if ( $email || ( $phone && $phone_uri ) ) : ?>
			<address class="dpi-staff-card__contact">
				<?php
				if ( $email ) :
					?>
					<?php
					$email_link = array( 'url' => 'mailto:' . $email, 'title' => $email, 'target' => '' );
					echo dpi_blocks_link_open( $email_link, 'dpi-staff-card__contact-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( function_exists( 'dpi_blocks_render_icon' ) ) :
						?>
					<span aria-hidden="true"><?php echo dpi_blocks_render_icon( 'solid:envelope', array( 'aria-hidden' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the plugin-owned icon renderer. ?></span><?php endif; ?><span><?php echo esc_html( $email ); ?></span><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
				<?php
				if ( $phone && $phone_uri ) :
					?>
					<?php
					$phone_link = array( 'url' => $phone_uri, 'title' => $phone, 'target' => '' );
					echo dpi_blocks_link_open( $phone_link, 'dpi-staff-card__contact-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					if ( function_exists( 'dpi_blocks_render_icon' ) ) :
						?>
					<span aria-hidden="true"><?php echo dpi_blocks_render_icon( 'solid:phone', array( 'aria-hidden' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the plugin-owned icon renderer. ?></span><?php endif; ?><span><?php echo esc_html( $phone ); ?></span><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
			</address>
		<?php endif; ?>

		<?php if ( $url || ( 'modal' === $interaction_mode && $bio ) ) : ?>
			<?php
			$bio_link = array(
				'url'    => $url ? $url : '#' . $dialog_id,
				'title'  => __( 'View biography', 'dpi-blocks' ),
				'target' => '',
			);
			$bio_attrs = array();
			if ( 'modal' === $interaction_mode && $bio && empty( $is_preview ) ) {
				$bio_attrs = array(
					'aria-controls'       => $dialog_id,
					'aria-haspopup'        => 'dialog',
					'data-dpi-dialog-open' => 'true',
				);
			}
			echo dpi_blocks_link_open( $bio_link, 'dpi-staff-card__bio-link', ! empty( $is_preview ), $bio_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html_e( 'View biography', 'dpi-blocks' );
			echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		<?php endif; ?>
	</div>

	<?php if ( 'modal' === $interaction_mode && $bio ) : ?>
		<dialog id="<?php echo esc_attr( $dialog_id ); ?>" class="dpi-staff-card__dialog" aria-labelledby="<?php echo esc_attr( $dialog_id . '-title' ); ?>" data-dpi-dialog>
			<div class="dpi-staff-card__dialog-inner">
				<button type="button" class="dpi-staff-card__dialog-close" aria-label="<?php esc_attr_e( 'Close biography', 'dpi-blocks' ); ?>" data-dpi-dialog-close>&times;</button>
				<h2 id="<?php echo esc_attr( $dialog_id . '-title' ); ?>"><?php echo esc_html( $name ); ?></h2>
				<?php
				if ( $position ) :
					?>
					<p><?php echo esc_html( $position ); ?></p><?php endif; ?>
				<div class="dpi-staff-card__biography"><?php echo apply_filters( 'the_content', $bio ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standard filtered post content. ?></div>
			</div>
		</dialog>
	<?php endif; ?>
</article>
