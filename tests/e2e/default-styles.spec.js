const { test, expect } = require('@playwright/test');

async function gallery(page, query = '') {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(`/tests/fixtures/defaults.php${query}`, { waitUntil: 'networkidle' });
  await expect(page.locator('.fixture-error')).toHaveCount(0);
  expect(errors).toEqual([]);
}

test('all real renderers and Lab choices fit the page and keep their controls usable', async ({ page }, testInfo) => {
  await gallery(page);
  const inventory = await page.locator('[data-block]').evaluateAll(nodes => [...new Set(nodes.map(node => node.dataset.block))]);
  expect(inventory).toHaveLength(16);
  expect(await page.locator('[data-scenario]').count()).toBeGreaterThan(75);

  const overflow = await page.locator('.fixture-scenario > .dpi-block').evaluateAll(nodes => nodes.filter(node => {
    const parent = node.parentElement.getBoundingClientRect();
    const rect = node.getBoundingClientRect();
    return rect.width < 1 || rect.left < parent.left - 1 || rect.right > parent.right + 1;
  }).map(node => node.parentElement.dataset));
  expect(overflow).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  await expect(page.locator('.dpi-button').first()).toHaveCSS('border-top-style', 'solid');
  await expect(page.locator('[data-dpi-slick]:visible').first()).toHaveClass(/slick-initialized/);
  for (const slug of inventory) {
    await page.locator(`[data-block="${slug}"]`).first().screenshot({ path: testInfo.outputPath(`${slug}.png`) });
  }
});

test('semantic theme styles work with arbitrary preset names and plain CSS still wins', async ({ page }) => {
  await gallery(page, '?block=mission');
  const block = page.locator('.dpi-block').first();
  const action = block.locator('.dpi-button').first();
  await expect(action).toHaveCSS('background-color', 'rgb(52, 95, 98)');
  await expect(action).toHaveCSS('color', 'rgb(244, 240, 232)');
  await expect(block.locator('h2')).toHaveCSS('font-family', 'Georgia, serif');
  expect(await block.evaluate(node => getComputedStyle(node).getPropertyValue('--dpi-block-gap').trim())).toBe('24px');

  await page.addStyleTag({ content: '@layer fixture-theme { .dpi-block { --dpi-block-gap: 17px; } .dpi-button { background: rgb(120, 40, 20); border-radius: 2px; padding: 11px; } }' });
  await expect(action).toHaveCSS('background-color', 'rgb(120, 40, 20)');
  await expect(action).toHaveCSS('border-radius', '2px');
  await expect(action).toHaveCSS('padding-top', '11px');
  expect(await block.evaluate(node => getComputedStyle(node).getPropertyValue('--dpi-block-gap').trim())).toBe('17px');
});

test('themes without semantic tokens get neutral defaults without guessing their presets', async ({ page }) => {
  await gallery(page, '?block=mission&theme=bare');
  const block = page.locator('.dpi-block').first();
  await expect(block.locator('h2')).toHaveCSS('font-family', 'Arial, sans-serif');
  await expect(block.locator('.dpi-button').first()).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
});

test('split layouts fill the content width when optional media is missing', async ({ page }) => {
  await gallery(page);
  for (const slug of ['mission', 'mass-times', 'featured-links']) {
    const scenario = page.locator(`[data-block="${slug}"][data-scenario="Without optional media"]`);
    const grid = scenario.locator(`[class="dpi-${slug}__${slug === 'featured-links' ? 'body' : 'inner'}"]`);
    const count = await grid.evaluate(node => getComputedStyle(node).gridTemplateColumns.split(' ').length);
    expect(count, slug).toBe(1);
  }
});

test('dark themes and every banner image placement keep a readable foreground pair', async ({ page }) => {
  await gallery(page, '?block=feature-banner&theme=dark');
  for (const placement of ['background', 'left', 'right']) {
    for (const variant of ['light', 'dark']) {
      const slide = page.locator(`[data-scenario="${placement} / ${variant}"] .dpi-feature-banner__slide`);
      await expect(slide).toHaveClass(new RegExp(`dpi-feature-banner__slide--${placement}`));
      const colors = await slide.evaluate(node => {
        const style = getComputedStyle(node);
        return [style.color, style.backgroundColor];
      });
      expect(colors[0]).not.toBe(colors[1]);
    }
  }
});

test('blocks also stack inside narrow desktop columns', async ({ page }) => {
  await gallery(page, '?block=mission');
  await page.locator('.fixture-shell').evaluate(node => { node.style.width = '320px'; });
  const columns = await page.locator('.dpi-mission__inner').first().evaluate(node => getComputedStyle(node).gridTemplateColumns.split(' ').length);
  expect(columns).toBe(1);
});

test('accordion, tabs, staff dialog and image reveal retain keyboard affordances', async ({ page }) => {
  await gallery(page);
  const accordion = page.locator('[data-dpi-accordion-trigger]').first();
  await accordion.focus();
  await expect(accordion).toHaveCSS('outline-style', 'solid');
  await page.keyboard.press('Enter');
  await expect(accordion).toHaveAttribute('aria-expanded', 'true');

  const tabs = page.locator('[data-dpi-tabs]').filter({ has: page.locator('[data-dpi-tab]') }).first();
  await tabs.locator('[data-dpi-tab]').first().focus();
  await page.keyboard.press('ArrowRight');
  await expect(tabs.locator('[data-dpi-tab]').nth(1)).toHaveAttribute('aria-selected', 'true');

  await page.locator('[data-dpi-dialog-open]').first().click();
  await expect(page.locator('dialog[open]')).toHaveCount(1);
  await page.keyboard.press('Escape');
  await expect(page.locator('dialog[open]')).toHaveCount(0);

  const reveal = page.locator('.dpi-image-buttons__card--reveal').first();
  await reveal.focus();
  await expect(reveal).toHaveCSS('outline-style', 'solid');
  await expect(reveal.locator('.dpi-image-buttons__reveal')).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
});
