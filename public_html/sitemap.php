<?php
header("Content-Type: application/xml; charset=utf-8");

$rootDir = __DIR__;
if (!file_exists($rootDir . '/config.php') || !file_exists($rootDir . '/.installed')) {
    exit;
}

require_once $rootDir . '/config.php';
$pdo = getDBConnection();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

$products = $pdo->query("SELECT id, name, created_at FROM products ORDER BY id DESC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Home URL -->
    <url>
        <loc><?php echo htmlspecialchars($baseUrl); ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <!-- Track Order URL -->
    <url>
        <loc><?php echo htmlspecialchars($baseUrl); ?>track-order</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <!-- Categories -->
    <?php foreach ($categories as $cat): ?>
        <url>
            <loc><?php echo htmlspecialchars($baseUrl); ?>?cat=<?php echo $cat['id']; ?></loc>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    <?php endforeach; ?>
</urlset>
