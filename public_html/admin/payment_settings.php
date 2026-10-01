<?php
$pageTitle = 'Payment Gateways & UPI';
$activeTab = 'payment_settings';
require_once __DIR__ . '/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // COD
    $codEnabled = isset($_POST['cod_enabled']) ? '1' : '0';
    $codExtra = (float)($_POST['cod_extra_charge'] ?? 0);

    // UPI
    $upiEnabled = isset($_POST['upi_enabled']) ? '1' : '0';
    $upiId = trim($_POST['upi_id'] ?? '');
    $upiName = trim($_POST['upi_name'] ?? '');
    $upiQr = trim($_POST['upi_qr_image'] ?? '');

    // BharatPe
    $bharatpeEnabled = isset($_POST['bharatpe_enabled']) ? '1' : '0';
    $bharatpeMerchantId = trim($_POST['bharatpe_merchant_id'] ?? '');
    $bharatpeQr = trim($_POST['bharatpe_qr_image'] ?? '');

    // Razorpay
    $razorpayEnabled = isset($_POST['razorpay_enabled']) ? '1' : '0';
    $razorpayKey = trim($_POST['razorpay_key_id'] ?? '');
    $razorpaySecret = trim($_POST['razorpay_key_secret'] ?? '');

    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    // UPI QR upload
    if (isset($_FILES['upi_qr_file']) && $_FILES['upi_qr_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['upi_qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $fn = 'upi_qr_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['upi_qr_file']['tmp_name'], $uploadDir . $fn)) {
                $upiQr = 'uploads/' . $fn;
            }
        }
    }

    // BharatPe QR upload
    if (isset($_FILES['bharatpe_qr_file']) && $_FILES['bharatpe_qr_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['bharatpe_qr_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $fn = 'bharatpe_qr_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['bharatpe_qr_file']['tmp_name'], $uploadDir . $fn)) {
                $bharatpeQr = 'uploads/' . $fn;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $updates = [
        'cod_enabled' => $codEnabled,
        'cod_extra_charge' => $codExtra,
        'upi_enabled' => $upiEnabled,
        'upi_id' => $upiId,
        'upi_name' => $upiName,
        'upi_qr_image' => $upiQr,
        'bharatpe_enabled' => $bharatpeEnabled,
        'bharatpe_merchant_id' => $bharatpeMerchantId,
        'bharatpe_qr_image' => $bharatpeQr,
        'razorpay_enabled' => $razorpayEnabled,
        'razorpay_key_id' => $razorpayKey,
        'razorpay_key_secret' => $razorpaySecret
    ];

    foreach ($updates as $k => $v) {
        $stmt->execute([$k, $v, $v]);
    }

    $msg = "All payment gateways and Indian UPI settings updated successfully!";
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

<div style="max-width: 800px;">
    <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 6px;">💳 Indian Payment Gateways & UPI Setup</h2>
    <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 24px;">Configure Cash on Delivery (COD), UPI (GPay, PhonePe, Paytm), BharatPe Merchant QR, and Razorpay.</p>

    <form method="POST" action="payment_settings.php" enctype="multipart/form-data">
        <!-- 1. Cash on Delivery -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>💵</span> Cash on Delivery (COD)
            </h3>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="cod_enabled" value="1" <?php echo ($settings['cod_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                Enable Cash on Delivery at Checkout
            </label>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Extra Handling Charge for COD (Optional, ₹)</label>
                <input type="number" step="0.01" name="cod_extra_charge" value="<?php echo htmlspecialchars($settings['cod_extra_charge'] ?? '0'); ?>" style="width: 200px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
        </div>

        <!-- 2. BharatPe Module -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>🇮🇳</span> BharatPe Merchant QR Payment
            </h3>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="bharatpe_enabled" value="1" <?php echo ($settings['bharatpe_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                Enable BharatPe Instant QR & Soundbox Payment
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">BharatPe Merchant ID / UPI VPA</label>
                    <input type="text" name="bharatpe_merchant_id" value="<?php echo htmlspecialchars($settings['bharatpe_merchant_id'] ?? 'bharatpe.987654@icici'); ?>" placeholder="e.g. bharatpe.987654@icici" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Upload BharatPe Standee QR Photo</label>
                    <input type="file" name="bharatpe_qr_file" accept="image/*" style="font-size: 12px;">
                    <input type="hidden" name="bharatpe_qr_image" value="<?php echo htmlspecialchars($settings['bharatpe_qr_image'] ?? ''); ?>">
                </div>
            </div>
            <?php if (!empty($settings['bharatpe_qr_image'])): ?>
                <div style="margin-top: 8px;">
                    <small style="color: var(--text-sub);">Current BharatPe QR:</small><br>
                    <img src="../<?php echo htmlspecialchars($settings['bharatpe_qr_image']); ?>" style="width: 90px; height: 90px; object-fit: contain; border: 1px solid var(--border); border-radius: 8px; margin-top: 4px;">
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Direct UPI Module (GPay, PhonePe, Paytm, BHIM) -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>⚡</span> Direct UPI Payments (PhonePe, Google Pay, Paytm)
            </h3>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="upi_enabled" value="1" <?php echo ($settings['upi_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                Enable 1-Click UPI Deep-link & Dynamic QR at Checkout
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Your UPI ID (VPA) *</label>
                    <input type="text" name="upi_id" value="<?php echo htmlspecialchars($settings['upi_id'] ?? 'alixdeal@upi'); ?>" placeholder="e.g. 9876543210@paytm or shop@ybl" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Business / Payee Display Name</label>
                    <input type="text" name="upi_name" value="<?php echo htmlspecialchars($settings['upi_name'] ?? 'AlixDeal Shopping'); ?>" placeholder="e.g. AlixDeal Store" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Upload Shop UPI QR Code Photo</label>
                <input type="file" name="upi_qr_file" accept="image/*" style="font-size: 12px;">
                <input type="hidden" name="upi_qr_image" value="<?php echo htmlspecialchars($settings['upi_qr_image'] ?? ''); ?>">
            </div>
            <?php if (!empty($settings['upi_qr_image'])): ?>
                <div style="margin-top: 8px;">
                    <small style="color: var(--text-sub);">Current UPI QR:</small><br>
                    <img src="../<?php echo htmlspecialchars($settings['upi_qr_image']); ?>" style="width: 90px; height: 90px; object-fit: contain; border: 1px solid var(--border); border-radius: 8px; margin-top: 4px;">
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Razorpay Gateway Module -->
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <span>💳</span> Razorpay Payment Gateway (Cards, NetBanking, UPI, Wallets)
            </h3>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="razorpay_enabled" value="1" <?php echo ($settings['razorpay_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                Enable Razorpay Checkout Gateway
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key_id" value="<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>" placeholder="rzp_live_..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_key_secret" value="<?php echo htmlspecialchars($settings['razorpay_key_secret'] ?? ''); ?>" placeholder="Key secret" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
            </div>
        </div>

        <button type="submit" class="btn" style="padding: 12px 28px; font-size: 14px;">💾 Save All Payment Gateways</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
