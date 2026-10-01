<?php
$pageTitle = 'Product Reviews Moderation';
$activeTab = 'reviews';
require_once __DIR__ . '/header.php';

$msg = '';

// Toggle approve
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE reviews SET is_approved = NOT is_approved WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Review status updated!";
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Review removed.";
}

// Add sample review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $prodId = (int)$_POST['product_id'];
    $cName = trim($_POST['customer_name']);
    $cPhone = trim($_POST['customer_phone']);
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if (!empty($cName) && !empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, customer_name, customer_phone, rating, comment, is_approved) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$prodId, $cName, $cPhone, $rating, $comment]);
        $msg = "New customer review added!";
    }
}

$products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
$reviews = $pdo->query("SELECT r.*, p.name as product_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id ORDER BY r.id DESC")->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <div class="card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">⭐ Add Customer Review</h3>

        <form method="POST" action="reviews.php">
            <input type="hidden" name="add_review" value="1">

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Select Product *</label>
                <select name="product_id" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <?php foreach ($products as $pr): ?>
                        <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Customer Name *</label>
                <input type="text" name="customer_name" placeholder="e.g. Rahul Sharma" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Star Rating (1-5)</label>
                <select name="rating" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Excellent)</option>
                    <option value="4">⭐⭐⭐⭐ (4 Stars - Very Good)</option>
                    <option value="3">⭐⭐⭐ (3 Stars - Average)</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Feedback / Testimonial *</label>
                <textarea name="comment" rows="3" placeholder="e.g. Received within 2 days in Mumbai. Amazing sound quality and battery life!" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Add Verified Review</button>
        </form>
    </div>

    <div class="card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Customer Reviews & Feedback</h3>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product & Customer</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-sub); padding: 32px;">No reviews found. Add verified reviews on the left.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($rev['customer_name']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-sub);"><?php echo htmlspecialchars($rev['product_name']); ?></div>
                                </td>
                                <td><span style="color: #F59E0B; font-weight: 700;"><?php echo str_repeat('⭐', $rev['rating']); ?></span></td>
                                <td style="max-width: 250px; font-size: 12px;"><?php echo htmlspecialchars($rev['comment']); ?></td>
                                <td>
                                    <a href="reviews.php?toggle=<?php echo $rev['id']; ?>" style="text-decoration: none;">
                                        <span class="status-badge" style="background: <?php echo $rev['is_approved'] ? '#DCFCE7' : '#FEF3C7'; ?>; color: <?php echo $rev['is_approved'] ? '#166534' : '#92400E'; ?>;">
                                            <?php echo $rev['is_approved'] ? '✓ Approved' : '⏳ Pending'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <a href="reviews.php?delete=<?php echo $rev['id']; ?>" onclick="return confirm('Delete review?');" class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
