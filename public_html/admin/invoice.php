<?php
require_once __DIR__ . '/auth.php';
checkAdminAuth();
$pdo = getDBConnection();

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// Fetch items
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Fetch settings
$settings = [];
$stmtS = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmtS->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #333; margin: 0; padding: 24px; font-size: 13px; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #FF5722; padding-bottom: 16px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #FF5722; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-box { text-align: right; }
        .print-btn { background: #FF5722; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } .invoice-box { border: none; box-shadow: none; padding: 0; } }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 800px; margin: 0 auto 10px; text-align: right;">
    <button class="print-btn" onclick="window.print()">🖨️ Print Tax Invoice / Packing Slip</button>
</div>

<div class="invoice-box">
    <div class="header">
        <div>
            <div class="logo">🔥 <?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?></div>
            <div><?php echo htmlspecialchars($settings['address'] ?? 'India'); ?></div>
            <div>Phone: <?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?></div>
            <?php if (!empty($settings['gst_number'])): ?>
                <div><strong>GSTIN:</strong> <?php echo htmlspecialchars($settings['gst_number']); ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0; color: #333;">TAX INVOICE</h2>
            <div style="font-size: 14px; font-weight: bold; margin-top: 4px;">Invoice #: <?php echo htmlspecialchars($order['order_number']); ?></div>
            <div>Date: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
            <div>Payment: <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <strong style="color: #666; font-size: 11px; text-transform: uppercase;">BILL TO / SHIP TO:</strong>
            <h4 style="margin: 4px 0;"><?php echo htmlspecialchars($order['customer_name']); ?></h4>
            <div><?php echo htmlspecialchars($order['shipping_address']); ?></div>
            <div><?php echo htmlspecialchars($order['city']); ?> - <?php echo htmlspecialchars($order['postal_code']); ?></div>
            <div>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></div>
        </div>
        <div style="text-align: right;">
            <strong style="color: #666; font-size: 11px; text-transform: uppercase;">DISPATCH STATUS:</strong>
            <h3 style="margin: 4px 0; color: #10B981;"><?php echo htmlspecialchars($order['status']); ?></h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item & Details</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($it['product_name']); ?></strong>
                        <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($it['selected_color'] . ' • ' . $it['selected_size']); ?></div>
                    </td>
                    <td style="text-align: center;"><?php echo $it['quantity']; ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($it['price'], 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($it['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        <div style="margin-bottom: 6px;">Delivery / Shipping: <strong>FREE</strong></div>
        <div style="font-size: 18px; font-weight: bold; color: #FF5722;">Total Payable Amount: ₹<?php echo number_format($order['total_amount'], 2); ?></div>
        <div style="font-size: 11px; color: #666; margin-top: 4px;">(Includes all applicable taxes)</div>
    </div>

    <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 16px; font-size: 11px; color: #888; text-align: center;">
        Thank you for shopping with <?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?>! For order assistance, contact <?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>.
    </div>
</div>

</body>
</html>
