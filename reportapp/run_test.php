<?php
header('Content-Type: application/json');

// Disable PHP execution time limit - Playwright tests can run for several minutes
set_time_limit(0);
ini_set('max_execution_time', 0);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$reportId = $input['reportId'] ?? '';
$reportName = $input['reportName'] ?? '';
$actualName = $input['actualName'] ?? '';
$businessArea = $input['businessArea'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$timeout = $input['timeout'] ?? 30000;

if (empty($reportId)) {
    echo json_encode(['success' => false, 'error' => 'No report ID provided']);
    exit;
}

if (empty($businessArea)) {
    echo json_encode(['success' => false, 'error' => 'No business area selected']);
    exit;
}

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

// Validate timeout
$timeout = intval($timeout);
if ($timeout < 1000) {
    $timeout = 30000;
}

// Configuration
$playwrightPath = 'npx';
$testScriptDir = __DIR__ . '/playwright';
$testScriptPath = $testScriptDir . '/reportapp.spec.js';

// Ensure playwright directory exists
if (!is_dir($testScriptDir)) {
    mkdir($testScriptDir, 0777, true);
}

// Generate the dynamic test script
$displayNameEscaped = addslashes($reportName);
$actualNameEscaped = addslashes($actualName);
$bizAreaEscaped = addslashes($businessArea);
$usernameEscaped = addslashes($username);
$passwordEscaped = addslashes($password);

$testScript = generateTestScript($reportId, $displayNameEscaped, $actualNameEscaped, $bizAreaEscaped, $usernameEscaped, $passwordEscaped, $timeout);
file_put_contents($testScriptPath, $testScript);

// Run Playwright test
$command = sprintf(
    'cd "%s" && %s playwright test reportapp.spec.js --config=playwright.config.js 2>&1',
    escapeshellarg($testScriptDir),
    $playwrightPath
);

// For Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $command = sprintf(
        'cd /d %s && %s playwright test reportapp.spec.js --config=playwright.config.js 2>&1',
        escapeshellarg($testScriptDir),
        $playwrightPath
    );
}

exec($command, $output, $returnCode);

$outputStr = implode("\n", $output);

if ($returnCode === 0) {
    echo json_encode([
        'success' => true,
        'reportId' => $reportId,
        'reportName' => $reportName,
        'actualName' => $actualName,
        'businessArea' => $businessArea,
        'username' => $username,
        'timeout' => $timeout,
        'output' => $outputStr
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Playwright test failed with exit code ' . $returnCode,
        'output' => $outputStr
    ]);
}

/**
 * Generate the Playwright test script - uses default browser
 */
function generateTestScript($reportId, $displayName, $actualName, $bizAreaFilter, $username, $password, $timeout) {
    return <<<JS
import { test, expect, chromium } from '@playwright/test';

const reportid = '{$reportId}';
const displayName = '{$displayName}';
const actualName = '{$actualName}';
const businessArea = '{$bizAreaFilter}';
const username = '{$username}';
const password = '{$password}';
const timeout = {$timeout};

test('Run report: {$displayName} - User: {$username} - BizArea: {$bizAreaFilter}', async () => {
  // Launch using default browser (Playwright finds it automatically)
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });

  const page = await context.newPage();

  const response = await page.goto('https://fiori.acme.com/fiori?saml2=disabled');

  // Login with user-provided credentials
  await page.getByRole('textbox', { name: 'User' }).click();
  await page.getByRole('textbox', { name: 'User' }).fill(username);
  await page.getByRole('textbox', { name: 'Password' }).click();
  await page.getByRole('textbox', { name: 'Password' }).fill(password);
  await page.getByRole('button', { name: 'Log On' }).click();

  // Handle dialogs with timeout - if OK button not displayed in 3 seconds, continue
  page.on('dialog', dialog => dialog.accept());
  try {
    await page.getByRole('button', { name: 'OK' }).click({ timeout: 2000 });
  } catch (dialogErr) {
    console.error('WARNING: OK dialog button not displayed within 2 seconds, continuing...');
    console.error('Error details:', dialogErr.message);
  }

  // Search for the report using the ACTUAL name
  await page.getByRole('button', { name: 'Open Search' }).click();
  await page.getByRole('searchbox', { name: 'Search In: Apps' }).fill(actualName);

  // Click on the report using the DISPLAY name
  await page.getByText(displayName).click();

  // Interact with the report
  await page.getByRole('textbox', { name: 'Business Area' }).click();
  await page.getByRole('textbox', { name: 'Business Area' }).fill(businessArea);
  // await page.getByText('Earth & Mineral Sciences').click();
  await page.getByRole('button', { name: 'Go' }).click();

  // Wait for results to load using the user-selected timeout
  await page.waitForTimeout(timeout);

  // Take a screenshot for evidence
  await page.screenshot({ path: `screenshot-report-{$reportId}-user-{$username}-bizarea-{$bizAreaFilter}.png`, fullPage: true });

  // Log out of the dashboard
  try {
    await page.getByRole('region', { name: 'Expanded header' }).click();
    await page.getByRole('button', { name: 'Me Area' }).click();
    await page.getByText('Sign Out').click();
    await page.getByRole('button', { name: 'OK' }).click();
    console.log('Logout successful');
  } catch (logoutErr) {
    console.error('ERROR: Failed to log out of the dashboard');
    console.error('Error details:', logoutErr.message);
    // Take a screenshot to help diagnose the logout failure
    try {
      await page.screenshot({ path: `screenshot-logout-error-report-{$reportId}-user-{$username}-bizarea-{$bizAreaFilter}.png`, fullPage: true });
      console.log('Screenshot saved: screenshot-logout-error-report-{$reportId}-user-{$username}-bizarea-{$bizAreaFilter}.png');
    } catch (screenshotErr) {
      console.error('ERROR: Also failed to take logout error screenshot:', screenshotErr.message);
    }
  }

  await browser.close();
});
JS;
}
