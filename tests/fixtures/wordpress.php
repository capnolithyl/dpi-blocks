<?php
/**
 * Deterministic data adapters for offline browser rendering.
 * These are test doubles for WordPress/ACF, not a second block implementation.
 * Production PHP renderers, helpers, styles and scripts are used unchanged.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'DPI_BLOCKS_DIR', ABSPATH );
define( 'DPI_BLOCKS_URL', '/' );
define( 'DPI_BLOCKS_VERSION', 'fixture' );

$fixture_fields = array();
$fixture_missing_media = false;
$fixture_post_id = 1;

class WP_Post {
	public int $ID;
	public function __construct( int $id ) { $this->ID = $id; }
}

class WP_Term {
	public int $term_id;
	public string $name;
	public function __construct( int $id ) { $this->term_id = $id; $this->name = 'Category ' . $id; }
}

class WP_Query {
	private int $index = 0;
	public function __construct( array $args ) {}
	public function have_posts(): bool { return $this->index < 3; }
	public function the_post(): void { $GLOBALS['fixture_post_id'] = ++$this->index; }
}

function __( string $text, string $domain = '' ): string { return $text; }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ): string { return esc_html( $value ); }
function esc_url( $value ): string { return esc_attr( $value ); }
function esc_url_raw( $value ): string { return (string) $value; }
function esc_html__( string $text, string $domain = '' ): string { return esc_html( $text ); }
function esc_html_e( string $text, string $domain = '' ): void { echo esc_html( $text ); }
function esc_attr_e( string $text, string $domain = '' ): void { echo esc_attr( $text ); }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_key( string $value ): string { return (string) preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function sanitize_title( string $value ): string { return sanitize_key( str_replace( ' ', '-', $value ) ); }
function sanitize_email( string $value ): string { return $value; }
function sanitize_hex_color( string $value ): ?string { return preg_match( '/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $value ) ? $value : null; }
function wp_json_encode( $value, int $flags = 0 ): string { return (string) json_encode( $value, $flags ); }
function wp_json_file_decode( string $file, array $args = array() ): array { return json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR ); }
function wp_unique_id( string $prefix = '' ): string { static $id = 0; return $prefix . ++$id; }
function wp_strip_all_tags( string $value ): string { return strip_tags( $value ); }
function strip_shortcodes( string $value ): string { return $value; }
function wp_kses_post( string $value ): string { return $value; }
function wpautop( string $value ): string { return str_contains( $value, '<p>' ) ? $value : '<p>' . $value . '</p>'; }
function wp_trim_words( string $value, int $words = 55 ): string { return implode( ' ', array_slice( explode( ' ', $value ), 0, $words ) ); }
function wp_parse_url( string $url ): array|false { return parse_url( $url ); }
function wp_normalize_path( string $path ): string { return $path; }
function is_wp_error( $value ): bool { return false; }
function apply_filters( string $name, $value, ...$args ) { return $value; }
function home_url( string $path = '/' ): string { return $path; }
function current_time( string $format ): string { return gmdate( $format ); }
function get_current_user_id(): int { return 1; }
function get_option( string $name, $fallback = false ) { return 'admin_email' === $name ? 'sample@example.com' : $fallback; }
function taxonomy_exists( string $name ): bool { return true; }
function post_type_exists( string $name ): bool { return true; }
function get_term( int $id, string $taxonomy = '' ): WP_Term { return new WP_Term( $id ); }
function get_terms( array $args ): array { return array_slice( array( 1, 2 ), 0, $args['number'] ?? 2 ); }
function get_category_link( int $id ): string { return '/category/' . $id; }
function get_posts( array $args ): array {
	$ids = array_slice( array( 1, 2, 3, 4 ), 0, $args['posts_per_page'] ?? 4 );
	return 'ids' === ( $args['fields'] ?? '' ) ? $ids : array_map( static fn( int $id ) => new WP_Post( $id ), $ids );
}
function fixture_id( $post ): int { return $post instanceof WP_Post ? $post->ID : absint( $post ); }
function get_the_ID(): int { return $GLOBALS['fixture_post_id']; }
function get_post_type( $post ): string { return 'staff'; }
function get_permalink( $post ): string { return '/example/' . fixture_id( $post ); }
function get_the_title( $post ): string { return 'Example title ' . fixture_id( $post ); }
function the_title(): void { echo get_the_title( get_the_ID() ); }
function get_the_date( string $format, $post ): string { return date( $format, 1700000000 ); }
function get_the_excerpt( $post ): string { return 'Supporting copy that can wrap naturally at every screen size.'; }
function get_post_field( string $name, $post ): string { return '<p>A representative biography or article.</p>'; }
function wp_reset_postdata(): void { $GLOBALS['fixture_post_id'] = 1; }
function get_post_ancestors( int $id ): array { return array(); }
function has_post_thumbnail( $post ): bool { return ! $GLOBALS['fixture_missing_media']; }
function get_post_thumbnail_id( $post ): int { return $GLOBALS['fixture_missing_media'] ? 0 : 1; }
function get_the_post_thumbnail( $post, string $size, array $attrs = array() ): string { return wp_get_attachment_image( 1, $size, false, $attrs ); }
function wp_get_attachment_image_url( int $id, string $size = 'full' ): string { return '/tests/fixtures/photo.svg'; }
function wp_get_attachment_url( int $id ): string { return '/tests/fixtures/photo.svg'; }
function get_post_mime_type( int $id ): string { return 'video/mp4'; }
function wp_check_filetype( string $url ): array { return array( 'type' => 'video/mp4' ); }
function wp_get_attachment_image( int $id, string $size, bool $icon = false, array $attrs = array() ): string {
	return '<img src="/tests/fixtures/photo.svg" width="800" height="600" alt="' . esc_attr( $attrs['alt'] ?? 'Fixture photograph' ) . '">';
}
function has_shortcode( string $value, string $name ): bool { return str_contains( $value, '[' . $name ); }
function do_shortcode( string $value ): string { return '<div class="provider-owned-feed">A feed provider owns the appearance of this content.</div>'; }
function add_query_arg( array $values, string $url ): string { return $url . '?' . http_build_query( $values ); }
function get_block_wrapper_attributes( array $attrs ): string {
	return implode( ' ', array_map( static fn( $name, $value ) => $name . '="' . esc_attr( $value ) . '"', array_keys( $attrs ), $attrs ) );
}
function get_field( string $name, $post_id = false ) {
	if ( false !== $post_id ) {
		return array( 'staff_position' => 'Office coordinator', 'staff_email' => 'someone-with-a-very-long-address@example.com', 'staff_phone' => '(555) 123-4567' )[ $name ] ?? null;
	}
	return $GLOBALS['fixture_fields'][ $name ] ?? null;
}

/** Format generated Lab data into the return shapes expected by renderers. */
function fixture_format_fields( array $fields, array $values ): array {
	$result = array();
	foreach ( $fields as $field ) {
		$name = $field['name'] ?? '';
		$key = $name;
		if ( '' === $name || ! array_key_exists( $key, $values ) ) { continue; }
		$value = $values[ $key ];
		$type = $field['type'];
		if ( 'repeater' === $type ) {
			$value = array_map( static fn( array $row ) => fixture_format_fields( $field['sub_fields'], $row ), (array) $value );
		} elseif ( 'group' === $type ) {
			$value = fixture_format_fields( $field['sub_fields'], (array) $value );
		} elseif ( 'image' === $type ) {
			$value = $value ? array( 'ID' => $value, 'url' => '/tests/fixtures/photo.svg' ) : false;
		}
		$result[ $name ] = $value;
	}
	return $result;
}

require_once DPI_BLOCKS_DIR . 'includes/Autoloader.php';
\DPI\Blocks\Autoloader::register();
require_once DPI_BLOCKS_DIR . 'includes/functions.php';
