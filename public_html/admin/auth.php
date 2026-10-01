<?php
/**
 * Admin Authentication & Session Guard
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rootDir = dirname(__DIR__);
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    header("Location: ../install/index.php");
    exit;
}

require_once $rootDir . '/config.php';

function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}
