<?php
/**
 * Native block metadata discovery and editor availability.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Blocks {
	/** @var list<string> */
	private const CANONICAL_BLOCKS = array(
		'dpi/accordion',
		'dpi/anchor-navigation',
		'dpi/community-slider',
		'dpi/feature-banner',
		'dpi/featured-links',
		'dpi/five-pillars',
		'dpi/hero',
		'dpi/image-buttons',
		'dpi/image-buttons-alt',
		'dpi/interior-hero',
		'dpi/mass-times',
		'dpi/mission',
		'dpi/office-grid',
		'dpi/social-media',
		'dpi/staff-card',
		'dpi/stats',
	);

	/** Metadata keys themes may safely customize. */
	private const CUSTOMIZABLE_METADATA_KEYS = array(
		'title',
		'icon',
		'description',
		'keywords',
		'supports',
		'attributes',
		'parent',
		'ancestor',
		'context',
		'providesContext',
		'usesContext',
		'selectors',
		'styles',
		'variations',
		'example',
		'allowedBlocks',
	);

	/** @var list<string> */
	private array $registration_notices = array();

	/** Register block hooks. */
	public function register(): void {
		// Run after themes so an existing theme-owned category is reused.
		add_filter( 'block_categories_all', array( $this, 'register_category' ), 100, 2 );
		add_filter( 'block_type_metadata', array( $this, 'assign_category' ) );
		add_filter( 'allowed_block_types_all', array( $this, 'filter_inserter_blocks' ), 100, 2 );
		add_action( 'init', array( $this, 'register_blocks' ), 5 );
		add_action( 'admin_notices', array( $this, 'render_registration_notices' ) );
	}

	/** Discover and register every valid block directory. */
	public function register_blocks(): void {
		$plugin_root = untrailingslashit( DPI_BLOCKS_DIR . 'blocks' );
		$directories = array( $plugin_root );
		/** @param list<string> $directories Block root directories. */
		$directories = apply_filters( 'dpi_blocks/block_directories', $directories );
		$directories = is_array( $directories ) ? $directories : array();

		// The bundled canonical definitions always register first. Filtered roots
		// extend discovery but cannot replace a canonical dpi/* registration.
		array_unshift( $directories, $plugin_root );
		$roots     = array();
		$root_keys = array();
		foreach ( $directories as $directory ) {
			if ( ! is_string( $directory ) || str_contains( $directory, "\0" ) ) {
				continue;
			}

			$root = untrailingslashit( trim( $directory ) );
			if ( '' === $root ) {
				continue;
			}

			$resolved_root = realpath( $root );
			$root          = is_string( $resolved_root ) ? $resolved_root : $root;
			$root_key      = wp_normalize_path( $root );
			$root_key      = '\\' === DIRECTORY_SEPARATOR ? strtolower( $root_key ) : $root_key;
			if ( isset( $root_keys[ $root_key ] ) ) {
				continue;
			}

			$root_keys[ $root_key ] = true;
			$roots[]                = $root;
		}

		$seen_names = array();

		foreach ( $roots as $root ) {
			if ( ! is_dir( $root ) ) {
				continue;
			}

			$children = glob( $root . '/*/block.json' );
			if ( false === $children ) {
				continue;
			}

			sort( $children, SORT_NATURAL );
			foreach ( $children as $metadata_file ) {
				$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
				$name     = is_array( $metadata ) && isset( $metadata['name'] ) && is_string( $metadata['name'] )
					? $metadata['name']
					: '';

				if ( ! preg_match( '/^[a-z0-9-]+\/[a-z0-9-]+$/', $name ) ) {
					$this->registration_notices[] = sprintf(
						/* translators: %s: block metadata filename. */
						__( 'DPI Blocks skipped invalid block metadata in %s.', 'dpi-blocks' ),
						basename( dirname( $metadata_file ) ) . '/block.json'
					);
					continue;
				}

				$already_registered = class_exists( '\\WP_Block_Type_Registry' )
					&& \WP_Block_Type_Registry::get_instance()->is_registered( $name );
				if ( isset( $seen_names[ $name ] ) || $already_registered ) {
					$this->registration_notices[] = sprintf(
						/* translators: %s: duplicate canonical block name. */
						__( 'DPI Blocks skipped a duplicate block registration for %s. Extension directories must use unique block names.', 'dpi-blocks' ),
						$name
					);
					continue;
				}

				$seen_names[ $name ] = $metadata_file;
				if ( false === register_block_type( dirname( $metadata_file ) ) ) {
					$this->registration_notices[] = sprintf(
						/* translators: %s: block name. */
						__( 'DPI Blocks could not register %s.', 'dpi-blocks' ),
						$name
					);
				}
			}
		}
	}

	/**
	 * Apply safe metadata extensions and replace the static theme category.
	 *
	 * Canonical identity, API version, and renderer ownership are always
	 * restored after theme filters run.
	 */
	public function assign_category( array $metadata ): array {
		$name = isset( $metadata['name'] ) && is_string( $metadata['name'] ) ? $metadata['name'] : '';
		if ( ! str_starts_with( $name, 'dpi/' ) ) {
			return $metadata;
		}

		$metadata['category'] = $this->resolve_category()['slug'];

		/*
		 * Register the shared structural stylesheet as the block's style handle.
		 * WordPress then loads it in both the front end and the iframe-based block
		 * editor canvas. Theme editor styles can safely layer presentation on top.
		 */
		$metadata['style'] = 'dpi-blocks';

		if ( ! in_array( $name, self::CANONICAL_BLOCKS, true ) || ! $this->is_canonical_source( $metadata, $name ) ) {
			return $metadata;
		}

		$original     = $metadata;
		$slug         = substr( $name, 4 );
		$source_file  = isset( $metadata['file'] ) ? (string) $metadata['file'] : '';
		$hook_context = array(
			'name'               => $name,
			'canonical_name'     => $name,
			'block_name'         => $name,
			'slug'               => $slug,
			'source_file'        => $source_file,
			'original_metadata'  => $original,
			'registration_state' => array(
				'canonical' => true,
				'category'  => $metadata['category'],
			),
		);

		/**
		 * Filter safe metadata for a canonical DPI block.
		 *
		 * @param array<string,mixed> $metadata     Decoded block metadata.
		 * @param array<string,mixed> $hook_context Registration context.
		 */
		try {
			$filtered = apply_filters( 'dpi_blocks/block_metadata', $metadata, $hook_context );
			if ( ! is_array( $filtered ) ) {
				throw new \UnexpectedValueException( 'Invalid general block metadata filter value.' );
			}

			/**
			 * Filter safe metadata for one canonical DPI block slug.
			 *
			 * @param array<string,mixed> $filtered     Metadata from the general filter.
			 * @param array<string,mixed> $hook_context Registration context.
			 */
			$filtered = apply_filters( 'dpi_blocks/block_metadata/' . $slug, $filtered, $hook_context );
			if ( ! is_array( $filtered ) ) {
				throw new \UnexpectedValueException( 'Invalid per-block metadata filter value.' );
			}
		} catch ( \Throwable $error ) {
			unset( $error );
			$this->registration_notices[] = sprintf(
				/* translators: %s: canonical block name. */
				__( 'DPI Blocks ignored an invalid or failed metadata customization for %s.', 'dpi-blocks' ),
				$name
			);
			$filtered = $original;
		}

		$metadata = $this->merge_customizable_metadata( $original, $filtered, $name );
		$acf      = isset( $original['acf'] ) && is_array( $original['acf'] ) ? $original['acf'] : array();
		unset( $acf['renderTemplate'] );
		$acf['renderCallback'] = 'dpi_blocks_render_acf_block';
		$acf['blockVersion']   = 3;

		$metadata['name']       = $name;
		$metadata['apiVersion'] = 3;
		$metadata['category']   = $this->resolve_category()['slug'];
		$metadata['textdomain'] = 'dpi-blocks';
		$metadata['acf']        = $acf;
		if ( isset( $original['file'] ) ) {
			$metadata['file'] = $original['file'];
		}

		return $metadata;
	}

	/** Display duplicate and invalid registration diagnostics to administrators. */
	public function render_registration_notices(): void {
		if ( ! $this->registration_notices || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( array_unique( $this->registration_notices ) as $notice ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $notice ) );
		}
	}

	/** Add the active theme category only if it does not already exist. */
	public function register_category( array $categories, $editor_context ): array {
		unset( $editor_context );
		$category = $this->resolve_category();

		foreach ( $categories as $existing ) {
			if ( isset( $existing['slug'] ) && $category['slug'] === $existing['slug'] ) {
				return $categories;
			}
		}

		$categories[] = $category;
		return $categories;
	}

	/** Hide disabled blocks from insertion without unregistering saved content. */
	public function filter_inserter_blocks( bool|array $allowed, $editor_context ): bool|array {
		unset( $editor_context );
		$disabled = array();
		$settings = Settings::get( 'blocks', array() );

		if ( is_array( $settings ) ) {
			foreach ( $settings as $slug => $enabled ) {
				if ( ! $enabled ) {
					$disabled[] = 'dpi/' . sanitize_key( (string) $slug );
				}
			}
		}

		if ( ! $disabled ) {
			return $allowed;
		}

		if ( true === $allowed ) {
			$allowed = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
		}

		if ( ! is_array( $allowed ) ) {
			return $allowed;
		}

		return array_values( array_diff( $allowed, $disabled ) );
	}

	/** Resolve an extensible category descriptor. */
	private function resolve_category(): array {
		$theme = wp_get_theme();
		$slug  = sanitize_key( (string) get_stylesheet() );
		$name  = trim( (string) $theme->get( 'Name' ) );

		if ( '' === $slug ) {
			$slug = 'dpi-blocks';
		}
		if ( '' === $name ) {
			$name = __( 'DPI Blocks', 'dpi-blocks' );
		}

		$category = array(
			'slug'  => $slug,
			'title' => $name,
			'icon'  => null,
		);

		/** @param array{slug:string,title:string,icon:mixed} $category Category descriptor. */
		$category = apply_filters( 'dpi_blocks/block_category', $category, $theme );

		if ( ! is_array( $category ) || empty( $category['slug'] ) || empty( $category['title'] ) ) {
			return array(
				'slug'  => 'dpi-blocks',
				'title' => __( 'DPI Blocks', 'dpi-blocks' ),
				'icon'  => null,
			);
		}

		$category['slug']  = sanitize_key( (string) $category['slug'] );
		$category['title'] = sanitize_text_field( (string) $category['title'] );
		if ( '' === $category['slug'] || '' === $category['title'] ) {
			return array(
				'slug'  => 'dpi-blocks',
				'title' => __( 'DPI Blocks', 'dpi-blocks' ),
				'icon'  => null,
			);
		}

		return $category;
	}

	/** Confirm canonical metadata came from its bundled block.json. */
	private function is_canonical_source( array $metadata, string $name ): bool {
		if ( empty( $metadata['file'] ) || ! is_string( $metadata['file'] ) || str_contains( $metadata['file'], "\0" ) ) {
			return false;
		}

		$slug     = substr( $name, 4 );
		$expected = realpath( DPI_BLOCKS_DIR . 'blocks/' . $slug . '/block.json' );
		$source   = realpath( $metadata['file'] );

		if ( ! is_string( $expected ) || ! is_string( $source ) ) {
			return false;
		}

		$expected = wp_normalize_path( $expected );
		$source   = wp_normalize_path( $source );
		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$expected = strtolower( $expected );
			$source   = strtolower( $source );
		}

		return $expected === $source;
	}

	/**
	 * Copy only validated, theme-customizable metadata keys over the original.
	 *
	 * @param array<string,mixed> $original Original bundled metadata.
	 * @param array<string,mixed> $filtered Filtered theme metadata.
	 * @param string              $name     Canonical block name.
	 * @return array<string,mixed>
	 */
	private function merge_customizable_metadata( array $original, array $filtered, string $name ): array {
		$merged = $original;

		foreach ( self::CUSTOMIZABLE_METADATA_KEYS as $key ) {
			if ( ! array_key_exists( $key, $filtered ) ) {
				if ( 'title' === $key ) {
					continue;
				}
				unset( $merged[ $key ] );
				continue;
			}

			if ( $this->valid_metadata_value( $key, $filtered[ $key ] ) ) {
				$merged[ $key ] = $filtered[ $key ];
				continue;
			}

			$this->registration_notices[] = sprintf(
				/* translators: 1: metadata key, 2: canonical block name. */
				__( 'DPI Blocks ignored an invalid %1$s metadata value for %2$s.', 'dpi-blocks' ),
				$key,
				$name
			);
		}

		return $merged;
	}

	/** Validate the broad value shapes accepted by WordPress block metadata. */
	private function valid_metadata_value( string $key, mixed $value ): bool {
		if ( 'title' === $key ) {
			return is_string( $value ) && '' !== trim( $value );
		}

		if ( 'description' === $key ) {
			return is_string( $value );
		}

		if ( 'icon' === $key ) {
			return is_string( $value ) || is_array( $value );
		}

		return is_array( $value );
	}
}
