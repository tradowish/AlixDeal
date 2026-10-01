<?php
$pageTitle = 'Push Notifications & Announcements';
$activeTab = 'notifications';
require_once __DIR__ . '/header.php';

$msg = '';

// Send notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notif'])) {
    $target = $_POST['target_user']; // 'all' or user_id
    $title = trim($_POST['title']);
    $content = trim($_POST['message']);

    if (!empty($title) && !empty($content)) {
        $userId = ($target === 'all') ? null : (int)$target;
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $title, $content]);
        $msg = "Notification broadcasted successfully!";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$delId]);
    $msg = "Notification removed.";
}

$users = $pdo->query("SELECT id, name, phone FROM users ORDER BY name ASC")->fetchAll();
$notifications = $pdo->query("SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.id DESC")->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <div class="card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">📢 Broadcast Notification</h3>

        <form method="POST" action="notifications.php">
            <input type="hidden" name="send_notif" value="1">

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Send To *</label>
                <select name="target_user" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="all">📢 All Customers (Broadcast Alert)</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name'] . ' (' . $u['phone'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Notification Title *</label>
                <input type="text" name="title" placeholder="e.g. ⚡ Flash Sale Live Now!" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Message Content *</label>
                <textarea name="message" rows="3" placeholder="e.g. Get 50% discount on all wireless earbuds today only with coupon ALIXDEAL50!" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">🚀 Send Notification Now</button>
        </form>
    </div>

    <div class="card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px;">Broadcast History</h3>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Title & Message</th>
                        <th>Date Sent</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-sub); padding: 32px;">No notifications sent yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <tr>
                                <td>
                                    <?php if ($n['user_id'] === null): ?>
                                        <span style="background: #EEF2FF; color: var(--primary); font-weight: 700; font-size: 11px; padding: 2px 8px; border-radius: 6px;">📢 ALL USERS</span>
                                    <?php else: ?>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($n['user_name'] ?? 'User #' . $n['user_id']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                    <div style="font-size: 12px; color: var(--text-sub); max-width: 320px;"><?php echo htmlspecialchars($n['message']); ?></div>
                                </td>
                                <td style="font-size: 11px; color: var(--text-sub);"><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="notifications.php?delete=<?php echo $n['id']; ?>" onclick="return confirm('Delete notification?');" class="btn btn-sm btn-danger">✕</a>
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
