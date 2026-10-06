<?php
/**
 * Feature / CTA Banner block renderer.
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

$dpi_normalize_buttons = static function ( $rows ): array {
	$buttons = array();

	foreach ( (array) $rows as $row ) {
		$link = is_array( $row ) && isset( $row['link'] ) ? $row['link'] : $row;

		if ( ! is_array( $link ) || empty( $link['url'] ) || empty( $link['title'] ) ) {
			continue;
		}

		$buttons[] = $link;

		if ( 2 === count( $buttons ) ) {
			break;
		}
	}

	return $buttons;
};

$dpi_normalize_slide = static function ( $slide ) use ( $dpi_normalize_buttons ): array {
	$slide = is_array( $slide ) ? $slide : array();

	$image        = $slide['image'] ?? 0;
	$image_id     = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );
	$image_layout = isset( $slide['image_layout'] ) ? (string) $slide['image_layout'] : 'background';
	$variant      = isset( $slide['visual_variant'] ) ? (string) $slide['visual_variant'] : 'dark';
	$headline_el  = isset( $slide['headline_element'] ) ? (string) $slide['headline_element'] : 'heading';

	return array(
		'eyebrow'          => trim( (string) ( $slide['eyebrow'] ?? '' ) ),
		'headline'         => trim( (string) ( $slide['headline'] ?? '' ) ),
		'headline_element' => in_array( $headline_el, array( 'heading', 'quote' ), true ) ? $headline_el : 'heading',
		'content'          => (string) ( $slide['content'] ?? '' ),
		'image_id'         => $image_id,
		'image_layout'     => in_array( $image_layout, array( 'background', 'left', 'right' ), true ) ? $image_layout : 'background',
		'visual_variant'   => in_array( $variant, array( 'dark', 'light' ), true ) ? $variant : 'dark',
		'buttons'          => $dpi_normalize_buttons( $slide['buttons'] ?? array() ),
	);
};

$dpi_has_content = static function ( array $slide ): bool {
	return (bool) (
		$slide['eyebrow']
		|| $slide['headline']
		|| trim( wp_strip_all_tags( $slide['content'] ) )
		|| $slide['image_id']
		|| $slide['buttons']
	);
};

$raw_slides = $dpi_get_field( 'slides', array() );
$slides     = array();

foreach ( (array) $raw_slides as $raw_slide ) {
	$slide = $dpi_normalize_slide( $raw_slide );

	if ( $dpi_has_content( $slide ) ) {
		$slides[] = $slide;
	}
}

/**
 * Backward compatibility for Feature Banner blocks saved before the slider
 * field model was introduced. Old blocks remain visible until an editor saves
 * them using the new Slides repeater.
 */
if ( empty( $slides ) && ! empty( $block['data'] ) && is_array( $block['data'] ) ) {
	$legacy_data = $block['data'];
	$legacy_cta  = isset( $legacy_data['cta'] ) && is_array( $legacy_data['cta'] )
		? array( array( 'link' => $legacy_data['cta'] ) )
		: array();

	$legacy_slide = $dpi_normalize_slide(
		array(
			'eyebrow'          => $legacy_data['eyebrow'] ?? '',
			'headline'         => $legacy_data['headline'] ?? '',
			'headline_element' => $legacy_data['headline_element'] ?? 'heading',
			'content'          => $legacy_data['content'] ?? '',
			'image'            => $legacy_data['image'] ?? 0,
			'image_layout'     => $legacy_data['image_layout'] ?? 'background',
			'visual_variant'   => $legacy_data['visual_variant'] ?? 'dark',
			'buttons'          => $legacy_cta,
		)
	);

	if ( $dpi_has_content( $legacy_slide ) ) {
		$slides[] = $legacy_slide;
	}
}

if ( empty( $slides ) ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-feature-banner dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add at least one feature slide.', 'dpi-blocks' )
		);
	}

	return;
}

$slide_count = count( $slides );
$has_slider  = $slide_count > 1;
$slick       = array(
	'arrows'         => $dpi_get_bool( 'show_arrows', true ),
	'dots'           => $dpi_get_bool( 'show_dots', true ),
	'infinite'       => true,
	'autoplay'       => $dpi_get_bool( 'autoplay', false ),
	'autoplaySpeed'  => max( 1000, absint( $dpi_get_field( 'autoplay_speed', 6000 ) ) ),
	'speed'          => 500,
	'pauseOnHover'   => true,
	'pauseOnFocus'   => true,
	'slidesToShow'   => 1,
	'slidesToScroll' => 1,
	'rows'           => 0,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'dpi-block dpi-feature-banner ' . ( $has_slider ? 'dpi-feature-banner--slider' : 'dpi-feature-banner--single' ),
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div
		class="dpi-feature-banner__slides"
		<?php if ( $has_slider ) : ?>
			data-dpi-slick="<?php echo esc_attr( wp_json_encode( $slick ) ); ?>"
		<?php endif; ?>
	>
		<?php foreach ( $slides as $index => $slide ) : ?>
			<?php
			$heading_id = $slide['headline'] && 'heading' === $slide['headline_element']
				? wp_unique_id( 'dpi-feature-banner-heading-' )
				: '';
			?>
			<article
				class="dpi-feature-banner__slide dpi-feature-banner__slide--<?php echo esc_attr( $slide['image_layout'] ); ?> dpi-feature-banner__slide--<?php echo esc_attr( $slide['visual_variant'] ); ?>"
				<?php echo $heading_id ? 'aria-labelledby="' . esc_attr( $heading_id ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
				<?php if ( $slide['image_id'] ) : ?>
					<figure class="dpi-feature-banner__media">
						<?php echo wp_get_attachment_image( $slide['image_id'], 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</figure>

					<?php if ( 'background' === $slide['image_layout'] ) : ?>
						<div class="dpi-feature-banner__overlay" aria-hidden="true"></div>
					<?php endif; ?>
				<?php endif; ?>

				<div class="dpi-feature-banner__content">
					<?php if ( $slide['eyebrow'] ) : ?>
						<p class="dpi-feature-banner__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></p>
					<?php endif; ?>

					<?php if ( $slide['headline'] ) : ?>
						<?php if ( 'quote' === $slide['headline_element'] ) : ?>
							<blockquote class="dpi-feature-banner__quote">
								<p><?php echo esc_html( $slide['headline'] ); ?></p>
							</blockquote>
						<?php else : ?>
							<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="dpi-feature-banner__heading"><?php echo esc_html( $slide['headline'] ); ?></h2>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( trim( wp_strip_all_tags( $slide['content'] ) ) ) : ?>
						<div class="dpi-feature-banner__text"><?php echo wp_kses_post( wpautop( $slide['content'] ) ); ?></div>
					<?php endif; ?>

					<?php if ( $slide['buttons'] ) : ?>
						<div class="dpi-feature-banner__actions">
							<?php foreach ( $slide['buttons'] as $button ) : ?>
								<?php
								echo dpi_blocks_link_open( $button, 'dpi-feature-banner__cta', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo esc_html( $button['title'] );
								echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
