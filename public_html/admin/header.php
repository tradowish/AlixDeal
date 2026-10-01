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
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - AlixDeal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
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
        .sidebar {
            width: 250px;
            background: var(--sidebar);
            color: #fff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .brand {
            padding: 24px 20px;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            border-bottom: 1px solid #1E293B;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-links {
            list-style: none;
            padding: 16px 12px;
            flex: 1;
        }
        .nav-item { margin-bottom: 4px; }
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #94A3B8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-item a:hover, .nav-item.active a {
            background: #1E293B;
            color: #fff;
        }
        .nav-item.active a {
            background: var(--primary);
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
            overflow-y: auto;
        }
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar h1 { font-size: 20px; font-weight: 700; }
        .admin-user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .content-area {
            padding: 32px;
            flex: 1;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
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
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            text-align: left;
            padding: 12px 14px;
            background: #F8FAFC;
            color: var(--text-sub);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background: #FAFAFA; }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-Pending { background: #FEF3C7; color: #92400E; }
        .status-Processing { background: #E0E7FF; color: #3730A3; }
        .status-Shipped { background: #DBEAFE; color: #1E40AF; }
        .status-Delivered { background: #DCFCE7; color: #166534; }
        .status-Cancelled { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <span>🛍️</span>
        <span>AlixDeal Control</span>
    </div>

    <ul class="nav-links">
        <li class="nav-item <?php echo ($activeTab ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <a href="index.php">📊 Dashboard</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'products' ? 'active' : ''; ?>">
            <a href="products.php">📦 Products</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'categories' ? 'active' : ''; ?>">
            <a href="categories.php">📁 Categories</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'orders' ? 'active' : ''; ?>">
            <a href="orders.php">🛒 Orders</a>
        </li>
        <li class="nav-item <?php echo ($activeTab ?? '') === 'settings' ? 'active' : ''; ?>">
            <a href="settings.php">⚙️ Settings</a>
        </li>
        <li class="nav-item">
            <a href="../index.php" target="_blank">🌐 View Storefront ↗</a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div style="color: #94A3B8; margin-bottom: 8px;">Logged in as:</div>
        <strong style="color: #fff;"><?php echo htmlspecialchars($adminName); ?></strong>
        <div style="margin-top: 12px;">
            <a href="logout.php" style="color: #F87171; text-decoration: none; font-size: 13px; font-weight: 600;">Sign Out →</a>
        </div>
    </div>
</aside>

<div class="main-wrapper">
    <header class="topbar">
        <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
        <div class="admin-user-badge">
            <span>👋 Hello, <?php echo htmlspecialchars($adminName); ?></span>
        </div>
    </header>
    <main class="content-area">
