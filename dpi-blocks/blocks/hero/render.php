<?php

/**
 * Hero block renderer.
 *
 * @package DPI_Blocks
 */

if (! defined('ABSPATH')) {
	exit;
}

$dpi_get_field = static function ($name, $fallback = null) {
	if (! function_exists('get_field')) {
		return $fallback;
	}

	$value = get_field($name);

	return null === $value || '' === $value ? $fallback : $value;
};

$dpi_get_bool = static function ($name, $fallback) use ($dpi_get_field) {
	$value = $dpi_get_field($name, null);

	return null === $value ? (bool) $fallback : (bool) $value;
};

$dpi_render_links = static function ($rows, $class_name = '') {
	if (! is_array($rows)) {
		return;
	}

	foreach ($rows as $row) {
		$link = is_array($row) && ! empty($row['url']) ? $row : ($row['link'] ?? null);

		if (! is_array($link) || empty($link['url']) || empty($link['title'])) {
			continue;
		}

		if (function_exists('dpi_blocks_render_link')) {
			echo wp_kses_post(dpi_blocks_render_link($link, $class_name));
			continue;
		}

		$target = ! empty($link['target']) ? (string) $link['target'] : '_self';
		$rel    = '_blank' === $target ? 'noopener noreferrer' : '';
		printf(
			'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
			esc_attr($class_name),
			esc_url($link['url']),
			esc_attr($target),
			$rel ? ' rel="' . esc_attr($rel) . '"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html($link['title'])
		);
	}
};

$dpi_render_content = static function ($heading, $subheadings, $buttons) use ($dpi_render_links) {
	$heading     = trim((string) $heading);
	$subheadings = is_array($subheadings) ? $subheadings : array();
	$lines       = array();

	foreach ($subheadings as $row) {
		$value = is_array($row) ? ($row['subheading'] ?? $row['video_subheading'] ?? '') : $row;
		$value = trim((string) $value);

		if ($value) {
			$lines[] = $value;
		}
	}
?>
<div class="dpi-hero__content">
    <?php if ($heading) : ?>
    <p class="dpi-hero__eyebrow"><?php echo esc_html($heading); ?></p>
    <?php endif; ?>
    <?php if ($lines) : ?>
    <h2 class="dpi-hero__title">
        <?php
				foreach ($lines as $index => $line) :
				?>
        <?php echo $index ? '<br>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?><?php echo esc_html($line); ?><?php endforeach; ?>
    </h2>
    <?php endif; ?>
    <?php if (is_array($buttons) && $buttons) : ?>
    <div class="dpi-hero__actions"><?php $dpi_render_links($buttons, 'dpi-button'); ?></div>
    <?php endif; ?>
</div>
<?php
};

