<?php
$pageTitle = 'Dashboard Overview';
$activeTab = 'dashboard';
require_once __DIR__ . '/header.php';

// Stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();

// Recent Orders
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();

// Top Products
$recentProducts = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 5")->fetchAll();
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 13px; color: var(--text-sub); font-weight: 600;">Total Revenue</div>
        <div style="font-size: 28px; font-weight: 800; color: #10B981; margin: 8px 0;">$<?php echo number_format($totalSales, 2); ?></div>
        <div style="font-size: 12px; color: var(--text-sub);">All active orders</div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 13px; color: var(--text-sub); font-weight: 600;">Total Orders</div>
        <div style="font-size: 28px; font-weight: 800; color: var(--primary); margin: 8px 0;"><?php echo $totalOrders; ?></div>
        <div style="font-size: 12px; color: var(--text-sub);"><a href="orders.php" style="color: var(--primary); text-decoration: none;">View orders →</a></div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 13px; color: var(--text-sub); font-weight: 600;">Live Products</div>
        <div style="font-size: 28px; font-weight: 800; color: #F59E0B; margin: 8px 0;"><?php echo $totalProducts; ?></div>
        <div style="font-size: 12px; color: var(--text-sub);"><a href="products.php" style="color: var(--primary); text-decoration: none;">Manage catalog →</a></div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div style="font-size: 13px; color: var(--text-sub); font-weight: 600;">Categories</div>
        <div style="font-size: 28px; font-weight: 800; color: #6366F1; margin: 8px 0;"><?php echo $totalCategories; ?></div>
        <div style="font-size: 12px; color: var(--text-sub);"><a href="categories.php" style="color: var(--primary); text-decoration: none;">Manage categories →</a></div>
    </div>
</div>

<div style="display: flex; gap: 12px; margin-bottom: 28px;">
    <a href="products.php?action=add" class="btn">+ Add New Product</a>
    <a href="categories.php" class="btn" style="background: #334155;">+ Add Category</a>
    <a href="../api/products.php" target="_blank" class="btn" style="background: #0284C7;">📱 Test Android REST API</a>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h2 style="font-size: 18px; font-weight: 700;">Recent Orders</h2>
        <a href="orders.php" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">View all</a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <p style="color: var(--text-sub); font-size: 14px; text-align: center; padding: 24px;">No orders yet. Orders from website & Android app will appear here.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?><br><small style="color: var(--text-sub);"><?php echo htmlspecialchars($order['customer_phone']); ?></small></td>
                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                            <td style="color: var(--text-sub); font-size: 13px;"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                            <td><a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm">Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h2 style="font-size: 18px; font-weight: 700;">Recent Catalog Items</h2>
        <a href="products.php" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">View catalog</a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Deal / Trending</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentProducts as $prod): ?>
                    <tr>
                        <td style="display: flex; align-items: center; gap: 12px;">
                            <img src="<?php echo htmlspecialchars($prod['image_url'] ?: 'https://via.placeholder.com/50'); ?>" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;">
                            <div>
                                <strong style="font-size: 14px;"><?php echo htmlspecialchars($prod['name']); ?></strong>
                                <div style="font-size: 12px; color: var(--text-sub);">SKU: <?php echo htmlspecialchars($prod['sku'] ?? 'N/A'); ?></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($prod['category_name'] ?? 'General'); ?></td>
                        <td><strong>$<?php echo number_format($prod['price'], 2); ?></strong></td>
                        <td><?php echo $prod['stock']; ?> units</td>
                        <td>
                            <?php if ($prod['is_deal']): ?><span style="color: #EF4444; font-weight: 700; font-size: 12px;">Sale</span> <?php endif; ?>
                            <?php if ($prod['is_trending']): ?><span style="color: #3B82F6; font-weight: 700; font-size: 12px;">Trending</span><?php endif; ?>
                        </td>
                        <td><a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
