<?php
$pageTitle = 'UPI & Payment Methods';
$activeTab = 'payment_settings';
require_once __DIR__ . '/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codEnabled = isset($_POST['cod_enabled']) ? '1' : '0';
    $upiEnabled = isset($_POST['upi_enabled']) ? '1' : '0';
    $upiId = trim($_POST['upi_id'] ?? '');
    $whatsapp = trim($_POST['whatsapp_number'] ?? '');
    $upiQr = trim($_POST['upi_qr_image'] ?? '');

    // Handle QR code upload
    if (isset($_FILES['qr_file']) && $_FILES['qr_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $fileExt = strtolower(pathinfo($_FILES['qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newFileName = 'upi_qr_' . time() . '.' . $fileExt;
            if (move_uploaded_file($_FILES['qr_file']['tmp_name'], $uploadDir . $newFileName)) {
                $upiQr = 'uploads/' . $newFileName;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute(['cod_enabled', $codEnabled, $codEnabled]);
    $stmt->execute(['upi_enabled', $upiEnabled, $upiEnabled]);
    $stmt->execute(['upi_id', $upiId, $upiId]);
    $stmt->execute(['whatsapp_number', $whatsapp, $whatsapp]);
    $stmt->execute(['upi_qr_image', $upiQr, $upiQr]);

    $msg = "Payment settings saved successfully!";
}

// Fetch settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmt->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 650px;">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">💳 Payment Gateways & Indian UPI Setup</h2>
    <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 24px;">Configure Cash on Delivery, instant UPI QR code payments (Google Pay, PhonePe, Paytm), and WhatsApp ordering.</p>

    <form method="POST" action="payment_settings.php" enctype="multipart/form-data">
        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; cursor: pointer;">
                <input type="checkbox" name="cod_enabled" value="1" <?php echo ($settings['cod_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                Enable Cash on Delivery (COD)
            </label>
            <p style="color: var(--text-sub); font-size: 12px; margin-left: 24px; margin-top: 4px;">Allows customers to pay in cash when the delivery agent arrives at their doorstep.</p>
        </div>

        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="upi_enabled" value="1" <?php echo ($settings['upi_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                Enable Instant UPI / QR Code Payment (GPay, PhonePe, Paytm, BHIM)
            </label>

            <div style="margin-bottom: 14px; margin-left: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Your UPI ID (VPA)</label>
                <input type="text" name="upi_id" value="<?php echo htmlspecialchars($settings['upi_id'] ?? 'alixdeal@upi'); ?>" placeholder="e.g. 9876543210@paytm or shop@ybl" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
            </div>

            <div style="margin-bottom: 14px; margin-left: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">UPI QR Code Photo (Upload from PhonePe/GPay Business)</label>
                <?php if (!empty($settings['upi_qr_image'])): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="../<?php echo htmlspecialchars($settings['upi_qr_image']); ?>" style="width: 120px; height: 120px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border); background: #fff; padding: 4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="qr_file" accept="image/*" style="font-size: 13px;">
                <input type="hidden" name="upi_qr_image" value="<?php echo htmlspecialchars($settings['upi_qr_image'] ?? ''); ?>">
            </div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
            <label style="display: block; font-size: 14px; font-weight: 700; margin-bottom: 6px;">Direct WhatsApp Order Number</label>
            <p style="color: var(--text-sub); font-size: 12px; margin-bottom: 10px;">Shows a 1-click "Order on WhatsApp" button on each product for customers who prefer chatting.</p>
            <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? '+919876543210'); ?>" placeholder="e.g. +919876543210 (include country code)" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <button type="submit" class="btn">💾 Save Payment Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
