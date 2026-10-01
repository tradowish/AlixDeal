<?php
/**
 * Alixdeal - Product Detail Page & Customer Reviews
 */
session_start();
$rootDir = __DIR__;
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    header("Location: install/index.php");
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch Product Details with Category
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

// Current logged in customer user
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmtU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtU->execute([$_SESSION['user_id']]);
    $currentUser = $stmtU->fetch();
}

// Fetch Site Settings
$settings = [];
$stmtS = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmtS->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
$siteName = $settings['site_name'] ?? 'Alixdeal';
$currency = $settings['currency_symbol'] ?? '₹';
$whatsapp = $settings['whatsapp_number'] ?? '+917351150482';
$announcement = $settings['announcement_text'] ?? '⚡ Mega Festive Sale Live: Use code ALIXDEAL50 for 50% Off + Free Delivery All Over India!';

// Handle New Customer Review Submission
$reviewMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    $custName = trim($_POST['customer_name'] ?? '');
    $custPhone = trim($_POST['customer_phone'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if (!empty($custName) && !empty($comment)) {
        $stmtRev = $pdo->prepare("INSERT INTO reviews (product_id, customer_name, customer_phone, rating, comment, is_approved) VALUES (?, ?, ?, ?, ?, 1)");
        $stmtRev->execute([$productId, $custName, $custPhone, $rating, $comment]);
        $reviewMsg = "Thank you! Your review has been published.";

        // Update product reviews count and rating
        $stmtAvg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total FROM reviews WHERE product_id = ? AND is_approved = 1");
        $stmtAvg->execute([$productId]);
        $avgData = $stmtAvg->fetch();
        if ($avgData) {
            $newAvg = round((float)$avgData['avg_rating'], 1);
            $newCount = (int)$avgData['total'];
            $pdo->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE id = ?")->execute([$newAvg, $newCount, $productId]);
            $product['rating'] = $newAvg;
            $product['reviews_count'] = $newCount;
        }
    }
}

// Fetch Approved Reviews
$stmtR = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY id DESC LIMIT 20");
$stmtR->execute([$productId]);
$reviews = $stmtR->fetchAll();

// Fetch Related Products from same category
$stmtRel = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
$stmtRel->execute([$product['category_id'] ?? 1, $productId]);
$relatedProducts = $stmtRel->fetchAll();

// Calculate discount
$discountPercent = 0;
if ($product['original_price'] > $product['price']) {
    $discountPercent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
}

// Variations
$colors = array_filter(array_map('trim', explode(',', $product['colors'] ?: 'Standard')));
$sizes = array_filter(array_map('trim', explode(',', $product['sizes'] ?: 'Standard')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($product['description'] ?? ''), 0, 160)); ?>">

    <!-- Google Fonts -->
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
            --success: #10B981;
            --warning: #F59E0B;
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

        .announcement-bar {
            background: linear-gradient(90deg, #FF5722, #FF9800);
            color: #fff;
            text-align: center;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 700;
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
        .hamburger-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-main);
        }
        .logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
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
            padding: 7px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
        }
        .btn-track { background: #EEF2FF; color: var(--primary); }
        .btn-cart { background: var(--primary); color: #fff; }

        /* Breadcrumbs */
        .breadcrumb {
            max-width: 1200px;
            margin: 16px auto 0;
            padding: 0 16px;
            font-size: 12.5px;
            color: var(--text-sub);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--text-sub); text-decoration: none; font-weight: 600; }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb span { color: #CBD5E1; }

        /* Product Detail Layout */
        .product-wrapper {
            max-width: 1200px;
            margin: 16px auto 40px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }
        @media (max-width: 860px) {
            .product-wrapper { grid-template-columns: 1fr; gap: 20px; }
        }

        /* Left Column: Image Gallery */
        .gallery-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .main-img-wrap {
            width: 100%;
            height: 380px;
            border-radius: 14px;
            overflow: hidden;
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .main-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .main-img:hover {
            transform: scale(1.05);
        }
        .product-discount-tag {
            position: absolute;
            top: 24px;
            left: 24px;
            background: var(--primary);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(255, 87, 34, 0.3);
        }

        /* Right Column: Info & Actions */
        .info-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            display: flex;
            flex-direction: column;
        }
        .cat-tag {
            font-size: 11px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
        }
        .product-title {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.3;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        .rating-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .stars { color: #F59E0B; font-weight: 700; }
        .reviews-count { color: var(--text-sub); }

        .price-container {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .price-current {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
        }
        .price-original {
            font-size: 16px;
            color: var(--text-sub);
            text-decoration: line-through;
        }
        .save-badge {
            background: #ECFDF5;
            color: #065F46;
            font-weight: 800;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
        }

        /* Option Selectors */
        .opt-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-main);
        }
        .pill-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .pill-option {
            padding: 8px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s;
        }
        .pill-option.active {
            border-color: var(--primary);
            background: #FFF1EE;
            color: var(--primary);
        }

        /* Quantity & Add to Cart */
        .buy-action-box {
            display: flex;
            gap: 12px;
            margin-top: 10px;
            margin-bottom: 24px;
        }
        .qty-picker {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: #F8FAFC;
            padding: 4px;
        }
        .qty-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            background: #fff;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .qty-val {
            min-width: 32px;
            text-align: center;
            font-size: 14px;
            font-weight: 800;
        }
        .btn-add-cart {
            flex: 1;
            padding: 14px 20px;
            background: linear-gradient(135deg, #FF5722 0%, #E64A19 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(255, 87, 34, 0.35);
            transition: all 0.2s;
        }
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 87, 34, 0.45);
        }

        /* Guarantees Box */
        .trust-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 16px;
            background: #F8FAFC;
            border-radius: 14px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Description & Reviews Section */
        .section-box {
            max-width: 1200px;
            margin: 0 auto 36px;
            padding: 0 16px;
            width: 100%;
        }
        .tab-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
        }
        .tab-card h3 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        .desc-text {
            font-size: 14px;
            line-height: 1.8;
            color: #334155;
            white-space: pre-line;
        }

        /* Reviews List */
        .review-item {
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .review-item:last-child { border-bottom: none; }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .reviewer-name { font-size: 13px; font-weight: 700; }
        .review-date { font-size: 11px; color: var(--text-sub); }
        .review-comment { font-size: 13px; color: var(--text-sub); line-height: 1.5; margin-top: 4px; }

        /* Review Form */
        .review-form {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            margin-top: 24px;
        }
        .review-form input, .review-form textarea, .review-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
            font-family: inherit;
        }
        .btn-submit-review {
            padding: 10px 18px;
            background: #0F172A;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
        }

        /* Related Products Grid */
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 14px;
        }
        .related-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }
        .related-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .related-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #F1F5F9;
        }
        .related-body { padding: 12px; }

        footer {
            background: #0F172A;
            color: #94A3B8;
            padding: 36px 16px 20px;
            font-size: 13px;
            margin-top: auto;
        }
    </style>
