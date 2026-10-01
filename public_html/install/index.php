<?php
/**
 * AlixDeal Shopping - 1-Click Web Auto Installer
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

$rootDir = dirname(__DIR__);
$installedLock = $rootDir . '/.installed';
$configFile = $rootDir . '/config.php';

// Already installed check
if (file_exists($installedLock) && file_exists($configFile)) {
    $alreadyInstalled = true;
} else {
    $alreadyInstalled = false;
}

// Current step (1: Requirements, 2: DB & Admin Info, 3: Success)
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($alreadyInstalled && $step !== 4) {
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

// Handle Form Submission on Step 2
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    
    $siteName = trim($_POST['site_name'] ?? 'AlixDeal Shopping');
    $adminName = trim($_POST['admin_name'] ?? 'Super Admin');
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@alixdeal.shop');
    $adminPassword = $_POST['admin_password'] ?? '';
    
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = "Please fill in all database connection fields.";
    } elseif (empty($adminUser) || empty($adminEmail) || empty($adminPassword)) {
        $error = "Please enter Admin username, email and password.";
    } elseif (strlen($adminPassword) < 6) {
        $error = "Admin password must be at least 6 characters long.";
    } else {
        // Attempt database connection
        try {
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Read and run schema.sql
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("schema.sql not found in install directory.");
            }
            
            $sql = file_get_contents($schemaFile);
            // Execute multiple queries
            $pdo->exec($sql);
            
            // Insert/Update Admin user with secure password hash
            $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
            
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
            
            // Update site name in settings
            $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$siteName, $siteName]);
            
            // Ensure uploads directory exists
            if (!is_dir($rootDir . '/uploads')) {
                @mkdir($rootDir . '/uploads', 0755, true);
            }
            
            // Generate config.php
            $configContent = "<?php\n";
            $configContent .= "/**\n * AlixDeal Shopping - Configuration File\n * Auto-generated on " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "define('DB_HOST', " . var_export($dbHost, true) . ");\n";
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
            
            file_put_contents($configFile, $configContent);
            
            // Create lock file
            file_put_contents($installedLock, "Installed successfully on " . date('r'));
            
            // Redirect to step 3 (Success)
            header("Location: index.php?step=3");
            exit;
            
        } catch (Exception $e) {
            $error = "Installation Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlixDeal Installer & Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --success: #10B981;
            --danger: #EF4444;
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
            padding: 24px;
        }
        .container {
            width: 100%;
            max-width: 680px;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.12), 0 0 1px 1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3730A3 0%, #4F46E5 100%);
            padding: 32px;
            color: #fff;
            text-align: center;
        }
        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 6px; letter-spacing: -0.5px; }
        .header p { font-size: 14px; opacity: 0.85; }
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
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
        }
        .step-item.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: #fff;
        }
        .content { padding: 32px; }
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
            font-weight: 600;
        }
        .badge-pass { background: #ECFDF5; color: #065F46; }
        .badge-fail { background: #FEF2F2; color: #991B1B; }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
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
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            margin: 24px 0 14px 0;
            padding-bottom: 6px;
            border-bottom: 1px dashed var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 14px;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }
        .btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1.5px solid var(--primary);
            margin-top: 10px;
        }
        .btn-outline:hover {
            background: #EEF2FF;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5; }
        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; }
        .success-box {
            text-align: center;
            padding: 20px 0;
        }
        .success-icon {
            width: 68px;
            height: 68px;
            background: #D1FAE5;
            color: #059669;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>AlixDeal Web & API Installer</h1>
        <p>Effortlessly set up your store database, admin panel, and REST APIs</p>
    </div>

    <?php if ($step === 99): ?>
        <div class="content success-box">
            <div class="success-icon">✓</div>
            <h2 style="margin-bottom: 8px;">Already Installed!</h2>
            <p style="color: var(--text-muted); margin-bottom: 24px; font-size: 14px;">
                AlixDeal is already configured and running on this domain.
            </p>
            <a href="../index.php" class="btn">View Storefront</a>
            <a href="../admin/" class="btn btn-outline">Access Admin Panel</a>
        </div>
    <?php else: ?>
        <div class="steps-nav">
            <div class="step-item <?php echo $step === 1 ? 'active' : ''; ?>">1. Requirements</div>
            <div class="step-item <?php echo $step === 2 ? 'active' : ''; ?>">2. Database & Admin</div>
            <div class="step-item <?php echo $step === 3 ? 'active' : ''; ?>">3. Ready!</div>
        </div>

        <div class="content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h2 style="font-size: 18px; margin-bottom: 16px;">System Compatibility Check</h2>
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
                    <a href="index.php?step=2" class="btn">All Good! Continue to Step 2 →</a>
                <?php else: ?>
                    <div class="alert alert-error">
                        Some required PHP extensions or file write permissions are missing. Please enable them in your cPanel PHP Selector (MultiPHP / Select PHP Version) or contact your host support, then refresh.
                    </div>
                    <button class="btn" disabled>Fix issues to proceed</button>
                <?php endif; ?>

            <?php elseif ($step === 2): ?>
                <form method="POST" action="index.php?step=2">
                    <input type="hidden" name="action" value="install">

                    <div class="section-title">
                        <span>🗄️ MySQL Database Details</span>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Database Host</label>
                            <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" placeholder="e.g. u123_alixdeal" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Database Username</label>
                            <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" placeholder="e.g. u123_admin" required>
                        </div>
                        <div class="form-group">
                            <label>Database Password</label>
                            <input type="password" name="db_pass" placeholder="Database password">
                        </div>
                    </div>

                    <div class="section-title">
                        <span>🛡️ Super Admin & Store Details</span>
                    </div>

                    <div class="form-group">
                        <label>Store / Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? 'AlixDeal Shopping'); ?>" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Admin Full Name</label>
                            <input type="text" name="admin_name" value="<?php echo htmlspecialchars($_POST['admin_name'] ?? 'Super Admin'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admin Username</label>
                            <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin'); ?>" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Admin Email</label>
                            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'admin@alixdeal.shop'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admin Password</label>
                            <input type="password" name="admin_password" placeholder="Min 6 characters" required>
                        </div>
                    </div>

                    <button type="submit" class="btn">🚀 Start 1-Click Install</button>
                </form>

            <?php elseif ($step === 3): ?>
                <div class="success-box">
                    <div class="success-icon">🎉</div>
                    <h2 style="font-size: 22px; margin-bottom: 8px;">Installation Successful!</h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6;">
                        Database tables, product catalog, sample categories, and Super Admin account have been created automatically.
                    </p>

                    <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 24px; text-align: left; font-size: 13px;">
                        <strong>Quick Links:</strong>
                        <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                            <li><strong>Storefront:</strong> <code>/</code> (Home page)</li>
                            <li><strong>Admin Panel:</strong> <code>/admin/</code> (Products, Orders, Settings)</li>
                            <li><strong>Android REST API:</strong> <code>/api/products.php</code></li>
                        </ul>
                    </div>

                    <a href="../admin/login.php" class="btn">🔐 Login to Admin Panel</a>
                    <a href="../index.php" class="btn btn-outline">🛍️ Visit Storefront</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
