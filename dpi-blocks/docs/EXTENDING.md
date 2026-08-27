# Extending DPI Blocks

DPI Blocks intentionally has no build step. PHP, CSS, JavaScript, block metadata, and ACF JSON are shipped as readable source. The plugin remains the canonical fallback while a theme customizes only the parts it owns.

## Override one block renderer

Copy only the renderer that needs to change into the active theme:

```text
wp-content/themes/example-theme/
└── dpi-blocks/
    └── blocks/
        └── hero/
            └── render.php
```

The lookup order is the child theme, the parent theme, and then the bundled plugin renderer. Removing the theme file immediately restores the plugin fallback. Theme files must be readable PHP files contained inside the active child or parent theme; invalid paths and traversal attempts are ignored.

An override receives the normal ACF render variables:

- `$block`
- `$content`
- `$is_preview`
- `$post_id`
- `$wp_block`
- `$context`

It also receives `$dpi_block_name`, `$dpi_block_slug`, and `$dpi_default_template`. The default path makes a small wrapper possible without copying the plugin renderer:

```php
<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="site-hero-frame">
	<?php require $dpi_default_template; ?>
</div>
```

Templates are loaded with `require`, so every block instance renders. Theme copies do not automatically inherit later plugin markup changes; compare them with the bundled renderer after plugin updates.

Template selection can be adjusted with these filters:

- `dpi_blocks/block_template_candidates`
- `dpi_blocks/block_template`

The first filters theme-relative candidates. The second filters the resolved absolute path. Both receive an associative context with the canonical block name, slug, source file, bundled template, and render arguments; the final-path context also contains the validated candidates and located selection. A final path is still subject to the plugin's PHP/readability/theme-containment checks.

The renderer fires these actions around the selected template:

- `dpi_blocks/before_block_render`
- `dpi_blocks/before_block_render/<slug>`
- `dpi_blocks/after_block_render`
- `dpi_blocks/after_block_render/<slug>`

Each action receives the same render context. Do not call `dpi_blocks_render_acf_block()` recursively from one of these hooks; the plugin guards accidental recursion, but hooks should decorate output or state rather than restart rendering.

## Extend ACF fields

For an additive field, the most update-friendly option is a separate theme-owned local field group located at `block == dpi/<slug>`. Give every theme group and field a globally unique, theme-prefixed key and name.

To modify or remove a bundled field, filter the decoded group before it is registered:

```php
add_filter(
	'dpi_blocks/acf_field_group/key=group_dpi_hero',
	static function ( array $group, array $context ): array {
		$group['instructions'] = __( 'Site-specific hero guidance.', 'example-theme' );
		return $group;
	},
	10,
	2
);
```

Available filters are:

- `dpi_blocks/acf_field_group`
- `dpi_blocks/acf_field_group/key=<group_key>`

Return `false` intentionally to suppress a complete group. The context identifies the group key, source JSON file, related module, and block name where applicable.

Keep the original group key unchanged. Retained bundled fields must keep their existing `key` and `name`; those identifiers connect saved block data to the editor. New fields need unique theme-prefixed keys and names. After filtering, the plugin validates duplicate keys, conditional-logic targets, and repeater/flexible-content `collapsed` references. An invalid mutation is rejected, the bundled group is registered instead, and administrators receive a notice. Removing a field does not erase its previously saved value.

## Customize block metadata

Use these filters for safe registration changes:

- `dpi_blocks/block_metadata`
- `dpi_blocks/block_metadata/<slug>`

They receive the metadata plus a context containing the canonical name, slug, and source `block.json`. Appropriate customizations include `title`, `icon`, `description`, `keywords`, `supports`, `attributes`, `parent`, `ancestor`, and block context declarations.

```php
add_filter(
	'dpi_blocks/block_metadata/hero',
	static function ( array $metadata, array $context ): array {
		$metadata['title']    = __( 'Site Hero', 'example-theme' );
		$metadata['keywords'] = array( 'hero', 'masthead', 'campaign' );
		return $metadata;
	},
	10,
	2
);
```

The plugin always restores the canonical `dpi/<slug>` name, Block API version 3, ACF block version 3, and its central render callback after filtering. A malformed filter result falls back to the bundled metadata and produces an administrator notice.

## Assets and JavaScript

The public block asset handles are:

- `dpi-blocks` for the shared structural stylesheet and initializer script
- `dpi-blocks-slick` for the local Slick stylesheet and script

Enqueue theme assets after, or with a dependency on, the appropriate plugin handle. The plugin does not auto-discover theme CSS or JavaScript.

Per-block requirements are filterable with:

- `dpi_blocks/block_asset_requirements`
- `dpi_blocks/block_asset_requirements/<slug>`

The requirements array has three booleans: `styles`, `script`, and `slick`. The context contains the canonical name, slug, source file, phase, parsed block/data state, rendered content where available, defaults, and current requirements. Slick always requires the shared initializer. Turning off a required plugin asset means the theme assumes responsibility for equivalent structure and behavior.

