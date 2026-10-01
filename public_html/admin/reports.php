<?php
$pageTitle = 'Sales & Revenue Analytics';
$activeTab = 'reports';
require_once __DIR__ . '/header.php';

// Total gross revenue
$stmtGross = $pdo->query("SELECT SUM(total_amount) as total, COUNT(id) as count FROM orders WHERE status != 'Cancelled'");
$gross = $stmtGross->fetch();
$totalRevenue = $gross['total'] ?? 0;
$totalOrders = $gross['count'] ?? 0;

// Delivered orders revenue
$stmtDelivered = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'Delivered'");
$deliveredTotal = $stmtDelivered->fetch()['total'] ?? 0;

// Payment mode distribution
$stmtPay = $pdo->query("SELECT payment_method, COUNT(id) as count, SUM(total_amount) as total FROM orders GROUP BY payment_method");
$payModes = $stmtPay->fetchAll();

// Recent 10 Orders
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10")->fetchAll();
?>

<div style="margin-bottom: 24px;">
    <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 4px;">📈 Sales & Financial Reports</h2>
    <p style="color: var(--text-sub); font-size: 13px;">Overview of store performance, revenue, orders, and payment method statistics.</p>
</div>

<!-- Stat Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">Total Gross Revenue</div>
        <div style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 6px;">₹<?php echo number_format($totalRevenue, 2); ?></div>
        <div style="font-size: 11px; color: #10B981; margin-top: 4px;">From all confirmed orders</div>
    </div>

    <div class="card" style="border-left: 4px solid #10B981;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">Delivered Revenue (Collected)</div>
        <div style="font-size: 24px; font-weight: 800; color: #10B981; margin-top: 6px;">₹<?php echo number_format($deliveredTotal, 2); ?></div>
        <div style="font-size: 11px; color: var(--text-sub); margin-top: 4px;">100% Realized Cashflow</div>
    </div>

    <div class="card" style="border-left: 4px solid #3B82F6;">
        <div style="font-size: 12px; font-weight: 700; color: var(--text-sub); text-transform: uppercase;">Total Orders Received</div>
        <div style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 6px;"><?php echo $totalOrders; ?></div>
        <div style="font-size: 11px; color: var(--text-sub); margin-top: 4px;">Average value: ₹<?php echo $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 2) : '0'; ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Payment Mode Breakdown -->
    <div class="card">
        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px;">Payment Mode Breakdown</h3>
        <?php foreach ($payModes as $pm): ?>
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 4px;">
                    <span><?php echo htmlspecialchars($pm['payment_method'] ?: 'Cash on Delivery'); ?></span>
                    <span>₹<?php echo number_format($pm['total'], 2); ?> (<?php echo $pm['count']; ?> orders)</span>
                </div>
                <div style="height: 6px; background: #E2E8F0; border-radius: 999px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $totalRevenue > 0 ? min(100, round(($pm['total'] / $totalRevenue) * 100)) : 0; ?>%; background: var(--primary);"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Sales Stream -->
    <div class="card">
        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px;">Recent Sales Stream</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($ro['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ro['customer_name']); ?></td>
                            <td><strong>₹<?php echo number_format($ro['total_amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($ro['payment_method']); ?></td>
                            <td><span class="status-badge status-<?php echo $ro['status']; ?>"><?php echo $ro['status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
