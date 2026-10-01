<?php
/**
 * REST API: Order Creation Endpoint
 * Used by Android App & Checkout
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$customerName = trim($data['customer_name'] ?? '');
$customerEmail = trim($data['customer_email'] ?? '');
$customerPhone = trim($data['customer_phone'] ?? '');
$shippingAddress = trim($data['shipping_address'] ?? '');
$city = trim($data['city'] ?? '');
$postalCode = trim($data['postal_code'] ?? '');
$paymentMethod = trim($data['payment_method'] ?? 'Cash On Delivery');
$items = $data['items'] ?? [];

if (empty($customerName) || empty($customerPhone) || empty($shippingAddress) || empty($items)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer name, phone, address, and at least one item are required'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(10, 99);
    $totalAmount = 0.00;

    foreach ($items as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        $totalAmount += ($price * $qty);
    }

    $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, city, postal_code, total_amount, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
    $stmt->execute([$orderNumber, $customerName, $customerEmail, $customerPhone, $shippingAddress, $city, $postalCode, $totalAmount, $paymentMethod]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_name, price, quantity, selected_color, selected_size, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $name = $item['name'] ?? 'Product';
        $price = (float)($item['price'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        $color = $item['color'] ?? 'Standard';
        $size = $item['size'] ?? 'Standard';
        $subtotal = $price * $qty;
        $stmtItem->execute([$orderId, $name, $price, $qty, $color, $size, $subtotal]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully',
        'order_number' => $orderNumber,
        'order_id' => (int)$orderId,
        'total' => $totalAmount
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create order: ' . $e->getMessage()
    ]);
}
