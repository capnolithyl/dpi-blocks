const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/e2e',
  testMatch: 'default-styles.spec.js',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: 'http://127.0.0.1:8097',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    launchOptions: process.env.DPI_BLOCKS_BROWSER_EXECUTABLE
      ? { executablePath: process.env.DPI_BLOCKS_BROWSER_EXECUTABLE }
      : {}
  },
  projects: [
    { name: 'desktop', use: { viewport: { width: 1440, height: 900 } } },
    { name: 'tablet', use: { viewport: { width: 768, height: 1024 } } },
    { name: 'phone', use: { viewport: { width: 390, height: 844 } } }
  ],
  webServer: {
    command: 'php -S 127.0.0.1:8097 -t .',
    url: 'http://127.0.0.1:8097/tests/fixtures/defaults.php',
    reuseExistingServer: !process.env.CI
  }
});
