<?php
// Load reports data
$reports = [];
$reportsFile = __DIR__ . '/data/reports.csv';
if (($handle = fopen($reportsFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
        $reports[] = [
            'id' => trim($data[0]),
            'displayName' => trim($data[1]),
            'actualName' => trim($data[2])
        ];
    }
    fclose($handle);
}

// Load business areas data
$bizareas = [];
$bizareasFile = __DIR__ . '/data/bizareas.csv';
if (($handle = fopen($bizareasFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
        $reportId = trim($data[0]);
        $bizArea = trim($data[1]);
        if (!isset($bizareas[$reportId])) {
            $bizareas[$reportId] = [];
        }
        $bizareas[$reportId][] = $bizArea;
    }
    fclose($handle);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>ACME Corp - SAP Report Runner</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #002D62;
            color: #FFFFFF;
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            color: #333;
        }
        h1 {
            color: #002D62;
            margin: 0 0 8px 0;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        select, input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafafa;
        }
        select:focus, input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #002D62;
            box-shadow: 0 0 0 3px rgba(0,45,98,0.15);
            background: #fff;
        }
        select:disabled, input:disabled {
            background: #f0f0f0;
            color: #999;
            cursor: not-allowed;
        }
        input[type="password"] {
            font-family: 'Segoe UI', monospace;
            letter-spacing: 2px;
        }
        .btn-run {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #002D62, #001a3a);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .btn-run:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,45,98,0.4);
        }
        .btn-run:active {
            transform: translateY(0);
        }
        .btn-run:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .status {
            margin-top: 20px;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }
        .status.running {
            display: block;
            background: #fff8e1;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .status.success {
            display: block;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
        }
        .status.error {
            display: block;
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffc107;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .report-info {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
        }
        .report-info strong {
            color: #002D62;
        }
        .selection-summary {
            background: #f0f7ff;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            font-size: 14px;
            border-left: 4px solid #002D62;
        }
        .selection-summary strong {
            color: #002D62;
        }
        .actual-name {
            color: #002D62;
            font-weight: 600;
        }
        .section-divider {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 24px 0;
        }
        .section-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 12px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 SAP Report Runner</h1>
        <p class="subtitle">ACME Corp</p>

        <div class="section-label">🔐 Credentials</div>
        <div class="form-group">
            <label for="username">User</label>
            <input type="text" id="username" placeholder="Enter username" value="randomuser1">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" placeholder="Enter password" value="SecretPassword1">
        </div>

        <hr class="section-divider">

        <div class="section-label">⚙️ Options</div>
        <div class="form-group">
            <label for="timeoutSelect">Timeout</label>
            <select id="timeoutSelect">
                <option value="30000" selected>30 seconds (default)</option>
                <option value="120000">2 minutes</option>
                <option value="300000">5 minutes</option>
            </select>
        </div>

        <hr class="section-divider">

        <div class="section-label">📋 Report Selection</div>
        <div class="form-group">
            <label for="reportSelect">Select Report</label>
            <select id="reportSelect">
                <option value="">-- Choose a report --</option>
                <?php foreach ($reports as $report): ?>
                <option value="<?php echo htmlspecialchars($report['id']); ?>" data-actual-name="<?php echo htmlspecialchars($report['actualName']); ?>">
                    <?php echo htmlspecialchars($report['displayName']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="bizareaSelect">Select Business Area</label>
            <select id="bizareaSelect" disabled>
                <option value="">-- First select a report --</option>
            </select>
        </div>

        <div class="selection-summary" id="selectionSummary" style="display: none;">
            <strong>Ready to run:</strong><br>
            User: <span id="summaryUser">-</span><br>
            Report (Display): <span id="summaryReport">-</span><br>
            Report (Search): <span class="actual-name" id="summaryActualName">-</span><br>
            Business Area: <span id="summaryBizArea">-</span><br>
            Timeout: <span id="summaryTimeout">30 seconds</span>
        </div>

        <button id="btnRun" class="btn-run" disabled>🚀 Run Playwright Test</button>

        <div id="status" class="status"></div>
    </div>

    <script>
        // Business areas data from PHP
        const bizareas = <?php echo json_encode($bizareas); ?>;

        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const timeoutSelect = document.getElementById('timeoutSelect');
        const reportSelect = document.getElementById('reportSelect');
        const bizareaSelect = document.getElementById('bizareaSelect');
        const btnRun = document.getElementById('btnRun');
        const statusDiv = document.getElementById('status');
        const selectionSummary = document.getElementById('selectionSummary');
        const summaryUser = document.getElementById('summaryUser');
        const summaryReport = document.getElementById('summaryReport');
        const summaryActualName = document.getElementById('summaryActualName');
        const summaryBizArea = document.getElementById('summaryBizArea');
        const summaryTimeout = document.getElementById('summaryTimeout');

        reportSelect.addEventListener('change', function() {
            const reportId = this.value;
            const displayName = this.options[this.selectedIndex].text;
            const actualName = this.options[this.selectedIndex].dataset.actualName;

            // Reset business area dropdown
            bizareaSelect.innerHTML = '<option value="">-- Choose a business area --</option>';
            bizareaSelect.disabled = true;
            btnRun.disabled = true;
            selectionSummary.style.display = 'none';

            if (!reportId) {
                bizareaSelect.innerHTML = '<option value="">-- First select a report --</option>';
                return;
            }

            // Populate business areas dropdown
            const areas = bizareas[reportId] || [];
            if (areas.length > 0) {
                areas.forEach(function(area) {
                    const option = document.createElement('option');
                    option.value = area;
                    option.textContent = area;
                    bizareaSelect.appendChild(option);
                });
                bizareaSelect.disabled = false;
            } else {
                bizareaSelect.innerHTML = '<option value="">No business areas found</option>';
            }

            // Store report info
            btnRun.dataset.reportId = reportId;
            btnRun.dataset.reportName = displayName;
            btnRun.dataset.actualName = actualName;
        });

        bizareaSelect.addEventListener('change', function() {
            const bizArea = this.value;
            const displayName = btnRun.dataset.reportName;
            const actualName = btnRun.dataset.actualName;
            const timeoutMs = timeoutSelect.value;
            const timeoutLabel = timeoutSelect.options[timeoutSelect.selectedIndex].text;

            if (!bizArea) {
                btnRun.disabled = true;
                selectionSummary.style.display = 'none';
                return;
            }

            btnRun.dataset.businessArea = bizArea;
            btnRun.dataset.timeout = timeoutMs;
            btnRun.disabled = false;

            // Show selection summary
            summaryUser.textContent = usernameInput.value || '-';
            summaryReport.textContent = displayName;
            summaryActualName.textContent = actualName;
            summaryBizArea.textContent = bizArea;
            summaryTimeout.textContent = timeoutLabel;
            selectionSummary.style.display = 'block';
        });

        timeoutSelect.addEventListener('change', function() {
            // Update summary if already visible
            if (selectionSummary.style.display === 'block') {
                summaryTimeout.textContent = this.options[this.selectedIndex].text;
            }
        });

        btnRun.addEventListener('click', async function() {
            const reportId = this.dataset.reportId;
            const reportName = this.dataset.reportName;
            const actualName = this.dataset.actualName;
            const businessArea = this.dataset.businessArea;
            const timeout = this.dataset.timeout || '30000';
            const timeoutLabel = timeoutSelect.options[timeoutSelect.selectedIndex].text;
            const username = usernameInput.value;
            const password = passwordInput.value;

            if (!reportId || !businessArea) return;
            if (!username || !password) {
                statusDiv.className = 'status error';
                statusDiv.innerHTML = '❌ <strong>Please enter both username and password.</strong>';
                return;
            }

            // Show running status
            statusDiv.className = 'status running';
            statusDiv.innerHTML = '<span class="spinner"></span> Running Playwright test for <strong>' + reportName + '</strong> (User: ' + username + ', Search: ' + actualName + ', Biz Area: ' + businessArea + ', Timeout: ' + timeoutLabel + ')...';
            btnRun.disabled = true;

            try {
                const response = await fetch('run_test.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reportId: reportId,
                        reportName: reportName,
                        actualName: actualName,
                        businessArea: businessArea,
                        username: username,
                        password: password,
                        timeout: parseInt(timeout)
                    })
                });

                // Get raw text first for debugging
                const rawText = await response.text();

                // Try to parse as JSON
                let result;
                try {
                    result = JSON.parse(rawText);
                } catch (parseErr) {
                    // Not valid JSON - show the raw response
                    statusDiv.className = 'status error';
                    statusDiv.innerHTML = '❌ <strong>Server returned invalid JSON (HTTP ' + response.status + ')</strong><br>' +
                        'Content-Type: ' + (response.headers.get('content-type') || 'none') + '<br>' +
                        'Response preview:<br>' +
                        '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;max-height:400px;border:1px solid #ddd;">' + escapeHtml(rawText.substring(0, 3000)) + '</pre>';
                    btnRun.disabled = false;
                    return;
                }

                if (result.success) {
                    statusDiv.className = 'status success';
                    statusDiv.innerHTML = '✅ <strong>Test completed successfully!</strong><br>' +
                        'User: ' + username + '<br>' +
                        'Report: ' + reportName + '<br>' +
                        'Search Name: <span class="actual-name">' + actualName + '</span><br>' +
                        'Business Area: ' + businessArea + '<br>' +
                        'Timeout: ' + timeoutLabel + '<br>' +
                        (result.output ? '<pre style="margin-top:10px;background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;">' + escapeHtml(result.output) + '</pre>' : '');
                } else {
                    statusDiv.className = 'status error';
                    statusDiv.innerHTML = '❌ <strong>Test failed:</strong><br>' + escapeHtml(result.error || 'Unknown error') + '<br>' +
                        (result.output ? '<pre style="margin-top:10px;background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;">' + escapeHtml(result.output) + '</pre>' : '');
                }
            } catch (err) {
                // Network or fetch-level error (not HTTP response error)
                statusDiv.className = 'status error';
                statusDiv.innerHTML = '❌ <strong>Request failed (network/fetch error):</strong><br>' +
                    'Type: ' + err.name + '<br>' +
                    'Message: ' + escapeHtml(err.message) + '<br><br>' +
                    '<strong>Troubleshooting:</strong><br>' +
                    '1. Check that run_test.php exists in the same folder as index.php<br>' +
                    '2. Verify PHP is enabled in Apache (browse to run_test.php directly)<br>' +
                    '3. Check Apache error logs for PHP syntax errors<br>' +
                    '4. Ensure the Apache service account has execute permissions on npx/node';
            } finally {
                btnRun.disabled = false;
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
