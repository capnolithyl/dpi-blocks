<?php
/**
 * Community slider block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_get_field = static function ( $name, $fallback = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	$value = get_field( $name );

	return null === $value || '' === $value ? $fallback : $value;
};

$dpi_get_bool = static function ( $name, $fallback ) use ( $dpi_get_field ) {
	$value = $dpi_get_field( $name, null );

	return null === $value ? (bool) $fallback : (bool) $value;
};

$heading        = trim( (string) $dpi_get_field( 'heading', '' ) );
$text           = (string) $dpi_get_field( 'text', '' );
$category_value   = $dpi_get_field( 'categories', array() );
$category_display = (string) $dpi_get_field( 'category_display', 'tabs' );
$category_display = in_array( $category_display, array( 'combined', 'tabs' ), true ) ? $category_display : 'tabs';
$layout           = (string) $dpi_get_field( 'layout', 'carousel' );
$layout         = in_array( $layout, array( 'carousel', 'grid' ), true ) ? $layout : 'carousel';
$posts_per_page = max( 1, min( 20, absint( $dpi_get_field( 'posts_per_page', 5 ) ) ) );
$show_date       = $dpi_get_bool( 'show_date', true );
$cta             = $dpi_get_field( 'cta', array() );
$cta             = is_array( $cta ) ? $cta : array();
$background_image = $dpi_get_field( 'background_image', array() );
$background_url   = is_array( $background_image ) && ! empty( $background_image['url'] )
	? (string) $background_image['url']
	: '';
$dpi_order      = strtoupper( (string) $dpi_get_field( 'order', 'DESC' ) );
$dpi_order      = in_array( $dpi_order, array( 'ASC', 'DESC' ), true ) ? $dpi_order : 'DESC';
$dpi_orderby    = (string) $dpi_get_field( 'orderby', 'date' );
$dpi_orderby    = in_array( $dpi_orderby, array( 'date', 'title', 'menu_order', 'rand' ), true ) ? $dpi_orderby : 'date';

$categories = array();

foreach ( (array) $category_value as $category ) {
	if ( $category instanceof WP_Term ) {
		$dpi_term = $category;
	} else {
		$dpi_term = get_term( absint( $category ), 'category' );
	}

	if ( $dpi_term instanceof WP_Term ) {
		$categories[ $dpi_term->term_id ] = $dpi_term;
	}
}

if ( empty( $categories ) ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-community-slider dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Select at least one post category.', 'dpi-blocks' )
		);
	}

	return;
}

$instance_id = wp_unique_id( 'dpi-community-slider-' );
$slick       = array(
	'arrows'         => $dpi_get_bool( 'show_arrows', true ),
	'dots'           => $dpi_get_bool( 'show_dots', false ),
	'infinite'       => $dpi_get_bool( 'infinite', true ),
	'autoplay'       => $dpi_get_bool( 'autoplay', false ),
	'autoplaySpeed'  => max( 1000, absint( $dpi_get_field( 'autoplay_delay', 5000 ) ) ),
	'speed'          => max( 0, absint( $dpi_get_field( 'transition_speed', 500 ) ) ),
	'slidesToShow'   => max( 1, min( 6, absint( $dpi_get_field( 'slides_mobile', 1 ) ) ) ),
	'slidesToScroll' => 1,
	'mobileFirst'    => true,
	'rows'           => 0,
	'responsive'     => array(
		array(
			'breakpoint' => 768,
			'settings'   => array(
				'slidesToShow' => max( 1, min( 6, absint( $dpi_get_field( 'slides_tablet', 2 ) ) ) ),
			),
		),
		array(
			'breakpoint' => 1024,
			'settings'   => array(
				'slidesToShow' => max( 1, min( 6, absint( $dpi_get_field( 'slides_desktop', 3 ) ) ) ),
			),
		),
	),
);

$wrapper_args = array(
	'class' => 'dpi-block dpi-community-slider dpi-community-slider--' . $layout,
);

if ( $background_url ) {
	$wrapper_args['style'] = sprintf(
		'--dpi-community-slider-background-image: url("%s");',
		esc_url( $background_url )
	);
}

$dpi_category_cta = static function ( WP_Term $category ) {
	$url = get_category_link( $category->term_id );

	if ( is_wp_error( $url ) || ! $url ) {
		return array();
	}

	return array(
		'url'    => $url,
		'title'  => sprintf(
			/* translators: %s: category name. */
			__( 'More %s', 'dpi-blocks' ),
			$category->name
		),
		'target' => '',
	);
};

