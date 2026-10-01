<?php
/**
 * AlixDeal Web Dispatcher & Root Redirector
 */
if (file_exists(__DIR__ . '/public_html/index.php')) {
    header("Location: public_html/");
    exit;
} elseif (file_exists(__DIR__ . '/install/index.php')) {
    header("Location: install/index.php");
    exit;
} else {
    header("Location: public_html/install/index.php");
    exit;
}
