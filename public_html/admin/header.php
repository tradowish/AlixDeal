<?php
require_once __DIR__ . '/auth.php';
checkAdminAuth();
$pdo = getDBConnection();
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - AlixDeal Control Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF5722;
            --primary-hover: #E64A19;
            --bg: #F8FAFC;
            --sidebar: #0F172A;
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
            display: flex;
            min-height: 100vh;
        }

        /* Responsive Sidebar Drawer */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 999;
        }
        .brand {
            padding: 20px;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            border-bottom: 1px solid #1E293B;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .close-sidebar-btn {
            display: none;
            background: none;
            border: none;
            color: #94A3B8;
            font-size: 22px;
            cursor: pointer;
        }
        .nav-links {
            list-style: none;
            padding: 16px 12px;
            flex: 1;
            overflow-y: auto;
        }
        .nav-category {
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px 4px;
        }
        .nav-item { margin-bottom: 3px; }
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 14px;
            color: #94A3B8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-item a:hover {
            background: #1E293B;
            color: #fff;
        }
        .nav-item.active a {
            background: var(--primary);
            color: #fff;
        }
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid #1E293B;
            font-size: 13px;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow-y: auto;
        }
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .hamburger-btn {
            display: none;
            background: none;
            border: 1px solid var(--border);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 20px;
            color: var(--text-main);
            cursor: pointer;
            line-height: 1;
        }
        .topbar h1 { font-size: 18px; font-weight: 800; }
        .admin-user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .content-area {
            padding: 24px;
            flex: 1;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { background: var(--primary-hover); }
        .btn-danger { background: #EF4444; }
        .btn-danger:hover { background: #DC2626; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            text-align: left;
            padding: 10px 12px;
            background: #F8FAFC;
            color: var(--text-sub);
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background: #FAFAFA; }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-Pending { background: #FEF3C7; color: #92400E; }
        .status-Processing { background: #E0E7FF; color: #3730A3; }
        .status-Shipped { background: #DBEAFE; color: #1E40AF; }
        .status-Delivered { background: #DCFCE7; color: #166534; }
        .status-Cancelled { background: #FEE2E2; color: #991B1B; }

        /* Mobile Breakpoint for Admin */
        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-overlay.open {
                display: block;
            }
            .hamburger-btn {
                display: block;
            }
            .close-sidebar-btn {
                display: block;
            }
            .content-area {
                padding: 16px;
            }
            .topbar {
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="adminSidebarOverlay" onclick="toggleAdminSidebar()"></div>

<aside class="sidebar" id="adminSidebar">
    <div class="brand">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span>🔥</span>
            <span>AlixDeal Control</span>
        </div>
        <button class="close-sidebar-btn" onclick="toggleAdminSidebar()">✕</button>
    </div>

    <ul class="nav-links">
        <li class="nav-category">Main Menu</li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <a href="index.php">📊 Dashboard</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'orders' ? 'active' : ''; ?>">
            <a href="orders.php">🛒 Orders & Delivery</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'products' ? 'active' : ''; ?>">
            <a href="products.php">📦 Products</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'inventory' ? 'active' : ''; ?>">
            <a href="inventory.php">📉 Stock & Low Inventory</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'categories' ? 'active' : ''; ?>">
            <a href="categories.php">📁 Categories</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'customers' ? 'active' : ''; ?>">
            <a href="customers.php">👥 Customers Directory</a>
        </li>

        <li class="nav-category">Marketing & Storefront</li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'banners' ? 'active' : ''; ?>">
            <a href="banners.php">🖼️ Hero Sliders & Banners</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'coupons' ? 'active' : ''; ?>">
            <a href="coupons.php">🎟️ Coupons & Discounts</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'reviews' ? 'active' : ''; ?>">
            <a href="reviews.php">⭐ Customer Reviews</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'seo' ? 'active' : ''; ?>">
            <a href="seo.php">🔍 SEO & Analytics</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'payment_settings' ? 'active' : ''; ?>">
            <a href="payment_settings.php">💳 BharatPe, UPI & Razorpay</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'inquiries' ? 'active' : ''; ?>">
            <a href="inquiries.php">💬 Customer Inquiries</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'reports' ? 'active' : ''; ?>">
            <a href="reports.php">📈 Sales & Revenue Reports</a>
        </li>

        <li class="nav-category">System</li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'settings' ? 'active' : ''; ?>">
            <a href="settings.php">⚙️ Store Settings</a>
        </li>
        <li class="nav-item">
            <a href="../index.php" target="_blank">🌐 View Live Storefront ↗</a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div style="color: #94A3B8; margin-bottom: 4px;">Logged in:</div>
        <strong style="color: #fff;"><?php echo htmlspecialchars($adminName); ?></strong>
        <div style="margin-top: 8px;">
            <a href="logout.php" style="color: #F87171; text-decoration: none; font-size: 12px; font-weight: 600;">Sign Out →</a>
        </div>
    </div>
</aside>

<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <!-- 3-line Mobile Hamburger Button -->
            <button class="hamburger-btn" onclick="toggleAdminSidebar()" title="Toggle Menu">☰</button>
            <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
        </div>
        <div class="admin-user-badge">
            <a href="../index.php" target="_blank" style="text-decoration: none; color: var(--primary); font-size: 13px; font-weight: 700;">Visit Store ↗</a>
            <span>•</span>
            <span>👋 <?php echo htmlspecialchars($adminName); ?></span>
        </div>
    </header>
    <main class="content-area">

    <script>
        function toggleAdminSidebar() {
            const sb = document.getElementById('adminSidebar');
            const overlay = document.getElementById('adminSidebarOverlay');
            if (sb && overlay) {
                sb.classList.toggle('open');
                overlay.classList.toggle('open');
            }
        }
    </script>
