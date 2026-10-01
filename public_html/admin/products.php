<?php
$pageTitle = 'Products Catalog';
$activeTab = 'products';
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';
$err = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Product deleted successfully.";
    $action = 'list';
}

// Handle Add / Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sku = trim($_POST['sku'] ?? 'p' . time());
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $original_price = (float)($_POST['original_price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $stock = (int)($_POST['stock'] ?? 100);
    $is_deal = isset($_POST['is_deal']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $colors = trim($_POST['colors'] ?? 'Standard');
    $sizes = trim($_POST['sizes'] ?? 'Standard');

    // Handle Image Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExt, $allowed)) {
            $newFileName = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newFileName)) {
                $imageUrl = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($name) || $price <= 0) {
        $err = "Product title and valid price are required.";
    } else {
        if ($id > 0) {
            // Update
            $sql = "UPDATE products SET sku=?, name=?, description=?, price=?, original_price=?, category_id=?, image_url=?, stock=?, is_deal=?, is_trending=?, colors=?, sizes=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $description, $price, $original_price, $category_id ?: null, $imageUrl, $stock, $is_deal, $is_trending, $colors, $sizes, $id]);
            $msg = "Product updated successfully.";
            $action = 'list';
        } else {
            // Insert
            $sql = "INSERT INTO products (sku, name, description, price, original_price, category_id, image_url, stock, is_deal, is_trending, colors, sizes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $description, $price, $original_price, $category_id ?: null, $imageUrl, $stock, $is_deal, $is_trending, $colors, $sizes]);
            $msg = "New product created successfully.";
            $action = 'list';
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
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

<?php if ($action === 'add' || $action === 'edit'): ?>
    <?php
    $product = [
        'id' => 0,
        'sku' => 'p' . time(),
        'name' => '',
        'description' => '',
        'price' => '0.00',
        'original_price' => '0.00',
        'category_id' => 0,
        'image_url' => '',
        'stock' => 50,
        'is_deal' => 0,
        'is_trending' => 0,
        'colors' => 'Standard',
        'sizes' => 'Standard'
    ];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $found = $stmt->fetch();
        if ($found) $product = $found;
    }
    ?>
    <div class="card" style="max-width: 800px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 18px; font-weight: 700;"><?php echo $action === 'edit' ? 'Edit Product' : 'Add New Product'; ?></h2>
            <a href="products.php" class="btn btn-sm" style="background: #64748B;">← Back to Catalog</a>
        </div>

        <form method="POST" action="products.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Product Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">SKU / Code</label>
                    <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Description</label>
                <textarea name="description" rows="4" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-family:inherit;"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Selling Price ($) *</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Original / Cut Price ($)</label>
                    <input type="number" step="0.01" name="original_price" value="<?php echo $product['original_price']; ?>" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Stock Quantity</label>
                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Category</label>
                    <select name="category_id" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:#fff;">
                        <option value="0">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Image URL</label>
                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" placeholder="https://..." style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Or Upload Image from Computer</label>
                <input type="file" name="image_file" accept="image/*" style="font-size: 13px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Available Colors (comma separated)</label>
                    <input type="text" name="colors" value="<?php echo htmlspecialchars($product['colors']); ?>" placeholder="Black, Silver, Blue" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
                <div>
                    <label style="display:block; font-size: 13px; font-weight:600; margin-bottom:6px;">Available Sizes (comma separated)</label>
                    <input type="text" name="sizes" value="<?php echo htmlspecialchars($product['sizes']); ?>" placeholder="S, M, L, XL" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;">
                </div>
            </div>

            <div style="display: flex; gap: 24px; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_deal" value="1" <?php echo $product['is_deal'] ? 'checked' : ''; ?>>
                    Mark as Deal / Sale Offer
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_trending" value="1" <?php echo $product['is_trending'] ? 'checked' : ''; ?>>
                    Mark as Trending Product
                </label>
            </div>

            <button type="submit" class="btn" style="padding: 12px 28px;">💾 Save Product</button>
        </form>
    </div>

<?php else: ?>
    <?php
    $search = trim($_GET['q'] ?? '');
    $catId = (int)($_GET['cat'] ?? 0);
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($catId > 0) {
        $sql .= " AND p.category_id = ?";
        $params[] = $catId;
    }
    $sql .= " ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allProducts = $stmt->fetchAll();
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="products.php" style="display: flex; gap: 8px;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search product or SKU..." style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; width: 220px;">
            <button type="submit" class="btn btn-sm">Search</button>
        </form>
        <a href="products.php?action=add" class="btn">+ Add Product</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Badges</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allProducts)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-sub); padding: 32px;">No products found in catalog.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allProducts as $p): ?>
                            <tr>
                                <td style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/60'); ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                        <div style="font-size: 12px; color: var(--text-sub);">SKU: <?php echo htmlspecialchars($p['sku'] ?? 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? 'General'); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($p['price'], 2); ?></strong>
                                    <?php if ($p['original_price'] > $p['price']): ?>
                                        <div style="font-size: 11px; text-decoration: line-through; color: var(--text-sub);">$<?php echo number_format($p['original_price'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $p['stock']; ?></td>
                                <td>
                                    <?php if ($p['is_deal']): ?><span style="color: #EF4444; font-weight: 700; font-size: 12px;">Sale</span> <?php endif; ?>
                                    <?php if ($p['is_trending']): ?><span style="color: #3B82F6; font-weight: 700; font-size: 12px;">Trending</span><?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="products.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm">Edit</a>
                                    <a href="products.php?action=delete&id=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="btn btn-sm btn-danger">Delete</a>
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
