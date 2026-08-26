<?php

/**
 * Hero heading, subheadings, and buttons.
 *
 * @package DPI_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dpi_heading     = trim( (string) $dpi_heading );
$dpi_subheadings = is_array( $dpi_subheadings ) ? $dpi_subheadings : array();
$dpi_lines       = array();

foreach ( $dpi_subheadings as $dpi_row ) {
	$dpi_value = is_array( $dpi_row ) ? ( $dpi_row['subheading'] ?? $dpi_row['video_subheading'] ?? '' ) : $dpi_row;
	$dpi_value = trim( (string) $dpi_value );

	if ( $dpi_value ) {
		$dpi_lines[] = $dpi_value;
	}
}
?>
<div class="dpi-hero__content">
	<?php if ( $dpi_heading ) : ?>
	<p class="dpi-hero__eyebrow"><?php echo esc_html( $dpi_heading ); ?></p>
	<?php endif; ?>
	<?php if ( $dpi_lines ) : ?>
	<h2 class="dpi-hero__title">
		<?php
		foreach ( $dpi_lines as $dpi_index => $dpi_line ) {
			if ( $dpi_index ) {
				echo '<br>';
			}
			echo esc_html( $dpi_line );
		}
		?>
	</h2>
	<?php endif; ?>
	<?php if ( is_array( $dpi_buttons ) && $dpi_buttons ) : ?>
	<div class="dpi-hero__actions"><?php $dpi_render_links( $dpi_buttons, 'dpi-button' ); ?></div>
	<?php endif; ?>
</div>
