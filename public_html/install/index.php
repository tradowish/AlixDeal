<?php
/**
 * AlixDeal Shopping - 1-Click Web Auto Installer & Auto-Fix Wizard
 * Auto-creates DB, Auto-fixes tables, tests privileges, and provides SweetAlert2 + Fallback Diagnostics
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rootDir = dirname(__DIR__);
$installedLock = $rootDir . '/.installed';
$configFile = $rootDir . '/config.php';

// Allow reinstall if explicitly requested via ?reinstall=1
if (isset($_GET['reinstall']) && $_GET['reinstall'] === '1') {
    @unlink($installedLock);
    header("Location: index.php?step=2");
    exit;
}

$alreadyInstalled = (file_exists($installedLock) && file_exists($configFile));

// Current step (1: Requirements, 2: DB & Admin Info, 3: Success)
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($alreadyInstalled && $step !== 3 && $step !== 4) {
    $step = 99; // locked
}

$error = '';
$success = '';

// Step 1: System Checks
$checks = [
    'php_version' => [
        'name' => 'PHP Version >= 7.4',
        'current' => PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, '7.4.0', '>=')
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'current' => extension_loaded('pdo') ? 'Enabled' : 'Missing',
        'pass' => extension_loaded('pdo')
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing',
        'pass' => extension_loaded('pdo_mysql')
    ],
    'curl' => [
        'name' => 'cURL Extension',
        'current' => extension_loaded('curl') ? 'Enabled' : 'Missing',
        'pass' => extension_loaded('curl')
    ],
    'mbstring' => [
        'name' => 'mbstring Extension',
        'current' => extension_loaded('mbstring') ? 'Enabled' : 'Missing',
        'pass' => extension_loaded('mbstring')
    ],
    'json' => [
        'name' => 'JSON Extension',
        'current' => extension_loaded('json') ? 'Enabled' : 'Missing',
        'pass' => extension_loaded('json')
    ],
    'root_writable' => [
        'name' => 'Root Directory Writable (for config.php)',
        'current' => is_writable($rootDir) ? 'Writable' : 'Read-only',
        'pass' => is_writable($rootDir)
    ],
    'uploads_writable' => [
        'name' => 'Uploads Directory Writable (/uploads)',
        'current' => (is_dir($rootDir . '/uploads') && is_writable($rootDir . '/uploads')) ? 'Writable' : (is_writable($rootDir) ? 'Will Auto-create' : 'Needs Permission'),
        'pass' => is_writable($rootDir) || (is_dir($rootDir . '/uploads') && is_writable($rootDir . '/uploads'))
    ]
];

$allPassed = true;
foreach ($checks as $c) {
    if (!$c['pass']) {
        $allPassed = false;
        break;
    }
}

/**
 * Robust Database Connection with Auto-Discovery of Host, Port and Auto-DB Creation
 */
function tryDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass) {
    // Parse host and optional port
    $hostPart = $dbHost;
    $portPart = null;
    if (strpos($dbHost, ':') !== false) {
        list($hostPart, $portPart) = explode(':', $dbHost, 2);
    }

    $candidateHosts = [$hostPart];
    if ($hostPart === 'localhost') {
        $candidateHosts[] = '127.0.0.1';
    } elseif ($hostPart === '127.0.0.1') {
        $candidateHosts[] = 'localhost';
    }

    $lastException = null;
    $connectedHost = null;
    $pdo = null;

    foreach ($candidateHosts as $host) {
        $portStr = $portPart ? ";port={$portPart}" : "";
        try {
            $dsn = "mysql:host={$host}{$portStr};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $connectedHost = $portPart ? "{$host}:{$portPart}" : $host;
            break;
        } catch (PDOException $e) {
            $lastException = $e;
            $msg = $e->getMessage();
            $code = $e->getCode();

            // Auto-Fix: If Unknown Database (Error 1049 or string match), attempt to auto-create it
            if ($code == 1049 || strpos($msg, 'Unknown database') !== false || strpos($msg, '1049') !== false) {
                try {
                    $rootDsn = "mysql:host={$host}{$portStr};charset=utf8mb4";
                    $rootPdo = new PDO($rootDsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                    // Try connecting to the newly created database
                    $pdo = new PDO("mysql:host={$host}{$portStr};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    $connectedHost = $portPart ? "{$host}:{$portPart}" : $host;
                    break;
                } catch (\Throwable $createEx) {
                    // Database auto-creation wasn't permitted for this user (common in shared cPanel)
                }
            }
        }
    }

    if (!$pdo && $lastException) {
        throw $lastException;
    }

    return [$pdo, $connectedHost ?: $dbHost];
}

/**
 * Robust SQL Statement Splitter and Executor
 * Correctly handles Windows CRLF, removes comments, and executes statements one-by-one with fallback
 */
function executeSqlSchema($pdo, $sqlContent, &$executedCount, &$errors) {
    // 1. Normalize line breaks to \n
    $sqlContent = str_replace(["\r\n", "\r"], "\n", $sqlContent);

    // 2. Remove comments line-by-line while preserving query body
    $lines = explode("\n", $sqlContent);
    $cleanLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '#') === 0) {
            continue;
        }
        $cleanLines[] = $line;
    }
    $cleanSql = implode("\n", $cleanLines);

    // 3. Split by semicolon followed by newline or end of string
    $queries = preg_split('/;\s*(\n|$)/', $cleanSql);

    $executedCount = 0;
    $errors = [];

    // Temporarily turn off foreign key checks
    try { $pdo->exec("SET FOREIGN_KEY_CHECKS=0;"); } catch (\Throwable $e) {}

    foreach ($queries as $q) {
        $q = trim($q);
        if (empty($q)) continue;

        try {
            $pdo->exec($q);
            $executedCount++;
        } catch (\Throwable $ex) {
            $errorMsg = $ex->getMessage();

            // Auto-Fix 1: If utf8mb4 unsupported, fallback to utf8
            if (strpos($errorMsg, 'Unknown character set') !== false || strpos($errorMsg, 'utf8mb4') !== false) {
                try {
                    $fallbackQ = str_replace('utf8mb4', 'utf8', $q);
                    $pdo->exec($fallbackQ);
                    $executedCount++;
                    continue;
                } catch (\Throwable $subEx) {}
            }

            // Auto-Fix 2: If foreign key failure, strip FOREIGN KEY clause and execute
            if (strpos($errorMsg, 'foreign key constraint') !== false || strpos($errorMsg, '1215') !== false) {
                try {
                    $noFkQ = preg_replace('/,\s*FOREIGN KEY\s*\([^)]+\)\s*REFERENCES\s*[^)]+\)/i', '', $q);
                    $pdo->exec($noFkQ);
                    $executedCount++;
                    continue;
                } catch (\Throwable $subEx) {}
            }

            // Ignore "Table already exists" or "Duplicate entry" warnings
            if (strpos($errorMsg, 'already exists') !== false || strpos($errorMsg, 'Duplicate entry') !== false) {
                $executedCount++;
                continue;
            }

            $errors[] = [
                'query_snippet' => substr($q, 0, 100) . '...',
                'error' => $errorMsg
            ];
        }
    }

    try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1;"); } catch (\Throwable $e) {}

    return count($errors) === 0;
}

/**
 * Diagnostic Helper: Human-friendly explanation & Auto-Fix recommendations for MySQL errors
 */
function getFriendlyErrorDetails($errorMsg, $dbHost, $dbName, $dbUser) {
    $title = 'Database Connection Issue';
    $helpHtml = '';
    $autoFixAction = '';

    if (strpos($errorMsg, '1045') !== false || strpos($errorMsg, 'Access denied') !== false) {
        $title = 'MySQL Access Denied (Error 1045)';
        $helpHtml = "
            <p><b>1. Username/Password Mismatch:</b> Verify the database username (<code>" . htmlspecialchars($dbUser) . "</code>) and password.</p>
            <p><b>2. cPanel Privilege Missing (Most Common):</b> In your cPanel &rarr; <b>MySQL Databases</b> &rarr; scroll down to <b>'Add User To Database'</b>:</p>
            <ul style='margin-left:20px; margin-top:4px;'>
                <li>Select User: <b>" . htmlspecialchars($dbUser) . "</b></li>
                <li>Select Database: <b>" . htmlspecialchars($dbName) . "</b></li>
                <li>Click <b>Add</b> and check <b>'ALL PRIVILEGES'</b>, then click <b>'Make Changes'</b>.</li>
            </ul>
        ";
    } elseif (strpos($errorMsg, '1049') !== false || strpos($errorMsg, 'Unknown database') !== false) {
        $title = 'Database Not Found (Error 1049)';
        $helpHtml = "
            <p>The database `<b>" . htmlspecialchars($dbName) . "</b>` does not exist on your MySQL server.</p>
            <p><b>Quick Fix:</b> In cPanel &rarr; <b>MySQL Databases</b> &rarr; under <b>'Create New Database'</b>, enter `<b>" . htmlspecialchars($dbName) . "</b>` and click <b>Create Database</b>.</p>
            <p><i>Note: On shared hosting, your database name usually includes your cPanel prefix (e.g., <code>cpaneluser_" . htmlspecialchars($dbName) . "</code>).</i></p>
        ";
        $autoFixAction = 'create_db';
    } elseif (strpos($errorMsg, '2002') !== false || strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No such file or directory') !== false) {
        $title = 'MySQL Server Unreachable (Error 2002)';
        $helpHtml = "
            <p>Cannot reach the database host <code>" . htmlspecialchars($dbHost) . "</code>.</p>
            <ul style='margin-left:20px; margin-top:4px;'>
                <li>Try changing Host to <b><code>127.0.0.1</code></b> instead of <code>localhost</code> (or vice versa).</li>
                <li>If MySQL is running on a custom port, enter it as <code>localhost:3306</code>.</li>
            </ul>
        ";
    } elseif (strpos($errorMsg, '1146') !== false || strpos($errorMsg, "doesn't exist") !== false) {
        $title = 'Database Table Missing (Error 1146)';
        $helpHtml = "
            <p>The tables could not be created automatically. This usually happens if table creation was interrupted or foreign key checks prevented table setup.</p>
            <p>Click the <b>'⚡ Auto-Repair Database'</b> button below to force table recreation with automatic fallback.</p>
        ";
        $autoFixAction = 'repair_tables';
    } else {
        $title = 'Database Error';
        $helpHtml = "<p>" . htmlspecialchars($errorMsg) . "</p><p>Please check your MySQL host, database name, and user permissions.</p>";
    }

    return [$title, $helpHtml, $autoFixAction];
}

