<?php
/**
 * Theme-overridable directory templates and request preparation.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

use WP_Post;
use WP_Query;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Route plugin-owned content types through neutral fallback templates. */
final class TemplateLoader {
	/** Register hooks. */
	public function register(): void {
		add_filter( 'template_include', array( $this, 'choose_template' ), 99 );
		add_action( 'pre_get_posts', array( $this, 'configure_directory_queries' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
	}

	/**
	 * Prefer a theme's explicit or standard type-specific template.
	 *
	 * @param string $template Template chosen by WordPress.
	 */
	public function choose_template( string $template ): string {
		$route = $this->current_route();
		if ( null === $route || ! ContentTypes::owns( $route['post_type'] ) ) {
			return $template;
		}

		/**
		 * Filter theme-relative template candidates for a DPI directory request.
		 *
		 * @param list<string>         $candidates Theme-relative candidate paths.
		 * @param array<string, mixed> $route      Route descriptor containing post_type,
		 *                                         theme_candidates and plugin_template.
		 */
		$candidates = apply_filters( 'dpi_blocks/template_candidates', $route['theme_candidates'], $route );
		$candidates = is_array( $candidates ) ? array_values( array_filter( array_map( 'strval', $candidates ) ) ) : $route['theme_candidates'];
		$override   = locate_template( $candidates, false, false );
		if ( $override ) {
			return $override;
		}

		$fallback = self::template_path( $route['plugin_template'] );
		return is_readable( $fallback ) ? $fallback : $template;
	}

	/** Load every directory item for stable grouping and menu-order sorting. */
	public function configure_directory_queries( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$is_staff    = ContentTypes::owns( 'staff' ) && ( $query->is_post_type_archive( 'staff' ) || $query->is_tax( 'staff_group' ) );
		$is_ministry = ContentTypes::owns( 'ministry' ) && ( $query->is_post_type_archive( 'ministry' ) || $query->is_tax( 'ministry_group' ) );
		$is_office   = ContentTypes::owns( 'office' ) && ( $query->is_post_type_archive( 'office' ) || $query->is_tax( 'office_group' ) );
		if ( ! $is_staff && ! $is_ministry && ! $is_office ) {
			return;
		}

		$query->set( 'posts_per_page', -1 );
		$query->set( 'no_found_rows', true );
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
		$query->set( 'order', 'ASC' );
	}

	/** Conditionally register and load directory assets. */
	public function enqueue_assets(): void {
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
		wp_register_style(
			'dpi-blocks-directory',
			DPI_BLOCKS_URL . 'assets/css/directory.css',
			array(),
			DPI_BLOCKS_VERSION
		);
		wp_register_script(
			'dpi-blocks-directory',
			DPI_BLOCKS_URL . 'assets/js/directory.js',
			array( 'jquery' ),
			DPI_BLOCKS_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		if ( ! $this->is_directory_request() ) {
			return;
		}

		wp_enqueue_style( 'dpi-blocks-directory' );

		$needs_group_navigation = false;
		if ( ContentTypes::owns( 'staff' ) && is_post_type_archive( 'staff' ) ) {
			$context                = self::directory_context( 'staff', 'staff_group' );
			$needs_group_navigation = count( $context['sections'] ) > 1;
		} elseif ( ContentTypes::owns( 'ministry' ) && is_post_type_archive( 'ministry' ) ) {
			$context                = self::directory_context( 'ministry', 'ministry_group' );
			$needs_group_navigation = count( $context['sections'] ) > 1;
		} elseif ( ContentTypes::owns( 'office' ) && is_post_type_archive( 'office' ) ) {
			$context                = self::directory_context( 'office', 'office_group' );
			$needs_group_navigation = count( $context['sections'] ) > 1;
		}

		$needs_staff_dialogs = ContentTypes::owns( 'staff' )
			&& ( is_post_type_archive( 'staff' ) || is_tax( 'staff_group' ) )
			&& 'modal' === Settings::get( 'staff_mode', 'single' );

		if ( $needs_group_navigation ) {
			wp_enqueue_style( 'dpi-blocks-slick' );
			wp_enqueue_script( 'dpi-blocks-slick' );

			$scripts = wp_scripts();
			if ( isset( $scripts->registered['dpi-blocks-directory'] ) && ! in_array( 'dpi-blocks-slick', $scripts->registered['dpi-blocks-directory']->deps, true ) ) {
				$scripts->registered['dpi-blocks-directory']->deps[] = 'dpi-blocks-slick';
			}
		}

		if ( $needs_group_navigation || $needs_staff_dialogs ) {
			wp_enqueue_script( 'dpi-blocks-directory' );
		}
	}

	/** Return an absolute plugin-template path. */
	public static function template_path( string $relative ): string {
		$relative = ltrim( str_replace( array( '../', '..\\' ), '', $relative ), '/\\' );
		return DPI_BLOCKS_DIR . 'templates/' . $relative;
	}

	/**
	 * Build grouped archive data from the current main query.
	 *
	 * Posts assigned to multiple groups intentionally appear in each group.
	 *
	 * @return array<string, mixed>
	 */
	public static function directory_context( string $post_type, string $taxonomy ): array {
		global $wp_query;

		$posts       = array_values( array_filter( $wp_query->posts ?? array(), static fn( $post ): bool => $post instanceof WP_Post && $post_type === $post->post_type ) );
		$is_taxonomy = is_tax( $taxonomy );
		$sections    = array();
		$all_posts   = array();

		foreach ( $posts as $post ) {
			$all_posts[ $post->ID ] = $post;
		}

		if ( $is_taxonomy ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$sections[] = array(
					'id'    => self::section_id( $taxonomy, $term->slug ),
					'label' => $term->name,
					'posts' => $posts,
				);
			}
		} else {
			$terms        = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => 'term_id',
					'order'      => 'ASC',
				)
			);
			$terms        = is_wp_error( $terms ) ? array() : $terms;
			$term_indexes = array();

			foreach ( $terms as $term ) {
				$term_indexes[ $term->term_id ] = count( $sections );
				$sections[]                     = array(
					'id'    => self::section_id( $taxonomy, $term->slug ),
					'label' => $term->name,
					'posts' => array(),
				);
			}

			$ungrouped = array();
			foreach ( $posts as $post ) {
				$assigned = get_the_terms( $post->ID, $taxonomy );
				$matched  = false;
				if ( $assigned && ! is_wp_error( $assigned ) ) {
					foreach ( $assigned as $term ) {
						if ( isset( $term_indexes[ $term->term_id ] ) ) {
							$sections[ $term_indexes[ $term->term_id ] ]['posts'][] = $post;
							$matched = true;
						}
					}
				}
				if ( ! $matched ) {
					$ungrouped[] = $post;
				}
			}

			if ( $ungrouped ) {
				$sections[] = array(
					'id'    => self::unique_ungrouped_section_id( $taxonomy, $sections ),
					'label' => self::ungrouped_label( $post_type ),
					'posts' => $ungrouped,
				);
			}
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object ) {
			$archive_title = $post_type_object->labels->archives ? $post_type_object->labels->archives : $post_type_object->labels->name;
		} else {
			$archive_title = ucfirst( $post_type );
		}
		$title       = $is_taxonomy ? single_term_title( '', false ) : $archive_title;
		$description = $is_taxonomy ? term_description() : '';

		return array(
			'post_type'   => $post_type,
			'taxonomy'    => $taxonomy,
			'title'       => $title,
			'description' => $description,
			'sections'    => $sections,
			'all_posts'   => array_values( $all_posts ),
			'modal'       => 'staff' === $post_type && 'modal' === Settings::get( 'staff_mode', 'single' ),
			'group_label' => self::group_navigation_label( $post_type ),
		);
	}

