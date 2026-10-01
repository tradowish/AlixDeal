<?php
$pageTitle = 'Product Categories';
$activeTab = 'categories';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    if (empty($name)) {
        $err = "Category name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $msg = "Category added successfully.";
        } catch (Exception $e) {
            $err = "Error adding category (may already exist).";
        }
    }
}

// Delete category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Category deleted.";
}

$categories = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id ASC")->fetchAll();
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
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Add New Category</h2>
        <form method="POST" action="categories.php">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Category Name</label>
                <input type="text" name="name" placeholder="e.g. Smart Watches" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Slug (Optional)</label>
                <input type="text" name="slug" placeholder="e.g. smart-watches" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <button type="submit" class="btn" style="width: 100%;">Create Category</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Existing Categories</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?php echo $cat['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                            <td style="color: var(--text-sub);"><?php echo htmlspecialchars($cat['slug']); ?></td>
                            <td><?php echo $cat['product_count']; ?> products</td>
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