// ==========================================
// AJAX: Test Database Connection
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'test_db') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'title' => 'Missing Fields',
            'message' => 'Please fill in Database Host, Database Name, and Username.',
            'help' => 'All three fields are required to establish a MySQL connection.'
        ]);
        exit;
    }

    try {
        list($pdo, $usedHost) = tryDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);

        // Test basic permissions
        $privileges = ['SELECT' => false, 'CREATE' => false];
        try {
            $pdo->query("SELECT 1");
            $privileges['SELECT'] = true;
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("CREATE TEMPORARY TABLE `_test_install_priv` (`id` INT)");
            $pdo->exec("DROP TEMPORARY TABLE `_test_install_priv`");
            $privileges['CREATE'] = true;
        } catch (\Throwable $e) {}

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'title' => 'Connection Successful! ✅',
            'used_host' => $usedHost,
            'message' => "Successfully connected to MySQL server on <b>" . htmlspecialchars($usedHost) . "</b> and opened database `<b>" . htmlspecialchars($dbName) . "</b>`!",
            'privileges' => $privileges
        ]);
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        list($title, $help, $action) = getFriendlyErrorDetails($msg, $dbHost, $dbName, $dbUser);

        ob_clean();
        echo json_encode([
            'status' => 'error',
            'title' => $title,
            'message' => $msg,
            'help' => $help,
            'autofix_action' => $action
        ]);
    }
    exit;
}

// ==========================================
// AJAX: 1-Click Auto-Fix Database
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'autofix') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';

    $fixesDone = [];
    $fixesFailed = [];

    // Attempt 1: Try auto-creating database
    try {
        $rootDsn = "mysql:host={$dbHost};charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $fixesDone[] = "Database `{$dbName}` auto-created successfully.";
    } catch (\Throwable $e) {
        $fixesFailed[] = "Could not auto-create database (may need manual creation in cPanel MySQL Databases): " . $e->getMessage();
    }

    // Attempt 2: Connect and verify tables
    try {
        list($pdo, $usedHost) = tryDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sqlContent = file_get_contents($schemaFile);
            $executedCount = 0;
            $errs = [];
            executeSqlSchema($pdo, $sqlContent, $executedCount, $errs);
            $fixesDone[] = "Executed {$executedCount} database schema statements successfully.";
        }
    } catch (\Throwable $e) {
        $fixesFailed[] = "Table execution: " . $e->getMessage();
    }

    // Attempt 3: Ensure directories exist and have permissions
    $uploadsDir = $rootDir . '/uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
        @mkdir($uploadsDir . '/products', 0755, true);
        @mkdir($uploadsDir . '/banners', 0755, true);
        @mkdir($uploadsDir . '/categories', 0755, true);
        @mkdir($uploadsDir . '/qr', 0755, true);
        $fixesDone[] = "Created /uploads directories with 0755 write permissions.";
    }

    ob_clean();
    echo json_encode([
        'status' => count($fixesDone) > 0 ? 'success' : 'error',
        'fixes_done' => $fixesDone,
        'fixes_failed' => $fixesFailed,
        'message' => count($fixesDone) > 0 ? 'Auto-fix completed operations!' : 'Auto-fix encountered issues.'
    ]);
    exit;
}

