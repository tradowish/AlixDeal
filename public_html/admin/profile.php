<?php
$pageTitle = 'Admin Profile & Security';
$activeTab = 'settings';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

$adminId = $_SESSION['admin_id'] ?? 1;
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($username) || empty($email)) {
        $err = "Name, Username, and Email cannot be empty.";
    } else {
        // If updating password
        if (!empty($newPass)) {
            if ($newPass !== $confirmPass) {
                $err = "New password and confirmation do not match.";
            } elseif (!password_verify($currentPass, $admin['password'])) {
                $err = "Current password is incorrect.";
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmtU = $pdo->prepare("UPDATE admins SET name = ?, username = ?, email = ?, password = ? WHERE id = ?");
                $stmtU->execute([$name, $username, $email, $newHash, $adminId]);
                $_SESSION['admin_name'] = $name;
                $msg = "Profile and password updated successfully!";
            }
        } else {
            $stmtU = $pdo->prepare("UPDATE admins SET name = ?, username = ?, email = ? WHERE id = ?");
            $stmtU->execute([$name, $username, $email, $adminId]);
            $_SESSION['admin_name'] = $name;
            $msg = "Profile updated successfully!";
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

<div class="card" style="max-width: 600px;">
    <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">🔐 Admin Profile & Credentials</h2>
    <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 24px;">Change your administrator login username, email address, and account password.</p>

    <form method="POST" action="profile.php">
        <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Admin Full Name *</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Login Username *</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Admin Email Address *</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 14px; padding-top: 14px; border-top: 1px dashed var(--border);">Change Password (Leave blank to keep current)</h3>

        <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Current Password</label>
            <input type="password" name="current_password" placeholder="Enter current password to verify" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 14px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">New Password</label>
            <input type="password" name="new_password" placeholder="Enter new strong password" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <button type="submit" class="btn">💾 Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
