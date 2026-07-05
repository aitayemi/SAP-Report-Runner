<?php
header('Content-Type: text/plain');

echo "=== PHP DIAGNOSTICS ===

";

echo "PHP Version: " . phpversion() . "
";
echo "PHP SAPI: " . php_sapi_name() . "
";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "

";

echo "=== FILE CHECKS ===
";
$files = [
    'run_test.php',
    'data/reports.csv',
    'data/bizareas.csv',
    'playwright/playwright.config.js',
    'playwright/package.json',
];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo $file . ": " . (file_exists($path) ? "EXISTS (" . filesize($path) . " bytes)" : "MISSING") . "
";
}

echo "
=== PLAYWRIGHT CHECKS ===
";
$nodePath = trim(shell_exec('where node 2>nul'));
echo "Node path: " . ($nodePath ?: "NOT FOUND") . "
";

$npxPath = trim(shell_exec('where npx 2>nul'));
echo "npx path: " . ($npxPath ?: "NOT FOUND") . "
";

echo "
=== DIRECTORY PERMISSIONS ===
";
$dirs = ['.', 'playwright', 'data'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    echo $dir . ": writable=" . (is_writable($path) ? "YES" : "NO") . ", readable=" . (is_readable($path) ? "YES" : "NO") . "
";
}

echo "
=== JSON TEST ===
";
$test = json_encode(['test' => 'ok', 'time' => date('c')]);
echo "JSON encode works: " . ($test !== false ? "YES" : "NO") . "
";
echo $test . "
";

echo "
=== DONE ===
";
