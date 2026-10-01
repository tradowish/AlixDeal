<?php
/**
 * AlixDeal Shopping - Advanced Responsive Storefront
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
$currency = $settings['currency_symbol'] ?? '₹';
$whatsapp = $settings['whatsapp_number'] ?? '+919876543210';
$announcement = $settings['announcement_text'] ?? '⚡ Special Offer: Free Delivery All Over India + 50% Off With Code ALIXDEAL50!';

// Active Banners for Hero Slider
$banners = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
if (empty($banners)) {
    $banners = [
        [
            'id' => 1,
            'title' => 'Mega Festive Deal Carnival',
            'subtitle' => 'Get Flat 50% OFF on all orders using coupon ALIXDEAL50 at checkout!',
            'badge_text' => 'ALIX EXCLUSIVE',
            'button_text' => 'Shop Hot Deals',
            'button_link' => '#products',
            'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=700&q=80',
            'bg_color' => '#0F172A'
        ]
    ];
}

// Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Product Filters
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['meta_title'] ?: ($siteName . ' - Best Deals & Online Shopping')); ?></title>
    
    <!-- SEO Meta Tags Managed from Admin -->
    <meta name="description" content="<?php echo htmlspecialchars($settings['meta_description'] ?? 'Best daily deals on electronics, fashion, and home essentials.'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['meta_keywords'] ?? 'online shopping, deals, electronics, earbuds, chargers'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($settings['meta_title'] ?: $siteName); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($settings['meta_description'] ?? ''); ?>">
    <?php if (!empty($settings['og_image'])): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($settings['og_image']); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">

    <!-- Google Analytics -->
    <?php if (!empty($settings['google_analytics'])): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($settings['google_analytics']); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo htmlspecialchars($settings['google_analytics']); ?>');
        </script>
    <?php endif; ?>

    <!-- Custom Header Scripts -->
    <?php if (!empty($settings['header_scripts'])): ?>
        <?php echo $settings['header_scripts']; ?>
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF5722;
            --primary-hover: #E64A19;
            --accent: #10B981;
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
            line-height: 1.5;
        }
        .announcement-bar {
            background: linear-gradient(90deg, #FF5722, #FF9800);
            color: #fff;
            text-align: center;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
        }
        .search-box {
            flex: 1;
            max-width: 480px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid var(--border);
            border-radius: 999px;
            font-size: 13px;
            outline: none;
            background: #F1F5F9;
        }
        .search-box input:focus {
            background: #fff;
            border-color: var(--primary);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 14px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-header {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-track {
            background: #EEF2FF;
            color: var(--primary);
        }
        .btn-track:hover {
            background: #E0E7FF;
        }
        .btn-cart {
            background: var(--primary);
            color: #fff;
        }
        .btn-cart:hover {
            background: var(--primary-hover);
        }

        /* --- HERO SLIDER --- */
        .slider-section {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 0 16px;
        }
        .slider-wrapper {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .slides-container {
            display: flex;
            transition: transform 0.5s ease-in-out;
            width: 100%;
        }
        .slide {
            min-width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 40px 48px;
            color: #fff;
            background-size: cover;
            background-position: center;
            min-height: 280px;
            position: relative;
        }
        .slide::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.6) 60%, rgba(15,23,42,0.2) 100%);
            z-index: 1;
        }
        .slide-content {
            position: relative;
            z-index: 2;
            max-width: 550px;
        }
        .slide-badge {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .slide-title {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        .slide-subtitle {
            font-size: 14px;
            color: #CBD5E1;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .slide-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .slide-btn:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .slide-img-preview {
            position: relative;
            z-index: 2;
            max-width: 320px;
            height: 200px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            background: rgba(255,255,255,0.85);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
        }
        .slider-arrow:hover { background: #fff; transform: translateY(-50%) scale(1.1); }
        .arrow-left { left: 16px; }
        .arrow-right { right: 16px; }
        .slider-dots {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 5;
            display: flex;
            gap: 8px;
        }
        .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.2s;
        }
        .slider-dot.active {
            background: var(--primary);
            width: 24px;
            border-radius: 999px;
        }

        /* Flash timer */
        .flash-bar {
            max-width: 1200px;
            margin: 20px auto 0;
            padding: 0 16px;
        }
        .flash-card {
            background: #FFF7ED;
            border: 1px solid #FFEDD5;
            border-radius: 14px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .flash-badge {
            font-size: 14px;
            font-weight: 800;
            color: #C2410C;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .countdown-timer {
            font-size: 13px;
            font-weight: 700;
            color: #9A3412;
            background: #FED7AA;
            padding: 4px 10px;
            border-radius: 6px;
        }

        /* Categories pills */
        .categories-nav {
            max-width: 1200px;
            margin: 24px auto 0;
            padding: 0 16px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .cat-pill {
            padding: 8px 18px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .cat-pill:hover, .cat-pill.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* Products Grid */
        .container {
            max-width: 1200px;
            margin: 24px auto 48px;
            padding: 0 16px;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.25s;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08);
            border-color: #CBD5E1;
        }
        .card-img-wrap {
            position: relative;
            height: 180px;
            background: #F1F5F9;
            overflow: hidden;
        }
        .card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .product-card:hover .card-img {
            transform: scale(1.05);
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--primary);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .card-body {
            padding: 14px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .card-cat {
            font-size: 11px;
            color: var(--text-sub);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }
        .price-box {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 12px;
        }
        .current-price {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
        }
        .cut-price {
            font-size: 12px;
            color: var(--text-sub);
            text-decoration: line-through;
        }
        .rating-box {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: var(--text-sub);
            margin-bottom: 14px;
        }
        .btn-card-buy {
            width: 100%;
            padding: 9px 0;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 6px;
        }
        .btn-card-buy:hover { background: var(--primary-hover); }
        .btn-whatsapp {
            width: 100%;
            padding: 7px 0;
            background: #25D366;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-whatsapp:hover { background: #1EBE5D; }

        /* Cart Drawer */
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
        }
        .cart-overlay.open { display: block; }
        .cart-drawer {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            max-width: 440px;
            background: #fff;
            z-index: 1001;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        .cart-drawer.open { transform: translateX(0); }
        .cart-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-body {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        .cart-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: #F8FAFC;
        }
        .close-drawer {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-sub);
        }

        /* Order Success Modal */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal.open { display: flex; }
        .modal-card {
            background: #fff;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            padding: 24px;
            text-align: center;
        }

        footer {
            background: #0F172A;
            color: #94A3B8;
            padding: 40px 20px 24px;
            font-size: 13px;
        }
        .footer-wrap {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #1E293B;
        }
        .footer-wrap h4 { color: #fff; margin-bottom: 14px; font-size: 15px; }
        .footer-wrap ul { list-style: none; }
        .footer-wrap li { margin-bottom: 8px; }
        .footer-wrap a { color: #94A3B8; text-decoration: none; }
        .footer-wrap a:hover { color: #fff; }
    </style>
</head>
<body>

<!-- Announcement Bar -->
<?php if (!empty($announcement)): ?>
    <div class="announcement-bar">
        <?php echo htmlspecialchars($announcement); ?>
    </div>
<?php endif; ?>

<!-- Header Navigation -->
<header class="header">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <span>🔥</span>
            <span><?php echo htmlspecialchars($siteName); ?></span>
        </a>

        <!-- Live Search -->
        <form method="GET" action="index.php" class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search wireless chargers, earbuds, shirts...">
        </form>

        <!-- Header Actions: Replaced Wishlist with Track Order! -->
        <div class="header-actions">
            <a href="track_order.php" class="btn-header btn-track">
                <span>📦</span>
                <span>Track Order</span>
            </a>

            <button onclick="openCartDrawer()" class="btn-header btn-cart">
                <span>🛒</span>
                <span>Cart (<span id="cart-badge-count">0</span>)</span>
            </button>
        </div>
    </div>
</header>

<!-- Hero Banners Carousel Slider -->
<section class="slider-section">
    <div class="slider-wrapper">
        <div class="slides-container" id="heroSlides">
            <?php foreach ($banners as $idx => $b): ?>
                <div class="slide" style="background-color: <?php echo htmlspecialchars($b['bg_color'] ?: '#0F172A'); ?>;">
                    <div class="slide-content">
                        <span class="slide-badge"><?php echo htmlspecialchars($b['badge_text'] ?: 'HOT DEAL'); ?></span>
                        <h2 class="slide-title"><?php echo htmlspecialchars($b['title']); ?></h2>
                        <p class="slide-subtitle"><?php echo htmlspecialchars($b['subtitle']); ?></p>
                        <a href="<?php echo htmlspecialchars($b['button_link'] ?: '#products'); ?>" class="slide-btn">
                            <?php echo htmlspecialchars($b['button_text'] ?: 'Shop Now'); ?> →
                        </a>
                    </div>
                    <?php if (!empty($b['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="Banner Image" class="slide-img-preview">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($banners) > 1): ?>
            <button class="slider-arrow arrow-left" onclick="prevSlide()">❮</button>
            <button class="slider-arrow arrow-right" onclick="nextSlide()">❯</button>

            <div class="slider-dots" id="sliderDots">
                <?php foreach ($banners as $idx => $b): ?>
                    <span class="slider-dot <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $idx; ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Flash Deals Countdown Bar -->
<section class="flash-bar">
    <div class="flash-card">
        <div class="flash-badge">
            <span>⚡ TODAY'S DEALS OF THE DAY</span>
            <span style="font-weight: 500; font-size: 12px; color: #7C2D12;">• Instant Delivery & Cash on Delivery Available</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12px; color: #7C2D12; font-weight: 600;">Sale Ends In:</span>
            <div class="countdown-timer" id="dealTimer">08h : 35m : 12s</div>
        </div>
    </div>
</section>

<!-- Category Filter Pills -->
<nav class="categories-nav">
    <a href="index.php" class="cat-pill <?php echo $catId === 0 ? 'active' : ''; ?>">🔥 All Deals</a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?cat=<?php echo $cat['id']; ?>" class="cat-pill <?php echo $catId === $cat['id'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- Products Grid Section -->
<main class="container" id="products">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 20px; font-weight: 800;">
            <?php echo !empty($search) ? 'Search Results for: "' . htmlspecialchars($search) . '"' : ($catId > 0 ? 'Category Deals' : 'Trending Hot Deals'); ?>
        </h2>
        <span style="font-size: 13px; color: var(--text-sub);"><?php echo count($products); ?> Products Available</span>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 18px; font-weight: 700;">No Products Found</h3>
            <p style="color: var(--text-sub); font-size: 14px; margin-top: 6px;">Try clearing search filters or browse our other categories.</p>
            <a href="index.php" class="slide-btn" style="margin-top: 18px;">View All Products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <?php
                $disc = 0;
                if ($p['original_price'] > $p['price']) {
                    $disc = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                }
                $cleanName = htmlspecialchars($p['name']);
                $waMsg = urlencode("Hello, I want to order *$cleanName* for $currency" . number_format($p['price'], 2) . " from $siteName");
                ?>
                <div class="product-card">
                    <div class="card-img-wrap">
                        <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'); ?>" alt="<?php echo $cleanName; ?>" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'">
                        <?php if ($disc > 0): ?>
                            <span class="discount-badge">-<?php echo $disc; ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <span class="card-cat"><?php echo htmlspecialchars($p['category_name'] ?? 'Deals'); ?></span>
                        <h3 class="card-title"><?php echo $cleanName; ?></h3>

                        <div class="price-box">
                            <span class="current-price"><?php echo $currency; ?><?php echo number_format($p['price'], 2); ?></span>
                            <?php if ($p['original_price'] > $p['price']): ?>
                                <span class="cut-price"><?php echo $currency; ?><?php echo number_format($p['original_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="rating-box">
                            <span style="color: #F59E0B;">⭐ <?php echo $p['rating']; ?></span>
                            <span>(<?php echo $p['reviews_count']; ?> reviews)</span>
                        </div>

                        <button class="btn-card-buy" onclick="addToCart(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                            ⚡ Buy / Add to Cart
                        </button>

                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>?text=<?php echo $waMsg; ?>" target="_blank" class="btn-whatsapp">
                            💬 Order on WhatsApp
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Slide-Out Cart & Instant Checkout Drawer -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <h3 style="font-size: 17px; font-weight: 800;">🛍️ Your Shopping Cart</h3>
        <button class="close-drawer" onclick="closeCartDrawer()">✕</button>
    </div>

    <div class="cart-body" id="cartItemList">
        <!-- Rendered via JS -->
    </div>

    <div class="cart-footer">
        <!-- Coupon input -->
        <div style="display: flex; gap: 8px; margin-bottom: 12px;">
            <input type="text" id="couponInput" placeholder="Promo Code (ALIXDEAL50)" style="flex: 1; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; text-transform: uppercase;">
            <button onclick="applyCoupon()" style="padding: 8px 14px; background: #0F172A; color: #fff; font-weight: 700; font-size: 12px; border-radius: 8px; border: none; cursor: pointer;">Apply</button>
        </div>
        <div id="couponMsg" style="font-size: 11px; margin-bottom: 10px;"></div>

        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-sub); margin-bottom: 4px;">
            <span>Subtotal:</span>
            <span id="cartSubtotal"><?php echo $currency; ?>0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #10B981; margin-bottom: 4px;" id="discountRow">
            <span>Coupon Discount:</span>
            <span id="cartDiscount">-<?php echo $currency; ?>0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--text-sub); margin-bottom: 8px;">
            <span>Delivery:</span>
            <span style="color: #10B981; font-weight: 700;">FREE DELIVERY</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; border-top: 1px dashed var(--border); padding-top: 8px; margin-bottom: 14px;">
            <span>Total Payable:</span>
            <span style="color: var(--primary);" id="cartTotal"><?php echo $currency; ?>0.00</span>
        </div>

        <button onclick="showCheckoutFields()" id="btnProceedCheckout" style="width: 100%; padding: 12px 0; background: var(--primary); color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 10px; cursor: pointer;">
            Proceed to Checkout →
        </button>

        <!-- Checkout Form (Hidden until clicked) -->
        <div id="checkoutFormBlock" style="display: none; margin-top: 14px; border-top: 1px solid var(--border); padding-top: 14px;">
            <h4 style="font-size: 14px; font-weight: 800; margin-bottom: 10px;">Delivery & Contact Details</h4>
            <input type="text" id="custName" placeholder="Full Name *" style="width: 100%; padding: 8px 12px; margin-bottom: 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            <input type="tel" id="custPhone" placeholder="10-digit Mobile Number *" style="width: 100%; padding: 8px 12px; margin-bottom: 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            <textarea id="custAddress" placeholder="Complete Street Address, Landmark *" rows="2" style="width: 100%; padding: 8px 12px; margin-bottom: 8px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit;"></textarea>
            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                <input type="text" id="custCity" placeholder="City *" style="flex: 1; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                <input type="text" id="custPincode" placeholder="PIN Code *" style="width: 110px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
            </div>

            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">Select Payment Mode</h4>
            <div style="display: flex; gap: 10px; margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <input type="radio" name="payMethod" value="Cash On Delivery" checked> Cash on Delivery (COD)
                </label>
                <label style="font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <input type="radio" name="payMethod" value="Instant UPI"> Instant UPI
                </label>
            </div>

            <button onclick="submitOrder()" style="width: 100%; padding: 12px 0; background: #10B981; color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 10px; cursor: pointer;">
                ✅ Confirm & Place Order Now
            </button>
        </div>
    </div>
</div>

<!-- Order Success Modal -->
<div class="modal" id="successModal">
    <div class="modal-card">
        <div style="font-size: 54px; margin-bottom: 10px;">🎉</div>
        <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">Order Placed Successfully!</h2>
        <p style="color: var(--text-sub); font-size: 14px; margin-bottom: 16px;">Thank you! Your order has been placed and our team is preparing it for dispatch.</p>

        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 12px; color: var(--text-sub);">YOUR ORDER ID:</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--primary);" id="confirmedOrderNum">ORD-XXXXXX</div>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="track_order.php" class="slide-btn" style="flex: 1; text-align: center; justify-content: center;">Track Order Status</a>
            <button onclick="closeSuccessModal()" style="padding: 12px 20px; background: #F1F5F9; color: var(--text-main); font-weight: 700; border-radius: 10px; border: none; cursor: pointer;">Continue Shopping</button>
        </div>
    </div>
</div>

<footer>
    <div class="footer-wrap">
        <div>
            <div class="logo" style="margin-bottom: 12px; color: #fff;">🔥 <?php echo htmlspecialchars($siteName); ?></div>
            <p style="line-height: 1.6;"><?php echo htmlspecialchars($settings['site_description'] ?? 'Exclusive daily deals on top trending products across India.'); ?></p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Browse Hot Deals</a></li>
                <li><a href="track_order.php">Track My Order</a></li>
                <li><a href="admin/login.php" target="_blank">Admin Control Panel</a></li>
            </ul>
        </div>
        <div>
            <h4>Customer Support</h4>
            <ul>
                <li>Email: <?php echo htmlspecialchars($settings['contact_email'] ?? 'support@alixdeal.shop'); ?></li>
                <li>Phone: <?php echo htmlspecialchars($settings['contact_phone'] ?? '+91 98765 43210'); ?></li>
                <li>WhatsApp: <?php echo htmlspecialchars($whatsapp); ?></li>
                <li>Location: <?php echo htmlspecialchars($settings['address'] ?? 'India'); ?></li>
            </ul>
        </div>
    </div>
    <div style="max-width: 1200px; margin: 20px auto 0; text-align: center; font-size: 12px;">
        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
    </div>
</footer>

<!-- Custom Footer Scripts -->
<?php if (!empty($settings['footer_scripts'])): ?>
    <?php echo $settings['footer_scripts']; ?>
<?php endif; ?>

<script>
    // Currency
    const currency = '<?php echo $currency; ?>';

    // Hero Slider JS
    let currentSlide = 0;
    const slidesCount = <?php echo count($banners); ?>;
    const slidesContainer = document.getElementById('heroSlides');
    const dots = document.querySelectorAll('.slider-dot');

    function updateSlider() {
        if (!slidesContainer) return;
        slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === currentSlide);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slidesCount;
        updateSlider();
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slidesCount) % slidesCount;
        updateSlider();
    }

    function goToSlide(idx) {
        currentSlide = idx;
        updateSlider();
    }

    // Auto rotate slides every 4.5 seconds
    if (slidesCount > 1) {
        setInterval(nextSlide, 4500);
    }

    // Countdown Timer JS (24h loop)
    function updateCountdown() {
        const timerEl = document.getElementById('dealTimer');
        if (!timerEl) return;
        const now = new Date();
        const midnight = new Date(now);
        midnight.setHours(24, 0, 0, 0);
        let diff = Math.floor((midnight - now) / 1000);
        let h = String(Math.floor(diff / 3600)).padStart(2, '0');
        let m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
        let s = String(diff % 60).padStart(2, '0');
        timerEl.textContent = `${h}h : ${m}m : ${s}s`;
    }
    setInterval(updateCountdown, 1000);
    updateCountdown();

    // Cart State & Drawer JS
    let cart = JSON.parse(localStorage.getItem('alixdeal_cart') || '[]');
    let discountPercent = 0;

    function saveCart() {
        localStorage.setItem('alixdeal_cart', JSON.stringify(cart));
        renderCart();
    }

    function addToCart(product) {
        const existing = cart.find(it => it.id === product.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image_url,
                color: (product.colors ? product.colors.split(',')[0].trim() : 'Standard'),
                size: (product.sizes ? product.sizes.split(',')[0].trim() : 'Standard'),
                quantity: 1
            });
        }
        saveCart();
        openCartDrawer();
    }

    function updateQty(id, delta) {
        const item = cart.find(it => it.id === id);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                cart = cart.filter(it => it.id !== id);
            }
        }
        saveCart();
    }

    function openCartDrawer() {
        document.getElementById('cartOverlay').classList.add('open');
        document.getElementById('cartDrawer').classList.add('open');
        renderCart();
    }

    function closeCartDrawer() {
        document.getElementById('cartOverlay').classList.remove('open');
        document.getElementById('cartDrawer').classList.remove('open');
    }

    function renderCart() {
        const listEl = document.getElementById('cartItemList');
        const badgeCount = document.getElementById('cart-badge-count');
        const totalItems = cart.reduce((acc, it) => acc + it.quantity, 0);
        if (badgeCount) badgeCount.textContent = totalItems;

        if (cart.length === 0) {
            listEl.innerHTML = `
                <div style="text-align: center; padding: 48px 16px; color: var(--text-sub);">
                    <div style="font-size: 40px; margin-bottom: 8px;">🛒</div>
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--text-main);">Your Cart is Empty</h4>
                    <p style="font-size: 13px; margin-top: 4px;">Explore our daily deals and tap "Buy / Add to Cart" on any product.</p>
                </div>
            `;
            document.getElementById('cartSubtotal').textContent = `${currency}0.00`;
            document.getElementById('cartDiscount').textContent = `-${currency}0.00`;
            document.getElementById('cartTotal').textContent = `${currency}0.00`;
            return;
        }

        let subtotal = 0;
        let html = '';
        cart.forEach(item => {
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;
            html += `
                <div style="display: flex; gap: 12px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                    <img src="${item.image || 'https://via.placeholder.com/60'}" style="width: 58px; height: 58px; border-radius: 8px; object-fit: cover;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 2px;">${item.name}</h4>
                        <div style="font-size: 11px; color: var(--text-sub);">${item.color} • ${item.size}</div>
                        <div style="font-size: 13px; font-weight: 800; color: var(--primary); margin-top: 4px;">${currency}${item.price.toFixed(2)}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button onclick="updateQty('${item.id}', -1)" style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid var(--border); background: #fff; cursor: pointer;">-</button>
                        <span style="font-size: 13px; font-weight: 700;">${item.quantity}</span>
                        <button onclick="updateQty('${item.id}', 1)" style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid var(--border); background: #fff; cursor: pointer;">+</button>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
        const discountAmount = subtotal * (discountPercent / 100);
        const total = subtotal - discountAmount;

        document.getElementById('cartSubtotal').textContent = `${currency}${subtotal.toFixed(2)}`;
        document.getElementById('cartDiscount').textContent = `-${currency}${discountAmount.toFixed(2)}`;
        document.getElementById('cartTotal').textContent = `${currency}${total.toFixed(2)}`;
    }

    function applyCoupon() {
        const code = document.getElementById('couponInput').value.trim().toUpperCase();
        const msgEl = document.getElementById('couponMsg');
        if (code === 'ALIXDEAL50') {
            discountPercent = 50;
            msgEl.style.color = '#10B981';
            msgEl.textContent = '🎉 Coupon ALIXDEAL50 Applied! 50% discount given.';
        } else if (code === 'SAVE100') {
            discountPercent = 20;
            msgEl.style.color = '#10B981';
            msgEl.textContent = '🎉 Coupon SAVE100 Applied! 20% discount given.';
        } else {
            msgEl.style.color = '#EF4444';
            msgEl.textContent = '❌ Invalid coupon. Try ALIXDEAL50';
        }
        renderCart();
    }

    function showCheckoutFields() {
        if (cart.length === 0) {
            alert('Your cart is empty. Please add products first!');
            return;
        }
        document.getElementById('checkoutFormBlock').style.display = 'block';
        document.getElementById('btnProceedCheckout').style.display = 'none';
    }

    async function submitOrder() {
        const name = document.getElementById('custName').value.trim();
        const phone = document.getElementById('custPhone').value.trim();
        const address = document.getElementById('custAddress').value.trim();
        const city = document.getElementById('custCity').value.trim();
        const pin = document.getElementById('custPincode').value.trim();
        const payMethod = document.querySelector('input[name="payMethod"]:checked').value;

        if (!name || !phone || !address || !city) {
            alert('Please fill all delivery details (Name, Phone, Address, City)!');
            return;
        }

        const orderData = {
            customer_name: name,
            customer_phone: phone,
            customer_email: name.toLowerCase().replace(/\s+/g, '') + '@customer.com',
            shipping_address: address,
            city: city,
            postal_code: pin,
            payment_method: payMethod,
            items: cart
        };

        try {
            const resp = await fetch('api/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });
            const result = await resp.json();

            if (result.status === 'success') {
                document.getElementById('confirmedOrderNum').textContent = result.order_number;
                cart = [];
                saveCart();
                closeCartDrawer();
                document.getElementById('successModal').classList.add('open');
            } else {
                alert(result.message || 'Error placing order');
            }
        } catch (e) {
            alert('Could not submit order: ' + e.message);
        }
    }

    function closeSuccessModal() {
        document.getElementById('successModal').classList.remove('open');
    }

    // Initialize cart badge
    renderCart();
</script>

</body>
</html>