	/** Normalize an ACF image value to an attachment ID. */
	public static function image_id( mixed $image ): int {
		if ( is_array( $image ) ) {
			return absint( $image['ID'] ?? $image['id'] ?? 0 );
		}
		return is_numeric( $image ) ? absint( $image ) : 0;
	}

	/** Describe the current plugin-owned request. */
	private function current_route(): ?array {
		if ( is_post_type_archive( 'staff' ) ) {
			return array(
				'post_type'        => 'staff',
				'theme_candidates' => array( 'dpi-blocks/archive-staff.php', 'archive-staff.php' ),
				'plugin_template'  => 'archive-staff.php',
			);
		}
		if ( is_tax( 'staff_group' ) ) {
			return array(
				'post_type'        => 'staff',
				'theme_candidates' => array( 'dpi-blocks/taxonomy-staff_group.php', 'taxonomy-staff_group.php' ),
				'plugin_template'  => 'taxonomy-staff_group.php',
			);
		}
		if ( is_singular( 'staff' ) ) {
			return array(
				'post_type'        => 'staff',
				'theme_candidates' => array( 'dpi-blocks/single-staff.php', 'single-staff.php' ),
				'plugin_template'  => 'single-staff.php',
			);
		}
		if ( is_post_type_archive( 'ministry' ) ) {
			return array(
				'post_type'        => 'ministry',
				'theme_candidates' => array( 'dpi-blocks/archive-ministry.php', 'archive-ministry.php' ),
				'plugin_template'  => 'archive-ministry.php',
			);
		}
		if ( is_tax( 'ministry_group' ) ) {
			return array(
				'post_type'        => 'ministry',
				'theme_candidates' => array( 'dpi-blocks/taxonomy-ministry_group.php', 'taxonomy-ministry_group.php' ),
				'plugin_template'  => 'taxonomy-ministry_group.php',
			);
		}
		if ( is_singular( 'ministry' ) ) {
			return array(
				'post_type'        => 'ministry',
				'theme_candidates' => array( 'dpi-blocks/single-ministry.php', 'single-ministry.php' ),
				'plugin_template'  => 'single-ministry.php',
			);
		}
		if ( is_post_type_archive( 'office' ) ) {
			return array(
				'post_type'        => 'office',
				'theme_candidates' => array( 'dpi-blocks/archive-office.php', 'archive-office.php' ),
				'plugin_template'  => 'archive-office.php',
			);
		}
		if ( is_tax( 'office_group' ) ) {
			return array(
				'post_type'        => 'office',
				'theme_candidates' => array( 'dpi-blocks/taxonomy-office_group.php', 'taxonomy-office_group.php' ),
				'plugin_template'  => 'taxonomy-office_group.php',
			);
		}
		if ( is_singular( 'office' ) ) {
			return array(
				'post_type'        => 'office',
				'theme_candidates' => array( 'dpi-blocks/single-office.php', 'single-office.php' ),
				'plugin_template'  => 'single-office.php',
			);
		}

		return null;
	}

