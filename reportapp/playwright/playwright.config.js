// playwright.config.js
module.exports = {
  testDir: '.',
  // Overall test timeout - must accommodate longest user-selected wait (5 min) + setup time
  timeout: 360000,
  use: {
    headless: true,
    viewport: { width: 1920, height: 1080 },
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    launchOptions: {
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
  },
  reporter: [['list'], ['html', { outputFolder: '../playwright-report' }]],
};
