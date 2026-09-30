<?php
/**
 * Mass times block renderer.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading        = function_exists( 'get_field' ) ? trim( (string) get_field( 'heading' ) ) : '';
$subheading     = function_exists( 'get_field' ) ? trim( (string) get_field( 'subheading' ) ) : '';
$image          = function_exists( 'get_field' ) ? get_field( 'image' ) : null;
$mass_times     = function_exists( 'get_field' ) ? get_field( 'mass_times' ) : array();
$links          = function_exists( 'get_field' ) ? get_field( 'links' ) : array();
$layout         = function_exists( 'get_field' ) ? (string) get_field( 'layout' ) : 'cards';
$media_position = function_exists( 'get_field' ) ? (string) get_field( 'media_position' ) : 'right';
$layout         = in_array( $layout, array( 'cards', 'list' ), true ) ? $layout : 'cards';
$media_position = in_array( $media_position, array( 'none', 'left', 'right' ), true ) ? $media_position : 'right';
$image_id       = is_array( $image ) ? absint( $image['ID'] ?? $image['id'] ?? 0 ) : absint( $image );
$mass_times     = is_array( $mass_times ) ? $mass_times : array();
$links          = is_array( $links ) ? $links : array();

$format_time = static function ( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	foreach ( array( 'H:i:s', 'H:i', 'G:i', 'g:i a', 'g:i A', 'h:i a', 'h:i A' ) as $format ) {
		$date = DateTimeImmutable::createFromFormat( '!' . $format, $value );

		if ( $date instanceof DateTimeImmutable ) {
			return $date->format( 'g:i a' );
		}
	}

	return $value;
};

$schedules = array();

foreach ( $mass_times as $schedule ) {
	if ( ! is_array( $schedule ) ) {
		continue;
	}

	$days = array();

	foreach ( (array) ( $schedule['days'] ?? array() ) as $day ) {
		if ( ! is_array( $day ) ) {
			continue;
		}

		$times = array();

		foreach ( (array) ( $day['times'] ?? array() ) as $time ) {
			if ( ! is_array( $time ) ) {
				continue;
			}

			$start = $format_time( $time['time'] ?? '' );

			if ( ! $start ) {
				continue;
			}

			$end     = $format_time( $time['end_time'] ?? '' );
			$note    = trim( (string) ( $time['notes'] ?? '' ) );
			$times[] = $start . ( $end ? ' - ' . $end : '' ) . ( $note ? ' ' . $note : '' );
		}

		$day_label = rtrim( trim( (string) ( $day['day'] ?? '' ) ), ':' );

		if ( $day_label && $times ) {
			$days[] = array(
				'label' => $day_label,
				'times' => $times,
			);
		}
	}

	$label = trim( (string) ( $schedule['label'] ?? '' ) );
	$notes = (string) ( $schedule['notes'] ?? '' );

	if ( $label && ( $days || trim( wp_strip_all_tags( $notes ) ) ) ) {
		$schedules[] = array(
			'label' => $label,
			'days'  => $days,
			'notes' => $notes,
		);
	}
}

if ( ! $heading && ! $subheading && ! $schedules ) {
	if ( ! empty( $is_preview ) ) {
		printf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'dpi-block dpi-mass-times dpi-block--placeholder' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Add a heading and at least one schedule.', 'dpi-blocks' )
		);
	}

	return;
}

$heading_id         = $heading ? wp_unique_id( 'dpi-mass-times-heading-' ) : '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => sprintf( 'dpi-block dpi-mass-times dpi-mass-times--%s dpi-mass-times--media-%s', $layout, $media_position ),
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php
if ( $heading_id ) :
	?>
	aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"<?php endif; ?>>
	<div class="dpi-mass-times__inner">
		<?php if ( $image_id && 'none' !== $media_position ) : ?>
			<figure class="dpi-mass-times__media"><?php echo wp_get_attachment_image( $image_id, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
		<?php endif; ?>

		<div class="dpi-mass-times__content">
			<?php if ( $heading || $subheading ) : ?>
				<header>
					<?php
					if ( $subheading ) :
						?>
						<p class="dpi-mass-times__eyebrow"><?php echo esc_html( $subheading ); ?></p><?php endif; ?>
					<?php
					if ( $heading ) :
						?>
						<h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
				</header>
			<?php endif; ?>

			<?php if ( $schedules ) : ?>
				<div class="dpi-mass-times__schedules">
					<?php foreach ( $schedules as $schedule ) : ?>
						<article class="dpi-mass-times__schedule">
							<h3><?php echo esc_html( $schedule['label'] ); ?></h3>
							<?php if ( $schedule['days'] ) : ?>
								<dl>
									<?php foreach ( $schedule['days'] as $day ) : ?>
										<div><dt><?php echo esc_html( $day['label'] ); ?></dt><dd><?php echo esc_html( implode( ', ', $day['times'] ) ); ?></dd></div>
									<?php endforeach; ?>
								</dl>
							<?php endif; ?>
							<?php
							if ( trim( wp_strip_all_tags( $schedule['notes'] ) ) ) :
								?>
								<div class="dpi-mass-times__notes"><?php echo wp_kses_post( wpautop( $schedule['notes'] ) ); ?></div><?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $links ) : ?>
				<div class="dpi-mass-times__actions">
					<?php foreach ( $links as $row ) : ?>
						<?php $action_link = is_array( $row ) ? ( $row['link'] ?? null ) : null; ?>
						<?php
						if ( is_array( $action_link ) && function_exists( 'dpi_blocks_render_link' ) ) :
							?>
							<?php echo wp_kses_post( dpi_blocks_render_link( $action_link, 'dpi-button', ! empty( $is_preview ) ) ); ?><?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
