<?php
/**
 * Shared and conditionally loaded plugin assets.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	private const CAROUSEL_BLOCKS    = array(
		'dpi/community-slider',
		'dpi/feature-banner',
		'dpi/hero',
		'dpi/social-media',
	);
	private const INTERACTIVE_BLOCKS = array(
		'dpi/community-slider',
		'dpi/feature-banner',
		'dpi/hero',
		'dpi/social-media',
		'dpi/staff-card',
	);

	/** Register asset hooks. */
	public function register(): void {
		add_action( 'init', array( $this, 'register_assets' ), 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_for_request' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'render_block', array( $this, 'enqueue_rendered_block' ), 10, 2 );
	}

	/** Register reusable handles without loading them. */
	public function register_assets(): void {
		wp_register_style(
			'dpi-blocks',
			DPI_BLOCKS_URL . 'assets/css/blocks.css',
			array(),
			DPI_BLOCKS_VERSION
		);
		wp_register_style(
			'dpi-blocks-slick',
			DPI_BLOCKS_URL . 'assets/vendor/slick/slick.css',
			array(),
			'1.8.1'
		);
		wp_register_script(
			'dpi-blocks-slick',
			DPI_BLOCKS_URL . 'assets/vendor/slick/slick.min.js',
			array( 'jquery' ),
			'1.8.1',
			true
		);
		wp_register_script(
			'dpi-blocks',
			DPI_BLOCKS_URL . 'assets/js/blocks.js',
			array( 'jquery' ),
			DPI_BLOCKS_VERSION,
			true
		);

		wp_localize_script(
			'dpi-blocks',
			'DPIBlocksConfig',
			array(
				'previousLabel' => __( 'Previous', 'dpi-blocks' ),
				'nextLabel'     => __( 'Next', 'dpi-blocks' ),
				'previousIcon'  => IconRegistry::render( 'solid:chevron-left' ),
				'nextIcon'      => IconRegistry::render( 'solid:chevron-right' ),
			)
		);
	}

	/** Load shared assets for blocks found in the queried content and directories. */
	public function enqueue_for_request(): void {
		global $post;

		if ( $post instanceof \WP_Post ) {
			$requirements = $this->content_requirements( (string) $post->post_content );
			if ( array_filter( $requirements ) ) {
				$this->enqueue_requirements( $requirements );
			}
		}
	}

	/** Ensure editor previews can initialize every carousel. */
	public function enqueue_editor_assets(): void {
		$requirements = array(
			'styles' => false,
			'script' => false,
			'slick'  => false,
		);

		foreach ( $this->registered_block_names() as $name ) {
			$defaults = $this->default_requirements( $name, array(), 'editor' );
			$filtered = $this->filter_requirements(
				$name,
				$defaults,
				array(
					'phase'                => 'editor',
					'block'                => array(),
					'data'                 => array(),
					'rendered_content'     => '',
					'is_registered'        => true,
					'default_requirements' => $defaults,
				)
			);
			$this->merge_requirements( $requirements, $filtered );
		}

		if ( array_filter( $requirements ) ) {
			$this->enqueue_requirements( $requirements );
		}
	}

	/** Render-time fallback for blocks embedded outside normal post content. */
	public function enqueue_rendered_block( string $content, array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( str_starts_with( $name, 'dpi/' ) ) {
			$data          = isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ? $block['attrs']['data'] : array();
			$defaults      = $this->default_requirements( $name, $data, 'render', $content );
			$is_registered = class_exists( '\\WP_Block_Type_Registry' )
				&& \WP_Block_Type_Registry::get_instance()->is_registered( $name );
			$requirements  = $this->filter_requirements(
				$name,
				$defaults,
				array(
					'phase'                => 'render',
					'block'                => $block,
					'data'                 => $data,
					'rendered_content'     => $content,
					'is_registered'        => $is_registered,
					'default_requirements' => $defaults,
				)
			);
			$this->enqueue_requirements( $requirements );
		}

		return $content;
	}

	/**
	 * Enqueue structural block assets and optional behavior.
	 *
	 * This compatibility method remains available to integrations that used the
	 * original public API. Use the asset-requirements filters for per-block
	 * control.
	 */
	public function enqueue_blocks( bool $with_slick = false, bool $with_script = true ): void {
		$this->enqueue_requirements(
			array(
				'styles' => true,
				'script' => $with_script,
				'slick'  => $with_slick,
			)
		);
	}

	/** Enqueue a normalized set of shared requirements. */
	private function enqueue_requirements( array $requirements ): void {
		$requirements = $this->normalize_requirements(
			$requirements,
			array(
				'styles' => false,
				'script' => false,
				'slick'  => false,
			)
		);

		if ( $requirements['styles'] ) {
			wp_enqueue_style( 'dpi-blocks' );
		}

		if ( $requirements['slick'] ) {
			wp_enqueue_style( 'dpi-blocks-slick' );
			wp_enqueue_script( 'dpi-blocks-slick' );

			$scripts = wp_scripts();
			if ( isset( $scripts->registered['dpi-blocks'] ) && ! in_array( 'dpi-blocks-slick', $scripts->registered['dpi-blocks']->deps, true ) ) {
				$scripts->registered['dpi-blocks']->deps[] = 'dpi-blocks-slick';
			}
		}

		if ( $requirements['script'] ) {
			wp_enqueue_script( 'dpi-blocks' );
		}
	}

	/** Return every registered canonical DPI block name. */
	private function registered_block_names(): array {
		$registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
		$names      = array_filter(
			array_keys( $registered ),
			static fn( string $name ): bool => str_starts_with( $name, 'dpi/' )
		);

		return array_values( $names );
	}

	/** Inspect serialized block data before the document head is printed. */
	private function content_requirements( string $content ): array {
		$requirements = array(
			'styles' => false,
			'script' => false,
			'slick'  => false,
		);
		$registered   = array_fill_keys( $this->registered_block_names(), true );

		$this->inspect_blocks( parse_blocks( $content ), $registered, $requirements );
		return $requirements;
	}

	/** Walk a parsed block tree, including nested DPI blocks. */
	private function inspect_blocks( array $blocks, array $registered, array &$requirements ): void {
		foreach ( $blocks as $block ) {
			$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( isset( $registered[ $name ] ) ) {
				$data     = isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ? $block['attrs']['data'] : array();
				$defaults = $this->default_requirements( $name, $data, 'scan' );
				$filtered = $this->filter_requirements(
					$name,
					$defaults,
					array(
						'phase'                => 'scan',
						'block'                => $block,
						'data'                 => $data,
						'rendered_content'     => '',
						'is_registered'        => true,
						'default_requirements' => $defaults,
					)
				);
				$this->merge_requirements( $requirements, $filtered );
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$this->inspect_blocks( $block['innerBlocks'], $registered, $requirements );
			}
		}
	}

	/** Resolve the plugin defaults for one block and request phase. */
	private function default_requirements( string $name, array $data, string $phase, string $content = '' ): array {
		$slick  = 'editor' === $phase
			? in_array( $name, self::CAROUSEL_BLOCKS, true )
			: $this->block_requires_slick( $name, $data ) || str_contains( $content, 'data-dpi-slick' );
		$script = 'editor' === $phase
			? in_array( $name, self::INTERACTIVE_BLOCKS, true )
			: $this->block_requires_script( $name, $data, $content );

		return array(
			'styles' => true,
			'script' => $slick || $script,
			'slick'  => $slick,
		);
	}

	/** Apply the public generic and per-block asset requirement filters. */
	private function filter_requirements( string $name, array $defaults, array $state ): array {
		$slug    = sanitize_key( substr( $name, 4 ) );
		$context = array_merge(
			array(
				'canonical_name'       => $name,
				'name'                 => $name,
				'block_name'           => $name,
				'slug'                 => $slug,
				'source_file'          => $this->block_source_file( $name ),
				'phase'                => '',
				'block'                => array(),
				'data'                 => array(),
				'rendered_content'     => '',
				'is_registered'        => false,
				'default_requirements' => $defaults,
			),
			$state
		);

		$context['requirements'] = $defaults;

		/**
		 * Filters the shared assets required by one DPI block.
		 *
		 * @param array<string,bool> $requirements Asset requirements.
		 * @param array<string,mixed> $context      Block and request context.
		 */
		$filtered     = apply_filters( 'dpi_blocks/block_asset_requirements', $defaults, $context );
		$requirements = $this->normalize_requirements( $filtered, $defaults );

		$context['requirements'] = $requirements;

		/**
		 * Filters the shared assets required by one specific DPI block.
		 *
		 * The dynamic portion of the hook name, `$slug`, is the canonical
		 * block slug without the `dpi/` namespace.
		 *
		 * @param array<string,bool> $requirements Asset requirements.
		 * @param array<string,mixed> $context      Block and request context.
		 */
		$filtered = apply_filters( "dpi_blocks/block_asset_requirements/{$slug}", $requirements, $context );

		return $this->normalize_requirements( $filtered, $requirements );
	}

	/** Normalize extension output while retaining safe values on invalid input. */
	private function normalize_requirements( mixed $candidate, array $fallback ): array {
		if ( ! is_array( $candidate ) ) {
			$candidate = array();
		}

		$requirements = array();
		foreach ( array( 'styles', 'script', 'slick' ) as $key ) {
			$value = $candidate[ $key ] ?? $fallback[ $key ] ?? false;
			if ( ! is_bool( $value ) ) {
				$value = $fallback[ $key ] ?? false;
			}
			$requirements[ $key ] = (bool) $value;
		}

		if ( $requirements['slick'] ) {
			$requirements['script'] = true;
		}

		return $requirements;
	}

	/** Merge one block's requirements into the request-level aggregate. */
	private function merge_requirements( array &$aggregate, array $requirements ): void {
		foreach ( array( 'styles', 'script', 'slick' ) as $key ) {
			$aggregate[ $key ] = ! empty( $aggregate[ $key ] ) || ! empty( $requirements[ $key ] );
		}
	}

	/** Return the canonical bundled metadata file when this plugin owns it. */
	private function block_source_file( string $name ): string {
		if ( ! str_starts_with( $name, 'dpi/' ) ) {
			return '';
		}

		$raw_slug = substr( $name, 4 );
		$slug     = sanitize_key( $raw_slug );
		if ( '' === $slug || $slug !== $raw_slug ) {
			return '';
		}

		$file = DPI_BLOCKS_DIR . 'blocks/' . $slug . '/block.json';

		return is_readable( $file ) ? wp_normalize_path( $file ) : '';
	}

	/** Determine whether one frontend block uses behavior from the shared initializer. */
	private function block_requires_script( string $name, array $data, string $content = '' ): bool {
		if ( str_contains( $content, 'data-dpi-tabs' ) || str_contains( $content, 'data-dpi-dialog' ) ) {
			return true;
		}

		if ( 'dpi/community-slider' === $name ) {
			$categories = $data['categories'] ?? $data['field_6a5e71e0ba16f'] ?? array();

			return is_array( $categories ) && count( $categories ) > 1;
		}

		if ( 'dpi/hero' === $name ) {
			$is_video = (bool) ( $data['media_type'] ?? $data['field_69dfd6d0051f3'] ?? false );

			return $is_video && (bool) ( $data['video_autoplay'] ?? $data['field_dpi_hero_video_autoplay'] ?? true );
		}

		if ( 'dpi/staff-card' === $name ) {
			$mode = (string) ( $data['interaction_mode'] ?? $data['field_dpi_staff_card_interaction_mode'] ?? 'inherit' );
			if ( 'inherit' === $mode ) {
				$mode = (string) Settings::get( 'staff_mode', 'single' );
			}

			return 'modal' === $mode;
		}

		return false;
	}

	/** Determine whether one saved ACF block configuration uses Slick. */
	private function block_requires_slick( string $name, array $data ): bool {
		if ( ! in_array( $name, self::CAROUSEL_BLOCKS, true ) ) {
			return false;
		}

		if ( 'dpi/community-slider' === $name ) {
			return 'grid' !== (string) ( $data['layout'] ?? $data['field_dpi_community_layout'] ?? 'carousel' );
		}

		if ( 'dpi/feature-banner' === $name ) {
			$source = (string) ( $data['content_source'] ?? $data['field_dpi_feature_banner_content_source'] ?? 'manual' );

			if ( 'categories' === $source ) {
				return true;
			}

			$slides = $data['slides'] ?? $data['field_dpi_feature_banner_slides'] ?? 0;

			return is_array( $slides ) ? count( $slides ) > 1 : absint( $slides ) > 1;
		}

		if ( 'dpi/hero' === $name ) {
			return ! (bool) ( $data['media_type'] ?? $data['field_69dfd6d0051f3'] ?? false );
		}

		return 'carousel' === (string) ( $data['feed_layout'] ?? $data['field_dpi_social_feed_layout'] ?? 'grid' );
	}
}