$media_type            = $dpi_get_bool('media_type', false) ? 'video' : 'slider';
$layout                = (string) $dpi_get_field('layout', 'overlay');
$layout                = in_array($layout, array('overlay', 'split'), true) ? $layout : 'overlay';
$section_height        = (string) $dpi_get_field('section_height', 'medium');
$section_height        = in_array($section_height, array('auto', 'small', 'medium', 'large', 'viewport'), true) ? $section_height : 'medium';
$opacity               = min(100, max(0, (float) $dpi_get_field('overlay_opacity', 50)));
$slick                 = array(
	'arrows'         => $dpi_get_bool('show_arrows', true),
	'dots'           => $dpi_get_bool('show_dots', true),
	'infinite'       => $dpi_get_bool('infinite', true),
	'autoplay'       => $dpi_get_bool('autoplay', true),
	'autoplaySpeed'  => max(1000, absint($dpi_get_field('autoplay_delay', 6000))),
	'speed'          => max(0, absint($dpi_get_field('transition_speed', 600))),
	'pauseOnHover'   => $dpi_get_bool('pause_on_hover', true),
	'pauseOnFocus'   => $dpi_get_bool('pause_on_focus', true),
	'slidesToShow'   => 1,
	'slidesToScroll' => 1,
	'rows'           => 0,
);
$featured_link         = $dpi_get_field('featured_link', null);
$featured_link_heading = trim((string) $dpi_get_field('featured_link_heading', ''));
$show_featured_link    = $dpi_get_bool('show_featured_link', true) && is_array($featured_link) && ! empty($featured_link['url']) && ! empty($featured_link['title']);
$wrapper_attributes    = get_block_wrapper_attributes(
	array(
		'class' => sprintf('dpi-block dpi-hero dpi-hero--%s dpi-hero--height-%s', $layout, $section_height),
		'style' => '--dpi-hero-overlay-opacity:' . ($opacity / 100) . ';',
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
			?>>
    <?php if ('video' === $media_type) : ?>
    <?php
		$video_file        = $dpi_get_field('video_file', null);
		$video_poster      = $dpi_get_field('video_poster', null);
		$video_heading     = $dpi_get_field('video_heading', '');
		$video_subheadings = $dpi_get_field('subheadings', array());
		$video_buttons     = $dpi_get_field('video_buttons', array());
		$video_url         = '';
		$video_mime        = '';
		$poster_url        = '';

		if (is_array($video_file)) {
			$video_url  = (string) ($video_file['url'] ?? '');
			$video_mime = (string) ($video_file['mime_type'] ?? '');
		} elseif (is_numeric($video_file)) {
			$video_url  = (string) wp_get_attachment_url(absint($video_file));
			$video_mime = (string) get_post_mime_type(absint($video_file));
		} elseif (is_string($video_file)) {
			$video_url = $video_file;
		}

		if (! $video_mime && $video_url) {
			$file_type  = wp_check_filetype($video_url);
			$video_mime = (string) ($file_type['type'] ?? 'video/mp4');
		}

		if (is_array($video_poster)) {
			$poster_url = (string) ($video_poster['url'] ?? '');
		} elseif (is_numeric($video_poster)) {
			$poster_url = (string) wp_get_attachment_image_url(absint($video_poster), 'full');
		} elseif (is_string($video_poster)) {
			$poster_url = $video_poster;
		}

		if (! $video_subheadings) {
			$legacy_subheading = $dpi_get_field('video_subheading', '');
			$video_subheadings = $legacy_subheading ? array(array('video_subheading' => $legacy_subheading)) : array();
		}
		?>
    <article class="dpi-hero__slide dpi-hero__slide--video">
        <div class="dpi-hero__media">
            <?php if ($video_url) : ?>
            <video class="dpi-hero__video" playsinline preload="metadata" <?php
																					if ($dpi_get_bool('video_autoplay', true)) :
																					?> autoplay<?php endif; ?> <?php
													if ($dpi_get_bool('video_muted', true)) :
													?> muted<?php endif; ?> <?php
													if ($dpi_get_bool('video_loop', true)) :
													?> loop<?php endif; ?> <?php
													if ($dpi_get_bool('video_controls', false)) :
													?> controls<?php endif; ?> <?php
													if ($poster_url) :
													?> poster="<?php echo esc_url($poster_url); ?>" <?php endif; ?>>
                <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($video_mime); ?>">
            </video>
            <?php elseif ($poster_url) : ?>
            <img src="<?php echo esc_url($poster_url); ?>" alt="">
            <?php elseif (! empty($is_preview)) : ?>
            <p class="dpi-block--placeholder"><?php esc_html_e('Choose a video or poster image.', 'dpi-blocks'); ?>
            </p>
            <?php endif; ?>
            <span class="dpi-hero__overlay" aria-hidden="true"></span>
        </div>
        <?php $dpi_render_content($video_heading, $video_subheadings, $video_buttons); ?>
    </article>
    <?php else : ?>
    <?php $slides = $dpi_get_field('slides', array()); ?>
    <?php if (is_array($slides) && $slides) : ?>
    <div class="dpi-hero__slides" <?php if (count($slides) > 1) : ?>
        data-dpi-slick="<?php echo esc_attr(wp_json_encode($slick)); ?>" <?php endif; ?>>
        <?php foreach ($slides as $slide) : ?>
        <?php
					$image    = is_array($slide) ? ($slide['image'] ?? null) : null;
					$image_id = is_array($image) ? absint($image['ID'] ?? $image['id'] ?? 0) : absint($image);
					?>
        <article class="dpi-hero__slide">
            <div class="dpi-hero__media">
                <?php if ($image_id) : ?>
                <?php echo wp_get_attachment_image($image_id, 'full', false, array('alt' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								?>
                <?php elseif (! empty($is_preview)) : ?>
                <p class="dpi-block--placeholder">
                    <?php esc_html_e('Choose an image for this slide.', 'dpi-blocks'); ?></p>
                <?php endif; ?>
                <span class="dpi-hero__overlay" aria-hidden="true"></span>
            </div>
            <?php $dpi_render_content($slide['heading'] ?? '', $slide['subheadings'] ?? array(), $slide['buttons'] ?? array()); ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php elseif (! empty($is_preview)) : ?>
    <p class="dpi-block--placeholder"><?php esc_html_e('Add at least one hero slide.', 'dpi-blocks'); ?></p>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($show_featured_link) : ?>
    <aside class="dpi-hero__featured-link" aria-label="<?php esc_attr_e('Featured link', 'dpi-blocks'); ?>">
        <?php
			if ($featured_link_heading) :
			?>
        <span><?php echo esc_html($featured_link_heading); ?></span><?php endif; ?>
        <?php $dpi_render_links(array($featured_link), 'dpi-hero__featured-link-action'); ?>
    </aside>
    <?php endif; ?>
</section>