// ==========================================
// Handle Step 2 Form Submission (Install)
// ==========================================
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    ob_start();

    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';

    $siteName = trim($_POST['site_name'] ?? 'AlixDeal Shopping');
    $adminName = trim($_POST['admin_name'] ?? 'Super Admin');
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@alixdeal.shop');
    $adminPassword = $_POST['admin_password'] ?? '';

    $response = ['status' => 'error', 'title' => '', 'message' => '', 'help' => ''];

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = "Please fill in all database connection fields.";
        $response['title'] = 'Missing Database Details';
        $response['message'] = $error;
    } elseif (empty($adminUser) || empty($adminEmail) || empty($adminPassword)) {
        $error = "Please enter Admin username, email and password.";
        $response['title'] = 'Missing Admin Credentials';
        $response['message'] = $error;
    } elseif (strlen($adminPassword) < 6) {
        $error = "Admin password must be at least 6 characters long.";
        $response['title'] = 'Password Too Short';
        $response['message'] = $error;
    } else {
        try {
            // 1. Connect with auto-recovery
            list($pdo, $usedHost) = tryDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);

            // 2. Read and execute schema.sql using robust splitter
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("schema.sql not found in install/ directory.");
            }

            $sqlContent = file_get_contents($schemaFile);
            $executedCount = 0;
            $schemaErrors = [];
            $schemaOk = executeSqlSchema($pdo, $sqlContent, $executedCount, $schemaErrors);

            if ($executedCount === 0 && !empty($schemaErrors)) {
                throw new Exception("Database schema execution failed: " . ($schemaErrors[0]['error'] ?? 'Unknown error'));
            }

            // 3. Ensure 'admins' table is ready
            $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // 4. Insert or Update Admin credentials
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$adminUser, $adminEmail]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$adminName, $adminUser, $adminEmail, $hashedPassword, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO admins (name, username, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$adminName, $adminUser, $adminEmail, $hashedPassword]);
            }

            // 5. Update site name in settings table
            try {
                $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE value = ?");
                $stmt->execute([$siteName, $siteName]);
            } catch (\Throwable $e) {}

            // 6. Ensure uploads subdirectories exist
            $uploadsDir = $rootDir . '/uploads';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0755, true);
            }
            @mkdir($uploadsDir . '/products', 0755, true);
            @mkdir($uploadsDir . '/banners', 0755, true);
            @mkdir($uploadsDir . '/categories', 0755, true);
            @mkdir($uploadsDir . '/qr', 0755, true);

            // 7. Generate clean config.php
            $configContent = "<?php\n";
            $configContent .= "/**\n * AlixDeal Shopping - Configuration File\n * Auto-generated on " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "define('DB_HOST', " . var_export($usedHost, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbName, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($dbUser, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
            $configContent .= "define('SITE_NAME', " . var_export($siteName, true) . ");\n";
            $configContent .= "define('BASE_URL', (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . \$_SERVER['HTTP_HOST'] . rtrim(dirname(\$_SERVER['SCRIPT_NAME']), '/admin/api/install') . '');\n\n";
            $configContent .= "function getDBConnection() {\n";
            $configContent .= "    static \$pdo = null;\n";
            $configContent .= "    if (\$pdo === null) {\n";
            $configContent .= "        try {\n";
            $configContent .= "            \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';\n";
            $configContent .= "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [\n";
            $configContent .= "                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
            $configContent .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $configContent .= "                PDO::ATTR_EMULATE_PREPARES => false,\n";
            $configContent .= "            ]);\n";
            $configContent .= "        } catch (PDOException \$e) {\n";
            $configContent .= "            die('Database Connection Error: ' . htmlspecialchars(\$e->getMessage()));\n";
            $configContent .= "        }\n";
            $configContent .= "    }\n";
            $configContent .= "    return \$pdo;\n";
            $configContent .= "}\n";

            $configWritten = @file_put_contents($configFile, $configContent);
            if ($configWritten === false) {
                // If permission issue, try chmod
                @chmod($rootDir, 0755);
                $configWritten = @file_put_contents($configFile, $configContent);
            }

            if ($configWritten === false) {
                throw new Exception("Unable to write 'config.php' in the root folder ({$rootDir}). Please check directory permissions (chmod 755 or 777).");
            }

            // 8. Create installation lock
            @file_put_contents($installedLock, "Installed successfully on " . date('r'));

            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Installation Complete! 🎉',
                    'message' => 'AlixDeal installed successfully with all database tables, catalog seeds, and Super Admin account.',
                    'redirect' => 'index.php?step=3'
                ]);
                exit;
            } else {
                header("Location: index.php?step=3");
                exit;
            }

        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            list($title, $help, $action) = getFriendlyErrorDetails($msg, $dbHost, $dbName, $dbUser);

            $error = $msg;
            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'title' => $title,
                    'message' => $msg,
                    'help' => $help,
                    'autofix_action' => $action
                ]);
                exit;
            }
        }
    }

    if ($isAjax) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlixDeal Installer & Auto-Fix Wizard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 with multiple CDN fallbacks -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        if (typeof Swal === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.10.1/sweetalert2.all.min.js"><\/script>');
        }
    </script>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-dark: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #EEF2FF 0%, #F8FAFC 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .container {
            width: 100%;
            max-width: 720px;
            background: var(--surface);
            border-radius: 24px;
            box-shadow: 0 20px 45px -15px rgba(79, 70, 229, 0.15), 0 0 1px 1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin: auto;
        }
        .header {
            background: linear-gradient(135deg, #3730A3 0%, #4F46E5 100%);
            padding: 32px 24px;
            color: #fff;
            text-align: center;
        }
        .header h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .steps-nav {
            display: flex;
            background: #F1F5F9;
            border-bottom: 1px solid var(--border);
        }
        .step-item {
            flex: 1;
            text-align: center;
            padding: 14px 10px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
        }
        .step-item.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: #fff;
        }
        .content { padding: 32px 28px; }
        .check-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .check-table tr {
            border-bottom: 1px solid var(--border);
        }
        .check-table td {
            padding: 12px 6px;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-pass { background: #ECFDF5; color: #065F46; }
        .badge-fail { background: #FEF2F2; color: #991B1B; }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .form-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .grid-2 { grid-template-columns: 1fr; gap: 0; }
            .content { padding: 24px 18px; }
        }
        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 800;
            color: #1E293B;
            margin: 24px 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-outline {
            background: transparent;
            color: var(--text-dark);
            border: 1.5px solid var(--border);
        }
        .btn-outline:hover { background: #F8FAFC; border-color: #CBD5E1; }
        .btn-sm { padding: 7px 14px; font-size: 12px; width: auto; border-radius: 8px; }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-warning:hover { background: #D97706; }
        .alert {
            padding: 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }
        .alert-success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .alert-info {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }
        .success-box {
            text-align: center;
            padding: 20px 0;
        }
        .success-icon {
            width: 72px;
            height: 72px;
            background: #D1FAE5;
            color: #059669;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            margin-bottom: 16px;
        }

        /* Native Fallback Modal (Guarantees error popup always works even if external CDN is blocked) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-box {
            background: #fff;
            width: 100%;
            max-width: 520px;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalPop 0.25s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes modalPop {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 800;
            color: #0F172A;
        }
        .modal-body {
            font-size: 13.5px;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .modal-body p { margin-bottom: 10px; }
        .modal-body code {
            background: #F1F5F9;
            padding: 2px 6px;
            border-radius: 4px;
            color: #4F46E5;
            font-weight: 700;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>

<!-- Native Fallback Modal Container -->
<div id="nativeModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <span id="modalIcon" style="font-size: 28px;">⚠️</span>
            <div class="modal-title" id="modalTitle">Installation Alert</div>
        </div>
        <div class="modal-body" id="modalContent"></div>
        <div class="modal-actions" id="modalButtons">
            <button type="button" onclick="closeNativeModal()" class="btn btn-sm btn-outline">Close</button>
        </div>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>AlixDeal Web & API Auto Installer</h1>
        <p>Effortlessly set up your store database, admin panel, and REST APIs</p>
    </div>

    <?php if ($step === 99): ?>
        <div class="content success-box">
            <div class="success-icon">✓</div>
            <h2 style="margin-bottom: 8px; font-weight: 800;">Already Installed!</h2>
            <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 14px;">
                AlixDeal is already configured and running on this domain.
            </p>
            <div style="display: flex; gap: 12px; margin-bottom: 18px;">
                <a href="../index.php" class="btn">🛍️ View Storefront</a>
                <a href="../admin/" class="btn btn-outline">🔐 Access Admin Panel</a>
            </div>
            <div>
                <a href="index.php?reinstall=1" onclick="return confirm('Reset and reinstall? This will allow you to enter new database details.');" style="color: var(--danger); font-size: 13px; font-weight: 700; text-decoration: none;">🔄 Reinstall / Setup New Database</a>
            </div>
        </div>
    <?php else: ?>
        <div class="steps-nav">
            <div class="step-item <?php echo $step === 1 ? 'active' : ''; ?>">1. Requirements</div>
            <div class="step-item <?php echo $step === 2 ? 'active' : ''; ?>">2. Database & Admin</div>
            <div class="step-item <?php echo $step === 3 ? 'active' : ''; ?>">3. Ready!</div>
        </div>

        <div class="content">
            <!-- Persistent Inline Error Banner (Guaranteed to be visible on Step 2) -->
            <div id="inlineErrorBox" style="display: <?php echo !empty($error) ? 'block' : 'none'; ?>;" class="alert alert-error">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;">
                    <div>
                        <strong id="inlineErrorTitle">⚠️ Installation Issue Detected:</strong>
                        <div id="inlineErrorMessage" style="margin-top: 4px;"><?php echo htmlspecialchars($error); ?></div>
                        <div id="inlineErrorHelp" style="margin-top: 8px; font-size: 12.5px; opacity: 0.95;"></div>
                    </div>
                    <button type="button" id="inlineAutoFixBtn" onclick="triggerAutoFix()" class="btn btn-sm btn-warning" style="display:none; white-space:nowrap;">⚡ Auto-Fix</button>
                </div>
            </div>

            <!-- Persistent Inline Success Banner -->
            <div id="inlineSuccessBox" style="display: none;" class="alert alert-success">
                <strong id="inlineSuccessTitle">✅ Status:</strong>
                <div id="inlineSuccessMessage" style="margin-top: 4px;"></div>
            </div>

            <?php if ($step === 1): ?>
                <h2 style="font-size: 18px; margin-bottom: 16px; font-weight: 800;">System Compatibility Check</h2>
                <table class="check-table">
                    <?php foreach ($checks as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td style="color: var(--text-muted); text-align: center;"><?php echo htmlspecialchars($item['current']); ?></td>
                            <td style="text-align: right;">
                                <?php if ($item['pass']): ?>
                                    <span class="badge badge-pass">✓ Passed</span>
                                <?php else: ?>
                                    <span class="badge badge-fail">✕ Required</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php if ($allPassed): ?>
                    <a href="index.php?step=2" class="btn">All Good! Continue to Step 2 &rarr;</a>
                <?php else: ?>
                    <div class="alert alert-error">
                        Some required PHP extensions or write permissions are missing. Please enable them in your cPanel PHP Selector (MultiPHP / Select PHP Version), then refresh.
                    </div>
                    <button class="btn" disabled>Fix issues to proceed</button>
                <?php endif; ?>

            <?php elseif ($step === 2): ?>
                <form id="installerForm" method="POST" action="index.php?step=2" onsubmit="handleInstall(event)">
                    <input type="hidden" name="action" value="install">

                    <div class="section-title">
                        <span>🗄️ MySQL Database Details</span>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" onclick="testConnection()" class="btn btn-sm btn-outline" style="font-size: 11px;">🔍 Test Connection</button>
                            <button type="button" onclick="triggerAutoFix()" class="btn btn-sm btn-outline" style="font-size: 11px; color: var(--warning); border-color: #FCD34D;">⚡ Auto-Fix</button>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Database Host</label>
                            <input type="text" name="db_host" id="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                            <div class="form-hint">Usually <code>localhost</code> or <code>127.0.0.1</code></div>
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="db_name" id="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" placeholder="e.g. cpaneluser_shop" required>
                            <div class="form-hint">Must match your cPanel MySQL Database name</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Database Username</label>
                            <input type="text" name="db_user" id="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" placeholder="e.g. cpaneluser_admin" required>
                            <div class="form-hint">MySQL user assigned to this database</div>
                        </div>
                        <div class="form-group">
                            <label>Database Password</label>
                            <input type="password" name="db_pass" id="db_pass" placeholder="MySQL user password">
                            <div class="form-hint">Password created in cPanel MySQL Users</div>
                        </div>
                    </div>

                    <div class="section-title">
                        <span>🛡️ Super Admin & Store Details</span>
                    </div>

                    <div class="form-group">
                        <label>Store / Site Name</label>
                        <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? 'AlixDeal Shopping'); ?>" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Admin Full Name</label>
                            <input type="text" name="admin_name" id="admin_name" value="<?php echo htmlspecialchars($_POST['admin_name'] ?? 'Super Admin'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admin Username</label>
                            <input type="text" name="admin_user" id="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin'); ?>" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Admin Email</label>
                            <input type="email" name="admin_email" id="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'admin@alixdeal.shop'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admin Password</label>
                            <input type="password" name="admin_password" id="admin_password" placeholder="Min 6 characters" required>
                        </div>
                    </div>

                    <button type="submit" id="installBtn" class="btn" style="margin-top: 10px;">
                        🚀 Start 1-Click Install
                    </button>
                </form>

                <script>
                    // Universal Popup Helper (SweetAlert2 with instant native fallback)
                    function showAppModal({ icon, title, html, showConfirm = true, confirmText = 'OK', confirmColor = '#4F46E5', onConfirm = null }) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: icon || 'info',
                                title: title || '',
                                html: html || '',
                                confirmButtonText: confirmText,
                                confirmButtonColor: confirmColor,
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed && typeof onConfirm === 'function') {
                                    onConfirm();
                                }
                            });
                        } else {
                            // Fallback to embedded pure HTML modal
                            const modal = document.getElementById('nativeModal');
                            const iconEl = document.getElementById('modalIcon');
                            const titleEl = document.getElementById('modalTitle');
                            const contentEl = document.getElementById('modalContent');
                            const btnContainer = document.getElementById('modalButtons');

                            const iconMap = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
                            iconEl.textContent = iconMap[icon] || 'ℹ️';
                            titleEl.textContent = title;
                            contentEl.innerHTML = html;

                            btnContainer.innerHTML = '';
                            if (showConfirm) {
                                const btn = document.createElement('button');
                                btn.className = 'btn btn-sm';
                                btn.style.background = confirmColor;
                                btn.style.color = '#fff';
                                btn.textContent = confirmText;
                                btn.onclick = () => {
                                    closeNativeModal();
                                    if (typeof onConfirm === 'function') onConfirm();
                                };
                                btnContainer.appendChild(btn);
                            }
                            modal.style.display = 'flex';
                        }
                    }

                    function closeNativeModal() {
                        const modal = document.getElementById('nativeModal');
                        if (modal) modal.style.display = 'none';
                    }

                    function showInlineError(title, message, helpHtml, showAutoFix = false) {
                        const box = document.getElementById('inlineErrorBox');
                        const titleEl = document.getElementById('inlineErrorTitle');
                        const msgEl = document.getElementById('inlineErrorMessage');
                        const helpEl = document.getElementById('inlineErrorHelp');
                        const fixBtn = document.getElementById('inlineAutoFixBtn');

                        if (box) {
                            titleEl.textContent = title || '⚠️ Issue Detected:';
                            msgEl.textContent = message || '';
                            helpEl.innerHTML = helpHtml || '';
                            fixBtn.style.display = showAutoFix ? 'inline-flex' : 'none';
                            box.style.display = 'block';
                            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }

                    function hideInlineError() {
                        const box = document.getElementById('inlineErrorBox');
                        if (box) box.style.display = 'none';
                    }

                    // Test Database Connection AJAX
                    async function testConnection() {
                        const host = document.getElementById('db_host').value.trim();
                        const db = document.getElementById('db_name').value.trim();
                        const user = document.getElementById('db_user').value.trim();
                        const pass = document.getElementById('db_pass').value;

                        if (!host || !db || !user) {
                            showAppModal({
                                icon: 'warning',
                                title: 'Missing Database Information',
                                html: 'Please enter <b>Database Host</b>, <b>Database Name</b>, and <b>Username</b> before testing.'
                            });
                            return;
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Testing MySQL Connection...',
                                text: 'Connecting to database server...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                        }

                        const formData = new FormData();
                        formData.append('db_host', host);
                        formData.append('db_name', db);
                        formData.append('db_user', user);
                        formData.append('db_pass', pass);

                        try {
                            const res = await fetch('index.php?action=test_db', {
                                method: 'POST',
                                body: formData
                            });

                            const text = await res.text();
                            let data;
                            try {
                                data = JSON.parse(text);
                            } catch (parseErr) {
                                throw new Error("Server returned non-JSON output: " + text.substring(0, 200));
                            }

                            if (data.status === 'success') {
                                hideInlineError();
                                showAppModal({
                                    icon: 'success',
                                    title: data.title || 'Connection Successful! ✅',
                                    html: `
                                        <div style="text-align:left; background:#ECFDF5; color:#065F46; padding:12px; border-radius:10px; font-size:13px; margin-bottom:12px;">
                                            ${data.message}
                                        </div>
                                        <div style="text-align:left; font-size:13px; color:#334155;">
                                            <b>Database Privileges:</b><br>
                                            • SELECT/READ: ${data.privileges && data.privileges.SELECT ? '🟢 Granted' : '🔴 Missing'}<br>
                                            • CREATE TABLE: ${data.privileges && data.privileges.CREATE ? '🟢 Granted' : '🔴 Missing'}
                                        </div>
                                    `
                                });
                            } else {
                                showInlineError(data.title, data.message, data.help, true);
                                showAppModal({
                                    icon: 'error',
                                    title: data.title || 'Connection Failed ❌',
                                    html: `
                                        <div style="text-align:left; background:#FEF2F2; color:#991B1B; padding:12px; border-radius:10px; font-size:13px; margin-bottom:12px; word-break:break-word;">
                                            <b>Error:</b> ${data.message}
                                        </div>
                                        <div style="text-align:left; font-size:13px; color:#334155; line-height:1.6;">
                                            <b>💡 How to Fix:</b><br>
                                            ${data.help || 'Please verify database name and username in cPanel.'}
                                        </div>
                                    `,
                                    confirmText: '⚡ Try 1-Click Auto-Fix',
                                    confirmColor: '#F59E0B',
                                    onConfirm: () => triggerAutoFix()
                                });
                            }
                        } catch (err) {
                            showInlineError('Server Request Error', err.message, 'Make sure your server is responding and not blocking requests.');
                            showAppModal({
                                icon: 'error',
                                title: 'Test Failed',
                                html: `Could not reach server: <pre style="background:#F1F5F9; padding:8px; border-radius:6px; font-size:12px; margin-top:8px;">${err.message}</pre>`
                            });
                        }
                    }

                    // 1-Click Auto-Fix AJAX
                    async function triggerAutoFix() {
                        const host = document.getElementById('db_host').value.trim();
                        const db = document.getElementById('db_name').value.trim();
                        const user = document.getElementById('db_user').value.trim();
                        const pass = document.getElementById('db_pass').value;

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '⚡ Running Auto-Fix...',
                                text: 'Attempting to create database, adjust permissions, and verify schema...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                        }

                        const formData = new FormData();
                        formData.append('db_host', host);
                        formData.append('db_name', db);
                        formData.append('db_user', user);
                        formData.append('db_pass', pass);

                        try {
                            const res = await fetch('index.php?action=autofix', {
                                method: 'POST',
                                body: formData
                            });

                            const text = await res.text();
                            const data = JSON.parse(text);

                            let detailsHtml = '<ul style="text-align:left; margin-left:16px; font-size:13px; line-height:1.7;">';
                            if (data.fixes_done && data.fixes_done.length > 0) {
                                data.fixes_done.forEach(f => {
                                    detailsHtml += `<li style="color:#059669;">✓ ${f}</li>`;
                                });
                            }
                            if (data.fixes_failed && data.fixes_failed.length > 0) {
                                data.fixes_failed.forEach(f => {
                                    detailsHtml += `<li style="color:#DC2626;">✕ ${f}</li>`;
                                });
                            }
                            detailsHtml += '</ul>';

                            showAppModal({
                                icon: data.status === 'success' ? 'success' : 'warning',
                                title: 'Auto-Fix Diagnostic Results',
                                html: `
                                    <div style="margin-bottom:12px; font-size:13px; text-align:left;">
                                        ${detailsHtml}
                                    </div>
                                    <p style="font-size:13px; color:#64748B;">You can now click <b>"Start 1-Click Install"</b> to complete your store setup.</p>
                                `
                            });
                        } catch (err) {
                            showAppModal({
                                icon: 'error',
                                title: 'Auto-Fix Encountered Error',
                                html: `Could not run automated fix: ${err.message}`
                            });
                        }
                    }

                    // Main 1-Click Install Handler
                    async function handleInstall(e) {
                        e.preventDefault();
                        const form = document.getElementById('installerForm');
                        const btn = document.getElementById('installBtn');

                        btn.disabled = true;
                        btn.innerHTML = '⏳ Installing Database & Setting Up Admin...';
                        hideInlineError();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Setting up AlixDeal...',
                                text: 'Creating database tables, catalog, and admin credentials...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });
                        }

                        const formData = new FormData(form);

                        try {
                            const res = await fetch('index.php?step=2&ajax=1', {
                                method: 'POST',
                                body: formData
                            });

                            const text = await res.text();
                            let data;
                            try {
                                data = JSON.parse(text);
                            } catch (jsonErr) {
                                throw new Error("Server returned non-JSON response: " + text.substring(0, 300));
                            }

                            if (data.status === 'success') {
                                showAppModal({
                                    icon: 'success',
                                    title: data.title || 'Installation Complete! 🎉',
                                    html: data.message || 'Your store is ready!',
                                    confirmText: 'Proceed to Dashboard',
                                    confirmColor: '#4F46E5',
                                    onConfirm: () => {
                                        window.location.href = data.redirect || 'index.php?step=3';
                                    }
                                });
                            } else {
                                btn.disabled = false;
                                btn.innerHTML = '🚀 Start 1-Click Install';

                                showInlineError(data.title, data.message, data.help, true);

                                showAppModal({
                                    icon: 'error',
                                    title: data.title || 'Installation Failed ❌',
                                    html: `
                                        <div style="text-align:left; background:#FEF2F2; color:#991B1B; padding:12px; border-radius:10px; font-size:13px; margin-bottom:12px; word-break:break-word;">
                                            <b>Error:</b> ${data.message}
                                        </div>
                                        <div style="text-align:left; font-size:13px; color:#334155; line-height:1.6;">
                                            <b>💡 Recommended Solution:</b><br>
                                            ${data.help || 'Please verify database name and username in cPanel.'}
                                        </div>
                                    `,
                                    confirmText: '⚡ Auto-Fix & Retry',
                                    confirmColor: '#F59E0B',
                                    onConfirm: () => triggerAutoFix()
                                });
                            }
                        } catch (err) {
                            btn.disabled = false;
                            btn.innerHTML = '🚀 Start 1-Click Install';

                            showInlineError('Installation Submission Error', err.message, 'A server communication issue occurred. Try clicking "Test Connection" first or submit again.', true);

                            showAppModal({
                                icon: 'error',
                                title: 'Submission Error',
                                html: `
                                    <div style="text-align:left; background:#FEF2F2; color:#991B1B; padding:10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
                                        ${err.message}
                                    </div>
                                    <p style="text-align:left; font-size:13px; color:#64748B;">
                                        Tip: If you are using cPanel, check that your user has been added to the database under <b>cPanel &rarr; MySQL Databases &rarr; Add User to Database</b> with <b>ALL PRIVILEGES</b>.
                                    </p>
                                `,
                                confirmText: '⚡ Try Auto-Fix',
                                confirmColor: '#F59E0B',
                                onConfirm: () => triggerAutoFix()
                            });
                        }
                    }
                </script>

            <?php elseif ($step === 3): ?>
                <div class="success-box">
                    <div class="success-icon">🎉</div>
                    <h2 style="font-size: 22px; margin-bottom: 8px; font-weight: 800;">Installation Successful!</h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6;">
                        Database tables, product catalog, sample categories, and Super Admin account have been configured successfully.
                    </p>

                    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 24px; text-align: left; font-size: 13.5px;">
                        <strong>🚀 Quick Links & Next Steps:</strong>
                        <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                            <li><strong>Storefront:</strong> <code>/</code> (Live deals, responsive carousel, cart)</li>
                            <li><strong>Admin Panel:</strong> <code>/admin/</code> (Products, categories, sliders, orders)</li>
                            <li><strong>Customer Portal:</strong> <code>/my_account.php</code> (Wallet, orders, tracking)</li>
                        </ul>
                    </div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="../admin/login.php" class="btn" style="flex: 1; min-width: 200px;">🔐 Login to Admin Panel</a>
                        <a href="../index.php" class="btn btn-outline" style="flex: 1; min-width: 200px;">🛍️ Visit Storefront</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
