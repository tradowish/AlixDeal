<?php
/**
 * AlixDeal Shopping - Advanced Responsive Storefront
 */
session_start();
$rootDir = __DIR__;
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    header("Location: install/index.php");
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

// Current logged in customer user
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmtU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtU->execute([$_SESSION['user_id']]);
    $currentUser = $stmtU->fetch();
}

// Fetch settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['key_name']] = $row['value'];
}
$siteName = $settings['site_name'] ?? 'Alixdeal';
$currency = $settings['currency_symbol'] ?? '₹';
$whatsapp = $settings['whatsapp_number'] ?? '+917351150482';
$announcement = $settings['announcement_text'] ?? '⚡ Special Festive Offer: Free Delivery All Over India + 50% Off With Code ALIXDEAL50!';

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
        ],
        [
            'id' => 2,
            'title' => 'Smart Electronics Extravaganza',
            'subtitle' => 'Top Rated 3-in-1 Fast Wireless Chargers, TWS Earbuds & Smartwatches up to 70% OFF.',
            'badge_text' => 'BESTSELLERS',
            'button_text' => 'Explore Gadgets',
            'button_link' => '#products',
            'image_url' => 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?w=700&q=80',
            'bg_color' => '#1E1B4B'
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

    <!-- Razorpay Checkout Script if enabled -->
    <?php if (($settings['razorpay_enabled'] ?? '0') === '1' && !empty($settings['razorpay_key_id'])): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>

    <!-- Custom Header Scripts -->
    <?php if (!empty($settings['header_scripts'])): ?>
        <?php echo $settings['header_scripts']; ?>
    <?php endif; ?>

    <!-- SweetAlert2 for Smooth Alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
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
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        /* Header Navigation */
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
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        /* Mobile Hamburger Button */
        .hamburger-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-main);
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: -0.5px;
        }
        .search-box {
            flex: 1;
            max-width: 440px;
            position: relative;
        }
        .search-box input {
            width: 100%;
            padding: 9px 14px 9px 36px;
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-sub);
            font-size: 13px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-header {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            white-space: nowrap;
        }
        .btn-track {
            background: #EEF2FF;
            color: var(--primary);
        }
        .btn-cart {
            background: var(--primary);
            color: #fff;
        }

        /* --- PREMIUM MOBILE SLIDER DRAWER NAVIGATION --- */
        .mobile-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .mobile-drawer-overlay.open {
            display: block;
            opacity: 1;
        }
        .mobile-drawer {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 310px;
            max-width: 85vw;
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            color: #fff;
            z-index: 9999;
            transform: translateX(-100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 10px 0 35px rgba(0, 0, 0, 0.4);
        }
        .mobile-drawer.open { transform: translateX(0); }
        .drawer-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.85);
        }
        .drawer-header .logo {
            color: #fff;
            font-size: 20px;
            font-weight: 800;
        }
        .drawer-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #94A3B8;
            cursor: pointer;
            transition: all 0.2s;
        }
        .drawer-close:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #EF4444;
        }
        .drawer-body {
            padding: 18px 14px;
            flex: 1;
            overflow-y: auto;
        }
        .drawer-user-card {
            background: linear-gradient(135deg, rgba(255, 87, 34, 0.15), rgba(255, 152, 0, 0.15));
            border: 1px solid rgba(255, 87, 34, 0.3);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 18px;
        }
        .drawer-user-card .badge-auth {
            font-size: 10px;
            font-weight: 800;
            color: #FF7043;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .drawer-user-card .user-name {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            margin-top: 2px;
        }
        .drawer-user-card .user-wallet {
            font-size: 12px;
            color: #34D399;
            font-weight: 700;
            margin-top: 3px;
        }
        .drawer-auth-btn {
            width: 100%;
            background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%);
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
            border: none;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 16px rgba(255, 87, 34, 0.35);
            transition: all 0.2s;
            margin-bottom: 18px;
        }
        .drawer-auth-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(255, 87, 34, 0.45);
        }
        .drawer-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: #CBD5E1;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .drawer-link:hover, .drawer-link.active {
            background: rgba(255, 255, 255, 0.08);
            color: #FF7043;
        }
        .drawer-category-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 14px 6px;
        }

        /* --- RESPONSIVE HERO SLIDER (100% CONTAINED ON MOBILE) --- */
        .slider-section {
            max-width: 1200px;
            margin: 16px auto 0;
            padding: 0 16px;
            width: 100%;
        }
        .slider-wrapper {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            width: 100%;
            background: #0F172A;
        }
        .slides-container {
            display: flex;
            transition: transform 0.5s ease-in-out;
            width: 100%;
        }
        .slide {
            min-width: 100%;
            max-width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 32px 36px;
            color: #fff;
            position: relative;
            box-sizing: border-box;
        }
        .slide-content {
            position: relative;
            z-index: 2;
            max-width: 520px;
            width: 100%;
        }
        .slide-badge {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .slide-title {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 8px;
            word-break: break-word;
        }
        .slide-subtitle {
            font-size: 13px;
            color: #CBD5E1;
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .slide-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .slide-btn:hover { background: var(--primary-hover); }
        .slide-img-preview {
            position: relative;
            z-index: 2;
            width: 280px;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            flex-shrink: 0;
            margin-left: 20px;
        }
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            background: rgba(255,255,255,0.85);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }
        .arrow-left { left: 12px; }
        .arrow-right { right: 12px; }
        .slider-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 5;
            display: flex;
            gap: 6px;
        }
        .slider-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            cursor: pointer;
            transition: all 0.2s;
        }
        .slider-dot.active {
            background: var(--primary);
            width: 20px;
            border-radius: 999px;
        }

        /* Mobile specific fixes for Hero Slider */
        @media (max-width: 768px) {
            .slide {
                flex-direction: column;
                align-items: flex-start;
                padding: 22px 18px 36px;
                text-align: left;
            }
            .slide-title {
                font-size: 20px;
                margin-bottom: 6px;
            }
            .slide-subtitle {
                font-size: 12px;
                margin-bottom: 14px;
            }
            .slide-img-preview {
                width: 100%;
                max-width: 100%;
                height: 140px;
                margin-left: 0;
                margin-top: 14px;
            }
            .slider-arrow { display: none; }
            .search-box { display: none; }
            .btn-track { display: none; }
        }

        /* Flash timer */
        .flash-bar {
            max-width: 1200px;
            margin: 16px auto 0;
            padding: 0 16px;
        }
        .flash-card {
            background: #FFF7ED;
            border: 1px solid #FFEDD5;
            border-radius: 12px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .flash-badge {
            font-size: 12px;
            font-weight: 800;
            color: #C2410C;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .countdown-timer {
            font-size: 12px;
            font-weight: 700;
            color: #9A3412;
            background: #FED7AA;
            padding: 3px 8px;
            border-radius: 6px;
        }

        /* Categories pills */
        .categories-nav {
            max-width: 1200px;
            margin: 18px auto 0;
            padding: 0 16px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .categories-nav::-webkit-scrollbar { display: none; }
        .cat-pill {
            padding: 7px 16px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-main);
            font-size: 12px;
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
            margin: 20px auto 40px;
            padding: 0 16px;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        .product-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.25s;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 16px -4px rgba(0,0,0,0.08);
        }
        .card-img-wrap {
            position: relative;
            height: 150px;
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
            top: 8px;
            left: 8px;
            background: var(--primary);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .card-body {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .card-cat {
            font-size: 10px;
            color: var(--text-sub);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 35px;
        }
        .price-box {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 8px;
        }
        .current-price {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .cut-price {
            font-size: 11px;
            color: var(--text-sub);
            text-decoration: line-through;
        }
        .btn-card-buy {
            width: 100%;
            padding: 8px 0;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            margin-bottom: 6px;
        }
        .btn-whatsapp {
            width: 100%;
            padding: 6px 0;
            background: #25D366;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        /* --- PREMIUM CART & CHECKOUT DRAWER --- */
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .cart-overlay.open { display: block; opacity: 1; }
        .cart-drawer {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            max-width: 450px;
            background: #FFFFFF;
            z-index: 1001;
            transform: translateX(100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.25);
        }
        .cart-drawer.open { transform: translateX(0); }
        .cart-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFFFFF;
        }
        .cart-header h3 {
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cart-free-shipping-bar {
            background: linear-gradient(90deg, #ECFDF5 0%, #D1FAE5 100%);
            border-bottom: 1px solid #A7F3D0;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 700;
            color: #065F46;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cart-body {
            padding: 16px;
            flex: 1;
            overflow-y: auto;
            background: #F8FAFC;
        }
        .cart-item-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            transition: all 0.2s;
        }
        .cart-item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .cart-item-card img {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--border);
        }
        .cart-footer {
            padding: 18px 20px;
            border-top: 1px solid var(--border);
            background: #FFFFFF;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.04);
        }
        .close-drawer {
            background: #F1F5F9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            color: var(--text-sub);
            transition: all 0.2s;
        }
        .close-drawer:hover {
            background: #FEE2E2;
            color: #DC2626;
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
            max-width: 460px;
            width: 100%;
            padding: 24px;
            text-align: center;
        }

        /* Floating WhatsApp Chat Button */
        .floating-wa-btn {
            position: fixed;
            bottom: 24px;
            right: 20px;
            background: #25D366;
            color: #fff;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 16px rgba(37,211,102,0.4);
            z-index: 99;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .floating-wa-btn:hover { transform: scale(1.1); }

        footer {
            background: #0F172A;
            color: #94A3B8;
            padding: 36px 16px 20px;
            font-size: 13px;
        }
        .footer-wrap {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #1E293B;
        }
        .footer-wrap h4 { color: #fff; margin-bottom: 12px; font-size: 14px; }
        .footer-wrap ul { list-style: none; }
        .footer-wrap li { margin-bottom: 6px; }
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
        <div class="nav-left">
            <!-- 3-line Mobile Hamburger Button -->
            <button class="hamburger-btn" onclick="toggleMobileDrawer()" title="Menu">☰</button>

            <a href="index.php" class="logo">
                <span>🔥</span>
                <span><?php echo htmlspecialchars($siteName); ?></span>
            </a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="index.php" class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search gadgets, earbuds, fashion...">
        </form>

        <!-- Header Actions: Track Order & Cart -->
        <div class="header-actions">
            <a href="track_order.php" class="btn-header btn-track">
                <span>📦</span>
                <span>Track Order</span>
            </a>

            <button onclick="openCartDrawer()" class="btn-header btn-cart" id="headerCartBtn">
                <span>🛒</span>
                <span>Cart (<span id="cart-badge-count">0</span>)</span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Navigation Slide Drawer -->
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay" onclick="toggleMobileDrawer()"></div>
<aside class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <div class="logo">
            <span>🔥</span>
            <span><?php echo htmlspecialchars($siteName); ?></span>
        </div>
        <button class="drawer-close" onclick="toggleMobileDrawer()" aria-label="Close menu">✕</button>
    </div>
    <div class="drawer-body">
        <?php if ($currentUser): ?>
            <div class="drawer-user-card">
                <div class="badge-auth">🌟 Verified Customer</div>
                <div class="user-name">👋 <?php echo htmlspecialchars($currentUser['name']); ?></div>
                <div class="user-wallet">💰 Wallet Balance: ₹<?php echo number_format($currentUser['wallet_balance'], 2); ?></div>
                <div style="display: flex; gap: 8px; margin-top: 10px;">
                    <a href="my_account.php" style="font-size: 11px; padding: 6px 12px; background: #FF5722; color:#fff; border-radius:8px; text-decoration:none; font-weight:700;">💳 My Account & Orders</a>
                    <button onclick="handleLogout()" style="font-size: 11px; padding: 6px 12px; background: rgba(255,255,255,0.15); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Sign Out</button>
                </div>
            </div>
        <?php else: ?>
            <div class="drawer-user-card">
                <div class="badge-auth">🎁 Exclusive New User Offer</div>
                <div class="user-name" style="font-size: 14px;">Register & Claim ₹50 Bonus</div>
                <div style="font-size: 12px; color: #CBD5E1; margin-top: 4px; line-height: 1.4;">Sign in to unlock exclusive wallet discounts and track doorstep deliveries.</div>
                <button onclick="toggleMobileDrawer(); openAuthModal('signup');" class="drawer-auth-btn" style="margin-top: 12px; margin-bottom: 0;">
                    <span>👤</span>
                    <span>Sign In / Register (+₹50)</span>
                </button>
            </div>
        <?php endif; ?>

        <form method="GET" action="index.php" style="margin-bottom: 14px;">
            <input type="text" name="q" placeholder="🔍 Search products..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
        </form>

        <a href="index.php" class="drawer-link <?php echo $catId === 0 ? 'active' : ''; ?>">🔥 All Hot Deals</a>
        <a href="track_order.php" class="drawer-link">📦 Track My Orders</a>
        <?php if ($currentUser): ?>
            <a href="my_account.php" class="drawer-link">💳 My Cash Wallet & Orders</a>
        <?php endif; ?>

        <div class="drawer-category-title">Categories</div>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?cat=<?php echo $cat['id']; ?>" class="drawer-link <?php echo $catId === $cat['id'] ? 'active' : ''; ?>">
                📁 <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>

        <div class="drawer-category-title">Help & Admin</div>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>" target="_blank" class="drawer-link" style="color: #25D366;">
            💬 WhatsApp Support
        </a>
        <a href="admin/login.php" target="_blank" class="drawer-link">
            ⚙️ Admin Panel
        </a>
    </div>
</aside>

<!-- Hero Banners Carousel Slider (100% Contained On Mobile) -->
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
                        <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="Banner Preview" class="slide-img-preview" onerror="this.style.display='none'">
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
            <span style="font-weight: 500; font-size: 11px; color: #7C2D12;">• Doorstep Delivery Across India</span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 11px; color: #7C2D12; font-weight: 600;">Ends In:</span>
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 18px; font-weight: 800;">
            <?php echo !empty($search) ? 'Search: "' . htmlspecialchars($search) . '"' : ($catId > 0 ? 'Category Deals' : 'Trending Hot Deals'); ?>
        </h2>
        <span style="font-size: 12px; color: var(--text-sub);"><?php echo count($products); ?> Items</span>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 48px 16px; background: #fff; border-radius: 14px; border: 1px solid var(--border);">
            <div style="font-size: 40px; margin-bottom: 10px;">🔍</div>
            <h3 style="font-size: 16px; font-weight: 700;">No Products Found</h3>
            <p style="color: var(--text-sub); font-size: 13px; margin-top: 4px;">Try searching for another gadget or view all deals.</p>
            <a href="index.php" class="slide-btn" style="margin-top: 14px;">View All Products</a>
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
                    <div class="card-img-wrap" onclick="window.location.href='product.php?id=<?php echo $p['id']; ?>'" style="cursor: pointer;" title="View Product Details">
                        <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'); ?>" alt="<?php echo $cleanName; ?>" class="card-img" onerror="this.src='https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'">
                        <?php if ($disc > 0): ?>
                            <span class="discount-badge">-<?php echo $disc; ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <span class="card-cat"><?php echo htmlspecialchars($p['category_name'] ?? 'Deals'); ?></span>
                        <h3 class="card-title" onclick="window.location.href='product.php?id=<?php echo $p['id']; ?>'" style="cursor: pointer;" title="View Product Details"><?php echo $cleanName; ?></h3>

                        <div class="price-box">
                            <span class="current-price"><?php echo $currency; ?><?php echo number_format($p['price'], 2); ?></span>
                            <?php if ($p['original_price'] > $p['price']): ?>
                                <span class="cut-price"><?php echo $currency; ?><?php echo number_format($p['original_price'], 2); ?></span>
                            <?php endif; ?>
                        </div>

                        <button class="btn-card-buy" onclick="addToCart(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                            ⚡ Buy / Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Slide-Out Cart & Multi-Gateway Checkout Drawer -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <h3 style="font-size: 16px; font-weight: 800;">🛍️ Your Shopping Cart</h3>
        <button class="close-drawer" onclick="closeCartDrawer()">✕</button>
    </div>

    <div class="cart-body" id="cartItemList">
        <!-- Rendered via JS -->
    </div>

    <div class="cart-footer">
        <!-- Promo Coupon -->
        <div style="display: flex; gap: 8px; margin-bottom: 10px;">
            <input type="text" id="couponInput" placeholder="Promo Code (ALIXDEAL50)" style="flex: 1; padding: 7px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 12px; text-transform: uppercase;">
            <button onclick="applyCoupon()" style="padding: 7px 12px; background: #0F172A; color: #fff; font-weight: 700; font-size: 12px; border-radius: 8px; border: none; cursor: pointer;">Apply</button>
        </div>
        <div id="couponMsg" style="font-size: 11px; margin-bottom: 8px;"></div>

        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-sub); margin-bottom: 4px;">
            <span>Subtotal:</span>
            <span id="cartSubtotal"><?php echo $currency; ?>0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #10B981; margin-bottom: 4px;" id="discountRow">
            <span>Coupon Discount:</span>
            <span id="cartDiscount">-<?php echo $currency; ?>0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-sub); margin-bottom: 6px;">
            <span>Delivery:</span>
            <span style="color: #10B981; font-weight: 700;">FREE DELIVERY</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; border-top: 1px dashed var(--border); padding-top: 8px; margin-bottom: 12px;">
            <span>Total Payable:</span>
            <span style="color: var(--primary);" id="cartTotal"><?php echo $currency; ?>0.00</span>
        </div>

        <button onclick="showCheckoutFields()" id="btnProceedCheckout" style="width: 100%; padding: 11px 0; background: var(--primary); color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 8px; cursor: pointer;">
            Proceed to Checkout →
        </button>

        <!-- Checkout Form Block -->
        <div id="checkoutFormBlock" style="display: none; margin-top: 12px; border-top: 1px solid var(--border); padding-top: 12px;">
            <h4 style="font-size: 13px; font-weight: 800; margin-bottom: 8px;">Delivery Details</h4>
            <input type="text" id="custName" placeholder="Full Name *" style="width: 100%; padding: 7px 10px; margin-bottom: 6px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
            <input type="tel" id="custPhone" placeholder="10-digit Mobile Number *" style="width: 100%; padding: 7px 10px; margin-bottom: 6px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
            <textarea id="custAddress" placeholder="Full Address, Landmark *" rows="2" style="width: 100%; padding: 7px 10px; margin-bottom: 6px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px; font-family: inherit;"></textarea>
            <?php if ($currentUser && $currentUser['wallet_balance'] > 0): ?>
                <div style="background: #ECFDF5; border: 1px solid #A7F3D0; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #065F46; cursor: pointer;">
                        <input type="checkbox" id="useWalletCheck" onchange="toggleWalletDeduction(this)">
                        💰 Use Wallet Balance (Available: ₹<?php echo number_format($currentUser['wallet_balance'], 2); ?>)
                    </label>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                <input type="text" id="custCity" placeholder="City *" style="flex: 1; padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                <input type="text" id="custPincode" placeholder="PIN Code *" style="width: 100px; padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
            </div>

            <!-- Indian Payment Gateways Selector -->
            <h4 style="font-size: 12px; font-weight: 700; margin-bottom: 6px;">Select Payment Mode:</h4>
            <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; background: #fff; padding: 8px; border: 1px solid var(--border); border-radius: 8px;">
                <?php if (($settings['cod_enabled'] ?? '1') === '1'): ?>
                    <label style="font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="payMethod" value="Cash On Delivery" checked onchange="togglePaymentQR(false)">
                        💵 Cash on Delivery (Pay at Doorstep)
                    </label>
                <?php endif; ?>

                <?php if (($settings['upi_enabled'] ?? '1') === '1'): ?>
                    <label style="font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="payMethod" value="Direct UPI" onchange="togglePaymentQR(true, 'upi')">
                        ⚡ Direct UPI (GPay, PhonePe, Paytm, BHIM)
                    </label>
                <?php endif; ?>

                <?php if (($settings['bharatpe_enabled'] ?? '1') === '1'): ?>
                    <label style="font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="payMethod" value="BharatPe QR" onchange="togglePaymentQR(true, 'bharatpe')">
                        🇮🇳 BharatPe Merchant QR
                    </label>
                <?php endif; ?>

                <?php if (($settings['razorpay_enabled'] ?? '0') === '1'): ?>
                    <label style="font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <input type="radio" name="payMethod" value="Razorpay" onchange="togglePaymentQR(false)">
                        💳 Razorpay (Credit/Debit Card, NetBanking)
                    </label>
                <?php endif; ?>
            </div>

            <!-- Dynamic QR Payment View -->
            <div id="qrPaymentBox" style="display: none; background: #FEF3C7; border: 1px solid #FDE68A; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: 700; color: #92400E; margin-bottom: 6px;" id="qrBoxTitle">Scan & Pay via any UPI App</div>
                <img id="dynamicQRImg" src="" style="width: 120px; height: 120px; margin: 0 auto; display: block; border-radius: 6px; background: #fff; padding: 4px;">
                <div style="margin-top: 6px;">
                    <a id="upiDeepLinkBtn" href="#" class="btn btn-sm" style="background: #0284C7; font-size: 11px;">🚀 Pay via UPI App (PhonePe / GPay)</a>
                </div>
            </div>

            <button onclick="submitOrder()" style="width: 100%; padding: 11px 0; background: #10B981; color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 8px; cursor: pointer;">
                ✅ Confirm & Place Order Now
            </button>
        </div>
    </div>
</div>

<!-- Order Success Modal -->
<div class="modal" id="successModal">
    <div class="modal-card">
        <div style="font-size: 48px; margin-bottom: 8px;">🎉</div>
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 6px;">Order Placed Successfully!</h2>
        <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 14px;">Your order has been recorded. Our team will verify and dispatch it promptly.</p>

        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 10px; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: var(--text-sub);">YOUR ORDER ID:</div>
            <div style="font-size: 18px; font-weight: 800; color: var(--primary);" id="confirmedOrderNum">ORD-XXXXXX</div>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="track_order.php" class="slide-btn" style="flex: 1; text-align: center; justify-content: center; font-size: 12px;">Track Order Status</a>
            <button onclick="closeSuccessModal()" style="padding: 10px 16px; background: #F1F5F9; color: var(--text-main); font-weight: 700; border-radius: 8px; border: none; cursor: pointer; font-size: 12px;">Continue Shopping</button>
        </div>
    </div>
</div>

<!-- User Auth Modal (Login / Signup) -->
<div id="authModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 10000; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(4px);">
    <div style="background: #fff; border-radius: 20px; max-width: 400px; width: 100%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); position: relative;">
        <button onclick="closeAuthModal()" style="position: absolute; right: 16px; top: 16px; background: none; border: none; font-size: 20px; color: var(--text-sub); cursor: pointer;">✕</button>

        <!-- Tabs -->
        <div style="display: flex; gap: 6px; background: #F1F5F9; padding: 4px; border-radius: 12px; margin-bottom: 18px;">
            <button id="authTabLogin" onclick="switchAuthTab('login')" style="flex: 1; padding: 8px; font-weight: 700; font-size: 13px; border-radius: 10px; border: none; background: #fff; color: var(--primary); cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">Sign In</button>
            <button id="authTabSignup" onclick="switchAuthTab('signup')" style="flex: 1; padding: 8px; font-weight: 700; font-size: 13px; border-radius: 10px; border: none; background: transparent; color: var(--text-sub); cursor: pointer;">Register (+₹50)</button>
        </div>

        <!-- Login Form -->
        <div id="authLoginForm">
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 4px;">Welcome Back! 👋</h3>
            <p style="color: var(--text-sub); font-size: 12px; margin-bottom: 16px;">Sign in with your mobile number to access your orders and cash wallet.</p>

            <form onsubmit="handleLoginSubmit(event)">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Mobile Number *</label>
                    <input type="tel" id="loginPhone" placeholder="10-digit mobile number" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Password *</label>
                    <input type="password" id="loginPassword" placeholder="Enter password" required style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <button type="submit" id="loginSubmitBtn" style="width: 100%; padding: 11px; background: var(--primary); color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 8px; cursor: pointer;">
                    Sign In →
                </button>
            </form>
        </div>

        <!-- Signup Form -->
        <div id="authSignupForm" style="display: none;">
            <div style="display: inline-block; background: #ECFDF5; color: #065F46; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; margin-bottom: 6px;">🎁 FREE ₹50 WALLET BONUS</div>
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 4px;">Create Customer Account</h3>
            <p style="color: var(--text-sub); font-size: 12px; margin-bottom: 16px;">Instant signup for seamless order tracking and member discounts.</p>

            <form onsubmit="handleSignupSubmit(event)">
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Full Name *</label>
                    <input type="text" id="signupName" placeholder="Your name" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Mobile Number *</label>
                    <input type="tel" id="signupPhone" placeholder="10-digit mobile number" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Email (Optional)</label>
                    <input type="email" id="signupEmail" placeholder="your@email.com" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Password *</label>
                    <input type="password" id="signupPassword" placeholder="Minimum 6 characters" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
                <button type="submit" id="signupSubmitBtn" style="width: 100%; padding: 11px; background: #10B981; color: #fff; font-weight: 800; font-size: 14px; border: none; border-radius: 8px; cursor: pointer;">
                    🎉 Create Account & Get ₹50
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Floating WhatsApp Widget -->
<?php if (($settings['whatsapp_floating_widget'] ?? '1') === '1'): ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>?text=<?php echo urlencode('Hello! I have a question about AlixDeal products.'); ?>" target="_blank" class="floating-wa-btn" title="Chat on WhatsApp">
        💬
    </a>
<?php endif; ?>

<footer>
    <div class="footer-wrap">
        <div>
            <div class="logo" style="margin-bottom: 10px; color: #fff;">🔥 <?php echo htmlspecialchars($siteName); ?></div>
            <p style="line-height: 1.5; font-size: 12px;"><?php echo htmlspecialchars($settings['site_description'] ?? 'India\'s favorite daily deals store for gadgets and essentials.'); ?></p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Browse Hot Deals</a></li>
                <li><a href="track_order.php">Track My Orders</a></li>
                <li><a href="sitemap.php" target="_blank">Sitemap XML</a></li>
                <li><a href="admin/login.php" target="_blank">Admin Control Panel</a></li>
            </ul>
        </div>
        <div>
            <h4>Customer Support</h4>
            <ul>
                <li>Email: <?php echo htmlspecialchars($settings['contact_email'] ?? 'support@alixdeal.shop'); ?></li>
                <li>Phone: <?php echo htmlspecialchars($settings['contact_phone'] ?? '+91 7351150482'); ?></li>
                <li>WhatsApp: <?php echo htmlspecialchars($whatsapp); ?></li>
                <li>Location: <?php echo htmlspecialchars($settings['address'] ?? 'Village Bajheri, City Muzaffarnagar, PIN 251001'); ?></li>
            </ul>
        </div>
    </div>
    <div style="max-width: 1200px; margin: 16px auto 0; text-align: center; font-size: 11px;">
        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
    </div>
</footer>

<!-- Custom Footer Scripts -->
<?php if (!empty($settings['footer_scripts'])): ?>
    <?php echo $settings['footer_scripts']; ?>
<?php endif; ?>

<script>
    const currency = '<?php echo $currency; ?>';
    const upiId = '<?php echo htmlspecialchars($settings['upi_id'] ?? 'alixdeal@upi'); ?>';
    const upiName = '<?php echo htmlspecialchars($settings['upi_name'] ?? 'AlixDeal'); ?>';
    const bharatpeId = '<?php echo htmlspecialchars($settings['bharatpe_merchant_id'] ?? ''); ?>';
    const razorpayKey = '<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>';

    const loggedUser = <?php echo json_encode($currentUser ? [
        'id' => (int)$currentUser['id'],
        'name' => $currentUser['name'],
        'phone' => $currentUser['phone'],
        'wallet' => (float)$currentUser['wallet_balance'],
        'discount' => (float)$currentUser['special_discount']
    ] : null); ?>;

    let walletDeducted = 0;

    function openAuthModal(tab = 'login') {
        const modal = document.getElementById('authModal');
        if (modal) {
            modal.style.display = 'flex';
            switchAuthTab(tab);
        }
    }

    function closeAuthModal() {
        const modal = document.getElementById('authModal');
        if (modal) modal.style.display = 'none';
    }

    function switchAuthTab(tab) {
        const loginTab = document.getElementById('authTabLogin');
        const signupTab = document.getElementById('authTabSignup');
        const loginForm = document.getElementById('authLoginForm');
        const signupForm = document.getElementById('authSignupForm');

        if (tab === 'login') {
            loginTab.style.background = '#fff';
            loginTab.style.color = 'var(--primary)';
            signupTab.style.background = 'transparent';
            signupTab.style.color = 'var(--text-sub)';
            loginForm.style.display = 'block';
            signupForm.style.display = 'none';
        } else {
            signupTab.style.background = '#fff';
            signupTab.style.color = 'var(--primary)';
            loginTab.style.background = 'transparent';
            loginTab.style.color = 'var(--text-sub)';
            signupForm.style.display = 'block';
            loginForm.style.display = 'none';
        }
    }

    async function handleLoginSubmit(e) {
        e.preventDefault();
        const phone = document.getElementById('loginPhone').value.trim();
        const pass = document.getElementById('loginPassword').value.trim();

        try {
            const resp = await fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone: phone, password: pass })
            });
            const res = await resp.json();
            if (res.status === 'success') {
                closeAuthModal();
                Swal.fire({
                    title: 'Welcome Back! 🎉',
                    text: res.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Login Failed', res.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        }
    }

    async function handleSignupSubmit(e) {
        e.preventDefault();
        const name = document.getElementById('signupName').value.trim();
        const phone = document.getElementById('signupPhone').value.trim();
        const email = document.getElementById('signupEmail').value.trim();
        const pass = document.getElementById('signupPassword').value.trim();

        try {
            const resp = await fetch('api/auth.php?action=signup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, phone: phone, email: email, password: pass })
            });
            const res = await resp.json();
            if (res.status === 'success') {
                closeAuthModal();
                Swal.fire({
                    title: 'Account Created! 🎁',
                    text: res.message,
                    icon: 'success',
                    confirmButtonColor: '#FF5722',
                    confirmButtonText: 'Start Shopping'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Signup Failed', res.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        }
    }

    function handleLogout() {
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
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        });
    }

    function toggleWalletDeduction(cb) {
        if (!loggedUser) return;
        const totalItems = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);
        const subtotal = totalItems - (totalItems * (discountPercent / 100));
        if (cb.checked) {
            walletDeducted = Math.min(loggedUser.wallet, subtotal);
        } else {
            walletDeducted = 0;
        }
        renderCart();
    }

    // Toggle Mobile Drawer Menu
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('mobileDrawerOverlay');
        if (drawer && overlay) {
            drawer.classList.toggle('open');
            overlay.classList.toggle('open');
        }
    }

    // Hero Slider JS (Auto slide & Touch Finger Swipe)
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

    if (slidesCount > 1) {
        setInterval(nextSlide, 5000);
    }

    // Touch & Finger Swipe on Hero Image Slider
    let touchStartX = 0;
    let touchEndX = 0;
    const heroSliderWrap = document.querySelector('.slider-wrapper');
    if (heroSliderWrap) {
        heroSliderWrap.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        heroSliderWrap.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const swipeDistance = touchEndX - touchStartX;
            if (swipeDistance < -40) {
                nextSlide(); // Finger swiped left
            } else if (swipeDistance > 40) {
                prevSlide(); // Finger swiped right
            }
        }, { passive: true });
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

        // Animate cart badge
        const badge = document.getElementById('cart-badge-count');
        if (badge) {
            badge.style.transform = 'scale(1.4)';
            setTimeout(() => { badge.style.transform = 'scale(1)'; }, 250);
        }

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

    function removeFromCart(id) {
        cart = cart.filter(it => it.id !== id);
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
                    <div style="font-size: 42px; margin-bottom: 8px;">🛍️</div>
                    <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main);">Your Cart is Empty</h4>
                    <p style="font-size: 13px; margin-top: 6px; line-height: 1.5;">Browse our trending hot deals and add items to your cart.</p>
                    <button onclick="closeCartDrawer()" class="slide-btn" style="margin-top: 16px; display: inline-flex;">Explore Deals Now</button>
                </div>
            `;
            document.getElementById('cartSubtotal').textContent = `${currency}0.00`;
            document.getElementById('cartDiscount').textContent = `-${currency}0.00`;
            document.getElementById('cartTotal').textContent = `${currency}0.00`;
            return;
        }

        let subtotal = 0;
        let html = `
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; padding: 8px 12px; margin-bottom: 14px; font-size: 12px; font-weight: 700; color: #065F46; display: flex; align-items: center; gap: 6px;">
                <span>🚚</span>
                <span>Unlocked FREE Delivery on this order!</span>
            </div>
        `;

        cart.forEach(item => {
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;
            html += `
                <div class="cart-item-card">
                    <img src="${item.image || 'https://via.placeholder.com/64'}" alt="${item.name}">
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #0F172A; margin-bottom: 3px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.name}</h4>
                        <div style="font-size: 11px; color: var(--text-sub); margin-bottom: 6px;">Variant: ${item.color}</div>
                        <div style="font-size: 14px; font-weight: 800; color: var(--primary);">${currency}${item.price.toFixed(2)}</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between;">
                        <button onclick="removeFromCart('${item.id}')" title="Remove" style="background: none; border: none; font-size: 14px; color: #94A3B8; cursor: pointer; padding: 2px;">🗑️</button>
                        <div style="display: flex; align-items: center; gap: 6px; background: #F1F5F9; border-radius: 8px; padding: 3px 6px;">
                            <button onclick="updateQty('${item.id}', -1)" style="width: 20px; height: 20px; border-radius: 4px; border: none; background: #fff; cursor: pointer; font-weight: 800; font-size: 12px; display:flex; align-items:center; justify-content:center; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">-</button>
                            <span style="font-size: 12px; font-weight: 800; min-width: 14px; text-align: center;">${item.quantity}</span>
                            <button onclick="updateQty('${item.id}', 1)" style="width: 20px; height: 20px; border-radius: 4px; border: none; background: #fff; cursor: pointer; font-weight: 800; font-size: 12px; display:flex; align-items:center; justify-content:center; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">+</button>
                        </div>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
        const discountAmount = subtotal * (discountPercent / 100);
        let finalPayable = subtotal - discountAmount;
        if (walletDeducted > 0) {
            finalPayable = Math.max(0, finalPayable - walletDeducted);
        }

        document.getElementById('cartSubtotal').textContent = `${currency}${subtotal.toFixed(2)}`;
        document.getElementById('cartDiscount').textContent = `-${currency}${discountAmount.toFixed(2)}`;
        document.getElementById('cartTotal').textContent = `${currency}${finalPayable.toFixed(2)}`;

        // Update dynamic QR link
        updateDynamicPaymentQR(finalPayable);
    }

    function applyCoupon() {
        const code = document.getElementById('couponInput').value.trim().toUpperCase();
        const msgEl = document.getElementById('couponMsg');
        if (code === 'ALIXDEAL50') {
            discountPercent = 50;
            msgEl.style.color = '#10B981';
            msgEl.textContent = '🎉 Coupon ALIXDEAL50 Applied! Flat 50% discount applied.';
        } else if (code === 'SAVE100') {
            discountPercent = 20;
            msgEl.style.color = '#10B981';
            msgEl.textContent = '🎉 Coupon SAVE100 Applied! 20% discount applied.';
        } else {
            msgEl.style.color = '#EF4444';
            msgEl.textContent = '❌ Invalid promo coupon. Use code ALIXDEAL50';
        }
        renderCart();
    }

    function showCheckoutFields() {
        if (cart.length === 0) {
            Swal.fire('Empty Cart', 'Your cart is empty. Please add items before checking out.', 'info');
            return;
        }

        // If not logged in, prompt user with instant sign-up / login discount!
        if (!loggedUser) {
            Swal.fire({
                title: '🎁 Claim ₹50 Wallet Discount!',
                text: 'Sign up or login now to claim ₹50 wallet discount on this order and track real-time delivery.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#FF5722',
                cancelButtonColor: '#64748B',
                confirmButtonText: '👤 Sign In / Register (+₹50)',
                cancelButtonText: '⚡ Continue as Guest'
            }).then((result) => {
                if (result.isConfirmed) {
                    openAuthModal('signup');
                } else {
                    document.getElementById('checkoutFormBlock').style.display = 'block';
                    document.getElementById('btnProceedCheckout').style.display = 'none';
                }
            });
            return;
        }

        document.getElementById('checkoutFormBlock').style.display = 'block';
        document.getElementById('btnProceedCheckout').style.display = 'none';
    }

    function togglePaymentQR(show, type = 'upi') {
        const box = document.getElementById('qrPaymentBox');
        if (!box) return;
        if (!show) {
            box.style.display = 'none';
            return;
        }
        box.style.display = 'block';
        const totalItems = cart.reduce((acc, it) => acc + (it.price * it.quantity), 0);
        const total = totalItems - (totalItems * (discountPercent / 100));
        updateDynamicPaymentQR(total, type);
    }

    function updateDynamicPaymentQR(amount, type = 'upi') {
        const qrImg = document.getElementById('dynamicQRImg');
        const deepBtn = document.getElementById('upiDeepLinkBtn');
        const titleEl = document.getElementById('qrBoxTitle');
        if (!qrImg || !deepBtn) return;

        const targetId = type === 'bharatpe' && bharatpeId ? bharatpeId : upiId;
        const upiString = `upi://pay?pa=${encodeURIComponent(targetId)}&pn=${encodeURIComponent(upiName)}&am=${amount.toFixed(2)}&cu=INR&tn=Order_${Date.now()}`;
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(upiString)}`;
        deepBtn.href = upiString;

        if (titleEl) {
            titleEl.textContent = type === 'bharatpe' ? `🇮🇳 BharatPe Merchant QR: ₹${amount.toFixed(2)}` : `⚡ Scan & Pay ₹${amount.toFixed(2)} via any UPI App`;
        }
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

    // Initialize cart badge & auto-open if requested
    renderCart();
    if (new URLSearchParams(window.location.search).get('open_cart') === '1') {
        openCartDrawer();
    }
</script>

</body>
</html>
