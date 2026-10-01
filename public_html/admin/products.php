<?php
$pageTitle = 'Products Catalog & Inventory';
$activeTab = 'products';
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';
$err = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Product deleted successfully.";
    $action = 'list';
}

// Handle Add / Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sku = trim($_POST['sku'] ?? 'p' . time());
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $original_price = (float)($_POST['original_price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $stock = (int)($_POST['stock'] ?? 100);
    $is_deal = isset($_POST['is_deal']) ? 1 : 0;
    $is_trending = isset($_POST['is_trending']) ? 1 : 0;
    $colors = trim($_POST['colors'] ?? 'Standard');
    $sizes = trim($_POST['sizes'] ?? 'Standard');

    // Handle Cropped Image Data (Base64) or normal file upload
    if (!empty($_POST['cropped_image_base64'])) {
        $base64 = $_POST['cropped_image_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $data = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                $uploadDir = dirname(__DIR__) . '/uploads/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fn = 'prod_crop_' . time() . '_' . rand(100, 999) . '.' . ($type === 'jpeg' ? 'jpg' : $type);
                if (file_put_contents($uploadDir . $fn, $decoded)) {
                    $imageUrl = 'uploads/' . $fn;
                }
            }
        }
    } elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $newFileName = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $fileExt;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newFileName)) {
                $imageUrl = 'uploads/' . $newFileName;
            }
        }
    }

    if (empty($name) || $price <= 0) {
        $err = "Product title and positive price are required.";
    } else {
        if ($id > 0) {
            $sql = "UPDATE products SET sku=?, name=?, description=?, price=?, original_price=?, category_id=?, image_url=?, stock=?, is_deal=?, is_trending=?, colors=?, sizes=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $description, $price, $original_price, $category_id ?: null, $imageUrl, $stock, $is_deal, $is_trending, $colors, $sizes, $id]);
            $msg = "Product updated successfully.";
            $action = 'list';
        } else {
            $sql = "INSERT INTO products (sku, name, description, price, original_price, category_id, image_url, stock, is_deal, is_trending, colors, sizes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $description, $price, $original_price, $category_id ?: null, $imageUrl, $stock, $is_deal, $is_trending, $colors, $sizes]);
            $msg = "New product created successfully.";
            $action = 'list';
        }
    }
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY parent_id ASC, name ASC")->fetchAll();
?>

