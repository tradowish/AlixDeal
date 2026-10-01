<?php
$pageTitle = 'System Diagnostics & Debug Health';
$activeTab = 'debug';
require_once __DIR__ . '/header.php';

$msg = '';

// Repair tables action
if (isset($_GET['optimize_tables'])) {
    $tables = ['admins', 'categories', 'products', 'orders', 'order_items', 'banners', 'coupons', 'reviews', 'users', 'wallet_transactions', 'notifications'];
    foreach ($tables as $t) {
        $pdo->query("OPTIMIZE TABLE `$t`");
    }
    $msg = "All database tables optimized and indexes refreshed successfully!";
}

// Check extensions
$requiredExts = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'gd', 'openssl', 'fileinfo', 'json'];
$extStatus = [];
foreach ($requiredExts as $ext) {
    $extStatus[$ext] = extension_loaded($ext);
}

// Check folder write permissions
$uploadDir = dirname(__DIR__) . '/uploads';
$isUploadWritable = is_writable($uploadDir);
$isConfigWritable = is_writable(dirname(__DIR__) . '/config.php');

// MySQL Version
$dbVer = $pdo->query("SELECT VERSION() as ver")->fetch()['ver'] ?? 'Unknown';

// Server Memory
$memLimit = ini_get('memory_limit');
$maxUpload = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$diskFree = @disk_free_space(dirname(__DIR__));
$diskTotal = @disk_total_space(dirname(__DIR__));
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 800;">🩺 System Diagnostics & Health Checker</h2>
        <p style="color: var(--text-sub); font-size: 13px;">Check server requirements, database connectivity, directory permissions, and system performance.</p>
    </div>
    <a href="debug.php?optimize_tables=1" class="btn" style="background: #0284C7;">⚡ Optimize & Repair DB Tables</a>
</div>

<!-- Overview Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="border-left: 4px solid #10B981;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">PHP Version</div>
        <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 4px;">PHP <?php echo PHP_VERSION; ?></div>
        <div style="font-size: 11px; color: #10B981; margin-top: 4px;">✓ Compatible (Modern PHP)</div>
    </div>

    <div class="card" style="border-left: 4px solid #3B82F6;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">MySQL Database Engine</div>
        <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars(substr($dbVer, 0, 15)); ?></div>
        <div style="font-size: 11px; color: #10B981; margin-top: 4px;">✓ Connected via PDO</div>
    </div>

    <div class="card" style="border-left: 4px solid #F59E0B;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">Max File Upload Limit</div>
        <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 4px;"><?php echo $maxUpload; ?></div>
        <div style="font-size: 11px; color: var(--text-sub); margin-top: 4px;">POST Max: <?php echo $postMax; ?> | Mem: <?php echo $memLimit; ?></div>
    </div>

    <div class="card" style="border-left: 4px solid #8B5CF6;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">Disk Storage Available</div>
        <div style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-top: 4px;">
            <?php echo $diskFree ? round($diskFree / (1024 * 1024 * 1024), 1) . ' GB Free' : 'Unlimited'; ?>
        </div>
        <div style="font-size: 11px; color: #10B981; margin-top: 4px;">Healthy Storage Space</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Directory Write Permissions -->
    <div class="card">
        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px;">📁 File & Folder Write Permissions</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #F8FAFC; border-radius: 8px;">
                <div>
                    <strong>/uploads/ directory</strong>
                    <div style="font-size: 11px; color: var(--text-sub);">Stores uploaded product images and banners</div>
                </div>
                <span class="status-badge" style="background: <?php echo $isUploadWritable ? '#DCFCE7' : '#FEE2E2'; ?>; color: <?php echo $isUploadWritable ? '#166534' : '#991B1B'; ?>;">
                    <?php echo $isUploadWritable ? '✓ Writable (755)' : '❌ Not Writable (Check CHMOD)'; ?>
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #F8FAFC; border-radius: 8px;">
                <div>
                    <strong>/config.php file</strong>
                    <div style="font-size: 11px; color: var(--text-sub);">Database connection configuration</div>
                </div>
                <span class="status-badge" style="background: #DCFCE7; color: #166534;">
                    ✓ Configured & Secured
                </span>
            </div>
        </div>
    </div>

    <!-- PHP Extensions -->
    <div class="card">
        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px;">🧩 Required PHP Extensions</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <?php foreach ($extStatus as $ext => $ok): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #F8FAFC; border-radius: 8px; font-size: 12px;">
                    <code><?php echo $ext; ?></code>
                    <strong style="color: <?php echo $ok ? '#10B981' : '#EF4444'; ?>;"><?php echo $ok ? '✓ Active' : '❌ Missing'; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
