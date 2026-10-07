# Theme-aware block defaults

DPI Blocks ships neutral presentation for all 16 blocks and their supported
layouts. The plugin supplies usable spacing, cards, controls, media treatment,
and responsive layouts. Site branding and the final design remain in the theme.

## What comes from the theme

`wp_get_global_styles()` supplies explicitly assigned body, heading, and button
styles, plus the global block gap. This includes user Global Styles edits.
Preset references remain CSS variables, so arbitrary palette/font/spacing slugs
work. The plugin does not pick the first preset or assume slugs such as `primary`,
`navy`, `body`, or `heading` exist. Merely listing a palette or font family does
not assign it a semantic role.

When semantic values are absent, text and fonts inherit. Borders and subtle card
surfaces derive from `currentColor`; actions use a neutral outline. Photography
uses an overridable white/black contrast pair. The plugin does not load fonts,
set a site-wide content width, or assign any parish content or assets.

## Ordinary CSS overrides

Structural selectors use `:where(...)`. Presentation adds one class of
specificity so unlayered theme resets do not erase padding, borders, or heading
sizes. Scope a theme override with `.dpi-block` (as in the button example below)
and it wins regardless of stylesheet order. Existing scoped IHM-specific rules
continue to win; no presentation override needs `!important`.

Design tokens and inherited heading fonts remain in the early `dpi-blocks`
cascade layer, so theme CSS variables and global heading typography retain
priority. Themes that use CSS layers can override tokens in their own layer and
use ordinary unlayered CSS for direct property overrides.
```css
.dpi-block {
  --dpi-block-gap: 2rem;
  --dpi-block-radius: 0.25rem;
  --dpi-heading-font: var(--wp--preset--font-family--my-display-font);
}

.dpi-block .dpi-button {
  background: var(--wp--preset--color--my-action-color);
  color: var(--wp--preset--color--my-button-text);
  border-radius: 0;
}
```

WordPress block-support colors, typography, spacing, and inline styles retain
their normal priority. A narrower scope can override any block independently.
Keep IHM-specific rules in `tailwind/custom/components/dpi-blocks/<slug>.css`.

## Optional theme.json token mapping

Themes with palettes but no semantic Global Styles can explicitly map their
existing presets. WordPress turns `settings.custom.dpiBlocks` into the CSS
properties consumed by the plugin. None of these settings is required.

```json
{
  "version": 3,
  "settings": {
    "custom": {
      "dpiBlocks": {
        "gap": "var(--wp--preset--spacing--roomy)",
        "padding": "var(--wp--preset--spacing--section)",
        "radius": "0.75rem",
        "bodyFont": "var(--wp--preset--font-family--text)",
        "headingFont": "var(--wp--preset--font-family--display)",
        "headingSize": "clamp(1.75rem, 3vw, 2.75rem)",
        "titleSize": "1.25em",
        "accent": "var(--wp--preset--color--accent)",
        "surface": "var(--wp--preset--color--surface)",
        "borderColor": "var(--wp--preset--color--border)",
        "buttonBackground": "var(--wp--preset--color--action)",
        "buttonColor": "var(--wp--preset--color--on-action)",
        "buttonRadius": "999px",
        "mediaBackground": "#000000",
        "mediaColor": "#ffffff",
        "inverseBackground": "var(--wp--preset--color--contrast)",
        "inverseColor": "var(--wp--preset--color--base)"
      }
    }
  }
}
```

## Keep only structural styles

For a theme that supplies all block presentation:

```php
add_filter( 'dpi_blocks/default_styles_enabled', '__return_false' );
```

Add the filter before `init` (for example in the theme's `functions.php`). This
disables the `block-defaults.css` file while retaining the shared and per-block
structural styles, JavaScript, and editor behavior.

## Verification

- `composer test`: PHP, semantic token resolution, safe CSS values, metadata
  stylesheet retention, editor layer order, and Lab scenario generation.
- `npm run test:styles`: production PHP renderers with deterministic WordPress
  and ACF data adapters, exercised in Chromium at desktop, tablet, and phone
  widths. No live site, account, or ACF license is needed for these CSS checks.
- `npm run test:e2e`: the existing administrator-only WordPress Block Lab test
  with `DPI_BLOCKS_TEST_URL`, `DPI_BLOCKS_TEST_USER`, and
  `DPI_BLOCKS_TEST_PASSWORD` configured.

The offline suite checks all discovered Lab choices, optional media, semantic
and absent theme tokens, unlayered reset resilience, theme override priority, dark banners, narrow columns,
and keyboard controls. The deployed Lab remains the integration check for a
real WordPress/ACF environment and third-party social feed providers.