<!-- Cropper.js & SweetAlert2 Libraries -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($msg)): ?>
    <div style="background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;">
        <?php echo htmlspecialchars($err); ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <?php
    $product = [
        'id' => 0,
        'sku' => 'p' . time(),
        'name' => '',
        'description' => '',
        'price' => '0.00',
        'original_price' => '0.00',
        'category_id' => 0,
        'image_url' => '',
        'stock' => 50,
        'is_deal' => 0,
        'is_trending' => 0,
        'colors' => 'Standard',
        'sizes' => 'Standard'
    ];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $found = $stmt->fetch();
        if ($found) $product = $found;
    }
    ?>
    <div class="card" style="max-width: 800px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 800;"><?php echo $action === 'edit' ? 'Edit Product' : 'Add New Product'; ?></h2>
            <a href="products.php" class="btn btn-sm" style="background: #64748B;">← Back to Products</a>
        </div>

        <form method="POST" action="products.php" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="cropped_image_base64" id="cropped_image_base64">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Product Title *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" placeholder="e.g. Fast 3-in-1 Wireless Charger Stand" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">SKU Code</label>
                    <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku']); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: monospace;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Product Description</label>
                <textarea name="description" rows="3" placeholder="Enter key features, specifications, and warranty details..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px;"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Offer Selling Price (₹) *</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Original MRP (₹) (Cut-price)</label>
                    <input type="number" step="0.01" name="original_price" value="<?php echo htmlspecialchars($product['original_price']); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Stock Units *</label>
                    <input type="number" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Category / Subcategory</label>
                <select name="category_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: #fff;">
                    <option value="0">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo ($cat['parent_id'] > 0 ? '↳ ' : '📁 ') . htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Image with Live Cropper -->
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Product Image with Advanced Crop Tool (1:1 Ratio)</label>

                <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 10px;">
                    <div id="imagePreviewContainer">
                        <?php if (!empty($product['image_url'])): ?>
                            <img id="currentImageThumb" src="<?php echo strpos($product['image_url'], 'http') === 0 ? $product['image_url'] : ('../' . $product['image_url']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                        <?php else: ?>
                            <div id="currentImageThumb" style="width: 80px; height: 80px; background: #E2E8F0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px;">📷</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <input type="file" id="imageInput" accept="image/*" style="font-size: 13px; margin-bottom: 6px;">
                        <div style="font-size: 11px; color: var(--text-sub);">Select a photo from device to open the crop window, or enter URL below.</div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 11px; font-weight: 600; color: var(--text-sub); margin-bottom: 4px;">Or External Image URL</label>
                    <input type="text" name="image_url" id="image_url_input" value="<?php echo htmlspecialchars($product['image_url']); ?>" placeholder="https://images.unsplash.com/..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Available Colors (Comma separated)</label>
                    <input type="text" name="colors" value="<?php echo htmlspecialchars($product['colors']); ?>" placeholder="Black, White, Navy Blue" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Available Sizes (Comma separated)</label>
                    <input type="text" name="sizes" value="<?php echo htmlspecialchars($product['sizes']); ?>" placeholder="Standard, M, L, XL" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>

            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                    <input type="checkbox" name="is_deal" value="1" <?php echo $product['is_deal'] ? 'checked' : ''; ?>>
                    ⚡ Feature in Hot Deals
                </label>
                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                    <input type="checkbox" name="is_trending" value="1" <?php echo $product['is_trending'] ? 'checked' : ''; ?>>
                    🔥 Trending Bestseller
                </label>
            </div>

            <button type="submit" class="btn" style="padding: 10px 24px;">💾 Save Product</button>
        </form>
    </div>

    <!-- Image Cropper Modal -->
    <div id="cropperModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #fff; border-radius: 16px; max-width: 500px; width: 100%; padding: 20px;">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 12px;">✂️ Crop Product Image (1:1 Ratio)</h3>
            <div style="max-height: 350px; overflow: hidden; margin-bottom: 14px; background: #000;">
                <img id="cropperImage" style="max-width: 100%; display: block;">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <button type="button" onclick="closeCropperModal()" style="padding: 8px 16px; border: 1px solid var(--border); background: #fff; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="button" onclick="applyCrop()" class="btn" style="background: #10B981;">✓ Crop & Use This Image</button>
            </div>
        </div>
    </div>

    <script>
        let cropper = null;
        const imgInput = document.getElementById('imageInput');
        const cropModal = document.getElementById('cropperModal');
        const cropImg = document.getElementById('cropperImage');

        imgInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function (event) {
                    cropImg.src = event.target.result;
                    cropModal.style.display = 'flex';
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(cropImg, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 0.9
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        function closeCropperModal() {
            cropModal.style.display = 'none';
            if (cropper) cropper.destroy();
        }

        function applyCrop() {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({ width: 600, height: 600 });
            const base64 = canvas.toDataURL('image/jpeg', 0.85);
            document.getElementById('cropped_image_base64').value = base64;

            // Update preview
            const thumb = document.getElementById('currentImageThumb');
            if (thumb.tagName === 'IMG') {
                thumb.src = base64;
            } else {
                thumb.outerHTML = `<img id="currentImageThumb" src="${base64}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">`;
            }
            closeCropperModal();
            Swal.fire({
                title: 'Image Cropped!',
                text: 'Cropped 1:1 image ready to save.',
                icon: 'success',
                timer: 1200,
                showConfirmButton: false
            });
        }
    </script>

<?php else: ?>
    <?php
    $products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
    ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800;">📦 Products Catalog</h2>
            <p style="color: var(--text-sub); font-size: 13px;">Manage all items, crop photos, modify pricing, and track inventory.</p>
        </div>
        <a href="products.php?action=add" class="btn">+ Add New Product</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product Photo</th>
                        <th>Title & SKU</th>
                        <th>Category</th>
                        <th>Price (₹)</th>
                        <th>Stock</th>
                        <th>Deals</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-sub); padding: 36px;">No products found. Click "+ Add New Product" to start.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo strpos($p['image_url'], 'http') === 0 ? $p['image_url'] : ('../' . $p['image_url']); ?>" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border);" onerror="this.src='https://via.placeholder.com/48'">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-sub);">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($p['category_name'] ?? 'General'); ?></td>
                                <td>
                                    <strong style="color: var(--primary);">₹<?php echo number_format($p['price'], 2); ?></strong>
                                    <?php if ($p['original_price'] > $p['price']): ?>
                                        <div style="font-size: 11px; color: var(--text-sub); text-decoration: line-through;">₹<?php echo number_format($p['original_price'], 2); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: <?php echo $p['stock'] <= 10 ? '#DC2626' : '#166534'; ?>;">
                                        <?php echo $p['stock']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['is_deal']): ?>
                                        <span style="background: #FEF3C7; color: #92400E; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px;">⚡ HOT DEAL</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="products.php?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm">Edit</a>
                                    <button onclick="confirmDelete(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>')" class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Product?',
                text: 'Are you sure you want to remove "' + name + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'products.php?action=delete&id=' + id;
                }
            });
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