</head>
<body>

<!-- Announcement -->
<?php if (!empty($announcement)): ?>
    <div class="announcement-bar"><?php echo htmlspecialchars($announcement); ?></div>
<?php endif; ?>

<!-- Header -->
<header class="header">
    <div class="nav-container">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span>🔥</span>
                <span><?php echo htmlspecialchars($siteName); ?></span>
            </a>
        </div>
        <div class="header-actions">
            <a href="index.php" class="btn-header" style="background:#F1F5F9; color:var(--text-main);">← Back to Deals</a>
            <a href="track_order.php" class="btn-header btn-track">📦 Track Order</a>
            <a href="index.php" class="btn-header btn-cart">🛒 View Cart</a>
        </div>
    </div>
</header>

<!-- Breadcrumbs -->
<div class="breadcrumb">
    <a href="index.php">Home</a>
    <span>/</span>
    <a href="index.php?cat=<?php echo $product['category_id'] ?? 1; ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Deals'); ?></a>
    <span>/</span>
    <strong style="color: var(--text-main);"><?php echo htmlspecialchars($product['name']); ?></strong>
</div>

<!-- Product Main Container -->
<div class="product-wrapper">
    <!-- Gallery Column -->
    <div class="gallery-box">
        <?php if ($discountPercent > 0): ?>
            <div class="product-discount-tag">-<?php echo $discountPercent; ?>% OFF</div>
        <?php endif; ?>
        <div class="main-img-wrap">
            <img id="mainProductImg" src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-img">
        </div>
    </div>

    <!-- Product Info Column -->
    <div class="info-box">
        <span class="cat-tag"><?php echo htmlspecialchars($product['category_name'] ?? 'HOT DEAL'); ?></span>
        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

        <div class="rating-row">
            <span class="stars">★ <?php echo number_format($product['rating'], 1); ?></span>
            <span class="reviews-count">(<?php echo (int)$product['reviews_count']; ?> customer ratings)</span>
            <span style="color: var(--success); font-weight: 700; margin-left: 8px;">🟢 In Stock & Ready to Ship</span>
        </div>

        <div class="price-container">
            <span class="price-current"><?php echo $currency; ?><?php echo number_format($product['price'], 2); ?></span>
            <?php if ($product['original_price'] > $product['price']): ?>
                <span class="price-original"><?php echo $currency; ?><?php echo number_format($product['original_price'], 2); ?></span>
                <span class="save-badge">You Save <?php echo $currency; ?><?php echo number_format($product['original_price'] - $product['price'], 2); ?></span>
            <?php endif; ?>
        </div>

        <!-- Color Variations -->
        <?php if (!empty($colors)): ?>
            <div class="opt-title">Select Color: <span id="selectedColorName" style="color:var(--primary);"><?php echo htmlspecialchars($colors[0]); ?></span></div>
            <div class="pill-group" id="colorPills">
                <?php foreach ($colors as $idx => $c): ?>
                    <button type="button" class="pill-option <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="selectVariant('color', this, '<?php echo htmlspecialchars($c); ?>')">
                        <?php echo htmlspecialchars($c); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Size Variations -->
        <?php if (!empty($sizes)): ?>
            <div class="opt-title">Select Size / Pack: <span id="selectedSizeName" style="color:var(--primary);"><?php echo htmlspecialchars($sizes[0]); ?></span></div>
            <div class="pill-group" id="sizePills">
                <?php foreach ($sizes as $idx => $s): ?>
                    <button type="button" class="pill-option <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="selectVariant('size', this, '<?php echo htmlspecialchars($s); ?>')">
                        <?php echo htmlspecialchars($s); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quantity & Buy Action -->
        <div class="opt-title">Quantity:</div>
        <div class="buy-action-box">
            <div class="qty-picker">
                <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                <span class="qty-val" id="qtyVal">1</span>
                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
            </div>
            <button type="button" class="btn-add-cart" onclick="buyThisProduct()">
                <span>⚡ Buy / Add to Cart</span>
            </button>
        </div>

        <!-- Trust Badges -->
        <div class="trust-grid">
            <div class="trust-item"><span>🚚</span> Free Express Delivery</div>
            <div class="trust-item"><span>💵</span> Cash on Delivery (COD)</div>
            <div class="trust-item"><span>🛡️</span> 100% Genuine Quality</div>
            <div class="trust-item"><span>🔄</span> 7 Days Easy Return</div>
        </div>
    </div>