```php
add_filter(
	'dpi_blocks/block_asset_requirements/staff-card',
	static function ( array $requirements, array $context ): array {
		// A custom Staff Card renderer added plugin-compatible dialog markup.
		$requirements['script'] = true;
		return $requirements;
	},
	10,
	2
);
```

After dynamically inserting supported markup, initialize it explicitly:

```js
window.DPIBlocks?.initialize(container);
```

`initialize()` accepts a `Document`, element, or ACF jQuery scope and is idempotent. The plugin still initializes on DOM readiness and ACF preview refreshes.

Interactive markup uses these attributes:

- `data-dpi-slick` contains a JSON Slick options object on a carousel host.
- `data-dpi-tabs` contains tabs marked `data-dpi-tab`; their `aria-controls` values match panels marked `data-dpi-tab-panel`.
- `data-dpi-social-feed` may identify a feed wrapper. `data-dpi-feed-selector` points to its track, with `data-dpi-feed-track` as the markup fallback.
- `data-dpi-dialog-open` uses `aria-controls` to target `data-dpi-dialog`; `data-dpi-dialog-close` closes it.

Keep the related ARIA roles, IDs, selected state, keyboard behavior, and no-JavaScript links when customizing markup.

## Styling contract

Every bundled renderer uses `get_block_wrapper_attributes()`, the generated `.wp-block-dpi-<slug>` class, and stable `.dpi-block`, `.dpi-<slug>`, and `.dpi-<slug>__*` component classes. Layout and state modifiers use `.dpi-<slug>--*`; placeholder output uses `.dpi-block--placeholder`. These classes and the interaction attributes above are the supported 1.x theme seam.

Themes may override these CSS variables:

```css
:root {
	--dpi-block-gap: var(--wp--preset--spacing--40, clamp(1rem, 2vw, 2rem));
	--dpi-section-gap: var(--dpi-block-gap);
	--dpi-card-gap: var(--dpi-block-gap);
	--dpi-media-ratio: 4 / 3;
	--dpi-carousel-control-size: 2.5rem;
	--dpi-block-radius: var(--wp--custom--border--radius, 0.5rem);
	--dpi-hero-overlay-color: var(--wp--preset--color--contrast, CanvasText);
}
```

The Hero renderer supplies `--dpi-hero-overlay-opacity` per block. Directory templates also use `--dpi-surface`, `--dpi-text`, and `--dpi-accent`; the compact search interface sets `--dpi-search-left` and `--dpi-search-top` dynamically. The plugin does not define a brand palette. Controls and SVG icons inherit `currentColor`.

## Add a uniquely named block

`dpi_blocks/block_directories` remains available for adding blocks, not replacing a bundled `dpi/*` block. Duplicate canonical names are rejected and the plugin copy remains authoritative.

```php
add_filter(
	'dpi_blocks/block_directories',
	static function ( array $directories ): array {
		$directories[] = get_stylesheet_directory() . '/custom-blocks';
		return $directories;
	}
);
```

Each immediate child directory needs a valid `block.json` and its own renderer. Use a unique namespace/name, register a theme-owned ACF group, and register any theme assets normally. Disabled DPI blocks remain registered and renderable so saved content never becomes unsupported; the setting affects only inserter availability.

## Other public integrations

- Functions: `dpi_blocks_render_top_bar()` and `dpi_blocks_render_search_trigger()`
- Actions: `dpi_blocks/top_bar` and `dpi_blocks/search_trigger`
- Shortcodes: `[dpi_top_bar]` and `[dpi_search_trigger]`
- Filters: `dpi_blocks/block_category`, `dpi_blocks/font_awesome_icons`, `dpi_blocks/social_feed_adapters`, and `dpi_blocks/template_candidates`

When search is inserted into a classic menu, Settings → DPI Blocks → Top Bar & Search can append one or more theme classes to the generated `<li>`. The stable `menu-item dpi-menu-search` classes are always retained.

Social-feed adapter callbacks receive the configured shortcode. Return `null` when unsupported, or an array containing a stable `name` and the feed's `track_selector`. This lets the initializer enhance known feed markup while leaving unknown shortcode output untouched.

Directory templates belong in `<theme>/dpi-blocks/`; conventional WordPress post-type and taxonomy template names also take precedence. Office overrides use `archive-office.php`, `taxonomy-office_group.php`, and `single-office.php`. For directory requests, WordPress checks each `dpi-blocks/` candidate before its conventional filename and checks the child theme before the parent theme for each candidate; the plugin template remains the final fallback. Font Awesome values use `style:icon-name`, for example `solid:church` or `brands:instagram`. Extending `dpi_blocks/font_awesome_icons` requires a corresponding symbol in a supported local sprite.
