=== DPI Blocks ===
Contributors: diocesan
Tags: acf, blocks, staff, ministry, offices
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Requires Plugins: advanced-custom-fields-pro
Stable tag: 1.6.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portable ACF blocks, header utilities, and staff/ministry/office directories for custom WordPress sites.

== Description ==

DPI Blocks provides sixteen dynamic ACF blocks, an optional top bar and accessible search UI, and opt-in Staff, Ministry, and Office content types. Presentation is intentionally neutral so themes can style the stable `dpi-*` classes and CSS variables.

Advanced Custom Fields Pro 6.6 or newer is required. ACF Extended and the ACF Font Awesome add-on are not required.

== Installation ==

1. Install and activate Advanced Custom Fields Pro 6.6 or newer.
2. Upload and activate DPI Blocks.
3. Configure features under Settings > DPI Blocks.
4. If a theme needs the top bar inside its header, call `dpi_blocks_render_top_bar()` at the desired location and turn off automatic placement.

== Theme integration ==

Themes can override one block renderer at `dpi-blocks/blocks/<slug>/render.php` while the plugin remains the fallback. They can call `dpi_blocks_render_top_bar()` and `dpi_blocks_render_search_trigger()`, or use the `dpi_blocks/top_bar` and `dpi_blocks/search_trigger` actions. Directory templates may also be overridden inside a theme's `dpi-blocks` directory. See `docs/EXTENDING.md` for the complete template, field, metadata, asset, CSS, and JavaScript extension contract.

== Changelog ==

= 1.6.7 =
* Changed Community Slider category selection to combine all selected categories into one post query and one carousel/grid instead of rendering a tab and panel per category.

= 1.6.6 =
* Added an optional Community Slider background image setting so themes can control the section artwork while editors choose the image.

= 1.6.5 =
* Added optional post dates and an optional section CTA to Community Slider cards so themes can reproduce richer news/event carousel designs.

= 1.6.4 =
* Made the Mission subheading optional.
* Made Community Slider introductory text optional.

= 1.6.3 =
* Replaced the Image Buttons reveal color picker with a theme-palette select populated from the active theme.json/global color settings.
* Theme color selections are stored as WordPress preset slugs and rendered through --wp--preset--color custom properties; legacy hex values remain supported.

= 1.6.2 =
* Updated Image Buttons Hover Reveal to slide the overlay up from the bottom while moving the existing heading and revealing supporting copy/actions.
* Added an optional Reveal Overlay Color picker so themes can supply a default while editors may override it per block.
* Removed list markers and default list spacing from Image Button lists in both the editor and front end.

= 1.6.1 =
* Render plugin-owned links as inert span elements in ACF block previews so Gutenberg clicks do not navigate or open link previews, while preserving the same component classes and styling.
* Disable pointer interaction for links produced by rich text or third-party shortcode output inside editor previews as a fallback.

= 1.6.0 =
* Added an optional Hover Reveal interaction to Image Buttons, with per-card supporting copy and action labels accessible by mouse hover and keyboard focus.

= 1.5.4 =
* Grouped the Blocks settings screen into task-based sections with clearer use-case descriptions and a card layout.

= 1.5.3 =
* Added descriptions to the Blocks settings list explaining what each available block is for.

= 1.5.2 =
* Load shared block structural styles inside the iframe-based block editor canvas so editor previews match the front end.

= 1.5.1 =
* Added Select all and Deselect all controls to the Blocks settings screen.

= 1.5.0 =
* Added a setting for appending custom CSS classes to the main-menu search trigger's list item.

= 1.4.0 =
* Added a Hero video-source choice for YouTube or videos uploaded to the WordPress Media Library.
* Increased the Stats block limit from three items to six.

= 1.3.0 =
* Added Accordion, Anchor Navigation, Feature Banner, Five Pillars, Interior Hero, and Office Grid blocks.

= 1.2.0 =
* Added an opt-in Office directory with Office Groups, configurable routes, and neutral archive, taxonomy, and single templates.

= 1.1.0 =
* Added child- and parent-theme overrides for individual block renderers.
* Added validated field-group and protected block-metadata extension hooks.
* Added per-block asset requirements and the public `DPIBlocks.initialize()` JavaScript API.

= 1.0.0 =
* Initial release.
