<?php
$pageTitle = 'Store Settings';
$activeTab = 'settings';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim($_POST['site_name'] ?? 'AlixDeal Shopping');
    $currency = trim($_POST['currency_symbol'] ?? '$');
    $email = trim($_POST['contact_email'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute(['site_name', $siteName, $siteName]);
    $stmt->execute(['currency_symbol', $currency, $currency]);
    $stmt->execute(['contact_email', $email, $email]);
    $stmt->execute(['contact_phone', $phone, $phone]);

    // Handle password change if requested
    $newPass = $_POST['new_password'] ?? '';
    if (!empty($newPass)) {
        if (strlen($newPass) < 6) {
            $err = "New password must be at least 6 characters.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['admin_id']]);
            $msg = "Settings and password updated successfully!";
        }
    } else {
        $msg = "Settings updated successfully!";
    }
}

// Fetch current settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['key_name']] = $row['value'];
}
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($err); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 650px;">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;">General Store Settings</h2>

    <form method="POST" action="settings.php">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Store / Brand Name</label>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal Shopping'); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Currency Symbol</label>
            <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '$'); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Support Email</label>
                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'support@alixdeal.shop'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Support Phone</label>
                <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+1 800 123 4567'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px; padding-top: 16px; border-top: 1px dashed var(--border);">Change Admin Password (Optional)</h3>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">New Password (leave blank to keep current)</label>
            <input type="password" name="new_password" placeholder="Min 6 characters" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <button type="submit" class="btn">Save Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
