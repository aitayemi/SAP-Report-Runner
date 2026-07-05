# SAP-Report-Runner
A dynamic web application built for **ACME Corporation** that automates SAP Fiori report execution using [Playwright](https://playwright.dev). Deployed on Apache with PHP on Windows, it provides a browser-based UI for non-technical users to select reports, choose business areas, and run automated end-to-end tests.

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Components](#components)
- [Data Flow](#data-flow)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [File Structure](#file-structure)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Overview

The SAP Report Runner bridges the gap between SAP Fiori's complex UI and automated testing. End users select a report from a dropdown, pick a business area, enter credentials, and click **Run**. The backend dynamically generates a Playwright test script, executes it, and returns the result.

### Key Features

| Feature | Description |
|---------|-------------|
| **Dynamic Report Selection** | Dropdown populated from `reports.csv` (3 fields: ID, Display Name, Search Name) |
| **Business Area Filtering** | Second dropdown populated from `bizareas.csv` based on selected report ID |
| **User Credentials** | Username and password fields with masked password input |
| **Configurable Timeout** | Choose 30s (default), 2 min, or 5 min wait time |
| **Headless Execution** | Runs in background via Apache/PHP service account |
| **Error Handling** | Graceful handling of dialog timeouts, logout failures, and non-JSON responses |
| **Screenshots** | Automatic screenshot capture for evidence and debugging |

---

## Architecture

```
+-------------------------------------------------------------+
|                        End User Browser                      |
|  +-------------------+  +-------------------+  +----------+ |
|  | Report Dropdown   |  | Business Area     |  | Timeout  | |
|  | (reports.csv)     |  | Dropdown          |  | Dropdown | |
|  +-------------------+  | (bizareas.csv)    |  +----------+ |
|                         +-------------------+              |
|  +-------------------+  +-------------------+              |
|  | Username          |  | Password          |              |
|  | (text input)      |  | (masked input)    |              |
|  +-------------------+  +-------------------+              |
|                         +-------------------+              |
|                         | [Run Playwright Test]           | |
|                         +-------------------+              |
+----------------------------|--------------------------------+
                             | AJAX POST (JSON)
                             v
+-------------------------------------------------------------+
|                    Apache Web Server (Windows)               |
|  +-------------------------------------------------------+  |
|  |  PHP Backend                                          |  |
|  |  +-----------------+  +-----------------------------+ |  |
|  |  | index.php       |  | run_test.php                | |  |
|  |  | (Frontend UI)   |->| (Generates & Runs Test)     | |  |
|  |  +-----------------+  +-----------------------------+ |  |
|  |           |                      |                    |  |
|  |           v                      v                    |  |
|  |  +-----------------+  +-----------------------------+ |  |
|  |  | data/           |  | playwright/                 | |  |
|  |  | - reports.csv   |  | - reportapp.spec.js         | |  |
|  |  | - bizareas.csv  |  |   (Generated dynamically)   | |  |
|  |  +-----------------+  +-----------------------------+ |  |
|  +-------------------------------------------------------+  |
+----------------------------|--------------------------------+
                             | exec()
                             v
+-------------------------------------------------------------+
|              Playwright Test Execution (Headless)            |
|  +-------------------------------------------------------+  |
|  |  1. Launch Chromium (headless)                        |  |
|  |  2. Navigate to SAP Fiori                             |  |
|  |  3. Login with user credentials                       |  |
|  |  4. Search report (actualName)                        |  |
|  |  5. Click report (displayName)                        |  |
|  |  6. Fill business area                                |  |
|  |  7. Click Go                                          |  |
|  |  8. Wait for results (user-selected timeout)          |  |
|  |  9. Take screenshot                                   |  |
|  |  10. Logout (with error handling)                     |  |
|  |  11. Close browser                                    |  |
|  +-------------------------------------------------------+  |
+----------------------------|--------------------------------+
                             | JSON Response
                             v
+-------------------------------------------------------------+
|                        End User Browser                      |
|  Status: Running / Success / Error with details              |
+-------------------------------------------------------------+
```

---

## Components

### Frontend (`index.php`)

| Element | Type | Description |
|---------|------|-------------|
| **User** | Text input | SAP username (default: `randomuser1`) |
| **Password** | Password input | SAP password (masked with dots) |
| **Timeout** | Dropdown | 30s (default), 2 min, 5 min |
| **Report** | Dropdown | Populated from `reports.csv` field 2 (Display Name) |
| **Business Area** | Dropdown | Populated from `bizareas.csv` based on selected report ID |
| **Run Button** | Button | Triggers AJAX POST to `run_test.php` |
| **Status Panel** | Div | Shows running/success/error states |

### Backend (`run_test.php`)

| Function | Description |
|----------|-------------|
| **Input Validation** | Checks report ID, business area, username, password |
| **Script Generation** | Dynamically generates `reportapp.spec.js` with user values |
| **Test Execution** | Runs `npx playwright test` via PHP `exec()` |
| **JSON Response** | Returns success/failure with output or error details |

### Data Files

| File | Format | Description |
|------|--------|-------------|
| `data/reports.csv` | `ID, DisplayName, ActualName` | Report definitions |
| `data/bizareas.csv` | `ReportID, BusinessArea` | Business area mappings |

### Playwright Configuration

| File | Purpose |
|------|---------|
| `playwright/playwright.config.js` | Test timeout (360s), headless mode, viewport |
| `playwright/reportapp.spec.js` | Generated test script (overwritten per run) |
| `playwright/package.json` | `@playwright/test` dependency |

---

## Data Flow

```
reports.csv                          bizareas.csv
+------+--------------------+        +------+--------+
| 01   | Cost Objects       |        | 01   | 1010   |
|      | Dashboard          |        | 01   | 1200   |
|      | Cost Ob            |        | 01   | 1640   |
+------+--------------------+        | 01   | 3860   |
| 02   | Random Report X    |        | 01   | 4970   |
|      | Rand RepX          |        | 02   | 324    |
+------+--------------------+        | 02   | 4576   |
| 03   | Random Report Y    |        | 03   | 1001   |
|      | Rand RepY          |        +------+--------+
+------+--------------------+
         |                              |
         v                              v
    +------------------+          +------------------+
    | Report Dropdown  |          | Business Area    |
    | (Display Name)   |--------->| Dropdown         |
    +------------------+          +------------------+
         |
         v
    +------------------+
    | Search Box Fill  |
    | (Actual Name)    |
    +------------------+
```

### Field Mapping

| CSV Field | Variable | Used In |
|-----------|----------|---------|
| Field 1: `01` | `reportId` | Matches `bizareas.csv`, screenshot filename |
| Field 2: `Cost Objects Dashboard` | `displayName` | Dropdown label, `getByText()` click |
| Field 3: `Cost Ob` | `actualName` | Search box fill |

---

## Prerequisites

### Server Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| Windows Server | 2016+ | Terminal server recommended |
| Apache HTTP Server | 2.4+ | With PHP module enabled |
| PHP | 8.0+ | `json`, `file` extensions enabled |
| Node.js | 18+ | In system PATH |
| Playwright | 1.40+ | Installed in `playwright/` folder |

### Browser Requirements

Playwright will download its default Chromium browser on first run. No system Chrome installation required.

```cmd
cd C:\Apache24\htdocs\reportapp\playwright
npm install @playwright/test
npx playwright install chromium
```

---

## Installation

### 1. Extract Application

```cmd
cd C:\Apache24\htdocs
mkdir reportapp
# Extract reportapp.zip contents into C:\Apache24\htdocs\reportapp\
```

### 2. Install Playwright Dependencies

```cmd
cd C:\Apache24\htdocs\reportapp\playwright
npm install
npx playwright install chromium
```

### 3. Configure Apache

Ensure PHP is enabled in `httpd.conf`:

```apache
LoadModule php_module "C:/php/php8apache2_4.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/php"
```

### 4. Set Permissions

Grant the Apache service account read/write access to:
- `C:\Apache24\htdocs\reportapp\playwright\`
- `C:\Apache24\htdocs\reportapp\playwright-report\`

### 5. Restart Apache

```cmd
net stop Apache2.4
net start Apache2.4
```

### 6. Verify

Browse to `http://localhost/reportapp/diagnose.php` to check:
- PHP version
- File existence
- Node.js/npx paths
- Directory permissions

---

## Configuration

### reports.csv

```csv
01,Cost Objects Dashboard,Cost Ob
02,Random Report X,Rand RepX
03,Random Report Y,Rand RepY
```

### bizareas.csv

```csv
01,1010
01,1200
01,1640
01,3860
01,4970
02,324
02,4576
03,1001
```

### Customizing the Target URL

Edit `run_test.php` and change:

```javascript
const response = await page.goto('https://fiori.acme.com/fiori?saml2=disabled');
```

---

## Usage

1. **Open** `http://localhost/reportapp/`
2. **Enter** username and password
3. **Select** timeout (default: 30 seconds)
4. **Select** report from dropdown
5. **Select** business area (auto-populated)
6. **Click** "Run Playwright Test"
7. **View** status: running → success/error

### Selection Summary

Before running, a summary box displays:
- User
- Report (Display)
- Report (Search)
- Business Area
- Timeout

---

## File Structure

```
reportapp/
├── index.php              # Frontend UI (PHP + HTML + JS)
├── run_test.php           # Backend handler (generates & runs tests)
├── diagnose.php           # Diagnostic page for troubleshooting
├── .htaccess              # Apache security config
├── README.md              # This file
├── data/
│   ├── reports.csv        # Report definitions (ID, Display, Search)
│   └── bizareas.csv       # Business area mappings (ReportID, Area)
└── playwright/
    ├── playwright.config.js   # Playwright configuration
    ├── package.json           # Node dependencies
    └── reportapp.spec.js      # Generated test script (runtime)
```

---

## Troubleshooting

| Error | Cause | Solution |
|-------|-------|----------|
| `Cannot find module '@playwright/test'` | Playwright not installed in `playwright/` folder | Run `npm install @playwright/test` in `playwright/` folder |
| `executable doesn't exist` | Browser path incorrect | Use default browser (no `executablePath`) or install via `npx playwright install chromium` |
| `maximum execution time of 30 seconds exceeded` | PHP timeout too short | `set_time_limit(0)` added to `run_test.php` |
| `Request failed: unexpected token '<'` | PHP returning HTML error instead of JSON | Check Apache error logs; verify PHP is enabled |
| Browser not launching | Apache runs as service account (no GUI) | Use `headless: true` (default) |
| Dialog timeout | OK button not appearing | 2-second timeout with catch-and-continue |
| Logout failure | Dashboard state changed | Error logged, screenshot saved, browser closed |

### Diagnostic Page

Browse to `http://localhost/reportapp/diagnose.php` for:
- PHP version check
- File existence verification
- Node.js/npx path detection
- Directory permission check
- JSON encode test

---

## License

© 2026 ACME Corporation. All rights reserved.

---

## Credits

Built for the ACME Corporation SAP Fiori environment using:
- [Playwright](https://playwright.dev) — Browser automation
- [Apache HTTP Server](https://httpd.apache.org) — Web server
- [PHP](https://php.net) — Backend scripting
