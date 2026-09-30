<?php
/**
 * Social media block renderer.
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
$heading_layout = (string) $dpi_get_field( 'heading_layout', 'stacked' );
$feed_layout    = (string) $dpi_get_field( 'feed_layout', 'grid' );
$heading_layout = in_array( $heading_layout, array( 'stacked', 'inline' ), true ) ? $heading_layout : 'stacked';
$feed_layout    = in_array( $feed_layout, array( 'grid', 'carousel' ), true ) ? $feed_layout : 'grid';
$shortcode      = trim( (string) $dpi_get_field( 'feed_shortcode_override', '' ) );
$shortcode      = $shortcode ? $shortcode : trim( (string) $dpi_get_field( 'instagram_shortcode', '' ) );
$shortcode      = $shortcode || ! function_exists( 'dpi_blocks_get_social_feed_shortcode' ) ? $shortcode : trim( (string) dpi_blocks_get_social_feed_shortcode() );
$profiles       = array();

if ( $dpi_get_bool( 'use_global_profiles', true ) && function_exists( 'dpi_blocks_get_social_profiles' ) ) {
	$profiles = dpi_blocks_get_social_profiles();
} else {
	$profiles = $dpi_get_field( 'profiles_override', array() );
}

$profiles = is_array( $profiles ) ? $profiles : array();
$items    = array();

foreach ( $profiles as $profile ) {
	if ( ! is_array( $profile ) ) {
		continue;
	}

	$link_value = $profile['link'] ?? $profile['url'] ?? '';
	$url        = is_array( $link_value ) ? (string) ( $link_value['url'] ?? '' ) : (string) $link_value;
	$label      = trim( (string) ( $profile['label'] ?? ( is_array( $link_value ) ? ( $link_value['title'] ?? '' ) : '' ) ) );

	if ( ! $url ) {
		continue;
	}

	$target  = is_array( $link_value ) && ! empty( $link_value['target'] ) ? (string) $link_value['target'] : '_blank';
	$target  = '_self' === $target ? '_self' : '_blank';
	$items[] = array(
		'url'    => $url,
		'label'  => $label ? $label : __( 'Social profile', 'dpi-blocks' ),
		'icon'   => $profile['icon'] ?? '',
		'target' => $target,
	);
}

if ( ! $heading && ! $text && ! $items && ! $shortcode ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-social-media dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add social profiles or a feed shortcode.', 'dpi-blocks' )
		);
	}

	return;
}

$slick              = array(
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
			'settings'   => array( 'slidesToShow' => max( 1, min( 6, absint( $dpi_get_field( 'slides_tablet', 2 ) ) ) ) ),
		),
		array(
			'breakpoint' => 1024,
			'settings'   => array( 'slidesToShow' => max( 1, min( 6, absint( $dpi_get_field( 'slides_desktop', 3 ) ) ) ) ),
		),
	),
);
$feed_adapter       = 'carousel' === $feed_layout && function_exists( 'dpi_blocks_get_social_feed_adapter' )
	? dpi_blocks_get_social_feed_adapter( $shortcode )
	: null;
$feed_selector      = is_array( $feed_adapter ) && ! empty( $feed_adapter['track_selector'] ) ? (string) $feed_adapter['track_selector'] : '';
$heading_id         = $heading ? wp_unique_id( 'dpi-social-media-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf( 'dpi-block dpi-social-media dpi-social-media--heading-%s dpi-social-media--feed-%s', $heading_layout, $feed_layout ),
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading_id ) :
	?>
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"<?php endif; ?>>
	<div class="dpi-social-media__header">
		<?php
		if ( $heading ) :
			?>
			<h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<?php
		if ( $text ) :
			?>
			<div class="dpi-social-media__intro"><?php echo wp_kses_post( wpautop( $text ) ); ?></div><?php endif; ?>
	</div>

	<?php if ( $items ) : ?>
		<ul class="dpi-social-media__profiles" aria-label="<?php esc_attr_e( 'Social media profiles', 'dpi-blocks' ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$profile_link = array(
					'url'    => $item['url'],
					'title'  => $item['label'],
					'target' => $item['target'],
				);
				?>
				<li><?php echo dpi_blocks_link_open( $profile_link, 'dpi-social-media__profile-link', ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					if ( $item['icon'] && function_exists( 'dpi_blocks_render_icon' ) ) :
						?>
						<span aria-hidden="true"><?php echo dpi_blocks_render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the plugin-owned icon renderer. ?></span><?php endif; ?>
					<span class="dpi-social-media__profile-label"><?php echo esc_html( $item['label'] ); ?></span>
				<?php echo dpi_blocks_link_close( ! empty( $is_preview ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $shortcode ) : ?>
		<div class="dpi-social-media__feed" data-dpi-social-feed
		<?php
		if ( $feed_selector ) :
			?>
			data-dpi-feed-selector="<?php echo esc_attr( $feed_selector ); ?>" data-dpi-slick="<?php echo esc_attr( wp_json_encode( $slick ) ); ?>"<?php endif; ?>>
			<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is provided by an administrator-selected integration. ?>
		</div>
	<?php endif; ?>
</section>