	/** Whether the current request belongs to a type owned by this plugin. */
	private function is_directory_request(): bool {
		return ( ContentTypes::owns( 'staff' ) && ( is_post_type_archive( 'staff' ) || is_tax( 'staff_group' ) || is_singular( 'staff' ) ) )
			|| ( ContentTypes::owns( 'ministry' ) && ( is_post_type_archive( 'ministry' ) || is_tax( 'ministry_group' ) || is_singular( 'ministry' ) ) )
			|| ( ContentTypes::owns( 'office' ) && ( is_post_type_archive( 'office' ) || is_tax( 'office_group' ) || is_singular( 'office' ) ) );
	}

	/** Accessible group-navigation label for a directory type. */
	private static function group_navigation_label( string $post_type ): string {
		switch ( $post_type ) {
			case 'staff':
				return __( 'Staff groups', 'dpi-blocks' );
			case 'office':
				return __( 'Office groups', 'dpi-blocks' );
			case 'ministry':
			default:
				return __( 'Ministry groups', 'dpi-blocks' );
		}
	}

	/** Label for directory entries not assigned to a group. */
	private static function ungrouped_label( string $post_type ): string {
		switch ( $post_type ) {
			case 'staff':
				return __( 'Other Staff', 'dpi-blocks' );
			case 'office':
				return __( 'Other Offices', 'dpi-blocks' );
			case 'ministry':
			default:
				return __( 'Other Ministries', 'dpi-blocks' );
		}
	}

	/** Return an ungrouped anchor that cannot collide with a real term slug. */
	private static function unique_ungrouped_section_id( string $taxonomy, array $sections ): string {
		$base     = self::section_id( $taxonomy, 'other' );
		$used     = array_fill_keys( array_column( $sections, 'id' ), true );
		$id       = $base;
		$sequence = 0;

		while ( isset( $used[ $id ] ) ) {
			++$sequence;
			$id = $base . '-ungrouped' . ( 1 < $sequence ? '-' . $sequence : '' );
		}

		return $id;
	}

	/** Stable section ID. */
	private static function section_id( string $taxonomy, string $slug ): string {
		return 'dpi-' . sanitize_html_class( str_replace( '_', '-', $taxonomy ) ) . '-' . sanitize_html_class( $slug );
	}
}
