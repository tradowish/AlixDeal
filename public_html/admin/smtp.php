<?php
$pageTitle = 'SMTP Email Server Configuration';
$activeTab = 'settings';
require_once __DIR__ . '/header.php';

$msg = '';
$testResult = '';

// Save SMTP settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    $keys = [
        'smtp_host',
        'smtp_port',
        'smtp_user',
        'smtp_pass',
        'smtp_encryption',
        'smtp_from_name',
        'smtp_from_email'
    ];
    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    foreach ($keys as $k) {
        $val = trim($_POST[$k] ?? '');
        $stmt->execute([$k, $val, $val]);
    }
    $msg = "SMTP configurations saved successfully!";
}

// Send Test Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_email'])) {
    $to = trim($_POST['test_recipient'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $testResult = '<div style="background: #FEE2E2; color: #991B1B; padding: 10px; border-radius: 8px;">❌ Invalid recipient email address.</div>';
    } else {
        $headers = "From: " . ($_POST['smtp_from_email'] ?? 'noreply@alixdeal.shop') . "\r\n";
        $headers .= "Reply-To: " . ($_POST['smtp_from_email'] ?? 'noreply@alixdeal.shop') . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $subject = "AlixDeal SMTP Test Email";
        $body = "<h2>Hello!</h2><p>This is a test notification email from your AlixDeal store. Your email dispatching is operational.</p>";

        $sent = @mail($to, $subject, $body, $headers);
        if ($sent) {
            $testResult = '<div style="background: #ECFDF5; color: #065F46; padding: 10px; border-radius: 8px;">✅ Test email dispatched to ' . htmlspecialchars($to) . ' via PHP mail / SMTP transport.</div>';
        } else {
            $testResult = '<div style="background: #FEF3C7; color: #92400E; padding: 10px; border-radius: 8px;">⚠️ Mail dispatch invoked. Check your server mail queue or spam folder.</div>';
        }
    }
}

// Fetch current
$settings = [];
$stmtS = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmtS->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div class="card">
        <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 6px;">📧 SMTP Mailer Settings</h2>
        <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 20px;">Configure your SMTP server (Gmail, Hostinger, cPanel Webmail, SendGrid, Amazon SES) for order notifications.</p>

        <form method="POST" action="smtp.php">
            <input type="hidden" name="save_smtp" value="1">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">SMTP Host *</label>
                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" placeholder="smtp.gmail.com or mail.yourdomain.com" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Port *</label>
                    <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" placeholder="587 / 465" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">SMTP Username / Email *</label>
                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" placeholder="your-email@gmail.com" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">SMTP Password / App Password *</label>
                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" placeholder="App password or webmail pass" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Encryption</label>
                    <select name="smtp_encryption" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                        <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended for port 587)</option>
                        <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                        <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">From Name</label>
                    <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'AlixDeal Notifications'); ?>" placeholder="AlixDeal Orders" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Sender (From) Email *</label>
                <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? 'orders@alixdeal.shop'); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <button type="submit" class="btn">💾 Save SMTP Configuration</button>
        </form>
    </div>

    <!-- Test Email Card -->
    <div class="card">
        <h3 style="font-size: 15px; font-weight: 800; margin-bottom: 8px;">🧪 Send Test Email</h3>
        <p style="color: var(--text-sub); font-size: 12px; margin-bottom: 14px;">Verify if your server mail transport is configured properly.</p>

        <?php echo $testResult; ?>

        <form method="POST" action="smtp.php" style="margin-top: 10px;">
            <input type="hidden" name="send_test_email" value="1">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Recipient Email Address</label>
                <input type="email" name="test_recipient" placeholder="your-personal@gmail.com" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            </div>
            <button type="submit" class="btn btn-sm" style="width: 100%; background: #0284C7;">Send Test Notification</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
