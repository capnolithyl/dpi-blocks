<?php
/**
 * Office Grid block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow        = function_exists( 'get_field' ) ? trim( (string) get_field( 'eyebrow' ) ) : '';
$heading        = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$intro          = function_exists( 'get_field' ) ? trim( (string) get_field( 'intro' ) ) : '';
$query_mode     = function_exists( 'get_field' ) ? (string) get_field( 'query_mode' ) : 'automatic';
$selected       = function_exists( 'get_field' ) ? get_field( 'selected_offices' ) : array();
$groups         = function_exists( 'get_field' ) ? get_field( 'office_groups' ) : array();
$item_count     = function_exists( 'get_field' ) ? absint( get_field( 'item_count' ) ) : 6;
$orderby        = function_exists( 'get_field' ) ? (string) get_field( 'orderby' ) : 'menu_order';
$order          = function_exists( 'get_field' ) ? (string) get_field( 'order' ) : 'ASC';
$show_image     = function_exists( 'get_field' ) ? (bool) get_field( 'show_image' ) : true;
$show_excerpt   = function_exists( 'get_field' ) ? (bool) get_field( 'show_excerpt' ) : true;
$link_label     = function_exists( 'get_field' ) ? trim( (string) get_field( 'link_label' ) ) : '';
$query_mode     = in_array( $query_mode, array( 'automatic', 'manual' ), true ) ? $query_mode : 'automatic';
$orderby        = in_array( $orderby, array( 'menu_order', 'title', 'date' ), true ) ? $orderby : 'menu_order';
$order          = 'DESC' === strtoupper( $order ) ? 'DESC' : 'ASC';
$item_count     = max( 1, min( 24, $item_count ?: 6 ) );
$link_label     = $link_label ?: __( 'Learn More', 'dpi-blocks' );
$selected       = is_array( $selected ) ? $selected : array();
$groups         = is_array( $groups ) ? $groups : ( $groups ? array( $groups ) : array() );

if ( ! post_type_exists( 'office' ) ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-office-grid dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Enable or register the Office post type to use this block.', 'dpi-blocks' )
		);
	}
	return;
}

$args = array(
	'post_type'           => 'office',
	'post_status'         => 'publish',
	'posts_per_page'      => $item_count,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => $orderby,
	'order'               => $order,
);

if ( 'menu_order' === $orderby ) {
	$args['orderby'] = array(
		'menu_order' => $order,
		'title'      => 'ASC',
	);
}

if ( 'manual' === $query_mode ) {
	$ids = array();
	foreach ( $selected as $office ) {
		$ids[] = $office instanceof WP_Post ? $office->ID : absint( $office );
	}
	$ids = array_values( array_filter( array_unique( $ids ) ) );
	if ( $ids ) {
		$args['post__in']       = $ids;
		$args['posts_per_page'] = count( $ids );
		$args['orderby']        = 'post__in';
	} else {
		$args['post__in'] = array( 0 );
	}
} elseif ( $groups && taxonomy_exists( 'office_group' ) ) {
	$term_ids = array_values( array_filter( array_map( 'absint', $groups ) ) );
	if ( $term_ids ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'office_group',
				'field'    => 'term_id',
				'terms'    => $term_ids,
			),
		);
	}
}

/**
 * Filter the Office Grid query arguments for project-specific relationships or rules.
 *
 * @param array<string,mixed> $args Query arguments.
 * @param array<string,mixed> $context Block query context.
 */
$args = apply_filters(
	'dpi_blocks/office_grid_query_args',
	$args,
	array(
		'query_mode' => $query_mode,
		'groups'     => $groups,
		'block'      => $block ?? array(),
	)
);
$args  = is_array( $args ) ? $args : array();
$query = new WP_Query( $args );

if ( ! $eyebrow && ! $heading && ! $intro && ! $query->have_posts() ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-office-grid dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'No offices match this block configuration.', 'dpi-blocks' )
		);
	}
	wp_reset_postdata();
	return;
}

$heading_id         = $heading ? wp_unique_id( 'dpi-office-grid-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-office-grid' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $heading_id ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; ?>>
	<?php if ( $eyebrow || $heading || $intro ) : ?>
		<header class="dpi-office-grid__header">
			<?php if ( $eyebrow ) : ?><p class="dpi-office-grid__eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<?php if ( $heading ) : ?><h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-office-grid__heading"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
			<?php if ( $intro ) : ?><p class="dpi-office-grid__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
		</header>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
		<div class="dpi-office-grid__items">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<?php
				$office_id = get_the_ID();
				$excerpt   = trim( (string) get_the_excerpt( $office_id ) );
				if ( ! $excerpt ) {
					$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $office_id ) ) ), 28 );
				}
				?>
				<article class="dpi-office-grid__item">
					<?php
					$office_link = array(
						'url'    => get_permalink( $office_id ),
						'title'  => get_the_title( $office_id ),
						'target' => '',
					);
					?>
					<?php if ( $show_image && has_post_thumbnail( $office_id ) ) : ?>
						<?php echo dpi_blocks_link_open( $office_link, 'dpi-office-grid__media', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo get_the_post_thumbnail( $office_id, 'medium_large', array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<div class="dpi-office-grid__content">
						<h3 class="dpi-office-grid__title"><?php echo dpi_blocks_link_open( $office_link, 'dpi-office-grid__title-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php the_title(); ?><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
						<?php if ( $show_excerpt && $excerpt ) : ?><p class="dpi-office-grid__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
						<?php echo dpi_blocks_link_open( $office_link, 'dpi-office-grid__link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $link_label ); ?><?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php endif; ?>
</section>
<?php
wp_reset_postdata();
