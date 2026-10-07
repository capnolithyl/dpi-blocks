<?php
/** Offline gallery of the real plugin renderers, driven by the real Lab scenarios. */

require __DIR__ . '/wordpress.php';

$lab = new \DPI\Blocks\BlockLab();
$inventory = new ReflectionMethod( $lab, 'inventory' );
$inventory->setAccessible( true );
$items = $inventory->invoke( $lab );
$theme = $_GET['theme'] ?? 'semantic';
$styles = 'bare' === $theme ? array() : array(
	'color' => array( 'text' => 'var:preset|color|ink-97', 'background' => 'var:preset|color|paper-97' ),
	'typography' => array( 'fontFamily' => 'var:preset|font-family|body-97', 'lineHeight' => 1.6 ),
	'spacing' => array( 'blockGap' => 'var:preset|spacing|roomy-97' ),
	'elements' => array(
		'heading' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|display-97' ) ),
		'button' => array( 'color' => array( 'background' => 'var:preset|color|action-97', 'text' => 'var:preset|color|paper-97' ) ),
	),
);
$theme_css = \DPI\Blocks\ThemeStyles::from_styles( $styles );
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<title>DPI Blocks default styles fixture</title>
<style>@layer dpi-blocks;</style>
<!-- Deliberately load the theme first to exercise cascade-layer precedence. -->
<style>
@layer fixture-theme {
  :root {
    --wp--preset--color--ink-97: #23383c; --wp--preset--color--paper-97: #f4f0e8;
    --wp--preset--color--action-97: #345f62; --wp--preset--font-family--body-97: Arial, sans-serif;
    --wp--preset--font-family--display-97: Georgia, serif; --wp--preset--spacing--roomy-97: 24px;
  }
  body { margin: 0; background: var(--wp--preset--color--paper-97); color: var(--wp--preset--color--ink-97); font: 16px/1.6 Arial, sans-serif; }
  <?php if ( 'dark' === $theme ) : ?>
  :root { --wp--preset--color--ink-97: #e7ede9; --wp--preset--color--paper-97: #162629; }
  <?php endif; ?>
}
.fixture-shell { width: min(100% - 32px, 1100px); margin: 24px auto; }
.fixture-scenario { margin: 24px 0; border: 1px solid #ccd3d1; }
.fixture-scenario > header { padding: 8px 16px; background: #e4e9e7; font: 14px/1.5 Arial, sans-serif; }
<?php echo $theme_css; ?>
</style>
<link rel="stylesheet" href="/assets/css/blocks.css">
<?php foreach ( glob( DPI_BLOCKS_DIR . 'blocks/*/style.css' ) as $style ) : ?>
<link rel="stylesheet" href="/blocks/<?php echo basename( dirname( $style ) ); ?>/style.css">
<?php endforeach; ?>
<link rel="stylesheet" href="/assets/css/block-defaults.css">
<link rel="stylesheet" href="/assets/vendor/slick/slick.css">
</head><body><main class="fixture-shell">
<?php
foreach ( $items as $item ) {
	$slug = substr( $item['name'], 4 );
	if ( isset( $_GET['block'] ) && $_GET['block'] !== $slug ) { continue; }
	$scenarios = $item['scenarios'];
	if ( 'feature-banner' === $slug ) {
		foreach ( array( 'background', 'left', 'right' ) as $placement ) {
			foreach ( array( 'light', 'dark' ) as $variant ) {
				$sample = $scenarios[0];
				$sample['name'] = $placement . ' / ' . $variant;
				$sample['fields']['slides'][0]['field_dpi_feature_banner_slide_image_layout'] = $placement;
				$sample['fields']['slides'][0]['field_dpi_feature_banner_slide_visual_variant'] = $variant;
				$scenarios[] = $sample;
			}
		}
	}
	foreach ( $scenarios as $scenario ) {
		$fixture_fields = fixture_format_fields( $item['field_group']['fields'], $scenario['fields'] );
		$fixture_missing_media = 'Without optional media' === $scenario['name'];
		// Keep playback and query data deterministic; test real markup without remote services.
		$fixture_fields['autoplay'] = false;
		$fixture_fields['video_autoplay'] = false;
		$fixture_fields['video_source'] = 'media';
		$fixture_fields['video'] = array();
		$fixture_fields['video_file'] = array();
		$fixture_fields['use_global_profiles'] = false;
		$fixture_fields['interaction_mode'] = 'modal';
		$is_preview = false;
		$post_id = 1;
		$block = array( 'name' => $item['name'] );
		echo '<section class="fixture-scenario" data-block="' . $slug . '" data-scenario="' . esc_attr( $scenario['name'] ) . '"><header>' . $item['title'] . ' — ' . esc_html( $scenario['name'] ) . '</header>';
		try {
			set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
			( static function ( $slug, $block, $is_preview, $post_id ) { require DPI_BLOCKS_DIR . 'blocks/' . $slug . '/render.php'; } )( $slug, $block, $is_preview, $post_id );
		} catch ( Throwable $error ) {
			echo '<pre class="fixture-error">' . esc_html( $error->getMessage() ) . '</pre>';
		} finally { restore_error_handler(); }
		echo '</section>';
	}
}
?>
</main>
<script src="/node_modules/jquery/dist/jquery.min.js"></script>
<script src="/assets/vendor/slick/slick.min.js"></script>
<script>window.DPIBlocksConfig = <?php echo wp_json_encode( array( 'previousLabel' => 'Previous', 'nextLabel' => 'Next', 'previousIcon' => dpi_blocks_render_icon( 'solid:chevron-left' ), 'nextIcon' => dpi_blocks_render_icon( 'solid:chevron-right' ) ) ); ?>;</script>
<script src="/assets/js/blocks.js"></script>
<script src="/blocks/accordion/view.js"></script><script src="/blocks/five-pillars/view.js"></script><script src="/blocks/anchor-navigation/view.js"></script>
</body></html>
