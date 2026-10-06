<?php
/**
 * Optional directory content types.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register plugin-owned Staff, Ministry, and Office directories. */
final class ContentTypes {
	/** @var array<string, bool> */
	private static array $owned = array();

	/** @var array<string, list<string>> */
	private array $conflicts = array();

	/** Register hooks. */
	public function register(): void {
		// Run after conventional priority-10 registrations so ownership conflicts
		// can be refused rather than silently replacing another plugin or theme.
		add_action( 'init', array( $this, 'register_types' ), 20 );
		add_action( 'admin_notices', array( $this, 'render_conflict_notices' ) );
	}

	/** Register every enabled, conflict-free module. */
	public function register_types(): void {
		if ( Settings::get( 'staff_enabled', false ) ) {
			$this->register_staff();
		}

		if ( Settings::get( 'ministry_enabled', false ) ) {
			$this->register_ministry();
		}

		if ( Settings::get( 'office_enabled', false ) ) {
			$this->register_office();
		}
	}

	/** Determine whether this request's registration is plugin-owned. */
	public static function owns( string $post_type ): bool {
		return ! empty( self::$owned[ $post_type ] );
	}

	/** Register Staff and Staff Groups. */
	private function register_staff(): void {
		if ( self::owns( 'staff' ) ) {
			return;
		}

		if ( $this->has_conflict( 'staff', 'staff_group', 'staff' ) ) {
			return;
		}

		register_post_type(
			'staff',
			array(
				'labels'             => $this->post_type_labels( __( 'Staff Members', 'dpi-blocks' ), __( 'Staff Member', 'dpi-blocks' ) ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-businessperson',
				'menu_position'      => 20,
				'has_archive'        => Settings::get( 'staff_archive_slug', 'staff' ),
				'rewrite'            => array(
					'slug'       => Settings::get( 'staff_single_slug', 'staff' ),
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
				'taxonomies'         => array( 'staff_group' ),
			)
		);

		register_taxonomy(
			'staff_group',
			array( 'staff' ),
			array(
				'labels'             => $this->taxonomy_labels( __( 'Staff Groups', 'dpi-blocks' ), __( 'Staff Group', 'dpi-blocks' ) ),
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'rewrite'            => array(
					'slug'       => Settings::get( 'staff_taxonomy_slug', 'staff-group' ),
					'with_front' => false,
				),
			)
		);

		foreach ( array(
			'staff_position' => 'sanitize_text_field',
			'staff_email'    => 'sanitize_email',
			'staff_phone'    => 'sanitize_text_field',
		) as $key => $sanitize_callback ) {
			register_post_meta(
				'staff',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize_callback,
					'auth_callback'     => static fn( bool $allowed, string $meta_key, int $post_id ): bool => current_user_can( 'edit_post', $post_id ),
				)
			);
		}

		self::$owned['staff'] = post_type_exists( 'staff' ) && taxonomy_exists( 'staff_group' );
	}

	/** Register Ministries and Ministry Groups. */
	private function register_ministry(): void {
		if ( self::owns( 'ministry' ) ) {
			return;
		}

		if ( $this->has_conflict( 'ministry', 'ministry_group', 'ministry' ) ) {
			return;
		}

		register_post_type(
			'ministry',
			array(
				'labels'             => $this->post_type_labels( __( 'Ministries', 'dpi-blocks' ), __( 'Ministry', 'dpi-blocks' ) ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-groups',
				'menu_position'      => 21,
				'has_archive'        => Settings::get( 'ministry_archive_slug', 'ministries' ),
				'rewrite'            => array(
					'slug'       => Settings::get( 'ministry_single_slug', 'ministry' ),
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
				'taxonomies'         => array( 'ministry_group' ),
			)
		);

		register_taxonomy(
			'ministry_group',
			array( 'ministry' ),
			array(
				'labels'             => $this->taxonomy_labels( __( 'Ministry Groups', 'dpi-blocks' ), __( 'Ministry Group', 'dpi-blocks' ), false ),
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => false,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'rewrite'            => array(
					'slug'       => Settings::get( 'ministry_taxonomy_slug', 'ministry-group' ),
					'with_front' => false,
				),
			)
		);

		self::$owned['ministry'] = post_type_exists( 'ministry' ) && taxonomy_exists( 'ministry_group' );
	}

	/** Register Offices and Office Groups. */
	private function register_office(): void {
		if ( self::owns( 'office' ) ) {
			return;
		}

		if ( $this->has_conflict( 'office', 'office_group', 'office' ) ) {
			return;
		}

		register_post_type(
			'office',
			array(
				'labels'             => $this->post_type_labels( __( 'Offices', 'dpi-blocks' ), __( 'Office', 'dpi-blocks' ) ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'menu_icon'          => 'dashicons-building',
				'menu_position'      => 22,
				'has_archive'        => Settings::get( 'office_archive_slug', 'offices' ),
				'rewrite'            => array(
					'slug'       => Settings::get( 'office_single_slug', 'office' ),
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
				'taxonomies'         => array( 'office_group' ),
			)
		);

		register_taxonomy(
			'office_group',
			array( 'office' ),
			array(
				'labels'             => $this->taxonomy_labels( __( 'Office Groups', 'dpi-blocks' ), __( 'Office Group', 'dpi-blocks' ) ),
				'public'             => true,
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'show_ui'            => true,
				'show_admin_column'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'rewrite'            => array(
					'slug'       => Settings::get( 'office_taxonomy_slug', 'office-group' ),
					'with_front' => false,
				),
			)
		);

		self::$owned['office'] = post_type_exists( 'office' ) && taxonomy_exists( 'office_group' );
	}

	/**
	 * Detect a pre-existing internal key and record an actionable notice.
	 *
	 * @param string $post_type Post type key.
	 * @param string $taxonomy  Taxonomy key.
	 * @param string $module    Module setting prefix.
	 */
	private function has_conflict( string $post_type, string $taxonomy, string $module ): bool {
		$keys = array();
		if ( post_type_exists( $post_type ) ) {
			$keys[] = $post_type;
		}
		if ( taxonomy_exists( $taxonomy ) ) {
			$keys[] = $taxonomy;
		}

		if ( $keys ) {
			$this->conflicts[ $module ] = $keys;
			return true;
		}

		return false;
	}

	/** Display one notice per refused module. */
	public function render_conflict_notices(): void {
		if ( ! current_user_can( 'manage_options' ) || ! $this->conflicts ) {
			return;
		}

		$url = Settings::page_url( 'content-types' );
		foreach ( $this->conflicts as $module => $keys ) {
			printf(
				'<div class="notice notice-error"><p>%1$s</p></div>',
				wp_kses_post(
					sprintf(
						/* translators: 1: module name, 2: conflicting WordPress keys, 3: settings URL. */
						__( 'DPI Blocks did not start the %1$s module because these internal keys already exist: %2$s. <a href="%3$s">Disable this module</a> or remove the conflicting registration before enabling it.', 'dpi-blocks' ),
						esc_html( ucfirst( $module ) ),
						esc_html( implode( ', ', $keys ) ),
						esc_url( $url )
					)
				)
			);
		}
	}

	/** Standard post-type labels. */
	private function post_type_labels( string $plural, string $singular ): array {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'add_new'            => __( 'Add New', 'dpi-blocks' ),
			/* translators: %s: singular content type label. */
			'add_new_item'       => sprintf( __( 'Add New %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular content type label. */
			'edit_item'          => sprintf( __( 'Edit %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular content type label. */
			'new_item'           => sprintf( __( 'New %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular content type label. */
			'view_item'          => sprintf( __( 'View %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: plural content type label. */
			'all_items'          => sprintf( __( 'All %s', 'dpi-blocks' ), $plural ),
			/* translators: %s: plural content type label. */
			'search_items'       => sprintf( __( 'Search %s', 'dpi-blocks' ), $plural ),
			/* translators: %s: lowercase plural content type label. */
			'not_found'          => sprintf( __( 'No %s found.', 'dpi-blocks' ), strtolower( $plural ) ),
			/* translators: %s: lowercase plural content type label. */
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'dpi-blocks' ), strtolower( $plural ) ),
			/* translators: %s: plural content type label. */
			'archives'           => sprintf( __( '%s Archive', 'dpi-blocks' ), $plural ),
		);
	}

	/** Standard group taxonomy labels. */
	private function taxonomy_labels( string $plural, string $singular, bool $hierarchical = true ): array {
		$labels = array(
			'name'          => $plural,
			'singular_name' => $singular,
			'menu_name'     => $plural,
			/* translators: %s: plural taxonomy label. */
			'all_items'     => sprintf( __( 'All %s', 'dpi-blocks' ), $plural ),
			/* translators: %s: singular taxonomy label. */
			'edit_item'     => sprintf( __( 'Edit %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'view_item'     => sprintf( __( 'View %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'update_item'   => sprintf( __( 'Update %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'add_new_item'  => sprintf( __( 'Add New %s', 'dpi-blocks' ), $singular ),
			/* translators: %s: singular taxonomy label. */
			'new_item_name' => sprintf( __( 'New %s Name', 'dpi-blocks' ), $singular ),
			/* translators: %s: plural taxonomy label. */
			'search_items'  => sprintf( __( 'Search %s', 'dpi-blocks' ), $plural ),
		);

		if ( $hierarchical ) {
			/* translators: %s: singular taxonomy label. */
			$labels['parent_item'] = sprintf( __( 'Parent %s', 'dpi-blocks' ), $singular );
			/* translators: %s: singular taxonomy label. */
			$labels['parent_item_colon'] = sprintf( __( 'Parent %s:', 'dpi-blocks' ), $singular );
		} else {
			/* translators: %s: plural taxonomy label. */
			$labels['popular_items'] = sprintf( __( 'Popular %s', 'dpi-blocks' ), $plural );
			/* translators: %s: lowercase plural taxonomy label. */
			$labels['separate_items_with_commas'] = sprintf( __( 'Separate %s with commas', 'dpi-blocks' ), strtolower( $plural ) );
			/* translators: %s: lowercase plural taxonomy label. */
			$labels['add_or_remove_items'] = sprintf( __( 'Add or remove %s', 'dpi-blocks' ), strtolower( $plural ) );
			/* translators: %s: lowercase plural taxonomy label. */
			$labels['choose_from_most_used'] = sprintf( __( 'Choose from the most used %s', 'dpi-blocks' ), strtolower( $plural ) );
		}

		return $labels;
	}
}
