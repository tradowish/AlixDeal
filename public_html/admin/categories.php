<?php
$pageTitle = 'Categories & Subcategories';
$activeTab = 'categories';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $name = trim($_POST['name'] ?? '');
    $parentId = (int)($_POST['parent_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    // Image Upload
    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['cat_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $fn = 'cat_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $uploadDir . $fn)) {
                $imageUrl = 'uploads/' . $fn;
            }
        }
    }

    if (empty($name)) {
        $err = "Category name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id, image_url) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), image_url = VALUES(image_url)");
            $stmt->execute([$name, $slug, $parentId, $imageUrl]);
            $msg = "Category / Subcategory saved successfully!";
        } catch (Exception $e) {
            $err = "Error saving category: " . $e->getMessage();
        }
    }
}

// Delete category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Category removed.";
}

// Fetch categories with parent names
$categories = $pdo->query("
    SELECT c.*, p.name as parent_name, COUNT(pr.id) as product_count 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    LEFT JOIN products pr ON c.id = pr.category_id 
    GROUP BY c.id 
    ORDER BY c.parent_id ASC, c.name ASC
")->fetchAll();

$parentCats = $pdo->query("SELECT * FROM categories WHERE parent_id = 0 ORDER BY name ASC")->fetchAll();
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
    <!-- Add Form -->
    <div class="card">
        <h2 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">📁 Add Category / Subcategory</h2>
        <form method="POST" action="categories.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Parent Category (Optional)</label>
                <select name="parent_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="0">None (Create as Main Category)</option>
                    <?php foreach ($parentCats as $p): ?>
                        <option value="<?php echo $p['id']; ?>">↳ Subcategory under: <?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Category Title *</label>
                <input type="text" name="name" placeholder="e.g. Wireless Audio" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Custom URL Slug (Optional)</label>
                <input type="text" name="slug" placeholder="e.g. wireless-audio" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Category Icon / Photo</label>
                <input type="file" name="cat_image" accept="image/*" style="font-size: 12px;">
            </div>

            <button type="submit" class="btn" style="width: 100%;">Create Category</button>
        </form>
    </div>

    <!-- Category List -->
    <div class="card">
        <h2 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">All Categories & Subcategories (<?php echo count($categories); ?>)</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Type / Parent</th>
                        <th>Products Linked</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if (!empty($cat['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($cat['image_url']); ?>" style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover;">
                                    <?php else: ?>
                                        <span style="font-size: 18px;">📁</span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                        <div style="font-size: 11px; color: var(--text-sub);"><?php echo htmlspecialchars($cat['slug']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($cat['parent_id'] > 0): ?>
                                    <span style="background: #F1F5F9; color: #475569; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                        ↳ Sub of: <?php echo htmlspecialchars($cat['parent_name'] ?? 'Parent'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #EEF2FF; color: var(--primary); font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                        ★ MAIN CATEGORY
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $cat['product_count']; ?></strong> items</td>
                            <td style="text-align: right;">
                                <a href="categories.php?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Delete this category?');" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
