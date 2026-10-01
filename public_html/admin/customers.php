<?php
$pageTitle = 'Customers Directory';
$activeTab = 'customers';
require_once __DIR__ . '/header.php';

// Aggregate customers from orders
$stmt = $pdo->query("
    SELECT 
        customer_phone,
        customer_name,
        customer_email,
        city,
        postal_code,
        COUNT(id) as total_orders,
        SUM(total_amount) as lifetime_spent,
        MAX(created_at) as last_order_date
    FROM orders
    GROUP BY customer_phone
    ORDER BY lifetime_spent DESC
");
$customers = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 800;">👥 Customer Directory & Lifetime Value</h2>
        <p style="color: var(--text-sub); font-size: 13px;">View all buyer phone numbers, order frequency, total amount spent, and contact on WhatsApp.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Mobile Phone</th>
                    <th>City & Pincode</th>
                    <th>Orders Placed</th>
                    <th>Total Spent (₹)</th>
                    <th>Last Active</th>
                    <th style="text-align: right;">Direct Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-sub); padding: 32px;">No customer orders placed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <?php
                        $cleanPhone = preg_replace('/[^0-9]/', '', $c['customer_phone']);
                        $waUrl = "https://wa.me/91" . substr($cleanPhone, -10);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['customer_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['customer_phone']); ?></td>
                            <td><?php echo htmlspecialchars($c['city'] . ' - ' . $c['postal_code']); ?></td>
                            <td><span style="font-weight: 700; background: #EEF2FF; color: var(--primary); padding: 2px 8px; border-radius: 6px;"><?php echo $c['total_orders']; ?> Orders</span></td>
                            <td><strong style="color: #059669;">₹<?php echo number_format($c['lifetime_spent'], 2); ?></strong></td>
                            <td style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M Y', strtotime($c['last_order_date'])); ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo $waUrl; ?>" target="_blank" class="btn btn-sm" style="background: #25D366;">💬 WhatsApp</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