</div>

<!-- Product Description Section -->
<div class="section-box">
    <div class="tab-card">
        <h3>📖 Product Description & Specifications</h3>
        <div class="desc-text"><?php echo htmlspecialchars($product['description'] ?: 'High quality genuine product with fast shipping and manufacturer warranty.'); ?></div>
    </div>
</div>

<!-- Customer Reviews Section -->
<div class="section-box">
    <div class="tab-card">
        <h3>⭐ Verified Customer Reviews (<?php echo count($reviews); ?>)</h3>

        <?php if (!empty($reviewMsg)): ?>
            <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
                <?php echo htmlspecialchars($reviewMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <p style="color: var(--text-sub); font-size: 13px;">No customer reviews yet. Be the first to share your experience!</p>
        <?php else: ?>
            <div>
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($rev['customer_name']); ?> <span style="color:#059669; font-size:11px;">✓ Verified Purchase</span></span>
                            <span class="review-date"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <div style="color: #F59E0B; font-size: 12px;"><?php echo str_repeat('★', $rev['rating']); ?></div>
                        <div class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Write a Review Form -->
        <div class="review-form">
            <h4 style="font-size: 14px; font-weight: 800; margin-bottom: 12px;">✍️ Write a Customer Review</h4>
            <form method="POST" action="product.php?id=<?php echo $productId; ?>">
                <input type="hidden" name="action" value="add_review">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <input type="text" name="customer_name" placeholder="Your Name *" required value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>">
                    <input type="tel" name="customer_phone" placeholder="Mobile Number (Optional)" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; margin-bottom: 4px; display: block;">Rating:</label>
                    <select name="rating">
                        <option value="5">★★★★★ 5 Stars (Excellent)</option>
                        <option value="4">★★★★☆ 4 Stars (Good)</option>
                        <option value="3">★★★☆☆ 3 Stars (Average)</option>
                    </select>
                </div>
                <textarea name="comment" rows="3" placeholder="Share your experience with this product..." required></textarea>
                <button type="submit" class="btn-submit-review">Submit Review</button>
            </form>
        </div>
    </div>
</div>

<!-- Related Products Section -->
<?php if (!empty($relatedProducts)): ?>
<div class="section-box">
    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 12px;">🔥 Customers Also Viewed</h3>
    <div class="related-grid">
        <?php foreach ($relatedProducts as $rp): ?>
            <a href="product.php?id=<?php echo $rp['id']; ?>" class="related-card">
                <img src="<?php echo htmlspecialchars($rp['image_url'] ?: 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80'); ?>" alt="<?php echo htmlspecialchars($rp['name']); ?>" class="related-img">
                <div class="related-body">
                    <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 4px; line-height: 1.3;"><?php echo htmlspecialchars($rp['name']); ?></h4>
                    <div style="font-size: 15px; font-weight: 800; color: var(--primary);"><?php echo $currency; ?><?php echo number_format($rp['price'], 2); ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<footer>
    <div style="max-width: 1200px; margin: 0 auto; text-align: center; font-size: 12px;">
        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?> • Muzaffarnagar, India. All rights reserved.
    </div>
</footer>

<script>
    let currentQty = 1;
    let selectedColor = '<?php echo htmlspecialchars($colors[0] ?? "Standard"); ?>';
    let selectedSize = '<?php echo htmlspecialchars($sizes[0] ?? "Standard"); ?>';

    function changeQty(delta) {
        currentQty = Math.max(1, currentQty + delta);
        document.getElementById('qtyVal').textContent = currentQty;
    }

    function selectVariant(type, btn, val) {
        const parent = btn.parentElement;
        parent.querySelectorAll('.pill-option').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        if (type === 'color') {
            selectedColor = val;
            document.getElementById('selectedColorName').textContent = val;
        } else if (type === 'size') {
            selectedSize = val;
            document.getElementById('selectedSizeName').textContent = val;
        }
    }

    function buyThisProduct() {
        let cart = JSON.parse(localStorage.getItem('alixdeal_cart') || '[]');
        const existing = cart.find(it => it.id === <?php echo $product['id']; ?> && it.color === selectedColor && it.size === selectedSize);
        if (existing) {
            existing.quantity += currentQty;
        } else {
            cart.push({
                id: <?php echo $product['id']; ?>,
                name: <?php echo json_encode($product['name']); ?>,
                price: <?php echo (float)$product['price']; ?>,
                image: <?php echo json_encode($product['image_url']); ?>,
                color: selectedColor,
                size: selectedSize,
                quantity: currentQty
            });
        }
        localStorage.setItem('alixdeal_cart', JSON.stringify(cart));

        Swal.fire({
            title: 'Added to Cart! 🛍️',
            text: '<?php echo addslashes($product['name']); ?> has been added to your shopping cart.',
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#FF5722',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'View Cart & Checkout',
            cancelButtonText: 'Continue Shopping'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?open_cart=1';
            }
        });
    }
</script>

</body>
</html>
