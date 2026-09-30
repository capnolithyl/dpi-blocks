<?php
/**
 * Administrative settings for DPI Blocks.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Own the plugin's single, versionable settings record.
 */
final class Settings {
	/** Option name. */
	public const OPTION_NAME = 'dpi_blocks_settings';

	/** Rewrite flush request flag. */
	public const REWRITE_FLAG = 'dpi_blocks_flush_rewrite_rules';

	/** Settings page slug. */
	public const PAGE_SLUG = 'dpi-blocks';

	/** Supported blocks. */
	private const BLOCKS = array(
		'accordion'         => 'Accordion / FAQ',
		'anchor-navigation' => 'Anchor Navigation',
		'community-slider'  => 'Community Slider',
		'feature-banner'    => 'Feature / CTA Banner',
		'featured-links'    => 'Featured Links',
		'five-pillars'      => 'Pillars',
		'hero'              => 'Hero',
		'image-buttons'     => 'Image Buttons',
		'image-buttons-alt' => 'Image Buttons Alt',
		'interior-hero'     => 'Interior Hero',
		'mass-times'        => 'Mass Times',
		'mission'           => 'Mission',
		'office-grid'       => 'Office Grid',
		'social-media'      => 'Social Media',
		'staff-card'        => 'Staff Card',
		'stats'             => 'Stats',
	);

