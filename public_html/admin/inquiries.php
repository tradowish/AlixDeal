<?php
$pageTitle = 'Customer Inquiries & Messages';
$activeTab = 'inquiries';
require_once __DIR__ . '/header.php';

$msg = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Inquiry deleted.";
}

$inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC")->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">💬 Customer Inquiries</h2>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone / Email</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiries)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-sub); padding: 32px;">No inquiries found yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($inq['name']); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($inq['phone']); ?></div>
                                <small style="color: var(--text-sub);"><?php echo htmlspecialchars($inq['email']); ?></small>
                            </td>
                            <td style="max-width: 400px;"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></td>
                            <td style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M Y, h:i A', strtotime($inq['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <a href="inquiries.php?delete=<?php echo $inq['id']; ?>" onclick="return confirm('Delete this message?');" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
