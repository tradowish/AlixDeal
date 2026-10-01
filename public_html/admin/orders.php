<?php
$pageTitle = 'Orders Management';
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
        <div class="card" style="max-width: 800px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 18px; font-weight: 700;">Order: <?php echo htmlspecialchars($order['order_number']); ?></h2>
                    <span style="font-size: 13px; color: var(--text-sub);">Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <a href="orders.php" class="btn btn-sm" style="background: #64748B;">← Back to Orders</a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; background: #F8FAFC; padding: 16px; border-radius: 12px;">
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Customer Information</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?>
                    </p>
                </div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Delivery Address</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                        <strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?> <?php echo htmlspecialchars($order['postal_code']); ?>
                    </p>
                </div>
            </div>

            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 12px;">Order Items</h3>
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
                                <td>$<?php echo number_format($it['price'], 2); ?></td>
                                <td><?php echo $it['quantity']; ?></td>
                                <td style="text-align: right;"><strong>$<?php echo number_format($it['subtotal'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" style="text-align: right; font-weight: 700; font-size: 15px;">Total Amount:</td>
                            <td style="text-align: right; font-weight: 800; font-size: 16px; color: var(--primary);">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <form method="POST" action="orders.php?view=<?php echo $order['id']; ?>" style="display: flex; gap: 12px; align-items: center; background: #EEF2FF; padding: 14px; border-radius: 10px;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <label style="font-size: 13px; font-weight: 700; color: var(--primary);">Update Order Status:</label>
                <select name="status" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); font-weight: 600; background: #fff;">
                    <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo $order['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm">Update Status</button>
            </form>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    $orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
    ?>
    <div class="card">
        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">All Incoming Orders</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-sub); padding: 32px;">No orders found. When a customer checks out via web or Android app, orders appear here.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ord['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($ord['customer_phone']); ?></td>
                                <td><?php echo htmlspecialchars($ord['city']); ?></td>
                                <td><strong>$<?php echo number_format($ord['total_amount'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo $ord['status']; ?>"><?php echo $ord['status']; ?></span></td>
                                <td style="font-size: 12px; color: var(--text-sub);"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="orders.php?view=<?php echo $ord['id']; ?>" class="btn btn-sm">View Details</a>
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
