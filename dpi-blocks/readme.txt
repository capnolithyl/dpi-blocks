=== DPI Blocks ===
Contributors: diocesan
Tags: acf, blocks, staff, ministry, offices
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Requires Plugins: advanced-custom-fields-pro
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portable ACF blocks, header utilities, and staff/ministry/office directories for custom WordPress sites.

== Description ==

DPI Blocks provides ten dynamic ACF blocks, an optional top bar and accessible search UI, and opt-in Staff, Ministry, and Office content types. Presentation is intentionally neutral so themes can style the stable `dpi-*` classes and CSS variables.

Advanced Custom Fields Pro 6.6 or newer is required. ACF Extended and the ACF Font Awesome add-on are not required.

== Installation ==

1. Install and activate Advanced Custom Fields Pro 6.6 or newer.
2. Upload and activate DPI Blocks.
3. Configure features under Settings > DPI Blocks.
4. If a theme needs the top bar inside its header, call `dpi_blocks_render_top_bar()` at the desired location and turn off automatic placement.

== Theme integration ==

Themes can override one block renderer at `dpi-blocks/blocks/<slug>/render.php` while the plugin remains the fallback. They can call `dpi_blocks_render_top_bar()` and `dpi_blocks_render_search_trigger()`, or use the `dpi_blocks/top_bar` and `dpi_blocks/search_trigger` actions. Directory templates may also be overridden inside a theme's `dpi-blocks` directory. See `docs/EXTENDING.md` for the complete template, field, metadata, asset, CSS, and JavaScript extension contract.

== Changelog ==

= 1.2.0 =
* Added an opt-in Office directory with Office Groups, configurable routes, and neutral archive, taxonomy, and single templates.

= 1.1.0 =
* Added child- and parent-theme overrides for individual block renderers.
* Added validated field-group and protected block-metadata extension hooks.
* Added per-block asset requirements and the public `DPIBlocks.initialize()` JavaScript API.

= 1.0.0 =
* Initial release.
