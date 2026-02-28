<?php
/**
 * ================================================
 * CATALOG PAGE - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: pages/catalog.php
 * Updated: Real AJAX integration dengan API
 * ================================================
 */

// Start session & check login
session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login untuk akses catalog
checkLogin();

// Get logged in user
$user = getLoggedInUser();

// Get cart count
$cart_count = getCartItemCount();

// ================================================
// HELPER FUNCTION - IMAGE PATH
// ================================================
function getProductImage($filename) {
    if (empty($filename)) {
        return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400';
    }
    
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    $clean_filename = basename($filename);
    return '../assets/images/products/' . $clean_filename;
}

// ================================================
// FILTER & SEARCH LOGIC
// ================================================
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'semua';

$sql = "SELECT * FROM buah WHERE status = 'active'";
$params = [];

if (!empty($search)) {
    $sql .= " AND nama_buah LIKE ?";
    $params[] = "%{$search}%";
}

if ($filter === 'lokal') {
    $sql .= " AND kategori = 'lokal'";
} elseif ($filter === 'impor') {
    $sql .= " AND kategori = 'impor'";
}

$sql .= " ORDER BY nama_buah ASC";

try {
    $products = fetchAll($sql, $params);
} catch (Exception $e) {
    error_log("Catalog Error: " . $e->getMessage());
    $products = [];
}

