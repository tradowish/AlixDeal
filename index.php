<?php
/**
 * Alixdeal Root Dispatcher
 * Serves public_html seamlessly without exposing /public_html in the browser URL
 */
if (file_exists(__DIR__ . '/public_html/index.php')) {
    chdir(__DIR__ . '/public_html');
    require_once __DIR__ . '/public_html/index.php';
    exit;
} elseif (file_exists(__DIR__ . '/index.php') && __FILE__ !== __DIR__ . '/index.php') {
    require_once __DIR__ . '/index.php';
    exit;
} elseif (file_exists(__DIR__ . '/install/index.php')) {
    header("Location: install/index.php");
    exit;
} else {
    echo "Alixdeal installation files not found. Please verify directory structure.";
    exit;
}
