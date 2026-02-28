<?php
/**
 * ================================================
 * CART PAGE - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: pages/cart.php
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

$user       = getLoggedInUser();
$cart       = $_SESSION['cart'] ?? [];
$cart_count = count($cart);

$total = 0;
foreach ($cart as $item) {
    $total += $item['harga_kg'] * $item['qty'];
}

function getCartImage($filename) {
    if (empty($filename)) return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=100';
    if (filter_var($filename, FILTER_VALIDATE_URL)) return $filename;
    return '../assets/images/products/' . basename($filename);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Buah Segar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #2D8659;
            --color-primary-dark: #1F5F3F;
            --color-primary-light: #E8F5ED;
            --color-text: #1A1A1A;
            --color-text-light: #666666;
            --color-text-lighter: #999999;
            --color-background: #F5F7FA;
            --color-white: #FFFFFF;
            --color-border: #E5E5E5;
            --color-error: #DC2626;
            --color-success: #16A34A;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.12);
            --transition: 300ms cubic-bezier(0.4,0,0.2,1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: var(--color-background); color: var(--color-text); line-height: 1.6; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; padding: 0 1.5rem; }

        /* NAVBAR */
        .navbar { background: white; border-bottom: 1px solid var(--color-border); padding: 1.25rem 0; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-inner { max-width: 900px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: 0.625rem; text-decoration: none; }
        .logo-icon { font-size: 1.75rem; }
        .logo-text { font-family: var(--font-display); font-size: 1.375rem; font-weight: 700; color: var(--color-primary); }
        .nav-actions { display: flex; gap: 0.75rem; }
        .btn-nav { padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: var(--transition); border: 2px solid var(--color-primary); cursor: pointer; }
        .btn-nav.outline { background: white; color: var(--color-primary); }
        .btn-nav.outline:hover { background: var(--color-primary-light); }
        .btn-nav.solid { background: var(--color-primary); color: white; }
        .btn-nav.solid:hover { background: var(--color-primary-dark); }

        /* PAGE */
        .page-header { padding: 2rem 0 1.5rem; }
        .page-title { font-family: var(--font-display); font-size: 2rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem; }
        .cart-layout { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; padding-bottom: 3rem; }

        /* CART CARD */
        .cart-card { background: white; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); overflow: hidden; }
        .cart-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; }
        .cart-card-title { font-weight: 700; font-size: 1.05rem; }
        .cart-item-count { font-size: 0.85rem; color: var(--color-text-lighter); background: var(--color-background); padding: 0.25rem 0.75rem; border-radius: 99px; }

        /* CART ITEM */
        .cart-item { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); transition: var(--transition); }
        .cart-item:last-child { border-bottom: none; }
        .cart-item.removing { opacity: 0; transform: translateX(20px); transition: opacity 0.3s, transform 0.3s; }
        .item-img { width: 70px; height: 70px; border-radius: 12px; object-fit: cover; background: var(--color-background); flex-shrink: 0; }
        .item-info { flex: 1; min-width: 0; }
        .item-name { font-weight: 600; font-size: 1rem; margin-bottom: 0.2rem; }
        .item-price { font-size: 0.875rem; color: var(--color-text-light); }
        .item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 0.625rem; flex-shrink: 0; }
        .item-subtotal { font-weight: 700; font-size: 1rem; color: var(--color-primary); white-space: nowrap; }

        /* QTY */
        .qty-control { display: flex; align-items: center; border: 2px solid var(--color-border); border-radius: 10px; overflow: hidden; }
        .qty-btn { width: 34px; height: 34px; border: none; background: #f5f5f5; color: var(--color-text); font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition); user-select: none; }
        .qty-btn:hover:not(:disabled) { background: var(--color-primary-light); color: var(--color-primary); }
        .qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .qty-display { width: 40px; text-align: center; font-weight: 700; font-size: 0.95rem; border-left: 1px solid var(--color-border); border-right: 1px solid var(--color-border); line-height: 34px; }
        .btn-delete { background: none; border: none; cursor: pointer; color: var(--color-text-lighter); font-size: 1.1rem; padding: 0.25rem; border-radius: 6px; transition: var(--transition); }
        .btn-delete:hover { color: var(--color-error); background: #FEE2E2; }

        /* SUMMARY */
        .summary-card { background: white; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); padding: 1.5rem; height: fit-content; position: sticky; top: 90px; }
        .summary-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.5rem 0; border-bottom: 1px solid var(--color-border); }
        .summary-row .label { color: var(--color-text-light); }
        .summary-row .value { font-weight: 600; }
        .summary-divider { border: none; border-top: 2px solid var(--color-border); margin: 1rem 0; }
        .summary-total { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .summary-total .label { font-weight: 700; }
        .summary-total .value { font-weight: 700; font-size: 1.375rem; color: var(--color-primary); }
        .btn-checkout { width: 100%; padding: 1rem; background: var(--color-primary); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-checkout:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-back-link { display: block; text-align: center; margin-top: 1rem; color: var(--color-text-light); font-size: 0.875rem; text-decoration: none; }
        .btn-back-link:hover { color: var(--color-primary); }

        /* ================================================
           CHECKOUT MODAL
        ================================================ */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 500; align-items: center; justify-content: center; padding: 1rem; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: white; border-radius: 20px; width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); animation: popIn 0.25s ease-out; overflow: hidden; max-height: 90vh; overflow-y: auto; }
        @keyframes popIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-header { padding: 1.5rem 1.75rem 1rem; border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: white; z-index: 1; }
        .modal-title { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--color-text-lighter); padding: 0.25rem 0.5rem; border-radius: 6px; transition: var(--transition); }
        .modal-close:hover { background: #f5f5f5; color: var(--color-text); }
        .modal-body { padding: 1.5rem 1.75rem; }

        /* Order summary in modal */
        .modal-summary { background: var(--color-background); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .modal-summary-title { font-size: 0.75rem; font-weight: 600; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; }
        .modal-summary-items { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 0.75rem; }
        .modal-summary-item { display: flex; justify-content: space-between; font-size: 0.875rem; }
        .modal-summary-item .name { color: var(--color-text-light); }
        .modal-summary-item .sub { font-weight: 600; }
        .modal-summary-total { display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--color-border); font-weight: 700; font-size: 0.95rem; }
        .modal-summary-total .total-val { color: var(--color-primary); }

        /* Form fields */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; }
        .form-label .req { color: var(--color-error); margin-left: 2px; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--color-border); border-radius: 10px; font-size: 0.9375rem; font-family: var(--font-body); transition: var(--transition); color: var(--color-text); background: white; }
        .form-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(45,134,89,0.1); }
        .form-input.is-error { border-color: var(--color-error); box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
        textarea.form-input { resize: vertical; min-height: 90px; }
        .field-error { font-size: 0.8rem; color: var(--color-error); margin-top: 0.375rem; display: none; }
        .field-error.show { display: block; }

        .modal-footer { padding: 1rem 1.75rem 1.5rem; display: flex; gap: 0.75rem; border-top: 1px solid var(--color-border); }
        .btn-cancel { flex: 1; padding: 0.875rem; border: 2px solid var(--color-border); background: white; color: var(--color-text-light); border-radius: 10px; font-weight: 600; font-size: 0.9375rem; cursor: pointer; transition: var(--transition); }
        .btn-cancel:hover { border-color: var(--color-text-lighter); color: var(--color-text); }
        .btn-submit-order { flex: 2; padding: 0.875rem; background: var(--color-primary); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 0.9375rem; cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-submit-order:hover:not(:disabled) { background: var(--color-primary-dark); }
        .btn-submit-order:disabled { opacity: 0.6; cursor: not-allowed; }
        .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.4; }
        .empty-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-text { color: var(--color-text-light); margin-bottom: 2rem; font-size: 0.95rem; }
        .btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 2rem; background: var(--color-primary); color: white; border-radius: 10px; text-decoration: none; font-weight: 700; transition: var(--transition); }
        .btn-primary:hover { background: var(--color-primary-dark); transform: translateY(-2px); }

        /* TOAST */
        .toast { position: fixed; bottom: 2rem; right: 2rem; background: white; border-radius: 12px; padding: 1rem 1.5rem; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 1rem; z-index: 9999; animation: slideUp 0.3s ease-out; min-width: 280px; border-left: 4px solid var(--color-success); }
        .toast.error { border-left-color: var(--color-error); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .toast-icon { font-size: 1.25rem; }
        .toast-msg { font-weight: 600; font-size: 0.9rem; flex: 1; }

        /* SUCCESS OVERLAY */
        .success-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .success-overlay.show { display: flex; }
        .success-box { background: white; border-radius: 20px; padding: 3rem 2.5rem; text-align: center; max-width: 380px; width: 90%; animation: popIn 0.3s ease-out; }
        .success-icon { font-size: 4rem; margin-bottom: 1rem; }
        .success-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-success); }
        .success-text { color: var(--color-text-light); margin-bottom: 0.375rem; }
        .success-order-id { font-size: 0.875rem; color: var(--color-text-lighter); margin-bottom: 1.5rem; font-weight: 600; }
        .success-actions { display: flex; flex-direction: column; gap: 0.75rem; }
        .btn-success { display: block; padding: 0.875rem 2rem; background: var(--color-primary); color: white; border-radius: 10px; text-decoration: none; font-weight: 700; transition: var(--transition); }
        .btn-success:hover { background: var(--color-primary-dark); }
        .btn-success-outline { display: block; padding: 0.875rem 2rem; background: white; color: var(--color-primary); border: 2px solid var(--color-primary); border-radius: 10px; text-decoration: none; font-weight: 700; transition: var(--transition); }
        .btn-success-outline:hover { background: var(--color-primary-light); }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .cart-layout { grid-template-columns: 1fr; }
            .summary-card { position: static; }
            .item-img { width: 55px; height: 55px; }
        }
        @media (max-width: 480px) {
            .container { padding: 0 1rem; }
            .cart-item { flex-wrap: wrap; }
            .item-right { flex-direction: row; align-items: center; width: 100%; justify-content: space-between; }
            .modal-header, .modal-body, .modal-footer { padding-left: 1.25rem; padding-right: 1.25rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-inner">
            <a href="../index.php" class="nav-logo">
                <span class="logo-icon">🍎</span>
                <span class="logo-text">Buah Segar</span>
            </a>
            <div class="nav-actions">
                <a href="catalog.php" class="btn-nav outline">← Lanjut Belanja</a>
                <a href="../auth/logout.php" class="btn-nav solid">Logout</a>
            </div>
        </div>
    </nav>

    <!-- ================================================
         CHECKOUT MODAL
    ================================================ -->
    <div class="modal-overlay" id="checkoutModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title">📦 Detail Pengiriman</h2>
                <button class="modal-close" id="modalClose" type="button">✕</button>
            </div>
            <div class="modal-body">

                <!-- Ringkasan di dalam modal -->
                <div class="modal-summary">
                    <div class="modal-summary-title">Ringkasan Pesanan</div>
                    <div class="modal-summary-items">
                        <?php foreach ($cart as $product_id => $item): ?>
                        <div class="modal-summary-item">
                            <span class="name"><?php echo htmlspecialchars($item['nama_buah']); ?> ×<?php echo $item['qty']; ?>kg</span>
                            <span class="sub">Rp <?php echo number_format($item['harga_kg'] * $item['qty'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-summary-total">
                        <span>Total</span>
                        <span class="total-val">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- FORM -->
                <div class="form-group">
                    <label class="form-label" for="inputNama">Nama Penerima <span class="req">*</span></label>
                    <input type="text" class="form-input" id="inputNama"
                           placeholder="Nama lengkap penerima"
                           value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>">
                    <div class="field-error" id="errNama">Nama penerima harus diisi</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inputTelepon">Nomor Telepon <span class="req">*</span></label>
                    <input type="tel" class="form-input" id="inputTelepon"
                           placeholder="Contoh: 08123456789"
                           value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>">
                    <div class="field-error" id="errTelepon">Nomor telepon harus diisi</div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="inputAlamat">Alamat Pengiriman <span class="req">*</span></label>
                    <textarea class="form-input" id="inputAlamat"
                              placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota..."><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                    <div class="field-error" id="errAlamat">Alamat pengiriman harus diisi</div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="btnCancel" type="button">Batal</button>
                <button class="btn-submit-order" id="btnSubmitOrder" type="button">
                    <span id="submitText">✅ Konfirmasi Pesanan</span>
                    <div class="spinner" id="submitSpinner"></div>
                </button>
            </div>
        </div>
    </div>

    <!-- SUCCESS OVERLAY -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-box">
            <div class="success-icon">🎉</div>
            <h2 class="success-title">Pesanan Berhasil!</h2>
            <p class="success-text">Pesanan kamu sudah masuk dan sedang diproses oleh admin.</p>
            <p class="success-order-id" id="successOrderId"></p>
            <div class="success-actions">
                <a href="#" id="btnLihatPesanan" class="btn-success">📋 Lihat Pesanan Saya</a>
                <a href="catalog.php" class="btn-success-outline">🛍️ Belanja Lagi</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🛒 Keranjang Belanja</h1>
        </div>

        <?php if ($cart_count > 0): ?>

        <div class="cart-layout">
            <!-- ITEMS -->
            <div>
                <div class="cart-card">
                    <div class="cart-card-header">
                        <span class="cart-card-title">Item Pesanan</span>
                        <span class="cart-item-count" id="itemCountLabel"><?php echo $cart_count; ?> jenis buah</span>
                    </div>
                    <div id="cartItemsContainer">
                        <?php foreach ($cart as $product_id => $item):
                            $subtotal = $item['harga_kg'] * $item['qty'];
                        ?>
                        <div class="cart-item" id="item-<?php echo $product_id; ?>">
                            <img src="<?php echo htmlspecialchars(getCartImage($item['gambar'])); ?>"
                                 alt="<?php echo htmlspecialchars($item['nama_buah']); ?>"
                                 class="item-img"
                                 onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=100'">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['nama_buah']); ?></div>
                                <div class="item-price">Rp <?php echo number_format($item['harga_kg'], 0, ',', '.'); ?>/kg</div>
                            </div>
                            <div class="item-right">
                                <span class="item-subtotal" id="subtotal-<?php echo $product_id; ?>">
                                    Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                </span>
                                <div class="qty-control">
                                    <button class="qty-btn qty-minus" data-product-id="<?php echo $product_id; ?>"
                                            <?php echo $item['qty'] <= 1 ? 'disabled' : ''; ?>>−</button>
                                    <span class="qty-display" id="qty-<?php echo $product_id; ?>"><?php echo $item['qty']; ?></span>
                                    <button class="qty-btn qty-plus" data-product-id="<?php echo $product_id; ?>"
                                            data-max="<?php echo (int)$item['stok_max']; ?>"
                                            <?php echo $item['qty'] >= $item['stok_max'] ? 'disabled' : ''; ?>>+</button>
                                </div>
                                <button class="btn-delete" data-product-id="<?php echo $product_id; ?>" title="Hapus item">🗑️</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SUMMARY -->
            <div>
                <div class="summary-card">
                    <h2 class="summary-title">Ringkasan Pesanan</h2>
                    <div id="summaryItems">
                        <?php foreach ($cart as $product_id => $item): ?>
                        <div class="summary-row" id="summary-row-<?php echo $product_id; ?>">
                            <span class="label"><?php echo htmlspecialchars($item['nama_buah']); ?> ×<?php echo $item['qty']; ?>kg</span>
                            <span class="value" id="summary-sub-<?php echo $product_id; ?>">Rp <?php echo number_format($item['harga_kg'] * $item['qty'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <hr class="summary-divider">
                    <div class="summary-total">
                        <span class="label">Total</span>
                        <span class="value" id="grandTotal">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                    <button class="btn-checkout" id="btnCheckout" type="button">✅ Checkout Sekarang</button>
                    <a href="catalog.php" class="btn-back-link">← Lanjut Belanja</a>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="cart-card">
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h2 class="empty-title">Keranjang Kosong</h2>
                <p class="empty-text">Belum ada produk di keranjang Anda<br>Yuk mulai belanja buah segar!</p>
                <a href="catalog.php" class="btn-primary">🍎 Mulai Belanja</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // ================================================
    // UTILITY
    // ================================================
    function formatRupiah(n) {
        return 'Rp ' + parseInt(n).toLocaleString('id-ID');
    }
    function showToast(msg, type = 'success') {
        const t = document.createElement('div');
        t.className = 'toast ' + (type === 'error' ? 'error' : '');
        t.innerHTML = `<div class="toast-icon">${type==='success'?'✅':'❌'}</div><div class="toast-msg">${msg}</div>`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }
    function updateItemCountLabel() {
        const n = document.querySelectorAll('.cart-item').length;
        const el = document.getElementById('itemCountLabel');
        if (el) el.textContent = n + ' jenis buah';
    }

    // ================================================
    // MODAL OPEN / CLOSE
    // ================================================
    const modal = document.getElementById('checkoutModal');

    document.getElementById('btnCheckout')?.addEventListener('click', () => {
        clearErrors();
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('modalClose')?.addEventListener('click', closeModal);
    document.getElementById('btnCancel')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // ESC key
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // ================================================
    // FORM VALIDATION
    // ================================================
    function clearErrors() {
        ['Nama','Telepon','Alamat'].forEach(f => {
            document.getElementById('input'+f)?.classList.remove('is-error');
            const err = document.getElementById('err'+f);
            if (err) err.classList.remove('show');
        });
    }

    function validateForm() {
        clearErrors();
        let ok = true;

        const nama   = document.getElementById('inputNama');
        const telp   = document.getElementById('inputTelepon');
        const alamat = document.getElementById('inputAlamat');

        if (!nama.value.trim()) {
            nama.classList.add('is-error');
            document.getElementById('errNama').classList.add('show');
            ok = false;
        }
        if (!telp.value.trim()) {
            telp.classList.add('is-error');
            document.getElementById('errTelepon').textContent = 'Nomor telepon harus diisi';
            document.getElementById('errTelepon').classList.add('show');
            ok = false;
        } else if (!/^[0-9+\-\s]{8,15}$/.test(telp.value.trim())) {
            telp.classList.add('is-error');
            document.getElementById('errTelepon').textContent = 'Format nomor tidak valid (8-15 digit)';
            document.getElementById('errTelepon').classList.add('show');
            ok = false;
        }
        if (!alamat.value.trim()) {
            alamat.classList.add('is-error');
            document.getElementById('errAlamat').classList.add('show');
            ok = false;
        }
        return ok;
    }

    // ================================================
    // SUBMIT ORDER
    // ================================================
    document.getElementById('btnSubmitOrder')?.addEventListener('click', async function() {
        if (!validateForm()) return;

        const nama   = document.getElementById('inputNama').value.trim();
        const telp   = document.getElementById('inputTelepon').value.trim();
        const alamat = document.getElementById('inputAlamat').value.trim();

        this.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('submitSpinner').style.display = 'block';

        try {
            const fd = new FormData();
            fd.append('nama_pemesan', nama);
            fd.append('no_telepon', telp);
            fd.append('alamat_kirim', alamat);

            const res  = await fetch('../api/checkout.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                closeModal();
                document.getElementById('successOrderId').textContent = 'No. Pesanan: #' + data.data.order_id;
                document.getElementById('btnLihatPesanan').href = 'order_detail.php?id=' + data.data.order_id;
                document.getElementById('successOverlay').classList.add('show');
            } else {
                showToast(data.message, 'error');
                this.disabled = false;
                document.getElementById('submitText').style.display = 'inline';
                document.getElementById('submitSpinner').style.display = 'none';
            }
        } catch(err) {
            showToast('Terjadi kesalahan sistem', 'error');
            this.disabled = false;
            document.getElementById('submitText').style.display = 'inline';
            document.getElementById('submitSpinner').style.display = 'none';
        }
    });

    // ================================================
    // QTY MINUS / PLUS
    // ================================================
    document.addEventListener('click', async function(e) {
        const minus = e.target.classList.contains('qty-minus');
        const plus  = e.target.classList.contains('qty-plus');
        if (!minus && !plus) return;

        const productId = e.target.dataset.productId;
        const qtyEl     = document.getElementById('qty-' + productId);
        const current   = parseInt(qtyEl.textContent);
        const max       = parseInt(document.querySelector(`.qty-plus[data-product-id="${productId}"]`)?.dataset.max) || 9999;

        if (minus && current <= 1) return;
        if (plus  && current >= max) return;

        await updateQty(productId, minus ? current - 1 : current + 1);
    });

    async function updateQty(productId, newQty) {
        const minusBtn = document.querySelector(`.qty-minus[data-product-id="${productId}"]`);
        const plusBtn  = document.querySelector(`.qty-plus[data-product-id="${productId}"]`);
        const qtyEl    = document.getElementById('qty-' + productId);
        if (minusBtn) minusBtn.disabled = true;
        if (plusBtn)  plusBtn.disabled  = true;

        try {
            const fd = new FormData();
            fd.append('product_id', productId);
            fd.append('quantity', newQty);
            const res  = await fetch('../api/update_cart.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                qtyEl.textContent = newQty;
                const subEl = document.getElementById('subtotal-' + productId);
                if (subEl) subEl.textContent = formatRupiah(data.data.subtotal);
                const sumSubEl = document.getElementById('summary-sub-' + productId);
                if (sumSubEl) sumSubEl.textContent = formatRupiah(data.data.subtotal);
                const sumRow = document.getElementById('summary-row-' + productId);
                if (sumRow) {
                    const lbl = sumRow.querySelector('.label');
                    if (lbl) lbl.textContent = lbl.textContent.split(' ×')[0] + ' ×' + newQty + 'kg';
                }
                const totalEl = document.getElementById('grandTotal');
                if (totalEl) totalEl.textContent = formatRupiah(data.data.cart_total);
            } else {
                showToast(data.message, 'error');
            }
        } catch(err) {
            showToast('Terjadi kesalahan', 'error');
        } finally {
            const cur = parseInt(qtyEl.textContent);
            const max = parseInt(plusBtn?.dataset.max) || 9999;
            if (minusBtn) minusBtn.disabled = (cur <= 1);
            if (plusBtn)  plusBtn.disabled  = (cur >= max);
        }
    }

    // ================================================
    // DELETE ITEM
    // ================================================
    document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;
        if (!confirm('Hapus item ini dari keranjang?')) return;

        const productId = btn.dataset.productId;
        try {
            const fd = new FormData();
            fd.append('product_id', productId);
            const res  = await fetch('../api/remove_cart.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                const itemEl = document.getElementById('item-' + productId);
                if (itemEl) { itemEl.classList.add('removing'); setTimeout(() => itemEl.remove(), 300); }
                document.getElementById('summary-row-' + productId)?.remove();
                const totalEl = document.getElementById('grandTotal');
                if (totalEl) totalEl.textContent = formatRupiah(data.data.cart_total);
                setTimeout(() => {
                    updateItemCountLabel();
                    if (data.data.cart_count === 0) location.reload();
                }, 350);
                showToast('Item dihapus dari keranjang');
            } else {
                showToast(data.message, 'error');
            }
        } catch(err) {
            showToast('Terjadi kesalahan', 'error');
        }
    });
    </script>
</body>
</html>