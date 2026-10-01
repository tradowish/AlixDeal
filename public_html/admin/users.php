<?php
$pageTitle = 'Registered Users & Wallet Management';
$activeTab = 'users';
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// Impersonate / Login as Customer
if (isset($_GET['login_as'])) {
    $targetId = (int)$_GET['login_as'];
    $stmtU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtU->execute([$targetId]);
    $u = $stmtU->fetch();
    if ($u) {
        // Set customer session
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_name'] = $u['name'];
        $_SESSION['user_phone'] = $u['phone'];
        header("Location: ../my_account.php");
        exit;
    }
}

// Modify Wallet Balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'wallet_adjust') {
    $userId = (int)$_POST['user_id'];
    $type = $_POST['type'] === 'debit' ? 'debit' : 'credit';
    $amount = (float)$_POST['amount'];
    $desc = trim($_POST['description'] ?? 'Admin adjustment');

    if ($amount > 0) {
        if ($type === 'credit') {
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = GREATEST(0, wallet_balance - ?) WHERE id = ?");
            $stmt->execute([$amount, $userId]);
        }
        $stmtT = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $stmtT->execute([$userId, $type, $amount, $desc]);
        $msg = "Wallet balance updated successfully!";
    }
}

// Update VIP Discount & Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $userId = (int)$_POST['user_id'];
    $discount = (float)$_POST['special_discount'];
    $status = $_POST['status'] === 'blocked' ? 'blocked' : 'active';

    $stmt = $pdo->prepare("UPDATE users SET special_discount = ?, status = ? WHERE id = ?");
    $stmt->execute([$discount, $status, $userId]);
    $msg = "User settings updated!";
}

// Delete user
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delId]);
    $msg = "User deleted.";
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 800;">👥 Registered Users & Wallet Control</h2>
        <p style="color: var(--text-sub); font-size: 13px;">Manage customers, add/remove wallet balance, grant VIP discounts, and 1-click login as customer.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone / Email</th>
                    <th>Wallet Balance</th>
                    <th>VIP Discount</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-sub); padding: 36px;">No registered users yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td>
                                <div><?php echo htmlspecialchars($u['phone']); ?></div>
                                <small style="color: var(--text-sub);"><?php echo htmlspecialchars($u['email']); ?></small>
                            </td>
                            <td>
                                <strong style="color: #059669; font-size: 15px;">₹<?php echo number_format($u['wallet_balance'], 2); ?></strong>
                            </td>
                            <td>
                                <?php if ($u['special_discount'] > 0): ?>
                                    <span style="background: #FEF3C7; color: #92400E; font-weight: 700; padding: 2px 8px; border-radius: 6px; font-size: 12px;"><?php echo (float)$u['special_discount']; ?>% OFF</span>
                                <?php else: ?>
                                    <span style="color: var(--text-sub); font-size: 12px;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge" style="background: <?php echo $u['status'] === 'active' ? '#DCFCE7' : '#FEE2E2'; ?>; color: <?php echo $u['status'] === 'active' ? '#166534' : '#991B1B'; ?>;">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <!-- 1-Click Login as User -->
                                    <a href="users.php?login_as=<?php echo $u['id']; ?>" target="_blank" class="btn btn-sm" style="background: #0284C7;" title="Log in as this customer">🔑 Login as User</a>
                                    <!-- Adjust Wallet button -->
                                    <button onclick="openWalletModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>', <?php echo $u['wallet_balance']; ?>)" class="btn btn-sm" style="background: #059669;">💰 Wallet</button>
                                    <!-- Edit VIP / Status -->
                                    <button onclick="openEditModal(<?php echo $u['id']; ?>, <?php echo $u['special_discount']; ?>, '<?php echo $u['status']; ?>')" class="btn btn-sm" style="background: #64748B;">⚙️ VIP</button>
                                    <a href="users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user account?');" class="btn btn-sm btn-danger">✕</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Adjust Wallet -->
<div id="walletModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #fff; border-radius: 16px; max-width: 440px; width: 100%; padding: 24px;">
        <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 6px;">💰 Add / Deduct Wallet Balance</h3>
        <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 16px;" id="walletModalUser">Customer Name</p>

        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="wallet_adjust">
            <input type="hidden" name="user_id" id="modalUserId">

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Action</label>
                <select name="type" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="credit">➕ Add Credit (Cashback / Bonus)</option>
                    <option value="debit">➖ Deduct Balance (Penalty / Refund)</option>
                </select>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Amount (₹) *</label>
                <input type="number" step="0.01" name="amount" placeholder="e.g. 100.00" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Description / Reason</label>
                <input type="text" name="description" placeholder="e.g. Festive promotion bonus" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="closeWalletModal()" style="padding: 8px 16px; border: 1px solid var(--border); background: #fff; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn">Update Balance</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: VIP Discount & Status -->
<div id="editModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: #fff; border-radius: 16px; max-width: 440px; width: 100%; padding: 24px;">
        <h3 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">⚙️ VIP Discount & Account Status</h3>

        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="user_id" id="editUserId">

            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Special VIP Discount Percentage (%)</label>
                <input type="number" step="0.1" name="special_discount" id="editDiscount" placeholder="e.g. 10 (for 10% auto discount)" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                <small style="color: var(--text-sub); font-size: 11px;">Automatically deducted on top of any order this user places.</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Account Status</label>
                <select name="status" id="editStatus" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="active">Active (Normal Access)</option>
                    <option value="blocked">Blocked / Suspended</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" style="padding: 8px 16px; border: 1px solid var(--border); background: #fff; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn">Save VIP Settings</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openWalletModal(id, name, balance) {
        document.getElementById('modalUserId').value = id;
        document.getElementById('walletModalUser').textContent = name + ' (Current Balance: ₹' + balance.toFixed(2) + ')';
        document.getElementById('walletModal').style.display = 'flex';
    }
    function closeWalletModal() {
        document.getElementById('walletModal').style.display = 'none';
    }

    function openEditModal(id, discount, status) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editDiscount').value = discount;
        document.getElementById('editStatus').value = status;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
