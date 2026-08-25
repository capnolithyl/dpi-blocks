<?php
/** Shared neutral directory layout. */

use DPI\Blocks\TemplateLoader;

defined( 'ABSPATH' ) || exit;

$dpi_archive_image = 0;
if ( 'ministry' === $dpi_directory['post_type'] && function_exists( 'get_field' ) ) {
	$dpi_archive_image = TemplateLoader::image_id( get_field( 'featured_image', 'dpi_ministry_settings' ) );
}
?>
<main id="primary" class="dpi-directory" data-dpi-directory data-dpi-directory-type="<?php echo esc_attr( $dpi_directory['post_type'] ); ?>">
	<header class="dpi-directory__header <?php echo $dpi_archive_image ? 'dpi-directory__header--image' : ''; ?>">
		<?php if ( $dpi_archive_image ) : ?>
			<?php
			echo wp_get_attachment_image(
				$dpi_archive_image,
				'full',
				false,
				array(
					'class'         => 'dpi-directory__header-image',
					'alt'           => '',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated attachment markup.
			?>
		<?php endif; ?>
		<div class="dpi-directory__header-inner">
			<h1 class="dpi-directory__title"><?php echo esc_html( $dpi_directory['title'] ); ?></h1>
			<?php if ( $dpi_directory['description'] ) : ?>
				<div class="dpi-directory__description"><?php echo wp_kses_post( $dpi_directory['description'] ); ?></div>
			<?php endif; ?>
		</div>
	</header>

	<div class="dpi-directory__body">
		<?php if ( count( $dpi_directory['sections'] ) > 1 ) : ?>
			<nav class="dpi-directory-nav" aria-label="<?php echo esc_attr( 'staff' === $dpi_directory['post_type'] ? __( 'Staff groups', 'dpi-blocks' ) : __( 'Ministry groups', 'dpi-blocks' ) ); ?>" data-dpi-group-nav>
				<button class="dpi-directory-nav__arrow" type="button" aria-label="<?php esc_attr_e( 'Previous groups', 'dpi-blocks' ); ?>" data-dpi-group-prev hidden><?php echo \DPI\Blocks\IconRegistry::render( 'solid:chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG registry. ?></button>
				<div class="dpi-directory-nav__track" data-dpi-group-slider>
					<?php foreach ( $dpi_directory['sections'] as $dpi_section_index => $dpi_section ) : ?>
						<a class="dpi-directory-nav__link" href="#<?php echo esc_attr( $dpi_section['id'] ); ?>" data-dpi-group-link <?php echo 0 === $dpi_section_index ? 'aria-current="location"' : ''; ?>><?php echo esc_html( $dpi_section['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
				<button class="dpi-directory-nav__arrow" type="button" aria-label="<?php esc_attr_e( 'Next groups', 'dpi-blocks' ); ?>" data-dpi-group-next hidden><?php echo \DPI\Blocks\IconRegistry::render( 'solid:chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin-owned SVG registry. ?></button>
			</nav>
		<?php endif; ?>

		<?php if ( $dpi_directory['sections'] ) : ?>
			<div class="dpi-directory__sections">
				<?php foreach ( $dpi_directory['sections'] as $dpi_section ) : ?>
					<section id="<?php echo esc_attr( $dpi_section['id'] ); ?>" class="dpi-directory-section" aria-labelledby="<?php echo esc_attr( $dpi_section['id'] . '-title' ); ?>">
						<h2 id="<?php echo esc_attr( $dpi_section['id'] . '-title' ); ?>" class="dpi-directory-section__title"><?php echo esc_html( $dpi_section['label'] ); ?></h2>
						<?php if ( $dpi_section['posts'] ) : ?>
							<div class="dpi-directory-grid">
								<?php foreach ( $dpi_section['posts'] as $dpi_card_post ) : ?>
									<?php require TemplateLoader::template_path( '_directory-card.php' ); ?>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p><?php esc_html_e( 'No entries are available in this group yet.', 'dpi-blocks' ); ?></p>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="dpi-directory__empty"><?php esc_html_e( 'No entries are available yet.', 'dpi-blocks' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $dpi_directory['modal'] ) : ?>
		<?php foreach ( $dpi_directory['all_posts'] as $dpi_dialog_post ) : ?>
			<?php require TemplateLoader::template_path( '_staff-dialog.php' ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
</main>
