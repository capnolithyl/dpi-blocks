<?php
/** Plugin fallback for individual Offices. */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="dpi-directory dpi-directory--single">
	<?php while ( have_posts() ) : ?>
		<?php
		the_post();
		$dpi_office_archive = get_post_type_archive_link( 'office' );
		?>
		<article <?php post_class( 'dpi-directory-single' ); ?>>
			<header class="dpi-directory-single__header">
				<div class="dpi-directory-single__header-inner"><?php the_title( '<h1 class="dpi-directory-single__title">', '</h1>' ); ?></div>
			</header>
			<div class="dpi-directory-single__body">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="dpi-directory-single__media"><?php the_post_thumbnail( 'large', array( 'class' => 'dpi-directory-single__image' ) ); ?></div>
				<?php endif; ?>
				<div class="dpi-directory-single__content">
					<div class="dpi-directory-single__prose"><?php the_content(); ?></div>
					<?php
					wp_link_pages(
						array(
							'before' => '<nav class="dpi-directory-single__pages" aria-label="' . esc_attr__( 'Office pages', 'dpi-blocks' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
					<?php if ( $dpi_office_archive ) : ?>
						<p><a href="<?php echo esc_url( $dpi_office_archive ); ?>"><?php esc_html_e( 'Back to Offices', 'dpi-blocks' ); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
