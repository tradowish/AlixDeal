<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$rootDir = dirname(__DIR__);
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    echo json_encode(['status' => 'error', 'message' => 'System not installed']);
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($action === 'signup') {
    $name = trim($data['name'] ?? '');
    $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($name) || strlen($phone) < 10 || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide valid Name, 10-digit Phone, and Password.']);
        exit;
    }

    // Check if phone exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Mobile number already registered. Please login instead.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $initialBonus = 50.00; // Welcome ₹50 bonus
    $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, wallet_balance, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $phone, $email, $hash, $initialBonus]);
    $userId = $pdo->lastInsertId();

    // Log transaction
    $stmtT = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'credit', ?, 'Welcome Signup Bonus')");
    $stmtT->execute([$userId, $initialBonus]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_phone'] = $phone;

    echo json_encode([
        'status' => 'success',
        'message' => 'Account created successfully! ₹50 welcome bonus credited to your wallet.',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'wallet' => $initialBonus
        ]
    ]);
    exit;
}

if ($action === 'login') {
    $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($phone) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter phone number and password.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number or password.']);
        exit;
    }

    if ($user['status'] === 'blocked') {
        echo json_encode(['status' => 'error', 'message' => 'Your account is suspended. Please contact store support.']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_phone'] = $user['phone'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Welcome back, ' . $user['name'] . '!',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'wallet' => (float)$user['wallet_balance'],
            'discount' => (float)$user['special_discount']
        ]
    ]);
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_phone']);
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
    exit;
}

if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, name, phone, email, wallet_balance, special_discount FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(['status' => 'logged_in', 'user' => $user]);
            exit;
        }
    }
    echo json_encode(['status' => 'guest']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
