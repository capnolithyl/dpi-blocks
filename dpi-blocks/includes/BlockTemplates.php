<?php
/**
 * Theme-overridable ACF block template loader.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve and render plugin block templates without changing saved block names. */
final class BlockTemplates {
	/** @var list<string> */
	private const BLOCK_SLUGS = array(
		'community-slider',
		'featured-links',
		'hero',
		'image-buttons',
		'image-buttons-alt',
		'mass-times',
		'mission',
		'social-media',
		'staff-card',
		'stats',
	);

	/** @var array<string, string> */
	private static array $active_renders = array();

	/** @var array<string, bool> */
	private static array $active_fallbacks = array();

	/**
	 * Render one ACF block through a validated theme override or its bundled fallback.
	 *
	 * The deliberately broad parameter types mirror ACF's public callback contract.
	 *
	 * @param mixed $block      ACF block settings and attributes.
	 * @param mixed $content    Inner block content.
	 * @param mixed $is_preview Whether this is an editor preview.
	 * @param mixed $post_id    Current post ID.
	 * @param mixed $wp_block   WordPress block instance.
	 * @param mixed $context    Provided block context.
	 */
	public static function render( mixed $block, mixed $content = '', mixed $is_preview = false, mixed $post_id = 0, mixed $wp_block = null, mixed $context = null ): void {
		$block_array = is_array( $block ) ? $block : array();
		$block_name  = isset( $block_array['name'] ) && is_string( $block_array['name'] ) ? $block_array['name'] : '';
		$block_slug  = self::slug_from_name( $block_name );

		if ( null === $block_slug ) {
			return;
		}

		$default_template = realpath( DPI_BLOCKS_DIR . 'blocks/' . $block_slug . '/render.php' );
		if ( false === $default_template || ! self::is_readable_php_file( $default_template ) ) {
			return;
		}

		$render_key = self::render_key( $block_array, $wp_block, $block_name );
		if ( isset( self::$active_renders[ $render_key ] ) ) {
			// If the bundled renderer itself re-enters the callback, returning is
			// the only safe option. A selected theme override may fall back once.
			if ( self::same_path( self::$active_renders[ $render_key ], $default_template ) ) {
				return;
			}

			// A theme override may mistakenly invoke this callback. Render the
			// canonical template once, then stop any deeper callback recursion.
			if ( ! isset( self::$active_fallbacks[ $render_key ] ) ) {
				self::$active_fallbacks[ $render_key ] = true;
				self::require_template( $default_template, $block, $content, $is_preview, $post_id, $wp_block, $context, $block_name, $block_slug, $default_template );
			}
			return;
		}

		// A resolving sentinel also protects against filters which mistakenly
		// invoke the render callback before a template has been selected.
		self::$active_renders[ $render_key ] = '';

		try {
			$render_context = array(
				'name'             => $block_name,
				'canonical_name'   => $block_name,
				'block_name'       => $block_name,
				'slug'             => $block_slug,
				'source_file'      => $default_template,
				'default_template' => $default_template,
				'block'            => $block,
				'content'          => $content,
				'is_preview'       => $is_preview,
				'post_id'          => $post_id,
				'wp_block'         => $wp_block,
				'context'          => $context,
			);

			$template                            = self::resolve_template( $block_slug, $default_template, $render_context );
			self::$active_renders[ $render_key ] = $template;
			$render_context['template']          = $template;
			$render_context['is_theme_override'] = ! self::same_path( $template, $default_template );

			do_action( 'dpi_blocks/before_block_render', $render_context );
			do_action( 'dpi_blocks/before_block_render/' . $block_slug, $render_context );

			try {
				self::require_template( $template, $block, $content, $is_preview, $post_id, $wp_block, $context, $block_name, $block_slug, $default_template );
			} finally {
				do_action( 'dpi_blocks/after_block_render/' . $block_slug, $render_context );
				do_action( 'dpi_blocks/after_block_render', $render_context );
			}
		} finally {
			unset( self::$active_renders[ $render_key ], self::$active_fallbacks[ $render_key ] );
		}
	}

	/**
	 * Resolve a child-theme, parent-theme, or plugin template.
	 *
	 * @param string              $block_slug      Canonical block slug.
	 * @param string              $default_template Bundled template path.
	 * @param array<string,mixed> $render_context  Render state supplied to filters.
	 */
	private static function resolve_template( string $block_slug, string $default_template, array $render_context ): string {
		$candidates = array( 'dpi-blocks/blocks/' . $block_slug . '/render.php' );

		/**
		 * Filter theme-relative template candidates passed to locate_template().
		 *
		 * @param list<string>        $candidates     Relative template candidates.
		 * @param array<string,mixed> $render_context Current block render state.
		 */
		$candidates = apply_filters( 'dpi_blocks/block_template_candidates', $candidates, $render_context );
		$candidates = self::valid_relative_candidates( $candidates );
		$located    = $candidates ? locate_template( $candidates, false, false ) : '';
		$template   = is_string( $located ) && self::is_valid_theme_template( $located ) ? realpath( $located ) : false;
		$template   = is_string( $template ) ? $template : $default_template;

		$filter_context                        = $render_context;
		$filter_context['template_candidates'] = $candidates;
		$filter_context['located_template']    = $template;

		/**
		 * Filter the final block template path.
		 *
		 * Returning anything except a readable PHP file inside the active child
		 * or parent theme restores the bundled plugin template.
		 *
		 * @param string              $template       Resolved template path.
		 * @param array<string,mixed> $filter_context Current block render state.
		 */
		$filtered_template = apply_filters( 'dpi_blocks/block_template', $template, $filter_context );

		if ( is_string( $filtered_template ) && self::is_allowed_template( $filtered_template, $default_template ) ) {
			$real_template = realpath( $filtered_template );
			if ( is_string( $real_template ) ) {
				return $real_template;
			}
		}

		return $default_template;
	}

