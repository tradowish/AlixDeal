<?php
/**
 * REST API: Products Endpoint
 * Accessible by Android Mobile App & Frontend
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rootDir = dirname(__DIR__);
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    http_response_code(503);
    echo json_encode(['error' => 'Store is not installed yet']);
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$dealsOnly = isset($_GET['deals']) ? (int)$_GET['deals'] : 0;
$search = trim($_GET['q'] ?? '');

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    
    if (!$p) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    echo json_encode(formatProduct($p));
    exit;
}

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($cat > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat;
}
if ($dealsOnly === 1) {
    $sql .= " AND p.is_deal = 1";
}
if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$formatted = array_map('formatProduct', $rows);

echo json_encode([
    'status' => 'success',
    'count' => count($formatted),
    'data' => $formatted
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

function formatProduct($row) {
    $colors = array_filter(array_map('trim', explode(',', $row['colors'] ?? 'Standard')));
    $sizes = array_filter(array_map('trim', explode(',', $row['sizes'] ?? 'Standard')));

    return [
        'id' => (string)($row['sku'] ?: 'p' . $row['id']),
        'db_id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'description' => (string)($row['description'] ?? ''),
        'price' => (float)$row['price'],
        'originalPrice' => (float)$row['original_price'],
        'rating' => (float)$row['rating'],
        'reviews' => (int)$row['reviews_count'],
        'category' => (string)($row['category_name'] ?? 'General'),
        'imageUrl' => (string)$row['image_url'],
        'isDeal' => (bool)$row['is_deal'],
        'isTrending' => (bool)$row['is_trending'],
        'stock' => (int)$row['stock'],
        'colors' => array_values($colors),
        'sizes' => array_values($sizes)
    ];
}
