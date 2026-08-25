<?php
/**
 * Portable top-bar and search integrations.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Provide theme-neutral header primitives and explicit integration APIs. */
final class Header {
	private const SEARCH_ID = 'dpi-site-search';

	private bool $surface_rendered = false;

	/** Register hooks and public integrations. */
	public function register(): void {
		add_action( 'init', array( $this, 'register_menu_location' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_body_open', array( $this, 'render_automatic_top_bar' ), 5 );
		add_action( 'wp_footer', array( $this, 'render_search_surface' ), 5 );
		add_filter( 'wp_nav_menu_items', array( $this, 'inject_search_into_menu' ), 20, 2 );

		add_action( 'dpi_blocks/top_bar', array( $this, 'render_top_bar' ) );
		add_action( 'dpi_blocks/search_trigger', array( $this, 'render_search_trigger' ) );
		add_action( 'dpi_blocks_top_bar', array( $this, 'render_top_bar' ) );
		add_action( 'dpi_blocks_search_trigger', array( $this, 'render_search_trigger' ) );
		add_shortcode( 'dpi_top_bar', array( $this, 'top_bar_shortcode' ) );
		add_shortcode( 'dpi_search_trigger', array( $this, 'search_trigger_shortcode' ) );
	}

	/** Register a stable plugin-owned menu location. */
	public function register_menu_location(): void {
		register_nav_menu( 'dpi-top-bar', __( 'DPI Top Bar', 'dpi-blocks' ) );
	}

	/** Conditionally register and load the small header bundle. */
	public function enqueue_assets(): void {
		wp_register_style(
			'dpi-blocks-header',
			DPI_BLOCKS_URL . 'assets/css/header.css',
			array(),
			DPI_BLOCKS_VERSION
		);
		wp_register_script(
			'dpi-blocks-header',
			DPI_BLOCKS_URL . 'assets/js/header.js',
			array(),
			DPI_BLOCKS_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		if ( Settings::get( 'top_bar_enabled', false ) || Settings::get( 'search_enabled', false ) ) {
			wp_enqueue_style( 'dpi-blocks-header' );
		}

		if ( Settings::get( 'search_enabled', false ) ) {
			wp_enqueue_script( 'dpi-blocks-header' );
		}
	}

	/** Render the configured automatic placement. */
	public function render_automatic_top_bar(): void {
		if ( Settings::get( 'top_bar_enabled', false ) && 'automatic' === Settings::get( 'top_bar_placement', 'automatic' ) ) {
			$this->render_top_bar();
		}
	}

	/**
	 * Render the accessible top-bar component.
	 *
	 * @param array<string, mixed> $args Optional menu_location and class values.
	 */
	public function render_top_bar( array $args = array() ): void {
		if ( ! Settings::get( 'top_bar_enabled', false ) ) {
			return;
		}

		$requested_menu = isset( $args['menu_location'] ) ? sanitize_key( (string) $args['menu_location'] ) : '';
		$menu_location  = '' !== $requested_menu ? $requested_menu : (string) Settings::get( 'top_bar_menu_location', 'dpi-top-bar' );
		$class_name     = isset( $args['class'] ) ? $this->sanitize_classes( (string) $args['class'] ) : '';
		$show_search    = Settings::get( 'search_enabled', false ) && 'top-bar' === Settings::get( 'search_location', 'top-bar' );
		$has_menu       = '' !== $menu_location && has_nav_menu( $menu_location );

		if ( ! $has_menu && ! $show_search ) {
			return;
		}
		?>
		<div class="dpi-top-bar <?php echo esc_attr( $class_name ); ?>" data-dpi-top-bar>
			<div class="dpi-top-bar__inner">
				<?php if ( $has_menu ) : ?>
					<nav class="dpi-top-bar__navigation" aria-label="<?php esc_attr_e( 'Quick links', 'dpi-blocks' ); ?>">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => $menu_location,
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
								'menu_class'     => 'dpi-top-bar__menu',
							)
						);
						?>
					</nav>
				<?php endif; ?>

				<?php if ( $show_search ) : ?>
					<?php $this->render_search_trigger( array( 'class' => 'dpi-top-bar__search' ) ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a search trigger for use by themes and templates.
	 *
	 * @param array<string, mixed> $args Optional class and label values.
	 */
	public function render_search_trigger( array $args = array() ): void {
		if ( ! Settings::get( 'search_enabled', false ) ) {
			return;
		}

		echo $this->search_trigger_markup( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in renderer.
	}

	/** Append the trigger to one selected theme menu location. */
	public function inject_search_into_menu( string $items, $args ): string {
		if ( ! Settings::get( 'search_enabled', false ) || 'main-menu' !== Settings::get( 'search_location', 'top-bar' ) ) {
			return $items;
		}

		$selected = (string) Settings::get( 'search_menu_location', '' );
		$location = is_object( $args ) && isset( $args->theme_location ) ? (string) $args->theme_location : '';
		if ( '' === $selected || $selected !== $location ) {
			return $items;
		}

		return $items . '<li class="menu-item dpi-menu-search">' . $this->search_trigger_markup( array( 'class' => 'dpi-menu-search__button' ) ) . '</li>';
	}

	/** Render the one global search surface. */
	public function render_search_surface(): void {
		if ( $this->surface_rendered || ! Settings::get( 'search_enabled', false ) ) {
			return;
		}
		$this->surface_rendered = true;

		$template  = (string) Settings::get( 'search_template', 'dialog' );
		$is_dialog = 'popover' !== $template;
		$query     = get_search_query( false );
		$tag       = $is_dialog ? 'dialog' : 'div';
		?>
		<<?php echo esc_html( $tag ); ?> id="<?php echo esc_attr( self::SEARCH_ID ); ?>"
			class="dpi-search dpi-search--<?php echo esc_attr( $is_dialog ? 'dialog' : 'popover' ); ?>"
			role="dialog"
			<?php echo $is_dialog ? 'aria-modal="true"' : 'popover="auto"'; ?>
			aria-labelledby="dpi-site-search-title"
			data-dpi-search-surface
			data-dpi-search-type="<?php echo esc_attr( $is_dialog ? 'dialog' : 'popover' ); ?>">
			<div class="dpi-search__panel">
				<button class="dpi-search__close" type="button" aria-label="<?php esc_attr_e( 'Close site search', 'dpi-blocks' ); ?>" data-dpi-search-close>
					<span aria-hidden="true">&times;</span>
				</button>
				<h2 id="dpi-site-search-title" class="dpi-search__title"><?php esc_html_e( 'Search this site', 'dpi-blocks' ); ?></h2>
				<form class="dpi-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="dpi-site-search-query"><?php esc_html_e( 'Search for:', 'dpi-blocks' ); ?></label>
					<input id="dpi-site-search-query" class="dpi-search__input" type="search" name="s" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'What are you looking for?', 'dpi-blocks' ); ?>" data-dpi-search-input>
					<button class="dpi-search__submit" type="submit"><?php esc_html_e( 'Search', 'dpi-blocks' ); ?></button>
				</form>
			</div>
		</<?php echo esc_html( $tag ); ?>>
		<?php
	}

	/** Top-bar shortcode callback. */
	public function top_bar_shortcode( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'class'         => '',
				'menu_location' => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'dpi_top_bar'
		);
		ob_start();
		$this->render_top_bar( $attributes );
		return (string) ob_get_clean();
	}

	/** Search-trigger shortcode callback. */
	public function search_trigger_shortcode( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'class' => '',
				'label' => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'dpi_search_trigger'
		);
		ob_start();
		$this->render_search_trigger( $attributes );
		return (string) ob_get_clean();
	}

	/** Build escaped trigger markup. */
	private function search_trigger_markup( array $args = array() ): string {
		$label   = ! empty( $args['label'] ) ? sanitize_text_field( (string) $args['label'] ) : __( 'Open site search', 'dpi-blocks' );
		$classes = 'dpi-search-trigger ' . ( isset( $args['class'] ) ? $this->sanitize_classes( (string) $args['class'] ) : '' );
		$icon    = IconRegistry::render(
			'solid:magnifying-glass',
			array(
				'class'  => 'dpi-search-trigger__icon',
				'width'  => '24',
				'height' => '24',
			)
		);

		return sprintf(
			'<button class="%1$s" type="button" aria-label="%2$s" aria-controls="%3$s" aria-expanded="false" aria-haspopup="dialog" data-dpi-search-open>%4$s</button>',
			esc_attr( trim( $classes ) ),
			esc_attr( $label ),
			esc_attr( self::SEARCH_ID ),
			$icon
		);
	}

	/** Retain only valid CSS class characters. */
	private function sanitize_classes( string $classes ): string {
		$segments = preg_split( '/\s+/', trim( $classes ) );
		$segments = is_array( $segments ) ? $segments : array();

		return implode( ' ', array_filter( array_map( 'sanitize_html_class', $segments ) ) );
	}
}
