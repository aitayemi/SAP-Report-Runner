import { test, expect, chromium } from '@playwright/test';

// This file is overwritten dynamically by run_test.php on each run.
// It is kept here as a starter template for initial Playwright setup.

test('starter template', async () => {
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  await page.goto('https://fiori.acme.com/fiori?saml2=disabled');

  await browser.close();
});
