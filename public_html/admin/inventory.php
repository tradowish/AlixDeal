<?php
$pageTitle = 'Stock & Low Inventory Alerts';
$activeTab = 'inventory';
require_once __DIR__ . '/header.php';

$msg = '';

// Handle quick stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $prodId = (int)$_POST['product_id'];
    $newStock = (int)$_POST['stock'];
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$newStock, $prodId]);
    $msg = "Stock level updated successfully!";
}

$lowThreshold = 10;
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock ASC")->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 800;">📉 Inventory & Stock Management</h2>
        <p style="color: var(--text-sub); font-size: 13px;">Monitor real-time inventory and restock products with low stock levels.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Current Stock</th>
                    <th>Alert Status</th>
                    <th style="text-align: right;">Quick Restock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php
                    $stock = (int)$p['stock'];
                    $isLow = $stock <= $lowThreshold;
                    $isOut = $stock <= 0;
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/40'); ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                <div>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-sub);">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'General'); ?></td>
                        <td>₹<?php echo number_format($p['price'], 2); ?></td>
                        <td><strong><?php echo $stock; ?> units</strong></td>
                        <td>
                            <?php if ($isOut): ?>
                                <span class="status-badge" style="background: #FEE2E2; color: #991B1B;">⚠️ OUT OF STOCK</span>
                            <?php elseif ($isLow): ?>
                                <span class="status-badge" style="background: #FEF3C7; color: #92400E;">⚡ LOW STOCK (≤10)</span>
                            <?php else: ?>
                                <span class="status-badge" style="background: #DCFCE7; color: #166534;">✓ In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="inventory.php" style="display: inline-flex; align-items: center; gap: 6px;">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="number" name="stock" value="<?php echo $stock; ?>" style="width: 70px; padding: 4px 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                                <button type="submit" name="update_stock" class="btn btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
