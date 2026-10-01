<?php
$rootDir = __DIR__;
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    header("Location: install/index.php");
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

// Fetch settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmt->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
$currency = $settings['currency_symbol'] ?? '₹';

$searchQuery = trim($_GET['q'] ?? '');
$orders = [];
$searched = false;

if (!empty($searchQuery)) {
    $searched = true;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? OR customer_phone = ? ORDER BY id DESC");
    $stmt->execute([$searchQuery, $searchQuery]);
    $orders = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - <?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* --- TACTILE BACK BUTTON --- */
        .btn-back-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px 8px 12px;
            background: #FFFFFF;
            color: #0F172A;
            border: 1.5px solid #E2E8F0;
            border-radius: 999px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            overflow: hidden;
        }
        .btn-back-modern .back-icon-wrap {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FF5722;
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s;
        }
        .btn-back-modern:hover {
            border-color: #FF5722;
            color: #FF5722;
            background: #FFFBF9;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(255, 87, 34, 0.15);
        }
        .btn-back-modern:hover .back-icon-wrap {
            transform: translateX(-3px);
            background: #FF5722;
            color: #FFFFFF;
        }
        .btn-back-modern:active {
            transform: scale(0.93) !important;
        }

        .logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .logo:active { transform: scale(0.96); }

        .container {
            max-width: 800px;
            margin: 28px auto;
            padding: 0 16px;
            width: 100%;
            flex: 1;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #FF5722, #EA580C);
            color: white;
            font-weight: 800;
            font-size: 14px;
            border-radius: 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(255, 87, 34, 0.35);
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            position: relative;
            overflow: hidden;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 87, 34, 0.45);
        }
        .btn:active {
            transform: scale(0.93) !important;
        }

        /* --- TOUCH FEEDBACK & RIPPLE --- */
        .touch-ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.45);
            transform: scale(0);
            animation: rippleAnim 0.55s ease-out;
            pointer-events: none;
        }
        .touch-ripple.dark {
            background: rgba(15, 23, 42, 0.15);
        }
        @keyframes rippleAnim {
            to {
                transform: scale(3.5);
                opacity: 0;
            }
        }
        .progress-track {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 28px 0;
        }
        .progress-track::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 20px;
            right: 20px;
            height: 4px;
            background: #E2E8F0;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 25%;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #64748B;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 12px;
            font-weight: 700;
        }
        .step.active .step-circle {
            background: var(--primary);
            color: #fff;
        }
        .step.done .step-circle {
            background: #10B981;
            color: #fff;
        }
        .step-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-sub);
        }
        .step.active .step-label {
            color: var(--primary);
            font-weight: 700;
        }
        .step.done .step-label {
            color: #10B981;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <a href="index.php" class="btn-back-modern" title="Go back to store">
            <span class="back-icon-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </span>
            <span>Back to Store</span>
        </a>
        <a href="index.php" class="logo">
            <span>🔥</span>
            <span><?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?></span>
        </a>
    </div>
    <div>
        <a href="index.php?open_cart=1" class="btn-back-modern" style="padding: 8px 14px;">
            🛒 View Cart
        </a>
    </div>
</header>

<div class="container">
    <div class="card" style="text-align: center;">
        <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">📦 Track Your Order</h1>
        <p style="color: var(--text-sub); font-size: 14px; margin-bottom: 24px;">Enter your Order Number (e.g. ORD-...) or 10-digit Mobile Number to check real-time status.</p>

        <form method="GET" action="track_order.php" style="display: flex; gap: 10px; max-width: 500px; margin: 0 auto;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Order # or Phone number" required style="flex: 1; padding: 12px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px;">
            <button type="submit" class="btn">Track Now</button>
        </form>
    </div>

    <?php if ($searched): ?>
        <?php if (empty($orders)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
                <h3 style="font-size: 18px; font-weight: 700;">No Orders Found</h3>
                <p style="color: var(--text-sub); font-size: 14px; margin-top: 6px;">Please check the Order ID or phone number and try again.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $ord): ?>
                <?php
                // Fetch items
                $stmtIt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmtIt->execute([$ord['id']]);
                $items = $stmtIt->fetchAll();

                $status = $ord['status'];
                $s1 = ($status == 'Pending' || $status == 'Processing' || $status == 'Shipped' || $status == 'Delivered') ? 'done' : '';
                $s2 = ($status == 'Processing' || $status == 'Shipped' || $status == 'Delivered') ? 'done' : ($status == 'Pending' ? 'active' : '');
                $s3 = ($status == 'Shipped' || $status == 'Delivered') ? 'done' : ($status == 'Processing' ? 'active' : '');
                $s4 = ($status == 'Delivered') ? 'done' : ($status == 'Shipped' ? 'active' : '');
                ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span style="font-size: 12px; color: var(--text-sub); font-weight: 600;">ORDER NUMBER</span>
                            <h2 style="font-size: 18px; font-weight: 800;"><?php echo htmlspecialchars($ord['order_number']); ?></h2>
                            <span style="font-size: 12px; color: var(--text-sub);"><?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 12px; color: var(--text-sub); font-weight: 600;">STATUS</span>
                            <div>
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #EEF2FF; color: var(--primary);">
                                    <?php echo htmlspecialchars($ord['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($status !== 'Cancelled'): ?>
                        <div class="progress-track">
                            <div class="step <?php echo $s1; ?>">
                                <div class="step-circle">1</div>
                                <div class="step-label">Order Placed</div>
                            </div>
                            <div class="step <?php echo $s2; ?>">
                                <div class="step-circle">2</div>
                                <div class="step-label">Processing</div>
                            </div>
                            <div class="step <?php echo $s3; ?>">
                                <div class="step-circle">3</div>
                                <div class="step-label">Dispatched</div>
                            </div>
                            <div class="step <?php echo $s4; ?>">
                                <div class="step-circle">4</div>
                                <div class="step-label">Delivered</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="background: #FEE2E2; color: #991B1B; padding: 12px; border-radius: 8px; font-weight: 600; text-align: center; margin: 16px 0;">
                            This order was cancelled.
                        </div>
                    <?php endif; ?>

                    <div style="border-top: 1px dashed var(--border); padding-top: 16px; margin-top: 16px;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Ordered Items:</h4>
                        <?php foreach ($items as $it): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                <span><?php echo htmlspecialchars($it['product_name']); ?> (x<?php echo $it['quantity']; ?>) <small style="color: var(--text-sub);"><?php echo htmlspecialchars($it['selected_color'] . ' • ' . $it['selected_size']); ?></small></span>
                                <strong><?php echo $currency; ?><?php echo number_format($it['subtotal'], 2); ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 800; border-top: 1px solid var(--border); padding-top: 10px; margin-top: 10px;">
                            <span>Total (COD / Prepaid):</span>
                            <span style="color: var(--primary);"><?php echo $currency; ?><?php echo number_format($ord['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <div style="margin-top: 16px; background: #F8FAFC; padding: 12px; border-radius: 8px; font-size: 13px;">
                        <strong>Delivery Address:</strong> <?php echo htmlspecialchars($ord['customer_name']); ?>, <?php echo htmlspecialchars($ord['shipping_address']); ?>, <?php echo htmlspecialchars($ord['city']); ?> - <?php echo htmlspecialchars($ord['postal_code']); ?> | Phone: <?php echo htmlspecialchars($ord['customer_phone']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<footer style="text-align: center; padding: 24px; color: var(--text-sub); font-size: 13px; border-top: 1px solid var(--border); background: #fff;">
    © <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'AlixDeal'); ?>. All Rights Reserved.
</footer>

<script>
    function triggerHaptic(ms = 12) {
        if ('vibrate' in navigator) {
            try { navigator.vibrate(ms); } catch (e) {}
        }
    }

    // Dynamic Touch Ripple Effect
    document.addEventListener('pointerdown', function(e) {
        const btn = e.target.closest('button, .btn, .btn-back-modern, input[type="submit"]');
        if (!btn) return;
        
        triggerHaptic(10);

        const rect = btn.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.classList.add('touch-ripple');
        if (btn.classList.contains('btn-back-modern')) {
            ripple.classList.add('dark');
        }

        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
        ripple.style.top = `${e.clientY - rect.top - size / 2}px`;

        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
</script>

</body>
</html>
