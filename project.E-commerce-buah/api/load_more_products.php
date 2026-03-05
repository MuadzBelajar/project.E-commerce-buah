<?php
/**
 * ================================================
 * API: LOAD MORE PRODUCTS
 * ================================================
 * File: api/load_more_products.php
 * Returns HTML cards + total count as JSON
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$offset = max(0, intval($_GET['offset'] ?? 0));
$limit  = min(24, max(1, intval($_GET['limit'] ?? 12)));
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'semua';

function getProductImage($filename) {
    if (empty($filename)) return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400';
    if (filter_var($filename, FILTER_VALIDATE_URL)) return $filename;
    return '../assets/images/products/' . basename($filename);
}

// Count total
$count_sql    = "SELECT COUNT(*) as total FROM buah WHERE status = 'active'";
$count_params = [];
if (!empty($search)) { $count_sql .= " AND nama_buah LIKE ?"; $count_params[] = "%{$search}%"; }
if ($filter === 'lokal')  $count_sql .= " AND kategori = 'lokal'";
if ($filter === 'impor')  $count_sql .= " AND kategori = 'impor'";

try {
    $count_row = fetchOne($count_sql, $count_params);
    $total     = intval($count_row['total'] ?? 0);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

// Fetch products
$sql    = "SELECT * FROM buah WHERE status = 'active'";
$params = [];
if (!empty($search)) { $sql .= " AND nama_buah LIKE ?"; $params[] = "%{$search}%"; }
if ($filter === 'lokal')  $sql .= " AND kategori = 'lokal'";
if ($filter === 'impor')  $sql .= " AND kategori = 'impor'";
$sql .= " ORDER BY nama_buah ASC LIMIT $limit OFFSET $offset";

try {
    $products = fetchAll($sql, $params);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

if (empty($products)) {
    echo json_encode(['success' => true, 'html' => '', 'total' => $total]);
    exit;
}

// Build HTML
ob_start();
foreach ($products as $product):
    $stock      = floatval($product['stok_kg']);
    $is_oos     = $stock <= 0;
    $is_limited = !$is_oos && $stock <= 10;
    $badge_text = $is_oos ? 'Habis' : ($is_limited ? 'Terbatas' : 'Tersedia');
    $badge_cls  = $is_oos ? 'out-of-stock' : ($is_limited ? 'limited' : '');
?>
<div class="product-card" data-product-id="<?php echo $product['id']; ?>">
    <div class="product-image">
        <img src="<?php echo htmlspecialchars(getProductImage($product['gambar'])); ?>"
             alt="<?php echo htmlspecialchars($product['nama_buah']); ?>"
             loading="lazy"
             onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400'">
        <span class="stock-badge <?php echo $badge_cls; ?>"><?php echo $badge_text; ?></span>
    </div>
    <div class="product-info">
        <h3 class="product-name"><?php echo htmlspecialchars($product['nama_buah']); ?></h3>
        <p class="product-origin">
            📍 <?php echo htmlspecialchars($product['asal']); ?>
            <?php if ($product['kategori']==='impor'): ?>
            <span style="background:#DBEAFE;color:#2563EB;padding:.125rem .5rem;border-radius:4px;font-size:.75rem">Impor</span>
            <?php endif; ?>
        </p>
        <p class="product-desc"><?php echo htmlspecialchars($product['deskripsi'] ?? 'Buah segar berkualitas premium'); ?></p>
        <div class="product-footer">
            <span class="product-price">
                Rp <?php echo number_format($product['harga_kg'],0,',','.'); ?><small>/kg</small>
            </span>
            <span class="product-stock">Stok: <?php echo $stock; ?> kg</span>
        </div>
        <div class="product-actions">
            <div class="quantity-wrapper">
                <button type="button" class="qty-btn qty-minus"
                        data-product-id="<?php echo $product['id']; ?>"
                        <?php echo $is_oos?'disabled':''; ?>>−</button>
                <input type="number" class="quantity-input"
                       min="1" step="1" value="1"
                       max="<?php echo (int)$stock; ?>"
                       data-product-id="<?php echo $product['id']; ?>"
                       <?php echo $is_oos?'disabled':''; ?>>
                <button type="button" class="qty-btn qty-plus"
                        data-product-id="<?php echo $product['id']; ?>"
                        <?php echo $is_oos?'disabled':''; ?>>+</button>
            </div>
            <button class="btn-add-cart"
                    data-product-id="<?php echo $product['id']; ?>"
                    data-product-name="<?php echo htmlspecialchars($product['nama_buah']); ?>"
                    <?php echo $is_oos?'disabled':''; ?>>
                <span class="btn-text">🛒 Tambah</span>
                <span class="spinner"></span>
            </button>
        </div>
    </div>
</div>
<?php endforeach;
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html'    => $html,
    'total'   => $total,
    'count'   => count($products),
]);