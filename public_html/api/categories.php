<?php
/**
 * REST API: Categories Endpoint
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$rootDir = dirname(__DIR__);
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    http_response_code(503);
    echo json_encode(['error' => 'Store is not installed yet']);
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

$stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

echo json_encode([
    'status' => 'success',
    'data' => $categories
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
