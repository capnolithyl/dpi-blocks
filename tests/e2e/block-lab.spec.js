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

    await page.goto(`${siteUrl}/wp-admin/admin.php?page=dpi-blocks`, { waitUntil: 'networkidle' });

    const dpiMenu = page.locator('#toplevel_page_dpi-blocks .wp-submenu');
    await expect(dpiMenu.getByRole('link', { name: 'Blocks', exact: true })).toHaveAttribute('href', /page=dpi-blocks$/);
    await expect(dpiMenu.getByRole('link', { name: 'Top Bar & Search', exact: true })).toHaveAttribute('href', /page=dpi-blocks-header$/);
    await expect(dpiMenu.getByRole('link', { name: 'Custom Post Types', exact: true })).toHaveAttribute('href', /page=dpi-blocks-content-types$/);
    await expect(dpiMenu.getByRole('link', { name: 'Social', exact: true })).toHaveAttribute('href', /page=dpi-blocks-social$/);
    await expect(dpiMenu.getByRole('link', { name: 'Lab', exact: true })).toHaveAttribute('href', /page=dpi-blocks-lab$/);

    await page.goto(`${siteUrl}/wp-admin/admin.php?page=dpi-blocks-lab`, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'DPI Block Lab', level: 1 })).toBeVisible();

    const inventoryRows = page.locator('.dpi-block-lab-table tbody tr');
    expect(await inventoryRows.count()).toBeGreaterThanOrEqual(16);

    const preview = page.getByRole('link', { name: 'Open front-end Block Lab' });
    await expect(preview).toBeVisible();
    await expect(inventoryRows.first().getByRole('link', { name: 'View scenarios' })).toBeVisible();

    const previewHref = await preview.getAttribute('href');
    expect(previewHref).toBeTruthy();

    await page.goto(previewHref, { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'DPI Block Lab', level: 1 })).toBeVisible();

    const blockLinks = page.locator('.dpi-block-lab__index-link');
    expect(await blockLinks.count()).toBeGreaterThanOrEqual(16);
    const blockPages = await blockLinks.evaluateAll(links => links.map(link => ({ href: link.href, title: link.querySelector('strong')?.textContent?.trim() })));

    for (const blockPage of blockPages) {
      await page.goto(blockPage.href, { waitUntil: 'networkidle' });
      await expect(page.locator('[data-dpi-lab-block]'), blockPage.title).toHaveCount(1);
      await expect(page.locator('[data-dpi-lab-scenario]').first(), blockPage.title).toBeVisible();
      await expect(page.locator('.dpi-block-lab__error'), blockPage.title).toHaveCount(0);
      await expect(page.getByRole('link', { name: 'All blocks' }), blockPage.title).toBeVisible();

      const slickCandidates = page.locator('[data-dpi-slick]:visible');
      if (await slickCandidates.count()) {
        await expect(slickCandidates.first(), blockPage.title).toHaveClass(/slick-initialized/);
      }
    }

    expect(failedPluginRequests, failedPluginRequests.join('\n')).toEqual([]);
    expect(pageErrors, pageErrors.join('\n')).toEqual([]);
  });
});