	/** Return a canonical slug, or null for blocks this renderer does not own. */
	private static function slug_from_name( string $block_name ): ?string {
		if ( ! str_starts_with( $block_name, 'dpi/' ) ) {
			return null;
		}

		$slug = substr( $block_name, 4 );
		return in_array( $slug, self::BLOCK_SLUGS, true ) ? $slug : null;
	}

	/**
	 * Normalize user-filtered locate_template() candidates.
	 *
	 * @param mixed $candidates Candidate value returned by a filter.
	 * @return list<string>
	 */
	private static function valid_relative_candidates( mixed $candidates ): array {
		if ( ! is_array( $candidates ) ) {
			return array();
		}

		$valid = array();
		foreach ( $candidates as $candidate ) {
			if ( ! is_string( $candidate ) || str_contains( $candidate, "\0" ) ) {
				continue;
			}

			$candidate = ltrim( wp_normalize_path( trim( $candidate ) ), '/' );
			if (
				'' === $candidate
				|| str_contains( $candidate, '../' )
				|| '..' === $candidate
				|| 'php' !== strtolower( (string) pathinfo( $candidate, PATHINFO_EXTENSION ) )
				|| preg_match( '/^[A-Za-z]:\//', $candidate )
			) {
				continue;
			}

			$valid[] = $candidate;
		}

		return array_values( array_unique( $valid ) );
	}

	/** Determine whether a final path is an allowed plugin or theme template. */
	private static function is_allowed_template( string $template, string $default_template ): bool {
		if ( str_contains( $template, "\0" ) ) {
			return false;
		}

		$real_template = realpath( $template );
		if ( false === $real_template || ! self::is_readable_php_file( $real_template ) ) {
			return false;
		}

		if ( self::same_path( $real_template, $default_template ) ) {
			return true;
		}

		return self::is_valid_theme_template( $real_template );
	}

	/** Determine whether a path is a readable PHP file within an active theme. */
	private static function is_valid_theme_template( string $template ): bool {
		if ( str_contains( $template, "\0" ) ) {
			return false;
		}

		$real_template = realpath( $template );
		if ( false === $real_template || ! self::is_readable_php_file( $real_template ) ) {
			return false;
		}

		$roots = array_unique(
			array_filter(
				array(
					realpath( get_stylesheet_directory() ),
					realpath( get_template_directory() ),
				),
				'is_string'
			)
		);

		foreach ( $roots as $root ) {
			if ( self::path_is_within( $real_template, $root ) ) {
				return true;
			}
		}

		return false;
	}

	/** Determine whether a path is a readable regular PHP file. */
	private static function is_readable_php_file( string $file ): bool {
		return is_file( $file )
			&& is_readable( $file )
			&& 'php' === strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) );
	}

	/** Compare two normalized absolute paths. */
	private static function same_path( string $left, string $right ): bool {
		$left  = wp_normalize_path( $left );
		$right = wp_normalize_path( $right );
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$left  = strtolower( $left );
			$right = strtolower( $right );
		}

		return $left === $right;
	}

	/** Determine whether a resolved file remains within a resolved directory. */
	private static function path_is_within( string $file, string $directory ): bool {
		$file      = wp_normalize_path( $file );
		$directory = trailingslashit( wp_normalize_path( $directory ) );
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$file      = strtolower( $file );
			$directory = strtolower( $directory );
		}

		return str_starts_with( $file, $directory );
	}

	/** Build a recursion key which remains stable only for the active render. */
	private static function render_key( array $block, mixed $wp_block, string $block_name ): string {
		if ( ! empty( $block['id'] ) && is_scalar( $block['id'] ) ) {
			return $block_name . ':' . (string) $block['id'];
		}

		if ( is_object( $wp_block ) ) {
			return $block_name . ':object-' . spl_object_id( $wp_block );
		}

		$encoded_block = wp_json_encode( $block );
		return $block_name . ':' . md5( is_string( $encoded_block ) ? $encoded_block : $block_name );
	}

	/**
	 * Include a template with the complete ACF and DPI variable contract.
	 *
	 * @param mixed $block        ACF block settings and attributes.
	 * @param mixed $content      Inner block content.
	 * @param mixed $is_preview   Whether this is an editor preview.
	 * @param mixed $post_id      Current post ID.
	 * @param mixed $wp_block     WordPress block instance.
	 * @param mixed $context      Provided block context.
	 */
	// The parameters become local variables for the required renderer.
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	private static function require_template(
		string $template,
		mixed $block,
		mixed $content,
		mixed $is_preview,
		mixed $post_id,
		mixed $wp_block,
		mixed $context,
		string $dpi_block_name,
		string $dpi_block_slug,
		string $dpi_default_template
	): void {
		require $template;
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
}
