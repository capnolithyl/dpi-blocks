<?php
/**
 * Interior Hero block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow          = function_exists( 'get_field' ) ? trim( (string) get_field( 'eyebrow' ) ) : '';
$heading          = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$intro            = function_exists( 'get_field' ) ? trim( (string) get_field( 'intro' ) ) : '';
$background       = function_exists( 'get_field' ) ? get_field( 'background_image' ) : null;
$show_breadcrumbs = function_exists( 'get_field' ) ? (bool) get_field( 'show_breadcrumbs' ) : false;
$alignment        = function_exists( 'get_field' ) ? (string) get_field( 'content_alignment' ) : 'center';
$alignment        = in_array( $alignment, array( 'left', 'center' ), true ) ? $alignment : 'center';
$image_id         = is_array( $background ) ? absint( $background['ID'] ?? $background['id'] ?? 0 ) : absint( $background );
$current_post_id  = absint( $post_id ?? 0 );

if ( ! $current_post_id ) {
	$current_post_id = get_the_ID();
}

if ( ! $image_id && $current_post_id ) {
	$image_id = get_post_thumbnail_id( $current_post_id );
}

/**
 * Filter the image used by the Interior Hero after the block image and current
 * post featured image have been considered. Themes may use this to provide a
 * site-wide fallback image without making the reusable plugin own site data.
 *
 * @param int $image_id Current attachment ID.
 * @param int $current_post_id Current post ID.
 */
$image_id = absint( apply_filters( 'dpi_blocks/interior_hero_image_id', $image_id, $current_post_id ) );

if ( ! $heading && $current_post_id ) {
	$heading = trim( (string) get_the_title( $current_post_id ) );
}

$breadcrumbs = array();
if ( $show_breadcrumbs && $current_post_id ) {
	$breadcrumbs[] = array(
		'label' => __( 'Home', 'dpi-blocks' ),
		'url'   => home_url( '/' ),
	);

	$ancestors = array_reverse( get_post_ancestors( $current_post_id ) );
	foreach ( $ancestors as $ancestor_id ) {
		$breadcrumbs[] = array(
			'label' => get_the_title( $ancestor_id ),
			'url'   => get_permalink( $ancestor_id ),
		);
	}

	$breadcrumbs[] = array(
		'label' => $heading ?: get_the_title( $current_post_id ),
		'url'   => '',
	);

	/**
	 * Filter the breadcrumb items rendered by the Interior Hero block.
	 *
	 * @param array<int,array{label:string,url:string}> $breadcrumbs Breadcrumb items.
	 * @param int                                       $current_post_id Current post ID.
	 */
	$breadcrumbs = apply_filters( 'dpi_blocks/interior_hero_breadcrumbs', $breadcrumbs, $current_post_id );
	$breadcrumbs = is_array( $breadcrumbs ) ? $breadcrumbs : array();
}

if ( ! $eyebrow && ! $heading && ! $intro && ! $image_id && ! $breadcrumbs ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-interior-hero dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add an eyebrow, heading, intro, or background image.', 'dpi-blocks' )
		);
	}
	return;
}

$heading_id         = $heading ? wp_unique_id( 'dpi-interior-hero-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-interior-hero dpi-interior-hero--align-' . $alignment,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>>
	<?php if ( $image_id ) : ?>
		<div class="dpi-interior-hero__media" aria-hidden="true">
			<?php echo wp_get_attachment_image( $image_id, 'full', false, array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<div class="dpi-interior-hero__overlay" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="dpi-interior-hero__content">
		<?php if ( $breadcrumbs ) : ?>
			<nav class="dpi-interior-hero__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'dpi-blocks' ); ?>">
				<ol>
					<?php foreach ( $breadcrumbs as $item ) : ?>
						<?php
						$label = is_array( $item ) ? trim( (string) ( $item['label'] ?? '' ) ) : '';
						$url   = is_array( $item ) ? trim( (string) ( $item['url'] ?? '' ) ) : '';
						if ( ! $label ) {
							continue;
						}
						?>
						<li>
							<?php if ( $url ) : ?>
								<?php
								$breadcrumb_link = array( 'url' => $url, 'title' => $label, 'target' => '' );
								echo dpi_blocks_link_open( $breadcrumb_link, 'dpi-interior-hero__breadcrumb-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo esc_html( $label );
								echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							<?php else : ?>
								<span aria-current="page"><?php echo esc_html( $label ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>

		<?php if ( $eyebrow ) : ?>
			<p class="dpi-interior-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<?php if ( $heading ) : ?>
			<h1 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-interior-hero__heading"><?php echo esc_html( $heading ); ?></h1>
		<?php endif; ?>
		<?php if ( $intro ) : ?>
			<p class="dpi-interior-hero__intro"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>
	</div>
</section>
