<?php
$pageTitle = 'Hero Sliders & Banners';
$activeTab = 'banners';
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';
$err = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Banner deleted successfully.";
    $action = 'list';
}

// Handle Toggle Active
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Banner status updated.";
    $action = 'list';
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $badgeText = trim($_POST['badge_text'] ?? 'HOT DEAL');
    $buttonText = trim($_POST['button_text'] ?? 'Shop Now');
    $buttonLink = trim($_POST['button_link'] ?? '#products');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $bgColor = trim($_POST['bg_color'] ?? '#0F172A');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Handle Image Upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $newFileName = 'banner_' . time() . '_' . rand(100, 999) . '.' . $fileExt;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newFileName)) {
                $imageUrl = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($title)) {
        $err = "Banner title is required.";
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, badge_text=?, button_text=?, button_link=?, image_url=?, bg_color=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$title, $subtitle, $badgeText, $buttonText, $buttonLink, $imageUrl, $bgColor, $sortOrder, $isActive, $id]);
            $msg = "Banner updated successfully!";
            $action = 'list';
        } else {
            $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, badge_text, button_text, button_link, image_url, bg_color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $badgeText, $buttonText, $buttonLink, $imageUrl, $bgColor, $sortOrder, $isActive]);
            $msg = "New banner slider added successfully!";
            $action = 'list';
        }
    }
}
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
    $banner = [
        'id' => 0,
        'title' => '',
        'subtitle' => '',
        'badge_text' => 'HOT DEAL',
        'button_text' => 'Shop Now',
        'button_link' => '#products',
        'image_url' => '',
        'bg_color' => '#0F172A',
        'sort_order' => 0,
        'is_active' => 1
    ];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $found = $stmt->fetch();
        if ($found) $banner = $found;
    }
    ?>
    <div class="card" style="max-width: 700px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 700;"><?php echo $action === 'edit' ? 'Edit Hero Slider' : 'Add New Hero Slider'; ?></h2>
            <a href="banners.php" class="btn btn-sm" style="background: #64748B;">← Back to Sliders</a>
        </div>

        <form method="POST" action="banners.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Headline / Main Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($banner['title']); ?>" placeholder="e.g. Festive Carnival Sale 50% Off" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Subtitle / Offer Description</label>
                <textarea name="subtitle" rows="3" placeholder="e.g. Use code ALIXDEAL50 for Flat 50% off on all trending gadgets!" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit;"><?php echo htmlspecialchars($banner['subtitle']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Badge Tag Text</label>
                    <input type="text" name="badge_text" value="<?php echo htmlspecialchars($banner['badge_text']); ?>" placeholder="e.g. LIMITED TIME, HOT DEAL" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Banner Background Theme Color</label>
                    <input type="color" name="bg_color" value="<?php echo htmlspecialchars($banner['bg_color'] ?: '#0F172A'); ?>" style="width: 100%; height: 42px; padding: 2px; border: 1px solid var(--border); border-radius: 8px; cursor: pointer;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Button Label</label>
                    <input type="text" name="button_text" value="<?php echo htmlspecialchars($banner['button_text']); ?>" placeholder="e.g. Shop Now" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Button Link (Target URL or #products)</label>
                    <input type="text" name="button_link" value="<?php echo htmlspecialchars($banner['button_link']); ?>" placeholder="#products" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Banner Image URL</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($banner['image_url']); ?>" placeholder="https://images.unsplash.com/..." style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Or Upload Banner Photo</label>
                <input type="file" name="image_file" accept="image/*" style="font-size: 13px;">
            </div>

            <div style="display: flex; gap: 24px; align-items: center; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                    Active (Show in Hero Slider)
                </label>
            </div>

            <button type="submit" class="btn">💾 Save Slider Banner</button>
        </form>
    </div>

<?php else: ?>
    <?php
    $banners = $pdo->query("SELECT * FROM banners ORDER BY id ASC")->fetchAll();
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700;">Homepage Hero Sliders</h2>
            <p style="color: var(--text-sub); font-size: 13px;">Manage the top carousel promotions, banners, and discount advertisements.</p>
        </div>
        <a href="banners.php?action=add" class="btn">+ Add New Slider</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Slider Preview</th>
                        <th>Headline & Text</th>
                        <th>Badge</th>
                        <th>Button</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banners)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-sub); padding: 32px;">No banners found. Click "+ Add New Slider" above to create one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($banners as $b): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($b['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($b['image_url']); ?>" style="width: 120px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                    <?php else: ?>
                                        <div style="width: 120px; height: 60px; background: <?php echo htmlspecialchars($b['bg_color'] ?: '#334155'); ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 700;">Color Card</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($b['title']); ?></strong>
                                    <div style="font-size: 12px; color: var(--text-sub); max-width: 320px;"><?php echo htmlspecialchars($b['subtitle']); ?></div>
                                </td>
                                <td><span style="font-size: 11px; font-weight: 700; background: #EEF2FF; color: var(--primary); padding: 3px 8px; border-radius: 6px;"><?php echo htmlspecialchars($b['badge_text']); ?></span></td>
                                <td><a href="<?php echo htmlspecialchars($b['button_link']); ?>" target="_blank" style="font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;"><?php echo htmlspecialchars($b['button_text']); ?> ↗</a></td>
                                <td>
                                    <a href="banners.php?action=toggle&id=<?php echo $b['id']; ?>" style="text-decoration: none;">
                                        <span class="status-badge" style="background: <?php echo $b['is_active'] ? '#DCFCE7' : '#F1F5F9'; ?>; color: <?php echo $b['is_active'] ? '#166534' : '#64748B'; ?>;">
                                            <?php echo $b['is_active'] ? '● Active' : '○ Inactive'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <a href="banners.php?action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm">Edit</a>
                                    <a href="banners.php?action=delete&id=<?php echo $b['id']; ?>" onclick="return confirm('Delete this banner?');" class="btn btn-sm btn-danger">Delete</a>
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
