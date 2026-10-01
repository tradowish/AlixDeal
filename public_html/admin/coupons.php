<?php
$pageTitle = 'Coupons & Promo Codes';
$activeTab = 'coupons';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Add Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['discount_type'] === 'flat' ? 'flat' : 'percentage';
    $val = (float)($_POST['discount_value'] ?? 0);
    $minSpend = (float)($_POST['min_spend'] ?? 0);

    if (empty($code) || $val <= 0) {
        $err = "Coupon code and positive discount value are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_spend, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$code, $type, $val, $minSpend]);
            $msg = "Coupon $code created successfully!";
        } catch (Exception $e) {
            $err = "Error creating coupon (code may already exist).";
        }
    }
}

// Delete Coupon
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Coupon deleted.";
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Coupon status updated.";
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
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

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <div class="card">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">🎟️ Create Promo Coupon</h2>

        <form method="POST" action="coupons.php">
            <input type="hidden" name="action" value="add">

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Coupon Code *</label>
                <input type="text" name="code" placeholder="e.g. DIWALI50" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Discount Type</label>
                <select name="discount_type" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="percentage">Percentage (%) Off</option>
                    <option value="flat">Flat Amount (₹) Off</option>
                </select>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Discount Value *</label>
                <input type="number" step="0.01" name="discount_value" placeholder="e.g. 50 (for 50% or ₹50)" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Minimum Order Amount (₹)</label>
                <input type="number" step="0.01" name="min_spend" value="0.00" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <button type="submit" class="btn" style="width: 100%;">Create Coupon</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Active Coupons</h2>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Spend</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-sub); padding: 32px;">No coupons found. Create your first coupon on the left.</td></tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $cp): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary); font-size: 14px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($cp['code']); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo $cp['discount_type'] === 'percentage' ? ((int)$cp['discount_value'] . '% OFF') : ('₹' . number_format($cp['discount_value'], 2) . ' OFF'); ?></strong>
                                </td>
                                <td>₹<?php echo number_format($cp['min_spend'], 2); ?></td>
                                <td>
                                    <a href="coupons.php?toggle=<?php echo $cp['id']; ?>" style="text-decoration: none;">
                                        <span class="status-badge" style="background: <?php echo $cp['is_active'] ? '#DCFCE7' : '#F1F5F9'; ?>; color: <?php echo $cp['is_active'] ? '#166534' : '#64748B'; ?>;">
                                            <?php echo $cp['is_active'] ? '● Active' : '○ Inactive'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <a href="coupons.php?delete=<?php echo $cp['id']; ?>" onclick="return confirm('Delete this coupon?');" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
