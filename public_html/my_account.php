<?php
session_start();
$rootDir = __DIR__;
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    header("Location: install/index.php");
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?open_login=1");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Fetch user orders
$stmtO = $pdo->prepare("SELECT * FROM orders WHERE customer_phone = ? OR customer_phone = ? ORDER BY id DESC");
$stmtO->execute([$user['phone'], substr($user['phone'], -10)]);
$orders = $stmtO->fetchAll();

// Fetch wallet transactions
$stmtW = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmtW->execute([$userId]);
$transactions = $stmtW->fetchAll();

// Fetch settings
$settings = [];
$stmtS = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmtS->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
$currency = $settings['currency_symbol'] ?? '₹';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account & Wallet - <?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #FF5722;
            --primary-hover: #E64A19;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-sub: #64748B;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-main); }
        .header { background: #fff; border-bottom: 1px solid var(--border); padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 20px; font-weight: 800; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .container { max-width: 900px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: var(--primary); color: white; font-weight: 700; font-size: 13px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer; }
        .btn-outline { background: #fff; color: var(--text-main); border: 1px solid var(--border); }
        .btn-danger { background: #EF4444; }
        .wallet-card { background: linear-gradient(135deg, #FF5722, #EA580C); color: #fff; border-radius: 16px; padding: 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<header class="header">
    <a href="index.php" class="logo">
        <span>🔥</span>
        <span><?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?></span>
    </a>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="index.php" class="btn btn-outline">← Back to Shop</a>
        <button onclick="handleLogout()" class="btn btn-danger">Logout</button>
    </div>
</header>

<div class="container">
    <!-- User Profile Header -->
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <span style="font-size: 12px; color: var(--text-sub); font-weight: 600;">LOGGED IN CUSTOMER</span>
            <h1 style="font-size: 22px; font-weight: 800;"><?php echo htmlspecialchars($user['name']); ?></h1>
            <div style="font-size: 13px; color: var(--text-sub);">📱 <?php echo htmlspecialchars($user['phone']); ?> • ✉️ <?php echo htmlspecialchars($user['email'] ?: 'No email linked'); ?></div>
        </div>
        <?php if ($user['special_discount'] > 0): ?>
            <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 700;">
                🎁 VIP Member: <?php echo (float)$user['special_discount']; ?>% Auto Discount on All Orders!
            </div>
        <?php endif; ?>
    </div>

    <!-- Wallet Balance Card -->
    <div class="wallet-card">
        <div>
            <div style="font-size: 13px; font-weight: 600; opacity: 0.9;">ALIXDEAL CASH WALLET</div>
            <div style="font-size: 32px; font-weight: 800; margin-top: 4px;"><?php echo $currency; ?><?php echo number_format($user['wallet_balance'], 2); ?></div>
            <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">Usable instantly on checkout towards any purchase!</div>
        </div>
        <a href="index.php" class="btn" style="background: #fff; color: var(--primary);">Shop Deals Now →</a>
    </div>

    <!-- Orders Section -->
    <div class="card">
        <h2 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">📦 My Orders (<?php echo count($orders); ?>)</h2>
        <?php if (empty($orders)): ?>
            <p style="color: var(--text-sub); font-size: 13px;">You have not placed any orders yet. <a href="index.php" style="color: var(--primary); font-weight: 700;">Start shopping hot deals!</a></p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($orders as $o): ?>
                    <div style="border: 1px solid var(--border); border-radius: 12px; padding: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <strong style="color: var(--primary);"><?php echo htmlspecialchars($o['order_number']); ?></strong>
                            <div style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?> • <?php echo htmlspecialchars($o['payment_method']); ?></div>
                            <div style="font-size: 14px; font-weight: 800; margin-top: 4px;"><?php echo $currency; ?><?php echo number_format($o['total_amount'], 2); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #EEF2FF; color: var(--primary); margin-bottom: 6px;">
                                <?php echo htmlspecialchars($o['status']); ?>
                            </span><br>
                            <a href="track_order.php?q=<?php echo urlencode($o['order_number']); ?>" class="btn btn-outline" style="font-size: 12px; padding: 4px 10px;">Track Order ↗</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Wallet Transactions -->
    <?php if (!empty($transactions)): ?>
        <div class="card">
            <h2 style="font-size: 17px; font-weight: 800; margin-bottom: 16px;">💳 Wallet History</h2>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($transactions as $t): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                        <div>
                            <strong><?php echo htmlspecialchars($t['description']); ?></strong>
                            <div style="font-size: 11px; color: var(--text-sub);"><?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></div>
                        </div>
                        <div style="font-weight: 800; color: <?php echo $t['type'] === 'credit' ? '#10B981' : '#EF4444'; ?>;">
                            <?php echo $t['type'] === 'credit' ? '+' : '-'; ?><?php echo $currency; ?><?php echo number_format($t['amount'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    async function handleLogout() {
        Swal.fire({
            title: 'Sign out?',
            text: 'Are you sure you want to log out of your account?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FF5722',
            confirmButtonText: 'Yes, Sign Out'
        }).then(async (result) => {
            if (result.isConfirmed) {
                await fetch('api/auth.php?action=logout');
                Swal.fire({
                    title: 'Signed Out',
                    text: 'You have been logged out.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index.php';
                });
            }
        });
    }
</script>

</body>
</html>
