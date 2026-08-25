<?php
/** Plugin fallback for individual Staff profiles. */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="dpi-directory dpi-directory--single">
	<?php while ( have_posts() ) : ?>
		<?php
		the_post();
		$dpi_staff_id       = get_the_ID();
		$dpi_staff_position = dpi_blocks_get_staff_field( $dpi_staff_id, 'position' );
		$dpi_staff_email    = sanitize_email( dpi_blocks_get_staff_field( $dpi_staff_id, 'email' ) );
		$dpi_staff_phone    = dpi_blocks_get_staff_field( $dpi_staff_id, 'phone' );
		$dpi_staff_number   = $dpi_staff_phone ? preg_replace( '/[^0-9+]/', '', $dpi_staff_phone ) : '';
		$dpi_staff_tel      = $dpi_staff_number ? 'tel:' . $dpi_staff_number : '';
		$dpi_staff_archive  = get_post_type_archive_link( 'staff' );
		?>
		<article <?php post_class( 'dpi-directory-single' ); ?>>
			<header class="dpi-directory-single__header">
				<div class="dpi-directory-single__header-inner">
					<?php the_title( '<h1 class="dpi-directory-single__title">', '</h1>' ); ?>
					<?php
					if ( $dpi_staff_position ) :
						?>
						<p class="dpi-directory-single__subtitle"><?php echo esc_html( $dpi_staff_position ); ?></p><?php endif; ?>
				</div>
			</header>
			<div class="dpi-directory-single__body">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="dpi-directory-single__media"><?php the_post_thumbnail( 'large', array( 'class' => 'dpi-directory-single__image' ) ); ?></div>
				<?php endif; ?>
				<div class="dpi-directory-single__content">
					<?php if ( $dpi_staff_email || $dpi_staff_tel ) : ?>
						<ul class="dpi-directory-single__contact">
							<?php
							if ( $dpi_staff_email ) :
								?>
								<li><a href="<?php echo esc_url( 'mailto:' . $dpi_staff_email ); ?>"><?php echo esc_html( $dpi_staff_email ); ?></a></li><?php endif; ?>
							<?php
							if ( $dpi_staff_tel ) :
								?>
								<li><a href="<?php echo esc_url( $dpi_staff_tel ); ?>"><?php echo esc_html( $dpi_staff_phone ); ?></a></li><?php endif; ?>
						</ul>
					<?php endif; ?>
					<div class="dpi-directory-single__prose"><?php the_content(); ?></div>
					<?php
					if ( $dpi_staff_archive ) :
						?>
						<p><a href="<?php echo esc_url( $dpi_staff_archive ); ?>"><?php esc_html_e( 'Back to Staff', 'dpi-blocks' ); ?></a></p><?php endif; ?>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