	/** Register hooks. */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 1000 );
	}

	/**
	 * Return the complete default settings shape.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'blocks'                 => array_fill_keys( array_keys( self::BLOCKS ), true ),
			'top_bar_enabled'        => false,
			'top_bar_placement'      => 'automatic',
			'top_bar_menu_location'  => 'dpi-top-bar',
			'search_enabled'         => false,
			'search_location'        => 'top-bar',
			'search_menu_location'   => '',
			'search_menu_item_class' => '',
			'search_template'        => 'dialog',
			'staff_enabled'          => false,
			'staff_single_slug'      => 'staff',
			'staff_archive_slug'     => 'staff',
			'staff_taxonomy_slug'    => 'staff-group',
			'staff_mode'             => 'single',
			'ministry_enabled'       => false,
			'ministry_single_slug'   => 'ministry',
			'ministry_archive_slug'  => 'ministries',
			'ministry_taxonomy_slug' => 'ministry-group',
			'office_enabled'         => false,
			'office_single_slug'     => 'office',
			'office_archive_slug'    => 'offices',
			'office_taxonomy_slug'   => 'office-group',
			'social_profiles'        => array(
				'facebook'  => '',
				'instagram' => '',
				'youtube'   => '',
				'x-twitter' => '',
				'linkedin'  => '',
			),
			'social_feed_shortcode'  => '',
		);
	}

	/**
	 * Get all settings or one value.
	 *
	 * @param string|null $key     Setting key.
	 * @param mixed       $fallback Value returned for an unknown key.
	 * @return mixed
	 */
	public static function get( ?string $key = null, mixed $fallback = null ): mixed {
		$settings = self::merge_with_defaults( get_option( self::OPTION_NAME, array() ) );

		if ( null === $key ) {
			return $settings;
		}

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/** Ensure the option has a complete shape without overwriting saved values. */
	public static function ensure_defaults(): void {
		$current = get_option( self::OPTION_NAME, null );
		$merged  = self::merge_with_defaults( is_array( $current ) ? $current : array() );

		if ( ! is_array( $current ) || $current !== $merged ) {
			update_option( self::OPTION_NAME, $merged, false );
		}
	}

	/** Add Settings > DPI Blocks. */
	public function add_settings_page(): void {
		add_options_page(
			__( 'DPI Blocks', 'dpi-blocks' ),
			__( 'DPI Blocks', 'dpi-blocks' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/** Register the option and settings fields. */
	public function register_settings(): void {
		register_setting(
			'dpi_blocks',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}


	/**
	 * Load admin-only behavior for the Blocks settings tab.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'blocks'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only presentation state.
		if ( 'blocks' !== $tab ) {
			return;
		}

		wp_enqueue_script(
			'dpi-blocks-settings',
			DPI_BLOCKS_URL . 'assets/js/settings.js',
			array(),
			DPI_BLOCKS_VERSION,
			true
		);
	}

	/**
	 * Strictly sanitize the complete option payload.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input       = is_array( $input ) ? $input : array();
		$old         = self::get();
		$defaults    = self::defaults();
		$sanitized   = $defaults;
		$block_input = isset( $input['blocks'] ) && is_array( $input['blocks'] ) ? $input['blocks'] : array();

		foreach ( self::BLOCKS as $slug => $label ) {
			$sanitized['blocks'][ $slug ] = isset( $block_input[ $slug ] ) && '1' === (string) $block_input[ $slug ];
		}

		foreach ( array( 'top_bar_enabled', 'search_enabled', 'staff_enabled', 'ministry_enabled', 'office_enabled' ) as $key ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) && '1' === (string) $input[ $key ];
		}

		$sanitized['top_bar_placement']      = $this->choice( $input, 'top_bar_placement', array( 'automatic', 'manual' ), $defaults['top_bar_placement'] );
		$sanitized['search_location']        = $this->choice( $input, 'search_location', array( 'top-bar', 'main-menu' ), $defaults['search_location'] );
		$sanitized['search_template']        = $this->choice( $input, 'search_template', array( 'dialog', 'popover' ), $defaults['search_template'] );
		$sanitized['staff_mode']             = $this->choice( $input, 'staff_mode', array( 'single', 'modal' ), $defaults['staff_mode'] );
		$sanitized['top_bar_menu_location']  = $this->menu_location( $input['top_bar_menu_location'] ?? '', 'dpi-top-bar' );
		$sanitized['search_menu_location']   = $this->menu_location( $input['search_menu_location'] ?? '', '' );
		$sanitized['search_menu_item_class'] = $this->css_classes( (string) ( $input['search_menu_item_class'] ?? '' ) );

		foreach ( array( 'staff_single_slug', 'staff_archive_slug', 'staff_taxonomy_slug', 'ministry_single_slug', 'ministry_archive_slug', 'ministry_taxonomy_slug', 'office_single_slug', 'office_archive_slug', 'office_taxonomy_slug' ) as $key ) {
			$value             = sanitize_title( (string) ( $input[ $key ] ?? '' ) );
			$sanitized[ $key ] = '' !== $value ? $value : $defaults[ $key ];
		}
		$this->validate_route_slugs( $sanitized, $old );

		$profiles = isset( $input['social_profiles'] ) && is_array( $input['social_profiles'] ) ? $input['social_profiles'] : array();
		foreach ( array_keys( $defaults['social_profiles'] ) as $network ) {
			$sanitized['social_profiles'][ $network ] = esc_url_raw( (string) ( $profiles[ $network ] ?? '' ), array( 'http', 'https' ) );
		}
		$sanitized['social_feed_shortcode'] = sanitize_text_field( (string) ( $input['social_feed_shortcode'] ?? '' ) );

		$rewrite_keys = array(
			'staff_enabled',
			'staff_single_slug',
			'staff_archive_slug',
			'staff_taxonomy_slug',
			'ministry_enabled',
			'ministry_single_slug',
			'ministry_archive_slug',
			'ministry_taxonomy_slug',
			'office_enabled',
			'office_single_slug',
			'office_archive_slug',
			'office_taxonomy_slug',
		);
		foreach ( $rewrite_keys as $key ) {
			if ( $old[ $key ] !== $sanitized[ $key ] ) {
				update_option( self::REWRITE_FLAG, '1', false );
				break;
			}
		}

		return $sanitized;
	}

	/** Flush only after a settings save requested it. */
	public function maybe_flush_rewrite_rules(): void {
		if ( '1' !== get_option( self::REWRITE_FLAG ) ) {
			return;
		}

		flush_rewrite_rules( false );
		delete_option( self::REWRITE_FLAG );
	}

	/** Render the tabbed settings form. */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'blocks'      => __( 'Blocks', 'dpi-blocks' ),
			'header'      => __( 'Top Bar & Search', 'dpi-blocks' ),
			'directories' => __( 'Content Types', 'dpi-blocks' ),
			'social'      => __( 'Social', 'dpi-blocks' ),
		);
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'blocks'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only presentation state.
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'blocks';
		}
		$settings = self::get();
		$referer  = add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'options-general.php' )
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'DPI Blocks', 'dpi-blocks' ); ?></h1>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'DPI Blocks settings', 'dpi-blocks' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page' => self::PAGE_SLUG,
							'tab'  => $slug,
						),
						admin_url( 'options-general.php' )
					);
					?>
					<a class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $tab_url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form action="options.php" method="post">
				<?php settings_fields( 'dpi_blocks' ); ?>
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $referer ); ?>">
				<?php $this->render_tab( $tab, $settings ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render one settings tab while carrying fields from other tabs forward.
	 *
	 * @param string               $tab Active tab.
	 * @param array<string, mixed> $settings Settings.
	 */
	private function render_tab( string $tab, array $settings ): void {
		$this->render_preserved_fields( $tab, $settings );
		if ( 'blocks' === $tab ) {
			echo '<h2>' . esc_html__( 'Available blocks', 'dpi-blocks' ) . '</h2>';
			echo '<p>' . esc_html__( 'Disabled blocks are hidden from the inserter but remain registered so existing content keeps rendering.', 'dpi-blocks' ) . '</p>';
			echo '<p>';
			echo '<button type="button" class="button button-secondary" data-dpi-block-toggle="select">' . esc_html__( 'Select all', 'dpi-blocks' ) . '</button> ';
			echo '<button type="button" class="button button-secondary" data-dpi-block-toggle="deselect">' . esc_html__( 'Deselect all', 'dpi-blocks' ) . '</button>';
			echo '</p>';
			echo '<table class="form-table" role="presentation" data-dpi-block-list><tbody>';
			foreach ( self::BLOCKS as $slug => $label ) {
				$this->checkbox_row(
					'blocks[' . $slug . ']',
					$label,
					! empty( $settings['blocks'][ $slug ] ),
					$this->block_description( $slug )
				);
			}
			echo '</tbody></table>';
			return;
		}

		if ( 'header' === $tab ) {
			echo '<h2>' . esc_html__( 'Top bar and search', 'dpi-blocks' ) . '</h2><table class="form-table" role="presentation"><tbody>';
			$this->checkbox_row( 'top_bar_enabled', __( 'Enable the top bar', 'dpi-blocks' ), $settings['top_bar_enabled'] );
			$this->select_row(
				'top_bar_placement',
				__( 'Top bar placement', 'dpi-blocks' ),
				$settings['top_bar_placement'],
				array(
					'automatic' => __( 'Automatic at wp_body_open', 'dpi-blocks' ),
					'manual'    => __( 'Manual API/action/shortcode', 'dpi-blocks' ),
				)
			);
			$this->select_row( 'top_bar_menu_location', __( 'Top bar menu', 'dpi-blocks' ), $settings['top_bar_menu_location'], $this->menu_choices( true ) );
			$this->checkbox_row( 'search_enabled', __( 'Enable site search', 'dpi-blocks' ), $settings['search_enabled'] );
			$this->select_row(
				'search_location',
				__( 'Search trigger location', 'dpi-blocks' ),
				$settings['search_location'],
				array(
					'top-bar'   => __( 'Top bar', 'dpi-blocks' ),
					'main-menu' => __( 'Main menu', 'dpi-blocks' ),
				)
			);
			$this->select_row( 'search_menu_location', __( 'Main menu location', 'dpi-blocks' ), $settings['search_menu_location'], $this->menu_choices( false ) );
			$this->text_row( 'search_menu_item_class', __( 'Search menu item CSS classes', 'dpi-blocks' ), $settings['search_menu_item_class'], 'site-header__search' );
			$this->select_row(
				'search_template',
				__( 'Search presentation', 'dpi-blocks' ),
				$settings['search_template'],
				array(
					'dialog'  => __( 'Full modal', 'dpi-blocks' ),
					'popover' => __( 'Compact popover', 'dpi-blocks' ),
				)
			);
			echo '</tbody></table>';
			return;
		}

		if ( 'directories' === $tab ) {
			echo '<h2>' . esc_html__( 'Staff', 'dpi-blocks' ) . '</h2><table class="form-table" role="presentation"><tbody>';
			$this->checkbox_row( 'staff_enabled', __( 'Enable Staff content type', 'dpi-blocks' ), $settings['staff_enabled'] );
			$this->text_row( 'staff_single_slug', __( 'Staff single slug', 'dpi-blocks' ), $settings['staff_single_slug'] );
			$this->text_row( 'staff_archive_slug', __( 'Staff archive slug', 'dpi-blocks' ), $settings['staff_archive_slug'] );
			$this->text_row( 'staff_taxonomy_slug', __( 'Staff group slug', 'dpi-blocks' ), $settings['staff_taxonomy_slug'] );
			$this->select_row(
				'staff_mode',
				__( 'Staff archive behavior', 'dpi-blocks' ),
				$settings['staff_mode'],
				array(
					'single' => __( 'Real single links', 'dpi-blocks' ),
					'modal'  => __( 'Modal-enhanced real links', 'dpi-blocks' ),
				)
			);
			echo '</tbody></table><h2>' . esc_html__( 'Ministries', 'dpi-blocks' ) . '</h2><table class="form-table" role="presentation"><tbody>';
			$this->checkbox_row( 'ministry_enabled', __( 'Enable Ministry content type', 'dpi-blocks' ), $settings['ministry_enabled'] );
			$this->text_row( 'ministry_single_slug', __( 'Ministry single slug', 'dpi-blocks' ), $settings['ministry_single_slug'] );
			$this->text_row( 'ministry_archive_slug', __( 'Ministry archive slug', 'dpi-blocks' ), $settings['ministry_archive_slug'] );
			$this->text_row( 'ministry_taxonomy_slug', __( 'Ministry group slug', 'dpi-blocks' ), $settings['ministry_taxonomy_slug'] );
			echo '</tbody></table><h2>' . esc_html__( 'Offices', 'dpi-blocks' ) . '</h2><table class="form-table" role="presentation"><tbody>';
			$this->checkbox_row( 'office_enabled', __( 'Enable Office content type', 'dpi-blocks' ), $settings['office_enabled'] );
			$this->text_row( 'office_single_slug', __( 'Office single slug', 'dpi-blocks' ), $settings['office_single_slug'] );
			$this->text_row( 'office_archive_slug', __( 'Office archive slug', 'dpi-blocks' ), $settings['office_archive_slug'] );
			$this->text_row( 'office_taxonomy_slug', __( 'Office group slug', 'dpi-blocks' ), $settings['office_taxonomy_slug'] );
			echo '</tbody></table>';
			return;
		}

		echo '<h2>' . esc_html__( 'Social profiles', 'dpi-blocks' ) . '</h2><table class="form-table" role="presentation"><tbody>';
		foreach ( array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'youtube'   => 'YouTube',
			'x-twitter' => 'X / Twitter',
			'linkedin'  => 'LinkedIn',
		) as $network => $label ) {
			$this->url_row( 'social_profiles[' . $network . ']', $label, $settings['social_profiles'][ $network ] );
		}
		$this->text_row( 'social_feed_shortcode', __( 'Social feed shortcode', 'dpi-blocks' ), $settings['social_feed_shortcode'], '[plugin-shortcode]' );
		echo '</tbody></table>';
	}

	/** Preserve other tabs because WordPress replaces the whole option array. */
	private function render_preserved_fields( string $active_tab, array $settings ): void {
		$tab_keys = array(
			'blocks'      => array( 'blocks' ),
			'header'      => array( 'top_bar_enabled', 'top_bar_placement', 'top_bar_menu_location', 'search_enabled', 'search_location', 'search_menu_location', 'search_menu_item_class', 'search_template' ),
			'directories' => array( 'staff_enabled', 'staff_single_slug', 'staff_archive_slug', 'staff_taxonomy_slug', 'staff_mode', 'ministry_enabled', 'ministry_single_slug', 'ministry_archive_slug', 'ministry_taxonomy_slug', 'office_enabled', 'office_single_slug', 'office_archive_slug', 'office_taxonomy_slug' ),
			'social'      => array( 'social_profiles', 'social_feed_shortcode' ),
		);
		foreach ( $tab_keys as $tab => $keys ) {
			if ( $active_tab === $tab ) {
				continue;
			}
			foreach ( $keys as $key ) {
				$this->hidden_value( $key, $settings[ $key ] );
			}
		}
	}

	/** Recursively render hidden option values. */
	private function hidden_value( string $key, $value ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as $child_key => $child_value ) {
				$this->hidden_value( $key . '[' . $child_key . ']', $child_value );
			}
			return;
		}
		printf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $this->input_name( $key ) ), esc_attr( true === $value ? '1' : (string) $value ) );
	}

	/**
	 * Checkbox row.
	 *
	 * @param string $key         Setting key.
	 * @param string $label       Row label.
	 * @param bool   $checked     Whether the checkbox is enabled.
	 * @param string $description Optional explanatory copy.
	 */
	private function checkbox_row( string $key, string $label, bool $checked, string $description = '' ): void {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>',
			esc_attr( $this->input_name( $key ) ),
			checked( $checked, true, false ),
			esc_html__( 'Enabled', 'dpi-blocks' )
		);

		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}

		echo '</td></tr>';
	}


	/**
	 * Read a block's human-facing description from its block metadata.
	 */
	private function block_description( string $slug ): string {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return '';
		}

		$file = DPI_BLOCKS_DIR . 'blocks/' . $slug . '/block.json';
		if ( ! is_readable( $file ) ) {
			return '';
		}

		$metadata = wp_json_file_decode( $file, array( 'associative' => true ) );
		if ( ! is_array( $metadata ) || empty( $metadata['description'] ) || ! is_string( $metadata['description'] ) ) {
			return '';
		}

		return sanitize_text_field( $metadata['description'] );
	}

	/** Text row. */
	private function text_row( string $key, string $label, string $value, string $placeholder = '' ): void {
		printf( '<tr><th scope="row"><label for="dpi-%1$s">%2$s</label></th><td><input class="regular-text" id="dpi-%1$s" name="%3$s" type="text" value="%4$s" placeholder="%5$s"></td></tr>', esc_attr( sanitize_html_class( $key ) ), esc_html( $label ), esc_attr( $this->input_name( $key ) ), esc_attr( $value ), esc_attr( $placeholder ) );
	}

	/** URL row. */
	private function url_row( string $key, string $label, string $value ): void {
		$id = 'dpi-' . sanitize_html_class( $key );
		printf( '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input class="regular-text" id="%1$s" name="%3$s" type="url" value="%4$s"></td></tr>', esc_attr( $id ), esc_html( $label ), esc_attr( $this->input_name( $key ) ), esc_attr( $value ) );
	}

	/** Select row. */
	private function select_row( string $key, string $label, string $value, array $choices ): void {
		echo '<tr><th scope="row"><label for="dpi-' . esc_attr( sanitize_html_class( $key ) ) . '">' . esc_html( $label ) . '</label></th><td><select id="dpi-' . esc_attr( sanitize_html_class( $key ) ) . '" name="' . esc_attr( $this->input_name( $key ) ) . '">';
		foreach ( $choices as $choice => $choice_label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $choice ), selected( $value, $choice, false ), esc_html( $choice_label ) );
		}
		echo '</select></td></tr>';
	}

	/** Registered menu location choices. */
	private function menu_choices( bool $include_plugin ): array {
		$choices = array( '' => __( 'Select a menu location', 'dpi-blocks' ) );
		foreach ( get_registered_nav_menus() as $location => $description ) {
			if ( ! $include_plugin && 'dpi-top-bar' === $location ) {
				continue;
			}
			$choices[ $location ] = $description . ' (' . $location . ')';
		}
		return $choices;
	}

	/** Sanitize a choice. */
	private function choice( array $input, string $key, array $allowed, string $fallback ): string {
		$value = sanitize_key( (string) ( $input[ $key ] ?? '' ) );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/** Sanitize a registered theme location, preserving the plugin location. */
	private function menu_location( $value, string $fallback ): string {
		$value     = sanitize_key( (string) $value );
		$locations = array_keys( get_registered_nav_menus() );
		return in_array( $value, $locations, true ) ? $value : $fallback;
	}

	/** Sanitize a space-separated list of CSS classes. */
	private function css_classes( string $classes ): string {
		$segments = preg_split( '/\s+/', trim( $classes ) );
		$segments = is_array( $segments ) ? $segments : array();

		return implode( ' ', array_filter( array_map( 'sanitize_html_class', $segments ) ) );
	}

	/**
	 * Revert conflicting route fields while retaining one content type's matching
	 * single/archive pair (WordPress supports that common arrangement).
	 *
	 * @param array<string, mixed> $settings Sanitized new settings.
	 * @param array<string, mixed> $old      Previously stored settings.
	 */
	private function validate_route_slugs( array &$settings, array $old ): void {
		$modules   = array(
			'staff'    => array(
				'enabled'  => 'staff_enabled',
				'label'    => __( 'Staff', 'dpi-blocks' ),
				'routes'   => array( 'staff_single_slug', 'staff_archive_slug' ),
				'taxonomy' => 'staff_taxonomy_slug',
			),
			'ministry' => array(
				'enabled'  => 'ministry_enabled',
				'label'    => __( 'Ministries', 'dpi-blocks' ),
				'routes'   => array( 'ministry_single_slug', 'ministry_archive_slug' ),
				'taxonomy' => 'ministry_taxonomy_slug',
			),
			'office'   => array(
				'enabled'  => 'office_enabled',
				'label'    => __( 'Offices', 'dpi-blocks' ),
				'routes'   => array( 'office_single_slug', 'office_archive_slug' ),
				'taxonomy' => 'office_taxonomy_slug',
			),
		);
		$conflicts = $this->route_conflicts( $settings, $modules );

		if ( ! $conflicts ) {
			return;
		}

		$conflicting_keys = array();
		foreach ( $conflicts as $conflict ) {
			foreach ( $conflict['keys'] as $key ) {
				$conflicting_keys[ $key ] = true;
			}
		}

		// Prefer the last known route values when a submitted slug caused the
		// conflict. This preserves an already-valid enabled module configuration.
		foreach ( array_keys( $conflicting_keys ) as $key ) {
			if ( array_key_exists( $key, $old ) && $settings[ $key ] !== $old[ $key ] ) {
				$settings[ $key ] = $old[ $key ];
			}
		}

		$disabled  = array();
		$conflicts = $this->route_conflicts( $settings, $modules );
		while ( $conflicts ) {
			$module = $this->module_to_disable( $conflicts[0], $settings, $old, $modules );
			if ( null === $module ) {
				break;
			}

			$settings[ $modules[ $module ]['enabled'] ] = false;
			$disabled[ $module ]                        = $modules[ $module ]['label'];
			$conflicts                                  = $this->route_conflicts( $settings, $modules );
		}

		$message = $disabled
			? sprintf(
				/* translators: %s: comma-separated content-type module labels. */
				__( 'Route slugs must be unique between enabled content types, and taxonomy slugs cannot match their content routes. Conflicting values were not saved; these modules were disabled to preserve valid routes: %s.', 'dpi-blocks' ),
				implode( ', ', $disabled )
			)
			: __( 'Route slugs must be unique between enabled content types, and taxonomy slugs cannot match their content routes. Conflicting values were not saved.', 'dpi-blocks' );

		add_settings_error(
			self::OPTION_NAME,
			'dpi_blocks_route_collision',
			$message,
			'error'
		);
	}

	/**
	 * Find route collisions among enabled content-type modules.
	 *
	 * Matching single and archive slugs within one module are intentionally valid.
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 * @param array<string, array<string, mixed>> $modules Module route definitions.
	 * @return list<array{modules: list<string>, keys: list<string>}>
	 */
	private function route_conflicts( array $settings, array $modules ): array {
		$conflicts = array();
		$enabled   = array();

		foreach ( $modules as $name => $module ) {
			if ( empty( $settings[ $module['enabled'] ] ) ) {
				continue;
			}

			$enabled[ $name ] = $module;
			foreach ( $module['routes'] as $route_key ) {
				if ( $settings[ $route_key ] === $settings[ $module['taxonomy'] ] ) {
					$conflicts[] = array(
						'modules' => array( $name ),
						'keys'    => array( $route_key, $module['taxonomy'] ),
					);
				}
			}
		}

		$names = array_keys( $enabled );
		$count = count( $names );
		for ( $left_index = 0; $left_index < $count; $left_index++ ) {
			$left_name = $names[ $left_index ];
			$left      = $enabled[ $left_name ];
			$left_keys = array_merge( $left['routes'], array( $left['taxonomy'] ) );

			for ( $right_index = $left_index + 1; $right_index < $count; $right_index++ ) {
				$right_name = $names[ $right_index ];
				$right      = $enabled[ $right_name ];
				$right_keys = array_merge( $right['routes'], array( $right['taxonomy'] ) );

				foreach ( $left_keys as $left_key ) {
					foreach ( $right_keys as $right_key ) {
						if ( $settings[ $left_key ] === $settings[ $right_key ] ) {
							$conflicts[] = array(
								'modules' => array( $left_name, $right_name ),
								'keys'    => array( $left_key, $right_key ),
							);
						}
					}
				}
			}
		}

		return $conflicts;
	}

	/**
	 * Select the safest module to disable for an unresolved collision.
	 *
	 * Newly enabled modules lose to already-active modules. Otherwise the later
	 * module in the stable Staff, Ministry, Office order is disabled.
	 *
	 * @param array{modules: list<string>, keys: list<string>} $conflict Route conflict.
	 * @param array<string, mixed> $settings Sanitized settings.
	 * @param array<string, mixed> $old Previously stored settings.
	 * @param array<string, array<string, mixed>> $modules Module route definitions.
	 */
	private function module_to_disable( array $conflict, array $settings, array $old, array $modules ): ?string {
		foreach ( array_reverse( $conflict['modules'] ) as $name ) {
			$enabled_key = $modules[ $name ]['enabled'];
			if ( ! empty( $settings[ $enabled_key ] ) && empty( $old[ $enabled_key ] ) ) {
				return $name;
			}
		}

		foreach ( array_reverse( array_keys( $modules ) ) as $name ) {
			if ( in_array( $name, $conflict['modules'], true ) && ! empty( $settings[ $modules[ $name ]['enabled'] ] ) ) {
				return $name;
			}
		}

		return null;
	}

	/** Build a correctly nested HTML input name from a bracket path. */
	private function input_name( string $key ): string {
		$segments = preg_split( '/[\[\]]+/', $key, -1, PREG_SPLIT_NO_EMPTY );
		$segments = is_array( $segments ) ? $segments : array();
		$name     = self::OPTION_NAME;

		foreach ( $segments as $segment ) {
			$name .= '[' . sanitize_key( $segment ) . ']';
		}

		return $name;
	}

	/** Merge saved nested settings with defaults. */
	private static function merge_with_defaults( $settings ): array {
		$settings                  = is_array( $settings ) ? $settings : array();
		$defaults                  = self::defaults();
		$merged                    = array_merge( $defaults, array_intersect_key( $settings, $defaults ) );
		$merged['blocks']          = array_merge( $defaults['blocks'], isset( $settings['blocks'] ) && is_array( $settings['blocks'] ) ? array_intersect_key( $settings['blocks'], $defaults['blocks'] ) : array() );
		$merged['social_profiles'] = array_merge( $defaults['social_profiles'], isset( $settings['social_profiles'] ) && is_array( $settings['social_profiles'] ) ? array_intersect_key( $settings['social_profiles'], $defaults['social_profiles'] ) : array() );
		return $merged;
	}
}
