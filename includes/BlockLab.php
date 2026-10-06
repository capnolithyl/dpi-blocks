<?php
/**
 * Administrator-only visual block laboratory.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render schema-driven block examples without creating WordPress content.
 *
 * The lab reads the bundled ACF JSON, derives representative values, and
 * injects them through ACF's pre-load hook while the real block render
 * callback runs. This keeps the lab tied to the same renderer used in normal
 * pages and makes newly added blocks discoverable automatically.
 */
final class BlockLab {
	public const PAGE_SLUG = 'dpi-blocks-lab';
	public const QUERY_VAR = 'dpi_blocks_lab';

	/** @var array<string, mixed> */
	private array $active_fields = array();

	/** Register admin and front-end preview hooks. */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render_frontend' ), 1 );
	}

	/** Add Tools > DPI Block Lab. */
	public function add_admin_page(): void {
		add_management_page(
			__( 'DPI Block Lab', 'dpi-blocks' ),
			__( 'DPI Block Lab', 'dpi-blocks' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/** Load the small amount of chrome used by the admin inventory. */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'dpi-blocks-lab',
			DPI_BLOCKS_URL . 'assets/css/block-lab.css',
			array(),
			DPI_BLOCKS_VERSION
		);
	}

	/** Register the private front-end preview query variable. */
	public function register_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/** Render the admin inventory and preview link. */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$inventory   = $this->inventory();
		$preview_url = add_query_arg( self::QUERY_VAR, '1', home_url( '/' ) );
		?>
		<div class="wrap dpi-block-lab-admin">
			<h1><?php esc_html_e( 'DPI Block Lab', 'dpi-blocks' ); ?></h1>
			<p><?php esc_html_e( 'A schema-driven visual smoke test for every bundled DPI block. The front-end preview uses the active theme, the real block renderers, and representative values generated from the bundled ACF field definitions.', 'dpi-blocks' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Open front-end Block Lab', 'dpi-blocks' ); ?>
				</a>
			</p>

			<table class="widefat striped dpi-block-lab-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Block', 'dpi-blocks' ); ?></th>
						<th><?php esc_html_e( 'Field group', 'dpi-blocks' ); ?></th>
						<th><?php esc_html_e( 'Scenarios', 'dpi-blocks' ); ?></th>
						<th><?php esc_html_e( 'Status', 'dpi-blocks' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $inventory as $item ) : ?>
						<tr>
							<td><code><?php echo esc_html( $item['name'] ); ?></code><br><?php echo esc_html( $item['title'] ); ?></td>
							<td><?php echo $item['field_group'] ? esc_html( $item['field_group']['title'] ) : '<em>' . esc_html__( 'Missing', 'dpi-blocks' ) . '</em>'; ?></td>
							<td><?php echo esc_html( (string) count( $item['scenarios'] ) ); ?></td>
							<td>
								<?php if ( $item['field_group'] ) : ?>
									<span class="dpi-block-lab-status dpi-block-lab-status--ok"><?php esc_html_e( 'Ready', 'dpi-blocks' ); ?></span>
								<?php else : ?>
									<span class="dpi-block-lab-status dpi-block-lab-status--warning"><?php esc_html_e( 'Needs ACF field group', 'dpi-blocks' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/** Intercept the administrator-only front-end lab request. */
	public function maybe_render_frontend(): void {
		if ( '1' !== (string) get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			status_header( 404 );
			wp_die( esc_html__( 'Block Lab is available to administrators only.', 'dpi-blocks' ), '', array( 'response' => 404 ) );
		}

		if ( ! Dependencies::has_acf_pro() ) {
			wp_die(
				esc_html__( 'DPI Block Lab requires ACF Pro because it exercises the real ACF block render pipeline.', 'dpi-blocks' ),
				esc_html__( 'DPI Block Lab unavailable', 'dpi-blocks' )
			);
		}

		nocache_headers();
		$this->enqueue_frontend_assets();

		get_header();
		?>
		<main id="primary" class="dpi-block-lab">
			<header class="dpi-block-lab__intro">
				<h1><?php esc_html_e( 'DPI Block Lab', 'dpi-blocks' ); ?></h1>
				<p><?php esc_html_e( 'Each example below is rendered by the real plugin block callback using generated test data. Errors are isolated per scenario so one broken block does not hide the rest of the report.', 'dpi-blocks' ); ?></p>
			</header>

			<?php foreach ( $this->inventory() as $item ) : ?>
				<section class="dpi-block-lab__block" data-dpi-lab-block="<?php echo esc_attr( $item['name'] ); ?>">
					<header class="dpi-block-lab__block-header">
						<h2><?php echo esc_html( $item['title'] ); ?></h2>
						<code><?php echo esc_html( $item['name'] ); ?></code>
					</header>

					<?php if ( ! $item['field_group'] ) : ?>
						<div class="dpi-block-lab__error"><?php esc_html_e( 'No bundled ACF field group targets this block.', 'dpi-blocks' ); ?></div>
					<?php else : ?>
						<?php foreach ( $item['scenarios'] as $scenario ) : ?>
							<?php $this->render_scenario( $item['name'], $scenario ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
		</main>
		<?php
		get_footer();
		exit;
	}

	/** Ensure shared interactive assets are available before the theme prints wp_head(). */
	private function enqueue_frontend_assets(): void {
		wp_enqueue_style( 'dpi-blocks' );
		wp_enqueue_style( 'dpi-blocks-slick' );
		wp_enqueue_script( 'dpi-blocks-slick' );
		wp_enqueue_script( 'dpi-blocks' );
		wp_enqueue_style(
			'dpi-blocks-lab',
			DPI_BLOCKS_URL . 'assets/css/block-lab.css',
			array( 'dpi-blocks' ),
			DPI_BLOCKS_VERSION
		);

		if ( ! class_exists( '\\WP_Block_Type_Registry' ) ) {
			return;
		}

		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type ) {
			if ( ! str_starts_with( (string) $name, 'dpi/' ) ) {
				continue;
			}

			foreach ( array( 'style_handles', 'view_style_handles' ) as $property ) {
				foreach ( (array) ( $type->{$property} ?? array() ) as $handle ) {
					if ( is_string( $handle ) && '' !== $handle ) {
						wp_enqueue_style( $handle );
					}
				}
			}

			foreach ( array( 'script_handles', 'view_script_handles' ) as $property ) {
				foreach ( (array) ( $type->{$property} ?? array() ) as $handle ) {
					if ( is_string( $handle ) && '' !== $handle ) {
						wp_enqueue_script( $handle );
					}
				}
			}
		}
	}

	/**
	 * Render one scenario while converting plugin-origin warnings into a local
	 * diagnostic instead of breaking the entire lab page.
	 *
	 * @param string $block_name Canonical block name.
	 * @param array{name:string,fields:array<string,mixed>} $scenario Scenario definition.
	 */
	private function render_scenario( string $block_name, array $scenario ): void {
		$this->active_fields = $scenario['fields'];

		add_filter( 'acf/pre_load_value', array( $this, 'preload_field_value' ), PHP_INT_MAX, 3 );

		$previous_handler = set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				if ( 0 === ( error_reporting() & $severity ) ) {
					return false;
				}

				$plugin_root = wp_normalize_path( DPI_BLOCKS_DIR );
				$error_file  = wp_normalize_path( $file );
				if ( ! str_starts_with( $error_file, $plugin_root ) ) {
					return false;
				}

				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		?>
		<article class="dpi-block-lab__scenario" data-dpi-lab-scenario="<?php echo esc_attr( sanitize_title( $scenario['name'] ) ); ?>">
			<header class="dpi-block-lab__scenario-header">
				<h3><?php echo esc_html( $scenario['name'] ); ?></h3>
			</header>
			<div class="dpi-block-lab__canvas">
				<?php
				try {
					echo render_block(
						array(
							'blockName'    => $block_name,
							'attrs'        => array(),
							'innerBlocks'  => array(),
							'innerHTML'    => '',
							'innerContent' => array(),
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output belongs to the registered block renderer.
				} catch ( \Throwable $error ) {
					printf(
						'<div class="dpi-block-lab__error"><strong>%1$s</strong><br><code>%2$s</code></div>',
						esc_html__( 'Renderer error', 'dpi-blocks' ),
						esc_html( $error->getMessage() )
					);
				}
				?>
			</div>
			<details class="dpi-block-lab__data">
				<summary><?php esc_html_e( 'Generated field values', 'dpi-blocks' ); ?></summary>
				<pre><?php echo esc_html( (string) wp_json_encode( $scenario['fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			</details>
		</article>
		<?php

		remove_filter( 'acf/pre_load_value', array( $this, 'preload_field_value' ), PHP_INT_MAX );
		$this->active_fields = array();

		restore_error_handler();
	}

	/** Supply generated values to ACF while a lab scenario is rendering. */
	public function preload_field_value( mixed $value, mixed $post_id, array $field ): mixed {
		unset( $post_id );

		$name = isset( $field['name'] ) ? (string) $field['name'] : '';
		if ( '' !== $name && array_key_exists( $name, $this->active_fields ) ) {
			return $this->active_fields[ $name ];
		}

		return $value;
	}

	/**
	 * Discover bundled blocks and pair them with their bundled field groups.
	 *
	 * @return list<array{name:string,title:string,field_group:?array,scenarios:list<array{name:string,fields:array<string,mixed>}>}>
	 */
	private function inventory(): array {
		$groups = $this->field_groups_by_block();
		$items  = array();

		foreach ( glob( DPI_BLOCKS_DIR . 'blocks/*/block.json' ) ?: array() as $metadata_file ) {
			$metadata = wp_json_file_decode( $metadata_file, array( 'associative' => true ) );
			if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) {
				continue;
			}

			$name  = (string) $metadata['name'];
			$group = $groups[ $name ] ?? null;

			$items[] = array(
				'name'        => $name,
				'title'       => isset( $metadata['title'] ) ? (string) $metadata['title'] : $name,
				'field_group' => $group,
				'scenarios'   => $group ? $this->build_scenarios( $group ) : array(),
			);
		}

		usort(
			$items,
			static fn( array $left, array $right ): int => strcasecmp( $left['title'], $right['title'] )
		);

		return $items;
	}

	/** @return array<string, array<string,mixed>> */
	private function field_groups_by_block(): array {
		$groups = array();

		foreach ( glob( DPI_BLOCKS_DIR . 'acf-json/*.json' ) ?: array() as $file ) {
			$group = wp_json_file_decode( $file, array( 'associative' => true ) );
			if ( ! is_array( $group ) || empty( $group['fields'] ) || empty( $group['location'] ) ) {
				continue;
			}

			foreach ( (array) $group['location'] as $rules ) {
				foreach ( (array) $rules as $rule ) {
					if (
						is_array( $rule )
						&& 'block' === ( $rule['param'] ?? '' )
						&& '==' === ( $rule['operator'] ?? '' )
						&& is_string( $rule['value'] ?? null )
						&& str_starts_with( $rule['value'], 'dpi/' )
					) {
						$groups[ $rule['value'] ] = $group;
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * Build a baseline and an alternate field configuration.
	 *
	 * @param array<string,mixed> $group ACF field group.
	 * @return list<array{name:string,fields:array<string,mixed>}>
	 */
	private function build_scenarios( array $group ): array {
		$baseline  = array();
		$alternate = array();

		foreach ( (array) ( $group['fields'] ?? array() ) as $field ) {
			if ( ! is_array( $field ) || empty( $field['name'] ) ) {
				continue;
			}

			$name               = (string) $field['name'];
			$baseline[ $name ]  = $this->sample_value( $field, false );
			$alternate[ $name ] = $this->sample_value( $field, true );
		}

		$scenarios = array(
			array(
				'name'   => __( 'Baseline', 'dpi-blocks' ),
				'fields' => $baseline,
			),
		);

		if ( $alternate !== $baseline ) {
			$scenarios[] = array(
				'name'   => __( 'Alternate settings', 'dpi-blocks' ),
				'fields' => $alternate,
			);
		}

		return $scenarios;
	}

	/** Generate one representative raw ACF value from a field definition. */
	private function sample_value( array $field, bool $alternate ): mixed {
		$type = (string) ( $field['type'] ?? '' );

		if ( in_array( $type, array( 'tab', 'message', 'accordion' ), true ) ) {
			return null;
		}

		if ( 'true_false' === $type ) {
			$default = ! empty( $field['default_value'] );
			return $alternate ? ! $default : $default;
		}

		if ( in_array( $type, array( 'select', 'radio', 'button_group' ), true ) ) {
			return $this->sample_choice( $field, $alternate );
		}

		if ( 'checkbox' === $type ) {
			$choices = array_keys( (array) ( $field['choices'] ?? array() ) );
			return array_slice( $choices, 0, $alternate ? 2 : 1 );
		}

		if ( 'repeater' === $type ) {
			$rows = $alternate ? 2 : 1;
			$min  = absint( $field['min'] ?? 0 );
			$max  = absint( $field['max'] ?? 0 );
			$rows = max( $rows, $min );
			if ( $max > 0 ) {
				$rows = min( $rows, $max );
			}

			$value = array();
			for ( $index = 0; $index < $rows; $index++ ) {
				$row = array();
				foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub_field ) {
					if ( is_array( $sub_field ) && ! empty( $sub_field['name'] ) ) {
						$row[ $sub_field['name'] ] = $this->sample_value( $sub_field, $alternate || $index > 0 );
					}
				}
				$value[] = $row;
			}
			return $value;
		}

		if ( 'group' === $type ) {
			$value = array();
			foreach ( (array) ( $field['sub_fields'] ?? array() ) as $sub_field ) {
				if ( is_array( $sub_field ) && ! empty( $sub_field['name'] ) ) {
					$value[ $sub_field['name'] ] = $this->sample_value( $sub_field, $alternate );
				}
			}
			return $value;
		}

		if ( 'taxonomy' === $type ) {
			return $this->sample_taxonomy( $field, $alternate );
		}

		if ( in_array( $type, array( 'post_object', 'relationship' ), true ) ) {
			return $this->sample_posts( $field, $alternate );
		}

		if ( 'image' === $type ) {
			return $this->sample_attachment_id( 'image' );
		}

		if ( 'file' === $type ) {
			return $this->sample_attachment_id();
		}

		if ( 'gallery' === $type ) {
			$id = $this->sample_attachment_id( 'image' );
			return $id ? array( $id ) : array();
		}

		if ( 'link' === $type ) {
			return array(
				'url'    => home_url( '/' ),
				'title'  => $alternate ? __( 'Explore More', 'dpi-blocks' ) : __( 'Learn More', 'dpi-blocks' ),
				'target' => '',
			);
		}

		if ( 'url' === $type ) {
			return home_url( '/' );
		}

		if ( 'email' === $type ) {
			return (string) get_option( 'admin_email', 'webmaster@example.com' );
		}

		if ( 'number' === $type || 'range' === $type ) {
			if ( $alternate && isset( $field['max'] ) && '' !== (string) $field['max'] ) {
				return (float) $field['max'];
			}
			if ( isset( $field['default_value'] ) && '' !== (string) $field['default_value'] ) {
				return $field['default_value'];
			}
			return isset( $field['min'] ) && '' !== (string) $field['min'] ? (float) $field['min'] : 3;
		}

		if ( 'color_picker' === $type ) {
			return $alternate ? '#666666' : '#333333';
		}

		if ( 'date_picker' === $type ) {
			return current_time( 'Ymd' );
		}

		if ( 'date_time_picker' === $type ) {
			return current_time( 'Y-m-d H:i:s' );
		}

		if ( 'time_picker' === $type ) {
			return current_time( 'H:i:s' );
		}

		if ( 'user' === $type ) {
			return get_current_user_id();
		}

		if ( array_key_exists( 'default_value', $field ) && '' !== (string) $field['default_value'] ) {
			return $field['default_value'];
		}

		$label = trim( (string) ( $field['label'] ?? $field['name'] ?? 'Content' ) );
		if ( in_array( $type, array( 'textarea', 'wysiwyg' ), true ) ) {
			return sprintf(
				/* translators: %s: ACF field label. */
				__( 'Sample %s content generated by DPI Block Lab so this renderer can be checked without creating permanent content.', 'dpi-blocks' ),
				strtolower( $label )
			);
		}

		return $alternate
			? sprintf( __( 'Alternate %s', 'dpi-blocks' ), $label )
			: sprintf( __( 'Sample %s', 'dpi-blocks' ), $label );
	}

	/** Pick a default or alternate choice value. */
	private function sample_choice( array $field, bool $alternate ): mixed {
		$choices = array_keys( (array) ( $field['choices'] ?? array() ) );
		if ( ! $alternate && isset( $field['default_value'] ) && '' !== (string) $field['default_value'] ) {
			return $field['default_value'];
		}

		if ( ! $choices ) {
			return $field['default_value'] ?? '';
		}

		if ( $alternate && count( $choices ) > 1 ) {
			return $choices[1];
		}

		return $choices[0];
	}

	/** Return representative term IDs for a taxonomy field. */
	private function sample_taxonomy( array $field, bool $alternate ): mixed {
		$taxonomy = sanitize_key( (string) ( $field['taxonomy'] ?? 'category' ) );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $alternate ? 2 : 1,
				'fields'     => 'ids',
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}

		$multiple = in_array( (string) ( $field['field_type'] ?? '' ), array( 'checkbox', 'multi_select' ), true )
			|| ! empty( $field['multiple'] );

		return $multiple ? array_map( 'absint', $terms ) : absint( reset( $terms ) );
	}

	/** Return representative post IDs for post-object/relationship fields. */
	private function sample_posts( array $field, bool $alternate ): mixed {
		$post_type = $field['post_type'] ?? 'post';
		$post_type = is_array( $post_type ) && $post_type ? reset( $post_type ) : $post_type;
		$post_type = is_string( $post_type ) && '' !== $post_type ? $post_type : 'post';

		if ( ! post_type_exists( $post_type ) ) {
			return 'relationship' === ( $field['type'] ?? '' ) ? array() : 0;
		}

		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $alternate ? 2 : 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( 'relationship' === ( $field['type'] ?? '' ) || ! empty( $field['multiple'] ) ) {
			return array_map( 'absint', $ids );
		}

		return $ids ? absint( reset( $ids ) ) : 0;
	}

	/** Return one existing attachment ID without creating test media. */
	private function sample_attachment_id( string $mime = '' ): int {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);
		if ( 'image' === $mime ) {
			$args['post_mime_type'] = 'image';
		}

		$ids = get_posts( $args );
		return $ids ? absint( reset( $ids ) ) : 0;
	}
}
