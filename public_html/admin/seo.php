<?php
$pageTitle = 'SEO & Analytics Manager';
$activeTab = 'seo';
require_once __DIR__ . '/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'google_analytics',
        'header_scripts',
        'footer_scripts',
        'announcement_text'
    ];

    $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    foreach ($keys as $k) {
        $val = trim($_POST[$k] ?? '');
        $stmt->execute([$k, $val, $val]);
    }
    $msg = "SEO and script settings updated successfully!";
}

// Fetch current settings
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM settings");
while ($r = $stmt->fetch()) {
    $settings[$r['key_name']] = $r['value'];
}
?>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px;">
    <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">🔍 Search Engine Optimization (SEO)</h2>
    <p style="color: var(--text-sub); font-size: 13px; margin-bottom: 24px;">Optimize your store for Google, Yahoo, Bing, WhatsApp link previews, and search rankings.</p>

    <form method="POST" action="seo.php">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Meta Title (Google Search Title)</label>
            <input type="text" name="meta_title" value="<?php echo htmlspecialchars($settings['meta_title'] ?? ''); ?>" placeholder="AlixDeal - Best Online Shopping Deals in India" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
            <small style="color: var(--text-sub); font-size: 11px;">Recommended length: 50-60 characters</small>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Meta Description</label>
            <textarea name="meta_description" rows="3" placeholder="Shop trending electronics, gadgets, and apparel at up to 70% off with Free Delivery across India." style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit;"><?php echo htmlspecialchars($settings['meta_description'] ?? ''); ?></textarea>
            <small style="color: var(--text-sub); font-size: 11px;">Recommended length: 150-160 characters</small>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Meta Keywords (Comma separated)</label>
            <input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($settings['meta_keywords'] ?? ''); ?>" placeholder="online shopping, fast wireless charger, earbuds, smart watch, discounts" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">OpenGraph Social Share Image URL</label>
            <input type="text" name="og_image" value="<?php echo htmlspecialchars($settings['og_image'] ?? ''); ?>" placeholder="https://yourdomain.com/og-banner.jpg (Shown when shared on WhatsApp & Facebook)" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px; padding-top: 16px; border-top: 1px dashed var(--border);">📊 Analytics & Conversion Tracking</h3>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Google Analytics / Tag Manager Measurement ID</label>
            <input type="text" name="google_analytics" value="<?php echo htmlspecialchars($settings['google_analytics'] ?? ''); ?>" placeholder="e.g. G-XXXXXXXXXX" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Custom &lt;head&gt; Code / Scripts (Facebook Pixel, Verification Tags, CSS)</label>
            <textarea name="header_scripts" rows="3" placeholder="<!-- Paste Facebook Pixel or Google Search Console verification code here -->" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($settings['header_scripts'] ?? ''); ?></textarea>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Custom &lt;footer&gt; Code (Live Chat Widgets, Tawk.to, WhatsApp Widget)</label>
            <textarea name="footer_scripts" rows="3" placeholder="<!-- Paste chat or conversion scripts here -->" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($settings['footer_scripts'] ?? ''); ?></textarea>
        </div>

        <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 16px; padding-top: 16px; border-top: 1px dashed var(--border);">📢 Top Announcement Bar</h3>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Header Notice Bar Text</label>
            <input type="text" name="announcement_text" value="<?php echo htmlspecialchars($settings['announcement_text'] ?? ''); ?>" placeholder="⚡ Special Festival Offer: Free Delivery All Over India!" style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px;">
        </div>

        <button type="submit" class="btn">💾 Save SEO & Tracking Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
