<?php
$pageTitle = 'Orders & Dispatch Management';
$activeTab = 'orders';
require_once __DIR__ . '/header.php';

$msg = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (in_array($newStatus, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $msg = "Order status updated to $newStatus.";
    }
}

// Single order view
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($viewId > 0): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$viewId]);
    $order = $stmt->fetch();

    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmtItems->execute([$viewId]);
    $items = $stmtItems->fetchAll();
    ?>

    <?php if ($order): ?>
        <?php
        $cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
        $waMsg = urlencode("Hello " . $order['customer_name'] . ", your order #" . $order['order_number'] . " of ₹" . number_format($order['total_amount'], 2) . " is now " . strtoupper($order['status']) . ". Thank you for shopping with AlixDeal!");
        $waUrl = "https://wa.me/91" . substr($cleanPhone, -10) . "?text=" . $waMsg;
        ?>
        <div class="card" style="max-width: 800px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="font-size: 18px; font-weight: 800;">Order: <?php echo htmlspecialchars($order['order_number']); ?></h2>
                    <span style="font-size: 13px; color: var(--text-sub);">Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="invoice.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-sm" style="background: #0F172A;">🖨️ Tax Invoice</a>
                    <a href="<?php echo $waUrl; ?>" target="_blank" class="btn btn-sm" style="background: #25D366;">💬 WhatsApp Customer</a>
                    <a href="orders.php" class="btn btn-sm" style="background: #64748B;">← Back</a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: #F8FAFC; padding: 16px; border-radius: 12px;">
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Customer Information</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                        <strong>Payment:</strong> <span style="font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                    </p>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Delivery Address</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                        <strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?> - <?php echo htmlspecialchars($order['postal_code']); ?>
                    </p>
                </div>
            </div>

            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px;">Ordered Products</h3>
            <div class="table-responsive" style="margin-bottom: 24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Color/Size</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($it['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($it['selected_color'] . ' / ' . $it['selected_size']); ?></td>
                                <td>₹<?php echo number_format($it['price'], 2); ?></td>
                                <td><?php echo $it['quantity']; ?></td>
                                <td style="text-align: right;"><strong>₹<?php echo number_format($it['subtotal'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 700; font-size: 15px;">Total Order Value:</td>
                            <td style="text-align: right; font-weight: 800; font-size: 18px; color: var(--primary);">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="orders.php?view=<?php echo $order['id']; ?>" style="display: flex; gap: 12px; align-items: center; background: #EEF2FF; padding: 14px; border-radius: 10px; flex-wrap: wrap;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <label style="font-size: 13px; font-weight: 700; color: #3730A3;">Update Delivery Status:</label>
                <select name="status" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 600; background: #fff;">
                    <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing / Packing</option>
                    <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped / Dispatched</option>
                    <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-sm">Update Status</button>
            </form>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    $statusFilter = $_GET['status'] ?? '';
    $sql = "SELECT * FROM orders";
    if (!empty($statusFilter)) {
        $sql .= " WHERE status = " . $pdo->quote($statusFilter);
    }
    $sql .= " ORDER BY id DESC";
    $orders = $pdo->query($sql)->fetchAll();
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800;">🛒 Customer Orders & Shipping</h2>
            <p style="color: var(--text-sub); font-size: 13px;">Manage real-time customer purchases, update tracking, and print tax invoices.</p>
        </div>
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <a href="orders.php" class="btn btn-sm <?php echo empty($statusFilter) ? '' : 'btn-secondary'; ?>" style="<?php echo empty($statusFilter) ? '' : 'background: #E2E8F0; color: #334155;'; ?>">All (<?php echo count($orders); ?>)</a>
            <a href="orders.php?status=Pending" class="btn btn-sm" style="background: #FEF3C7; color: #92400E;">Pending</a>
            <a href="orders.php?status=Processing" class="btn btn-sm" style="background: #E0E7FF; color: #3730A3;">Processing</a>
            <a href="orders.php?status=Shipped" class="btn btn-sm" style="background: #DBEAFE; color: #1E40AF;">Shipped</a>
            <a href="orders.php?status=Delivered" class="btn btn-sm" style="background: #DCFCE7; color: #166534;">Delivered</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Amount (₹)</th>
                        <th>Payment Mode</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="9" style="text-align: center; color: var(--text-sub); padding: 36px;">No orders found in this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_phone']); ?></td>
                                <td><?php echo htmlspecialchars($o['city']); ?></td>
                                <td><strong>₹<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                                <td><small style="font-weight: 700; color: var(--text-sub);"><?php echo htmlspecialchars($o['payment_method']); ?></small></td>
                                <td><span class="status-badge status-<?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></td>
                                <td style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M, h:i A', strtotime($o['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="orders.php?view=<?php echo $o['id']; ?>" class="btn btn-sm">View Details</a>
                                    <a href="invoice.php?id=<?php echo $o['id']; ?>" target="_blank" class="btn btn-sm" style="background: #0F172A;">🖨️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
