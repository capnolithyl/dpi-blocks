<?php

declare(strict_types=1);

use DPI\Blocks\Assets;
use DPI\Blocks\Blocks;
use DPI\Blocks\ThemeStyles;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

require_once dirname( __DIR__, 2 ) . '/includes/ThemeStyles.php';
require_once dirname( __DIR__, 2 ) . '/includes/Blocks.php';
require_once dirname( __DIR__, 2 ) . '/includes/Assets.php';

final class ThemeStylesTest extends TestCase {
	public function test_palette_and_font_presets_without_semantic_styles_are_not_guessed(): void {
		$this->assertSame( '', ThemeStyles::from_styles( array() ) );
		$this->assertSame( '', ThemeStyles::from_styles( array(
			'color' => array( 'palette' => array( array( 'slug' => 'arbitrary', 'color' => '#fedcba' ) ) ),
			'typography' => array( 'fontFamilies' => array( array( 'slug' => 'brand', 'fontFamily' => 'Brand Serif' ) ) ),
		) ) );
	}

	public function test_semantic_styles_preserve_arbitrary_preset_names_and_css_values(): void {
		$css = ThemeStyles::from_styles( array(
			'color' => array( 'text' => 'var:preset|color|ink-97', 'background' => 'rgb(245 242 235)' ),
			'typography' => array( 'fontFamily' => '"Example Sans", sans-serif', 'lineHeight' => 1.7 ),
			'spacing' => array( 'blockGap' => array( 'row' => 'var:preset|spacing|roomy', 'column' => '1rem' ) ),
			'elements' => array(
				'heading' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|display-97' ) ),
				'button' => array( 'color' => array( 'background' => 'var:preset|color|action-97', 'text' => '#fff' ) ),
			),
		) );
		$this->assertStringContainsString( '@layer dpi-blocks{:where(.dpi-block){', $css );
		$this->assertStringContainsString( '--dpi-theme-text:var(--wp--preset--color--ink-97)', $css );
		$this->assertStringContainsString( '--dpi-theme-heading-font:var(--wp--preset--font-family--display-97)', $css );
		$this->assertStringContainsString( '--dpi-theme-button-background:var(--wp--preset--color--action-97)', $css );
		$this->assertStringContainsString( '--dpi-theme-body-font:"Example Sans", sans-serif', $css );
		$this->assertStringContainsString( '--dpi-theme-gap:var(--wp--preset--spacing--roomy)', $css );
	}

	public function test_custom_references_and_zero_values_remain_valid(): void {
		$css = ThemeStyles::from_styles( array(
			'typography' => array( 'fontSize' => 'var:custom|typeScale|body', 'lineHeight' => 'normal' ),
			'elements' => array( 'button' => array( 'border' => array( 'radius' => 0 ) ) ),
		) );
		$this->assertStringContainsString( '--dpi-theme-body-size:var(--wp--custom--type-scale--body)', $css );
		$this->assertStringContainsString( '--dpi-theme-button-radius:0;', $css );
	}

	public function test_invalid_or_structured_values_cannot_escape_the_token_rule(): void {
		foreach ( array( '#fff;}body{display:none', '</style><script>bad()</script>', 'red/*', 'url(https://example.com/)', 'red!important', 'var:preset|color|', array( 'topLeft' => '1rem' ) ) as $value ) {
			$this->assertSame( '', ThemeStyles::from_styles( array( 'color' => array( 'text' => $value ) ) ) );
		}
	}

	public function test_every_metadata_stylesheet_survives_shared_asset_registration(): void {
		$blocks = new Blocks();
		$method = new ReflectionMethod( Blocks::class, 'block_styles' );
		$method->setAccessible( true );
		foreach ( glob( dirname( __DIR__, 2 ) . '/blocks/*/block.json' ) ?: array() as $file ) {
			$metadata = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
			$styles = $method->invoke( $blocks, $metadata['style'] ?? array() );
			$this->assertSame( 'dpi-blocks', $styles[0] );
			$this->assertSame( 'dpi-blocks-defaults', end( $styles ) );
			foreach ( (array) ( $metadata['style'] ?? array() ) as $style ) {
				$this->assertContains( $style, $styles, $file );
			}
		}
	}

	public function test_editor_layer_is_declared_before_theme_layers_without_losing_settings(): void {
		$settings = ( new Assets() )->editor_style_layer( array(
			'other' => true,
			'styles' => array( array( 'css' => '@layer theme { h2 { color: red; } }' ) ),
		) );
		$this->assertTrue( $settings['other'] );
		$this->assertSame( '@layer dpi-blocks;', $settings['styles'][0]['css'] );
		$this->assertStringStartsWith( '@layer theme', $settings['styles'][1]['css'] );
	}
}
