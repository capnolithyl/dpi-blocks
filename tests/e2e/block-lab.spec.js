const { test, expect } = require('@playwright/test');

const siteUrl = (process.env.DPI_BLOCKS_TEST_URL || '').replace(/\/$/, '');
const username = process.env.DPI_BLOCKS_TEST_USER || '';
const password = process.env.DPI_BLOCKS_TEST_PASSWORD || '';

test.describe('DPI Block Lab', () => {
  test.skip(!siteUrl || !username || !password, 'Set DPI_BLOCKS_TEST_URL, DPI_BLOCKS_TEST_USER, and DPI_BLOCKS_TEST_PASSWORD.');

  test('renders every bundled block without renderer, JavaScript, or plugin asset failures', async ({ page }) => {
    const pageErrors = [];
    const failedPluginRequests = [];

    page.on('pageerror', error => {
      pageErrors.push(error.message);
    });

    page.on('requestfailed', request => {
      if (request.url().includes('/dpi-blocks/')) {
        failedPluginRequests.push(`${request.method()} ${request.url()} :: ${request.failure()?.errorText || 'request failed'}`);
      }
    });

    await page.goto(`${siteUrl}/wp-login.php`, { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill(username);
    await page.locator('#user_pass').fill(password);
    await Promise.all([
      page.waitForURL(url => url.pathname.includes('/wp-admin/')),
      page.locator('#wp-submit').click()
    ]);

    await page.goto(`${siteUrl}/wp-admin/tools.php?page=dpi-blocks-lab`, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'DPI Block Lab', level: 1 })).toBeVisible();

    const inventoryRows = page.locator('.dpi-block-lab-table tbody tr');
    expect(await inventoryRows.count()).toBeGreaterThanOrEqual(16);

    const preview = page.getByRole('link', { name: 'Open front-end Block Lab' });
    await expect(preview).toBeVisible();

    const previewHref = await preview.getAttribute('href');
    expect(previewHref).toBeTruthy();

    await page.goto(previewHref, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'DPI Block Lab', level: 1 })).toBeVisible();

    const blockSections = page.locator('[data-dpi-lab-block]');
    const scenarioSections = page.locator('[data-dpi-lab-scenario]');

    expect(await blockSections.count()).toBeGreaterThanOrEqual(16);
    expect(await scenarioSections.count()).toBeGreaterThanOrEqual(await blockSections.count());

    await expect(page.locator('.dpi-block-lab__error')).toHaveCount(0);

    const slickCandidates = page.locator('[data-dpi-slick]:visible');
    const slickCount = await slickCandidates.count();

    if (slickCount > 0) {
      await expect(slickCandidates.first()).toHaveClass(/slick-initialized/);
    }

    expect(failedPluginRequests, failedPluginRequests.join('\n')).toEqual([]);
    expect(pageErrors, pageErrors.join('\n')).toEqual([]);
  });
});