$total_products = count($products);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - Buah Segar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #2D8659;
            --color-primary-dark: #1F5F3F;
            --color-primary-light: #E8F5ED;
            --color-secondary: #FF8C42;
            --color-accent: #FFD166;
            --color-text: #1A1A1A;
            --color-text-light: #666666;
            --color-text-lighter: #999999;
            --color-background: #FFFFFF;
            --color-background-alt: #FAFAFA;
            --color-border: #E5E5E5;
            --color-error: #DC2626;
            --color-success: #16A34A;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: var(--color-background-alt); color: var(--color-text); line-height: 1.6; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
        
        /* NAVBAR */
        .navbar { background: white; border-bottom: 1px solid var(--color-border); padding: 1.25rem 0; position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow-sm); }
        .nav-container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .logo-icon { font-size: 2rem; }
        .logo-text { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--color-primary); }
        .nav-user { display: flex; align-items: center; gap: 1.5rem; }
        .user-name { font-size: 0.9375rem; color: var(--color-text-light); }
        .user-name strong { color: var(--color-text); font-weight: 600; }
        .nav-link { padding: 0.5rem 1.25rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9375rem; transition: var(--transition); position: relative; display: inline-flex; align-items: center; gap: 0.5rem; }
        .nav-link:hover { background: var(--color-primary-dark); transform: translateY(-2px); }
        .nav-link.secondary { background: transparent; color: var(--color-primary); border: 2px solid var(--color-primary); }
        .nav-link.secondary:hover { background: var(--color-primary-light); }
        .cart-badge { position: absolute; top: -8px; right: -8px; background: var(--color-error); color: white; font-size: 0.625rem; font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 6px; }
        
        /* HEADER */
        .page-header { padding: 3rem 0 2rem; background: linear-gradient(135deg, var(--color-primary-light) 0%, white 100%); }
        .page-title { font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.5rem; }
        .page-subtitle { font-size: 1.125rem; color: var(--color-text-light); }
        
        /* TOOLBAR */
        .toolbar { padding: 2rem 0; display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .search-box { flex: 1; min-width: 280px; max-width: 400px; position: relative; }
        .search-input { width: 100%; padding: 0.875rem 1rem 0.875rem 3rem; border: 2px solid var(--color-border); border-radius: 12px; font-size: 1rem; transition: var(--transition); }
        .search-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1); }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--color-text-light); }
        .filter-group { display: flex; gap: 0.75rem; }
        .filter-btn { padding: 0.75rem 1.5rem; border: 2px solid var(--color-border); background: white; border-radius: 10px; font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: var(--transition); color: var(--color-text); text-decoration: none; }
        .filter-btn:hover { border-color: var(--color-primary); color: var(--color-primary); background: var(--color-primary-light); }
        .filter-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .product-count { font-size: 0.9375rem; color: var(--color-text-light); }
        
        /* PRODUCTS GRID */
        .products-section { padding: 2rem 0 4rem; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; }
        .product-card { background: white; border-radius: 16px; overflow: hidden; border: 1px solid var(--color-border); transition: var(--transition); }
        .product-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); border-color: transparent; }
        .product-image { position: relative; width: 100%; height: 260px; overflow: hidden; background: var(--color-background-alt); }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: var(--transition); }
        .product-card:hover .product-image img { transform: scale(1.1); }
        .stock-badge { position: absolute; top: 12px; right: 12px; padding: 0.375rem 0.75rem; background: var(--color-success); color: white; font-size: 0.75rem; font-weight: 600; border-radius: 6px; text-transform: uppercase; }
        .stock-badge.out-of-stock { background: var(--color-error); }
        .stock-badge.limited { background: var(--color-secondary); }
        .product-info { padding: 1.25rem; }
        .product-name { font-size: 1.125rem; font-weight: 600; color: var(--color-text); margin-bottom: 0.5rem; }
        .product-origin { font-size: 0.875rem; color: var(--color-text-light); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.375rem; }
        .product-desc { font-size: 0.875rem; color: var(--color-text-light); line-height: 1.5; margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-footer { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--color-border); }
        .product-price { font-size: 1.375rem; font-weight: 700; color: var(--color-primary); }
        .product-price small { font-size: 0.875rem; font-weight: 400; color: var(--color-text-light); }
        .product-stock { font-size: 0.875rem; color: var(--color-text-lighter); }
        
        /* ===== QUANTITY +/- BUTTON ===== */
        .product-actions { display: flex; gap: 0.75rem; align-items: center; }
        .quantity-wrapper { display: flex; align-items: center; border: 2px solid var(--color-border); border-radius: 10px; overflow: hidden; background: white; transition: border-color var(--transition); }
        .quantity-wrapper:focus-within { border-color: var(--color-primary); }
        .qty-btn { width: 36px; height: 38px; border: none; background: #f5f5f5; color: var(--color-text); font-size: 1.1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition); flex-shrink: 0; user-select: none; }
        .qty-btn:hover:not(:disabled) { background: var(--color-primary-light); color: var(--color-primary); }
        .qty-btn:active:not(:disabled) { background: var(--color-primary); color: white; }
        .qty-btn:disabled { opacity: 0.35; cursor: not-allowed; }
        .quantity-input { width: 44px; padding: 0.25rem 0; border: none; border-left: 1px solid var(--color-border); border-right: 1px solid var(--color-border); text-align: center; font-weight: 700; font-size: 0.9375rem; font-family: var(--font-body); background: white; color: var(--color-text); -moz-appearance: textfield; }
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .quantity-input:focus { outline: none; }
        .quantity-input:disabled { background: #f5f5f5; color: var(--color-text-lighter); }

        /* ===== ADD TO CART BUTTON ===== */
        .btn-add-cart { flex: 1; padding: 0.75rem 1.25rem; background: var(--color-primary); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-add-cart:hover:not(:disabled) { background: var(--color-primary-dark); transform: translateY(-2px); }
        .btn-add-cart:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-add-cart.loading { background: var(--color-text-lighter); }
        .spinner { width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* TOAST */
        .toast { position: fixed; bottom: 2rem; right: 2rem; background: white; border-radius: 12px; padding: 1rem 1.5rem; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 1rem; z-index: 1000; animation: slideUp 0.3s ease-out; min-width: 300px; border-left: 4px solid var(--color-success); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .toast.error { border-left-color: var(--color-error); }
        .toast-icon { font-size: 1.5rem; }
        .toast-message { flex: 1; font-weight: 600; }
        
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-title { font-family: var(--font-display); font-size: 1.75rem; font-weight: 600; color: var(--color-text); margin-bottom: 0.5rem; }
        .empty-text { color: var(--color-text-light); margin-bottom: 2rem; }
        
        /* FOOTER */
        .footer { background: var(--color-text); color: white; padding: 3rem 0 1.5rem; margin-top: 4rem; }
        .footer-content { text-align: center; }
        .footer-logo { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .footer-text { color: rgba(255,255,255,0.7); font-size: 0.875rem; }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .nav-container { padding: 0 1rem; }
            .page-title { font-size: 2rem; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            .filter-group { justify-content: center; }
            .products-grid { grid-template-columns: 1fr; gap: 1.5rem; }
            .nav-user { flex-wrap: wrap; gap: 0.75rem; }
            .user-name { width: 100%; text-align: center; }
            .toast { bottom: 1rem; right: 1rem; left: 1rem; min-width: auto; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-logo">
                <span class="logo-icon">🍎</span>
                <span class="logo-text">Buah Segar</span>
            </a>
            <div class="nav-user">
                <span class="user-name">Halo, <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong></span>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php" class="nav-link secondary">Dashboard</a>
                <?php endif; ?>
                <a href="cart.php" class="nav-link secondary" id="cartLink">
                    🛒 Keranjang
                    <?php if ($cart_count > 0): ?>
                    <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Katalog Buah Segar</h1>
            <p class="page-subtitle">Pilih buah favorit Anda dan pesan sekarang!</p>
        </div>
    </div>
    
    <!-- Toolbar -->
    <div class="container">
        <div class="toolbar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <form method="GET" action="" style="margin: 0;">
                    <input type="text" name="search" class="search-input" placeholder="Cari buah..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                </form>
            </div>
            
            <div class="filter-group">
                <a href="?filter=semua<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $filter === 'semua' ? 'active' : ''; ?>">Semua</a>
                <a href="?filter=lokal<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $filter === 'lokal' ? 'active' : ''; ?>">Lokal</a>
                <a href="?filter=impor<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $filter === 'impor' ? 'active' : ''; ?>">Impor</a>
            </div>
            
            <div class="product-count">
                Menampilkan <strong><?php echo $total_products; ?></strong> produk
            </div>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="container">
        <div class="products-section">
            <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): 
                    $stock = floatval($product['stok_kg']);
                    $is_out_of_stock = $stock <= 0;
                    $is_limited = $stock > 0 && $stock <= 10;
                    $stock_status = $is_out_of_stock ? 'Habis' : ($is_limited ? 'Terbatas' : 'Tersedia');
                    $stock_class = $is_out_of_stock ? 'out-of-stock' : ($is_limited ? 'limited' : '');
                ?>
                <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars(getProductImage($product['gambar'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['nama_buah']); ?>" 
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400'">
                        <span class="stock-badge <?php echo $stock_class; ?>"><?php echo $stock_status; ?></span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['nama_buah']); ?></h3>
                        <p class="product-origin">
                            📍 <?php echo htmlspecialchars($product['asal']); ?>
                            <?php if ($product['kategori'] === 'impor'): ?>
                                <span style="background: #DBEAFE; color: #2563EB; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.25rem;">Impor</span>
                            <?php endif; ?>
                        </p>
                        <p class="product-desc"><?php echo htmlspecialchars($product['deskripsi'] ?? 'Buah segar berkualitas premium'); ?></p>
                        <div class="product-footer">
                            <span class="product-price">
                                Rp <?php echo number_format($product['harga_kg'], 0, ',', '.'); ?>
                                <small>/kg</small>
                            </span>
                            <span class="product-stock">Stok: <?php echo $stock; ?> kg</span>
                        </div>
                        <div class="product-actions">
                            <!-- Quantity +/- -->
                            <div class="quantity-wrapper">
                                <button type="button" class="qty-btn qty-minus" data-product-id="<?php echo $product['id']; ?>" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>−</button>
                                <input type="number" class="quantity-input"
                                       min="1" step="1" value="1"
                                       max="<?php echo (int)$stock; ?>"
                                       data-product-id="<?php echo $product['id']; ?>"
                                       <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                                <button type="button" class="qty-btn qty-plus" data-product-id="<?php echo $product['id']; ?>" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>+</button>
                            </div>
                            <!-- Add to cart -->
                            <button class="btn-add-cart"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-name="<?php echo htmlspecialchars($product['nama_buah']); ?>"
                                    <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                                <span class="btn-text">🛒 Tambah</span>
                                <span class="spinner" style="display: none;"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h2 class="empty-title">Produk Tidak Ditemukan</h2>
                <p class="empty-text">Maaf, tidak ada produk yang sesuai dengan pencarian Anda.<br>Coba kata kunci lain atau hapus filter.</p>
                <a href="catalog.php" class="nav-link">Lihat Semua Produk</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">🍎 Buah Segar</div>
                <p class="footer-text">&copy; 2026 Buah Segar. Kesegaran Alami Setiap Hari.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // ================================================
        // QUANTITY +/- BUTTONS
        // ================================================
        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.productId;
                const input = document.querySelector(`.quantity-input[data-product-id="${id}"]`);
                const min = parseInt(input.min) || 1;
                const current = parseInt(input.value) || 1;
                if (current > min) input.value = current - 1;
                updateMinusBtn(id);
            });
        });

        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.productId;
                const input = document.querySelector(`.quantity-input[data-product-id="${id}"]`);
                const max = parseInt(input.max);
                const current = parseInt(input.value) || 1;
                if (!max || current < max) input.value = current + 1;
                updateMinusBtn(id);
            });
        });

        // Prevent non-integer input & clamp on blur
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const min = parseInt(this.min) || 1;
                const max = parseInt(this.max);
                let val = parseInt(this.value) || min;
                if (val < min) val = min;
                if (max && val > max) val = max;
                this.value = val;
                updateMinusBtn(this.dataset.productId);
            });
        });

        function updateMinusBtn(productId) {
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            const minusBtn = document.querySelector(`.qty-minus[data-product-id="${productId}"]`);
            const min = parseInt(input.min) || 1;
            minusBtn.disabled = parseInt(input.value) <= min;
        }

        // Init: disable minus buttons on load (all start at min=1)
        document.querySelectorAll('.quantity-input').forEach(input => {
            updateMinusBtn(input.dataset.productId);
        });

        // ================================================
        // TOAST NOTIFICATION
        // ================================================
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">${type === 'success' ? '✅' : '❌'}</div>
                <div class="toast-message">${message}</div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // ================================================
        // UPDATE CART BADGE
        // ================================================
        function updateCartBadge(count) {
            const cartLink = document.getElementById('cartLink');
            let badge = document.getElementById('cartBadge');
            
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.id = 'cartBadge';
                    cartLink.appendChild(badge);
                }
                badge.textContent = count;
            } else {
                if (badge) badge.remove();
            }
        }
        
        // ================================================
        // ADD TO CART (AJAX)
        // ================================================
        document.querySelectorAll('.btn-add-cart').forEach(button => {
            button.addEventListener('click', async function() {
                if (this.disabled) return;
                
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const quantityInput = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                const quantity = parseInt(quantityInput.value);
                
                if (quantity <= 0 || isNaN(quantity)) {
                    showToast('Jumlah harus lebih dari 0', 'error');
                    return;
                }
                
                // Show loading
                this.disabled = true;
                this.classList.add('loading');
                this.querySelector('.btn-text').style.display = 'none';
                this.querySelector('.spinner').style.display = 'block';
                
                try {
                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('quantity', quantity);
                    
                    const response = await fetch('../api/add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(`${productName} ditambahkan ke keranjang!`, 'success');
                        updateCartBadge(data.data.cart_count);
                        
                        // Reset quantity ke 1
                        quantityInput.value = 1;
                        updateMinusBtn(productId);
                        
                        // Button success state
                        this.querySelector('.btn-text').textContent = '✓ Ditambahkan';
                        setTimeout(() => {
                            this.querySelector('.btn-text').textContent = '🛒 Tambah';
                        }, 2000);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Terjadi kesalahan sistem', 'error');
                } finally {
                    this.disabled = false;
                    this.classList.remove('loading');
                    this.querySelector('.btn-text').style.display = 'inline';
                    this.querySelector('.spinner').style.display = 'none';
                }
            });
        });
        
        // ================================================
        // SEARCH ON TYPE
        // ================================================
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }
    </script>
</body>
</html>