$dpi_render_footer_cta = static function ( array $link ) use ( $is_preview ) {
	if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
		return;
	}
	?>
	<div class="dpi-community-slider__footer">
		<?php
		echo dpi_blocks_link_open( $link, 'dpi-community-slider__cta dpi-button', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo esc_html( $link['title'] );
		echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
	<?php
};

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading ) :
	?>
	aria-labelledby="<?php echo esc_attr( $instance_id . '-heading' ); ?>"<?php endif; ?>>
	<?php if ( $heading || $text ) : ?>
		<header class="dpi-community-slider__header">
			<?php if ( $heading ) : ?>
				<h2 id="<?php echo esc_attr( $instance_id . '-heading' ); ?>"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<div class="dpi-community-slider__intro"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<?php if ( 'tabs' === $category_display && count( $categories ) > 1 ) : ?>
		<div class="dpi-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Post categories', 'dpi-blocks' ); ?>" data-dpi-tabs>
			<?php foreach ( array_values( $categories ) as $index => $category ) : ?>
				<?php $tab_id = $instance_id . '-tab-' . $category->term_id; ?>
				<button id="<?php echo esc_attr( $tab_id ); ?>" type="button" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $instance_id . '-panel-' . $category->term_id ); ?>" tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>" data-dpi-tab>
					<?php echo esc_html( $category->name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="dpi-community-slider__panels">
		<?php if ( 'combined' === $category_display || 1 === count( $categories ) ) : ?>
			<?php
			$community_posts = get_posts(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $posts_per_page,
					'category__in'        => array_keys( $categories ),
					'ignore_sticky_posts' => true,
					'orderby'             => $dpi_orderby,
					'order'               => $dpi_order,
					'no_found_rows'       => true,
				)
			);
			?>
			<div class="dpi-community-slider__panel">
				<?php if ( $community_posts ) : ?>
					<div
						class="dpi-community-slider__items"
						<?php if ( 'carousel' === $layout && count( $community_posts ) > 1 ) : ?>
							data-dpi-slick="<?php echo esc_attr( wp_json_encode( $slick ) ); ?>"
						<?php endif; ?>
					>
						<?php foreach ( $community_posts as $community_post ) : ?>
							<?php include __DIR__ . '/partials/card.php'; ?>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'No posts are available in the selected categories yet.', 'dpi-blocks' ); ?></p>
				<?php endif; ?>

				<?php
				if ( empty( $cta['url'] ) && 1 === count( $categories ) ) {
					$single_category = reset( $categories );
					if ( $single_category instanceof WP_Term ) {
						$dpi_render_footer_cta( $dpi_category_cta( $single_category ) );
					}
				}
				?>
			</div>
		<?php else : ?>
			<?php foreach ( array_values( $categories ) as $index => $category ) : ?>
				<?php
				$community_posts = get_posts(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => $posts_per_page,
						'category__in'        => array( $category->term_id ),
						'ignore_sticky_posts' => true,
						'orderby'             => $dpi_orderby,
						'order'               => $dpi_order,
						'no_found_rows'       => true,
					)
				);
				$panel_id = $instance_id . '-panel-' . $category->term_id;
				$tab_id   = $instance_id . '-tab-' . $category->term_id;
				?>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="dpi-community-slider__panel"
					role="tabpanel"
					aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
					<?php if ( 0 !== $index ) : ?>
						hidden
					<?php endif; ?>
					data-dpi-tab-panel
				>
					<?php if ( $community_posts ) : ?>
						<div
							class="dpi-community-slider__items"
							<?php if ( 'carousel' === $layout && count( $community_posts ) > 1 ) : ?>
								data-dpi-slick="<?php echo esc_attr( wp_json_encode( $slick ) ); ?>"
							<?php endif; ?>
						>
							<?php foreach ( $community_posts as $community_post ) : ?>
								<?php include __DIR__ . '/partials/card.php'; ?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p><?php esc_html_e( 'No posts are available in this category yet.', 'dpi-blocks' ); ?></p>
					<?php endif; ?>

					<?php
					if ( empty( $cta['url'] ) ) {
						$dpi_render_footer_cta( $dpi_category_cta( $category ) );
					}
					?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php
	if ( ! empty( $cta['url'] ) && ! empty( $cta['title'] ) ) {
		$dpi_render_footer_cta( $cta );
	}
	?>
</section>
