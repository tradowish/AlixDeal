<?php
/**
 * AlixDeal Shopping - Public Web Storefront
 */
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
while ($row = $stmt->fetch()) {
    $settings[$row['key_name']] = $row['value'];
}
$siteName = $settings['site_name'] ?? 'AlixDeal Shopping';
$currency = $settings['currency_symbol'] ?? '$';

// Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Filter
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($catId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catId;
}
if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Deals for banner
$deals = $pdo->query("SELECT * FROM products WHERE is_deal = 1 LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> - Modern eCommerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --accent: #F59E0B;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --text-main: #0F172A;
            --text-sub: #64748B;
            --border: #E2E8F0;
            --radius: 16px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
        }
        .navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .search-box {
            flex: 1;
            max-width: 480px;
        }
        .search-box form {
            display: flex;
            background: #F1F5F9;
            border-radius: 9999px;
            padding: 4px 6px 4px 16px;
            border: 1px solid transparent;
        }
        .search-box input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            font-size: 14px;
        }
        .search-box button {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 9999px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-btn {
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-sub);
            padding: 8px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-btn:hover { color: var(--primary); background: #EEF2FF; }
        .admin-link {
            background: #1E293B;
            color: white !important;
            border-radius: 8px;
        }
        .hero {
            background: linear-gradient(135deg, #1E1B4B 0%, #3730A3 50%, #4F46E5 100%);
            color: white;
            padding: 48px 24px;
            text-align: center;
        }
        .hero h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px; }
        .hero p { font-size: 16px; opacity: 0.9; max-width: 600px; margin: 0 auto 20px auto; }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        .category-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .cat-chip {
            padding: 8px 18px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9999px;
            text-decoration: none;
            color: var(--text-sub);
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .cat-chip.active, .cat-chip:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
        }
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -10px rgba(0,0,0,0.1);
        }
        .card-img-wrap {
            position: relative;
            width: 100%;
            height: 200px;
            background: #E2E8F0;
            overflow: hidden;
        }
        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tag-deal {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #EF4444;
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .card-cat {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 4px;
        }
        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-rating {
            font-size: 12px;
            color: var(--accent);
            font-weight: 700;
            margin-bottom: 12px;
        }
        .price-row {
            margin-top: auto;
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 14px;
        }
        .price { font-size: 18px; font-weight: 800; color: var(--text-main); }
        .original-price { font-size: 13px; text-decoration: line-through; color: var(--text-sub); }
        .btn-buy {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-buy:hover { background: var(--primary-dark); }
        .footer {
            background: #0F172A;
            color: #94A3B8;
            padding: 40px 20px;
            margin-top: 60px;
            text-align: center;
            font-size: 14px;
        }
        .footer a { color: #818CF8; text-decoration: none; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">
        🛍️ <?php echo htmlspecialchars($siteName); ?>
    </a>

    <div class="search-box">
        <form method="GET" action="index.php">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search electronics, apparel, deals...">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="nav-actions">
        <a href="api/products.php" target="_blank" class="nav-btn">📱 Android REST API</a>
        <a href="admin/" class="nav-btn admin-link">⚙️ Admin Panel</a>
    </div>
</nav>

<section class="hero">
    <h1>Exclusive Deals & Trending Finds</h1>
    <p>Discover handpicked items delivered straight to your door. Backed by real-time inventory and instant fulfillment.</p>
</section>

<div class="container">
    <div class="category-scroll">
        <a href="index.php" class="cat-chip <?php echo $catId === 0 ? 'active' : ''; ?>">All Categories</a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?cat=<?php echo $cat['id']; ?>" class="cat-chip <?php echo $catId == $cat['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 60px 20px; color: var(--text-sub);">
            <h3>No products found</h3>
            <p>Try searching with another keyword or explore all categories.</p>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <div class="card">
                    <div class="card-img-wrap">
                        <?php if ($p['is_deal']): ?>
                            <span class="tag-deal">SALE DEAL</span>
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80'); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                    </div>
                    <div class="card-body">
                        <div class="card-cat"><?php echo htmlspecialchars($p['category_name'] ?? 'Product'); ?></div>
                        <h3 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <div class="card-rating">★ <?php echo number_format($p['rating'], 1); ?> (<?php echo $p['reviews_count']; ?> reviews)</div>
                        
                        <div class="price-row">
                            <span class="price"><?php echo $currency . number_format($p['price'], 2); ?></span>
                            <?php if ($p['original_price'] > $p['price']): ?>
                                <span class="original-price"><?php echo $currency . number_format($p['original_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="admin/" class="btn-buy">Manage in Admin</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. Fully integrated with your Android Mobile App.</p>
    <p style="margin-top: 8px;"><a href="admin/">Admin Login</a> &bull; <a href="api/products.php">JSON API for Mobile</a></p>
</footer>

</body>
</html>
