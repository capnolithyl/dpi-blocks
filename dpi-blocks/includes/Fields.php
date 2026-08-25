<?php
/**
 * Authoritative ACF field group loader.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Fields {
	/** @var list<string> */
	private array $errors = array();

	/** @var array{keys: array<string, true>, names: array<string, true>}|null */
	private ?array $bundled_field_identifiers = null;

	/** @var array<string, array{keys: array<string, true>, names: array<string, true>}> */
	private array $theme_field_identifiers = array();

	/** Register ACF integrations. */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'register_groups' ), 5 );
		add_action( 'init', array( $this, 'register_directory_groups' ), 25 );
		add_action( 'init', array( $this, 'register_ministry_options_page' ), 25 );
		add_filter( 'acf/load_field', array( $this, 'populate_icon_choices' ) );
		add_action( 'admin_notices', array( $this, 'render_errors' ) );
	}

	/** Register the optional ministry archive settings screen. */
	public function register_ministry_options_page(): void {
		if (
			! Settings::get( 'ministry_enabled', false )
			|| ! ContentTypes::owns( 'ministry' )
			|| ! function_exists( 'acf_add_options_sub_page' )
		) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'Ministry Archive Settings', 'dpi-blocks' ),
				'menu_title'  => __( 'Archive Settings', 'dpi-blocks' ),
				'menu_slug'   => 'dpi-ministry-settings',
				'parent_slug' => 'edit.php?post_type=ministry',
				'capability'  => 'manage_options',
				'post_id'     => 'dpi_ministry_settings',
				'redirect'    => false,
			)
		);
	}

	/** Load every checked-in block JSON group directly into ACF. */
	public function register_groups(): void {
		$this->register_group_files();
	}

	/** Load enabled, conflict-free content-type groups after ownership is known. */
	public function register_directory_groups(): void {
		foreach ( array( 'staff', 'ministry' ) as $module ) {
			if ( Settings::get( $module . '_enabled', false ) && ContentTypes::owns( $module ) ) {
				$this->register_group_files( $module );
			}
		}
	}

	/** Load the JSON files belonging to the requested feature family. */
	private function register_group_files( ?string $requested_module = null ): void {
		$files = glob( DPI_BLOCKS_DIR . 'acf-json/*.json' );
		if ( false === $files ) {
			return;
		}

		sort( $files, SORT_NATURAL );

		foreach ( $files as $file ) {
			$module = $this->module_for_file( basename( $file ) );
			if ( $requested_module !== $module ) {
				continue;
			}

			$data = wp_json_file_decode( $file, array( 'associative' => true ) );

			if ( ! is_array( $data ) ) {
				$this->add_source_error( $file, __( 'the file could not be decoded', 'dpi-blocks' ) );
				continue;
			}

			$source_error = $this->validate_group( $data, $data );
			if ( null !== $source_error ) {
				$this->add_source_error( $file, $source_error );
				continue;
			}

			$context  = $this->field_group_context( $data, $file, $module, $requested_module );
			$filtered = $this->filter_group( $data, $context, $file );

			if ( false === $filtered ) {
				continue;
			}

			acf_add_local_field_group( $filtered );
		}
	}

	/**
	 * Apply the theme extension filters without risking field registration.
	 *
	 * @param array<string, mixed> $original Bundled field group.
	 * @param array<string, mixed> $context  Stable filter context.
	 * @param string               $file     Absolute source file.
	 * @return array<string, mixed>|false
	 */
	private function filter_group( array $original, array $context, string $file ): array|false {
		$group_key = (string) $original['key'];

		try {
			$filtered = apply_filters( 'dpi_blocks/acf_field_group', $original, $context );
		} catch ( \Throwable ) {
			return $this->fallback_group( $original, $file, __( 'a theme field filter could not be applied', 'dpi-blocks' ) );
		}

		if ( false === $filtered ) {
			unset( $this->theme_field_identifiers[ $group_key ] );
			return false;
		}

		if ( ! is_array( $filtered ) ) {
			return $this->fallback_group( $original, $file, __( 'the filter did not return an array or false', 'dpi-blocks' ) );
		}

		try {
			$filtered = apply_filters( 'dpi_blocks/acf_field_group/key=' . $original['key'], $filtered, $context );
		} catch ( \Throwable ) {
			return $this->fallback_group( $original, $file, __( 'a theme field filter could not be applied', 'dpi-blocks' ) );
		}

		if ( false === $filtered ) {
			unset( $this->theme_field_identifiers[ $group_key ] );
			return false;
		}

		if ( ! is_array( $filtered ) ) {
			return $this->fallback_group( $original, $file, __( 'the filter did not return an array or false', 'dpi-blocks' ) );
		}

		$new_identifiers = null;
		$filter_error    = $this->validate_group( $filtered, $original, $new_identifiers );
		if ( null !== $filter_error ) {
			return $this->fallback_group( $original, $file, $filter_error );
		}

		$identifier_error = $this->validate_global_identifiers( $group_key, $new_identifiers ?? array() );
		if ( null !== $identifier_error ) {
			return $this->fallback_group( $original, $file, $identifier_error );
		}

		$this->theme_field_identifiers[ $group_key ] = $new_identifiers ?? array(
			'keys'  => array(),
			'names' => array(),
		);

		return $filtered;
	}

	/**
	 * Restore a bundled group and release identifiers from an earlier registration pass.
	 *
	 * @param array<string, mixed> $original Bundled field group.
	 */
	private function fallback_group( array $original, string $file, string $reason ): array {
		unset( $this->theme_field_identifiers[ (string) $original['key'] ] );
		$this->add_override_error( $file, $reason );

		return $original;
	}

	/**
	 * Build the stable context passed to field-group filters.
	 *
	 * @param array<string, mixed> $group            Bundled field group.
	 * @param string               $file             Absolute source file.
	 * @param string|null          $module           Optional content-type module.
	 * @param string|null          $requested_module Current registration pass.
	 * @return array<string, mixed>
	 */
	private function field_group_context( array $group, string $file, ?string $module, ?string $requested_module ): array {
		$block_name = $this->block_name_for_group( $group );
		$group_key  = (string) $group['key'];
		$slug       = $block_name
			? substr( $block_name, (int) strpos( $block_name, '/' ) + 1 )
			: ( null !== $module ? $module : str_replace( 'group_dpi_', '', $group_key ) );

		$canonical_name = null !== $block_name ? $block_name : $group_key;

		return array(
			'canonical_name'     => $canonical_name,
			'name'               => $canonical_name,
			'slug'               => $slug,
			'source_file'        => wp_normalize_path( $file ),
			'group_key'          => $group_key,
			'block_name'         => $block_name,
			'module'             => $module,
			'registration_state' => array(
				'requested_module' => $requested_module,
				'module_enabled'   => null === $module || (bool) Settings::get( $module . '_enabled', false ),
				'module_owned'     => null === $module || ContentTypes::owns( $module ),
			),
		);
	}

	/**
	 * Find the canonical block name in a group's location rules.
	 *
	 * @param array<string, mixed> $group Field group.
	 */
	private function block_name_for_group( array $group ): ?string {
		$locations = $group['location'] ?? array();

		if ( ! is_array( $locations ) ) {
			return null;
		}

		$iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $locations ) );
		foreach ( $iterator as $key => $value ) {
			if ( 'value' !== $key || ! is_string( $value ) || ! str_starts_with( $value, 'dpi/' ) ) {
				continue;
			}

			$current = $iterator->getSubIterator();
			if ( 'block' === ( $current['param'] ?? '' ) ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Validate a filtered group without rewriting theme-owned changes.
	 *
	 * @param array<string, mixed> $group    Candidate field group.
	 * @param array<string, mixed> $original Bundled field group.
	 * @param array{keys: array<string, true>, names: array<string, true>}|null $new_identifiers New identifiers.
	 */
	private function validate_group( array $group, array $original, ?array &$new_identifiers = null ): ?string {
		$new_identifiers = array(
			'keys'  => array(),
			'names' => array(),
		);

		if ( empty( $group['key'] ) || ! is_string( $group['key'] ) ) {
			return __( 'the group key is missing', 'dpi-blocks' );
		}

		if ( (string) ( $original['key'] ?? '' ) !== $group['key'] ) {
			return __( 'the bundled group key was changed', 'dpi-blocks' );
		}

		if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
			return __( 'the group has no fields', 'dpi-blocks' );
		}

		if ( empty( $group['location'] ) || ! is_array( $group['location'] ) ) {
			return __( 'the group has no location rules', 'dpi-blocks' );
		}

		$original_fields  = array();
		$unused_refs      = array();
		$unused_collapsed = array();
		$error            = null;

		if (
			! $this->collect_fields(
				$original['fields'] ?? array(),
				$original_fields,
				$unused_refs,
				$unused_collapsed,
				$error
			)
		) {
			return null !== $error ? $error : __( 'the bundled fields are malformed', 'dpi-blocks' );
		}

		$fields        = array();
		$conditional   = array();
		$collapsed     = array();
		$collect_error = null;

		if ( ! $this->collect_fields( $group['fields'], $fields, $conditional, $collapsed, $collect_error ) ) {
			return null !== $collect_error ? $collect_error : __( 'the filtered fields are malformed', 'dpi-blocks' );
		}

		$retained_names = array();
		foreach ( $fields as $field_key => $field_name ) {
			if ( ! array_key_exists( $field_key, $original_fields ) ) {
				continue;
			}

			if ( $field_name !== $original_fields[ $field_key ] ) {
				return sprintf(
					/* translators: %s: ACF field key. */
					__( 'the retained field %s changed its name', 'dpi-blocks' ),
					$field_key
				);
			}

			if ( '' !== $field_name ) {
				$retained_names[ $field_name ] = true;
			}
		}

		$theme_prefixes = $this->theme_field_prefixes();
		$new_names      = array();
		foreach ( $fields as $field_key => $field_name ) {
			if ( array_key_exists( $field_key, $original_fields ) ) {
				continue;
			}

			$valid_key  = 1 === preg_match( '/^field_[a-z0-9_]+$/', $field_key );
			$valid_name = 1 === preg_match( '/^[a-z][a-z0-9_]*$/', $field_name );

			if (
				! $valid_key
				|| ! $valid_name
				|| ! $this->has_theme_prefix( $field_key, 'field_', $theme_prefixes )
				|| ! $this->has_theme_prefix( $field_name, '', $theme_prefixes )
			) {
				return sprintf(
					/* translators: %s: ACF field key. */
					__( 'the new field %s is not prefixed with the active theme slug', 'dpi-blocks' ),
					$field_key
				);
			}

			if ( isset( $retained_names[ $field_name ] ) || isset( $new_names[ $field_name ] ) ) {
				return sprintf(
					/* translators: %s: ACF field name. */
					__( 'the new field name %s is not unique', 'dpi-blocks' ),
					$field_name
				);
			}

			$new_names[ $field_name ]                = true;
			$new_identifiers['keys'][ $field_key ]   = true;
			$new_identifiers['names'][ $field_name ] = true;
		}

		foreach ( $conditional as $reference ) {
			if ( ! isset( $fields[ $reference ] ) ) {
				return sprintf(
					/* translators: %s: missing ACF field key. */
					__( 'conditional logic references missing field %s', 'dpi-blocks' ),
					$reference
				);
			}
		}

		foreach ( $collapsed as $reference ) {
			if ( ! in_array( $reference['target'], $reference['descendants'], true ) ) {
				return sprintf(
					/* translators: 1: container field key, 2: missing collapsed field key. */
					__( 'field %1$s has an invalid collapsed reference to %2$s', 'dpi-blocks' ),
					$reference['container'],
					$reference['target']
				);
			}
		}

		return null;
	}

	/**
	 * Reject new identifiers already owned by bundled or accepted theme fields.
	 *
	 * @param string $group_key Candidate group key.
	 * @param array{keys: array<string, true>, names: array<string, true>} $identifiers Candidate identifiers.
	 */
	private function validate_global_identifiers( string $group_key, array $identifiers ): ?string {
		$bundled = $this->bundled_field_identifiers();

		foreach ( array_keys( $identifiers['keys'] ?? array() ) as $field_key ) {
			if ( isset( $bundled['keys'][ $field_key ] ) ) {
				return sprintf(
					/* translators: %s: ACF field key. */
					__( 'the new field key %s is already used by a bundled field', 'dpi-blocks' ),
					$field_key
				);
			}

			foreach ( $this->theme_field_identifiers as $owner => $accepted ) {
				if ( $group_key !== $owner && isset( $accepted['keys'][ $field_key ] ) ) {
					return sprintf(
						/* translators: %s: ACF field key. */
						__( 'the new field key %s is already used by another filtered group', 'dpi-blocks' ),
						$field_key
					);
				}
			}
		}

		foreach ( array_keys( $identifiers['names'] ?? array() ) as $field_name ) {
			if ( isset( $bundled['names'][ $field_name ] ) ) {
				return sprintf(
					/* translators: %s: ACF field name. */
					__( 'the new field name %s is already used by a bundled field', 'dpi-blocks' ),
					$field_name
				);
			}

			foreach ( $this->theme_field_identifiers as $owner => $accepted ) {
				if ( $group_key !== $owner && isset( $accepted['names'][ $field_name ] ) ) {
					return sprintf(
						/* translators: %s: ACF field name. */
						__( 'the new field name %s is already used by another filtered group', 'dpi-blocks' ),
						$field_name
					);
				}
			}
		}

		return null;
	}

	/** Return a cached index of every identifier shipped in the plugin JSON. */
	private function bundled_field_identifiers(): array {
		if ( null !== $this->bundled_field_identifiers ) {
			return $this->bundled_field_identifiers;
		}

		$this->bundled_field_identifiers = array(
			'keys'  => array(),
			'names' => array(),
		);
		$files                           = glob( DPI_BLOCKS_DIR . 'acf-json/*.json' );

		if ( false === $files ) {
			return $this->bundled_field_identifiers;
		}

		foreach ( $files as $file ) {
			try {
				$group = wp_json_file_decode( $file, array( 'associative' => true ) );
			} catch ( \Throwable ) {
				continue;
			}

			if ( ! is_array( $group ) || empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			$fields      = array();
			$conditional = array();
			$collapsed   = array();
			$error       = null;
			if ( false === $this->collect_fields( $group['fields'], $fields, $conditional, $collapsed, $error ) ) {
				continue;
			}

			foreach ( $fields as $field_key => $field_name ) {
				$this->bundled_field_identifiers['keys'][ $field_key ] = true;
				if ( '' !== $field_name ) {
					$this->bundled_field_identifiers['names'][ $field_name ] = true;
				}
			}
		}

		return $this->bundled_field_identifiers;
	}

	/**
	 * Collect nested field identities and references.
	 *
	 * @param array<int|string, mixed>                                                    $source      Fields to inspect.
	 * @param array<string, string> $fields      Collected key/name pairs.
	 * @param list<string>          $conditional Conditional field keys.
	 * @param list<array{container: string, target: string, descendants: list<string>}> $collapsed   Collapsed references.
	 * @param string|null $error Validation error.
	 * @return list<string>|false Descendant keys, or false on malformed input.
	 */
	private function collect_fields(
		array $source,
		array &$fields,
		array &$conditional,
		array &$collapsed,
		?string &$error
	): array|false {
		$collected_here = array();

		foreach ( $source as $field ) {
			if (
				! is_array( $field )
				|| empty( $field['key'] )
				|| ! is_string( $field['key'] )
				|| ! array_key_exists( 'name', $field )
				|| ! is_string( $field['name'] )
			) {
				$error = __( 'a field is missing a valid key or name', 'dpi-blocks' );
				return false;
			}

			$field_key = $field['key'];
			if ( array_key_exists( $field_key, $fields ) ) {
				$error = sprintf(
					/* translators: %s: duplicated ACF field key. */
					__( 'field key %s is not unique', 'dpi-blocks' ),
					$field_key
				);
				return false;
			}

			$fields[ $field_key ] = $field['name'];
			$collected_here[]     = $field_key;

			$logic = $field['conditional_logic'] ?? false;
			if ( ! empty( $logic ) ) {
				if ( ! is_array( $logic ) ) {
					$error = sprintf(
						/* translators: %s: ACF field key. */
						__( 'field %s has malformed conditional logic', 'dpi-blocks' ),
						$field_key
					);
					return false;
				}

				foreach ( $logic as $rules ) {
					if ( ! is_array( $rules ) ) {
						$error = sprintf(
							/* translators: %s: ACF field key. */
							__( 'field %s has malformed conditional logic', 'dpi-blocks' ),
							$field_key
						);
						return false;
					}

					foreach ( $rules as $rule ) {
						if ( ! is_array( $rule ) || empty( $rule['field'] ) || ! is_string( $rule['field'] ) ) {
							$error = sprintf(
								/* translators: %s: ACF field key. */
								__( 'field %s has malformed conditional logic', 'dpi-blocks' ),
								$field_key
							);
							return false;
						}

						$conditional[] = $rule['field'];
					}
				}
			}

			$descendants = array();
			if ( array_key_exists( 'sub_fields', $field ) ) {
				if ( ! is_array( $field['sub_fields'] ) ) {
					$error = sprintf(
						/* translators: %s: ACF field key. */
						__( 'field %s has malformed sub fields', 'dpi-blocks' ),
						$field_key
					);
					return false;
				}

				$child_keys = $this->collect_fields( $field['sub_fields'], $fields, $conditional, $collapsed, $error );
				if ( false === $child_keys ) {
					return false;
				}
				$descendants = array_merge( $descendants, $child_keys );
			}

			if ( array_key_exists( 'layouts', $field ) ) {
				if ( ! is_array( $field['layouts'] ) ) {
					$error = sprintf(
						/* translators: %s: ACF field key. */
						__( 'field %s has malformed layouts', 'dpi-blocks' ),
						$field_key
					);
					return false;
				}

				foreach ( $field['layouts'] as $layout ) {
					if ( ! is_array( $layout ) || ( isset( $layout['sub_fields'] ) && ! is_array( $layout['sub_fields'] ) ) ) {
						$error = sprintf(
							/* translators: %s: ACF field key. */
							__( 'field %s has malformed layouts', 'dpi-blocks' ),
							$field_key
						);
						return false;
					}

					$child_keys = $this->collect_fields( $layout['sub_fields'] ?? array(), $fields, $conditional, $collapsed, $error );
					if ( false === $child_keys ) {
						return false;
					}
					$descendants = array_merge( $descendants, $child_keys );
				}
			}

			if ( ! empty( $field['collapsed'] ) ) {
				if ( ! is_string( $field['collapsed'] ) ) {
					$error = sprintf(
						/* translators: %s: ACF field key. */
						__( 'field %s has a malformed collapsed reference', 'dpi-blocks' ),
						$field_key
					);
					return false;
				}

				$collapsed[] = array(
					'container'   => $field_key,
					'target'      => $field['collapsed'],
					'descendants' => $descendants,
				);
			}

			$collected_here = array_merge( $collected_here, $descendants );
		}

		return $collected_here;
	}

	/** Return normalized child- and parent-theme prefixes for new fields. */
	private function theme_field_prefixes(): array {
		$prefixes = array();
		$slugs    = array_filter(
			array(
				function_exists( 'get_stylesheet' ) ? (string) get_stylesheet() : '',
				function_exists( 'get_template' ) ? (string) get_template() : '',
			)
		);

		foreach ( $slugs as $slug ) {
			$prefix = strtolower( str_replace( '-', '_', sanitize_key( $slug ) ) );
			$prefix = trim( (string) preg_replace( '/[^a-z0-9_]+/', '_', $prefix ), '_' );

			if ( '' !== $prefix ) {
				$prefixes[] = $prefix . '_';
			}
		}

		return array_values( array_unique( $prefixes ) );
	}

	/** Check whether a new field identifier uses an active theme prefix. */
	private function has_theme_prefix( string $value, string $base, array $prefixes ): bool {
		foreach ( $prefixes as $prefix ) {
			if ( str_starts_with( $value, $base . $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/** Record an invalid bundled definition. */
	private function add_source_error( string $file, string $reason ): void {
		$this->errors[] = sprintf(
			/* translators: 1: JSON filename, 2: validation reason. */
			__( '%1$s was skipped because %2$s.', 'dpi-blocks' ),
			basename( $file ),
			$reason
		);
	}

	/** Record a rejected theme filter result. */
	private function add_override_error( string $file, string $reason ): void {
		$this->errors[] = sprintf(
			/* translators: 1: JSON filename, 2: validation reason. */
			__( '%1$s used its bundled definition because %2$s.', 'dpi-blocks' ),
			basename( $file ),
			$reason
		);
	}

	/** Return the optional directory module associated with an ACF JSON file. */
	private function module_for_file( string $filename ): ?string {
		$modules = array(
			'group_dpi_staff_details.json'    => 'staff',
			'group_dpi_ministry_details.json' => 'ministry',
			'group_dpi_ministry_archive.json' => 'ministry',
		);

		return $modules[ $filename ] ?? null;
	}

	/** Populate only plugin-owned Font Awesome select fields. */
	public function populate_icon_choices( array $field ): array {
		$is_plugin_icon = ! empty( $field['dpi_font_awesome'] );
		$is_named_icon  = isset( $field['key'], $field['name'] )
			&& str_starts_with( (string) $field['key'], 'field_dpi_' )
			&& 'icon' === $field['name'];

		if ( 'select' === ( $field['type'] ?? '' ) && ( $is_plugin_icon || $is_named_icon ) ) {
			$field['choices'] = IconRegistry::choices();
			$field['ui']      = 1;
		}

		return $field;
	}

	/** Report invalid plugin-owned JSON or filtered definitions without exposing paths. */
	public function render_errors(): void {
		if ( ! $this->errors || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: one or more ACF field-definition notices. */
					__( 'DPI Blocks ACF field notice: %s', 'dpi-blocks' ),
					implode( ' ', array_unique( $this->errors ) )
				)
			)
		);
	}
}
