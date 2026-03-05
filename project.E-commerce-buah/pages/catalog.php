<?php
/**
 * CATALOG PAGE - BUAH SEGAR
 * Mobile: 3-col Shopee grid + Load More pagination
 */
session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();
$user       = getLoggedInUser();
$cart_count = getCartItemCount();

function getProductImage($filename) {
    if (empty($filename)) return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400';
    if (filter_var($filename, FILTER_VALIDATE_URL)) return $filename;
    return '../assets/images/products/' . basename($filename);
}

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'semua';

$count_sql    = "SELECT COUNT(*) as total FROM buah WHERE status = 'active'";
$count_params = [];
if (!empty($search)) { $count_sql .= " AND nama_buah LIKE ?"; $count_params[] = "%{$search}%"; }
if ($filter === 'lokal') $count_sql .= " AND kategori = 'lokal'";
if ($filter === 'impor') $count_sql .= " AND kategori = 'impor'";
try {
    $count_row      = fetchOne($count_sql, $count_params);
    $total_products = intval($count_row['total'] ?? 0);
} catch (Exception $e) { $total_products = 0; }

$limit  = 12;
$sql    = "SELECT * FROM buah WHERE status = 'active'";
$params = [];
if (!empty($search)) { $sql .= " AND nama_buah LIKE ?"; $params[] = "%{$search}%"; }
if ($filter === 'lokal') $sql .= " AND kategori = 'lokal'";
if ($filter === 'impor') $sql .= " AND kategori = 'impor'";
$sql .= " ORDER BY nama_buah ASC LIMIT $limit";
try { $products = fetchAll($sql, $params); } catch (Exception $e) { $products = []; }
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
    --primary:#2D8659; --primary-dark:#1F5F3F; --primary-light:#E8F5ED;
    --secondary:#FF8C42; --text:#1A1A1A; --text-light:#666; --text-lighter:#999;
    --bg:#fff; --bg-alt:#F5F7FA; --border:#E5E5E5; --error:#DC2626; --success:#16A34A;
    --font-display:'Playfair Display',serif; --font-body:'DM Sans',sans-serif;
    --shadow-sm:0 2px 8px rgba(0,0,0,.04); --shadow-md:0 4px 16px rgba(0,0,0,.08);
    --shadow-lg:0 8px 32px rgba(0,0,0,.12); --t:280ms cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font-body);background:var(--bg-alt);color:var(--text);line-height:1.6}
.container{max-width:1280px;margin:0 auto;padding:0 2rem}

/* NAVBAR */
.navbar{background:#fff;border-bottom:1px solid var(--border);padding:.875rem 0;position:sticky;top:0;z-index:1000;box-shadow:var(--shadow-sm)}
.nav-container{max-width:1280px;margin:0 auto;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}
.nav-logo{display:flex;align-items:center;gap:.5rem;text-decoration:none;flex-shrink:0}
.logo-icon{font-size:1.625rem}.logo-text{font-family:var(--font-display);font-size:1.25rem;font-weight:700;color:var(--primary)}

/* Desktop nav */
.nav-desktop{display:flex;align-items:center;gap:.5rem}
.nav-greeting{font-size:.875rem;color:var(--text-light);margin-right:.5rem;white-space:nowrap}
.nav-greeting strong{color:var(--text);font-weight:600}
.nav-btn{padding:.5rem .875rem;text-decoration:none;border-radius:8px;font-weight:600;font-size:.8125rem;transition:var(--t);position:relative;display:inline-flex;align-items:center;gap:.375rem;white-space:nowrap;border:2px solid var(--primary)}
.nav-btn.outline{background:transparent;color:var(--primary)}
.nav-btn.outline:hover{background:var(--primary-light)}
.nav-btn.solid{background:var(--primary);color:#fff}
.nav-btn.solid:hover{background:var(--primary-dark)}
.cart-badge{position:absolute;top:-7px;right:-7px;background:var(--error);color:#fff;font-size:.6rem;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px}

/* Mobile nav */
.nav-mobile{display:none;align-items:center;gap:.5rem}
.cart-icon-btn{position:relative;text-decoration:none;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:var(--bg-alt);border:none;cursor:pointer;font-size:1.25rem}
.hamburger-btn{width:40px;height:40px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:5px;background:none;border:2px solid var(--primary);border-radius:10px;cursor:pointer;padding:0}
.hamburger-btn span{width:18px;height:2px;background:var(--primary);border-radius:2px;transition:var(--t);display:block}
.hamburger-btn.open span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
.hamburger-btn.open span:nth-child(2){opacity:0}
.hamburger-btn.open span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}

/* Mobile dropdown menu */
.mobile-menu{display:none;position:absolute;top:calc(100% + 4px);right:0;background:#fff;border-radius:12px;box-shadow:var(--shadow-lg);border:1px solid var(--border);padding:.5rem;min-width:200px;z-index:999}
.mobile-menu.open{display:block}
.mobile-menu-wrap{position:relative}
.mobile-menu a{display:flex;align-items:center;gap:.625rem;padding:.75rem 1rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem;color:var(--text);transition:var(--t)}
.mobile-menu a:hover{background:var(--primary-light);color:var(--primary)}
.mobile-menu a.logout{color:var(--error);border-top:1px solid var(--border);margin-top:.25rem;padding-top:1rem}
.mobile-menu a.logout:hover{background:#FEE2E2}
.mobile-menu .m-user{padding:.75rem 1rem .5rem;font-size:.8125rem;color:var(--text-light);border-bottom:1px solid var(--border);margin-bottom:.25rem}
.mobile-menu .m-user strong{color:var(--text);display:block;font-size:.9rem}

/* PAGE HEADER */
.page-header{padding:2.5rem 0 2rem;background:linear-gradient(135deg,var(--primary-light) 0%,#fff 100%)}
.page-title{font-family:var(--font-display);font-size:2.25rem;font-weight:700;color:var(--text);margin-bottom:.375rem}
.page-subtitle{font-size:1rem;color:var(--text-light)}

/* TOOLBAR */
.toolbar{padding:1.25rem 0;display:flex;gap:.875rem;flex-wrap:wrap;align-items:center}
.search-box{flex:1;min-width:200px;position:relative}
.search-input{width:100%;padding:.75rem 1rem .75rem 2.75rem;border:2px solid var(--border);border-radius:12px;font-size:.9375rem;font-family:var(--font-body);transition:var(--t);background:#fff}
.search-input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(45,134,89,.1)}
.search-icon{position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:var(--text-light);pointer-events:none}
.filter-group{display:flex;gap:.5rem;flex-shrink:0}
.filter-btn{padding:.625rem 1.125rem;border:2px solid var(--border);background:#fff;border-radius:10px;font-size:.875rem;font-weight:600;cursor:pointer;transition:var(--t);color:var(--text);text-decoration:none;white-space:nowrap}
.filter-btn:hover{border-color:var(--primary);color:var(--primary);background:var(--primary-light)}
.filter-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
.product-count{font-size:.8125rem;color:var(--text-light);width:100%}

/* PRODUCTS GRID — desktop */
.products-section{padding:1.5rem 0 4rem}
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem}

/* PRODUCT CARD */
.product-card{background:#fff;border-radius:16px;overflow:hidden;border:1px solid var(--border);transition:var(--t)}
.product-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg);border-color:transparent}
.product-image{position:relative;width:100%;aspect-ratio:1/1;overflow:hidden;background:var(--bg-alt)}
.product-image img{width:100%;height:100%;object-fit:cover;transition:transform .45s ease}
.product-card:hover .product-image img{transform:scale(1.08)}
.stock-badge{position:absolute;top:10px;right:10px;padding:.3rem .7rem;background:var(--success);color:#fff;font-size:.7rem;font-weight:700;border-radius:6px;text-transform:uppercase}
.stock-badge.out-of-stock{background:var(--error)}.stock-badge.limited{background:var(--secondary)}
.product-info{padding:1.125rem}
.product-name{font-size:1rem;font-weight:600;color:var(--text);margin-bottom:.375rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-origin{font-size:.8125rem;color:var(--text-light);margin-bottom:.75rem;display:flex;align-items:center;gap:.375rem;flex-wrap:wrap}
.product-desc{font-size:.8125rem;color:var(--text-light);line-height:1.5;margin-bottom:.875rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-footer{display:flex;justify-content:space-between;align-items:center;margin-bottom:.875rem;padding-top:.75rem;border-top:1px solid var(--border)}
.product-price{font-size:1.25rem;font-weight:700;color:var(--primary)}
.product-price small{font-size:.8125rem;font-weight:400;color:var(--text-light)}
.product-stock{font-size:.8125rem;color:var(--text-lighter)}

/* ACTIONS */
.product-actions{display:flex;gap:.625rem;align-items:center}
.quantity-wrapper{display:flex;align-items:center;border:2px solid var(--border);border-radius:10px;overflow:hidden;background:#fff;transition:border-color var(--t);flex-shrink:0}
.quantity-wrapper:focus-within{border-color:var(--primary)}
.qty-btn{width:34px;height:36px;border:none;background:#f5f5f5;color:var(--text);font-size:1.05rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--t);user-select:none;flex-shrink:0}
.qty-btn:hover:not(:disabled){background:var(--primary-light);color:var(--primary)}
.qty-btn:disabled{opacity:.35;cursor:not-allowed}
.quantity-input{width:40px;padding:.25rem 0;border:none;border-left:1px solid var(--border);border-right:1px solid var(--border);text-align:center;font-weight:700;font-size:.9375rem;font-family:var(--font-body);background:#fff;color:var(--text);-moz-appearance:textfield}
.quantity-input::-webkit-outer-spin-button,.quantity-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.quantity-input:focus{outline:none}
.quantity-input:disabled{background:#f5f5f5;color:var(--text-lighter)}
.btn-add-cart{flex:1;padding:.625rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;transition:var(--t);display:flex;align-items:center;justify-content:center;gap:.375rem;min-width:0}
.btn-add-cart:hover:not(:disabled){background:var(--primary-dark);transform:translateY(-1px)}
.btn-add-cart:disabled{opacity:.5;cursor:not-allowed}
.btn-add-cart.loading{background:var(--text-lighter)}
.spinner{width:15px;height:15px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;display:none;flex-shrink:0}
@keyframes spin{to{transform:rotate(360deg)}}

/* LOAD MORE */
.load-more-wrap{text-align:center;margin-top:2.5rem}
.btn-load-more{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 2.5rem;background:#fff;color:var(--primary);border:2px solid var(--primary);border-radius:12px;font-weight:700;font-size:.9375rem;cursor:pointer;transition:var(--t);font-family:var(--font-body)}
.btn-load-more:hover:not(:disabled){background:var(--primary-light);transform:translateY(-2px);box-shadow:var(--shadow-md)}
.btn-load-more:disabled{opacity:.55;cursor:not-allowed;transform:none}
.load-more-count{font-size:.8125rem;color:var(--text-lighter);margin-top:.75rem}
.load-more-done{text-align:center;padding:1.25rem;color:var(--text-lighter);font-size:.875rem}

/* SKELETON */
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.skeleton-card{border-radius:16px;overflow:hidden;background:#fff;border:1px solid var(--border)}
.skeleton-img{aspect-ratio:1/1;background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite}
.skeleton-body{padding:1.125rem}
.skeleton-line{height:13px;border-radius:6px;background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;margin-bottom:.625rem}
.skeleton-line.w60{width:60%}.skeleton-line.w40{width:40%}

/* EMPTY */
.empty-state{text-align:center;padding:5rem 2rem}
.empty-icon{font-size:4rem;opacity:.4;margin-bottom:1rem}
.empty-title{font-family:var(--font-display);font-size:1.625rem;font-weight:700;margin-bottom:.5rem}
.empty-text{color:var(--text-light);margin-bottom:2rem;font-size:.9375rem}

/* TOAST */
.toast{position:fixed;bottom:2rem;right:2rem;background:#fff;border-radius:12px;padding:1rem 1.5rem;box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:.875rem;z-index:9999;animation:toastIn .3s ease-out;min-width:280px;border-left:4px solid var(--success)}
.toast.error{border-left-color:var(--error)}
@keyframes toastIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.toast-icon{font-size:1.25rem}.toast-msg{font-weight:600;font-size:.9rem;flex:1}

/* FOOTER */
.footer{background:var(--text);color:#fff;padding:3rem 0 1.5rem;margin-top:4rem}
.footer-inner{text-align:center}
.footer-logo{font-family:var(--font-display);font-size:1.375rem;font-weight:700;margin-bottom:.375rem}
.footer-copy{color:rgba(255,255,255,.6);font-size:.875rem}

/* TABLET 768-1023px */
@media(max-width:1023px){
    .products-grid{grid-template-columns:repeat(3,1fr);gap:1.25rem}
}

/* MOBILE < 768px — SHOPEE STYLE */
@media(max-width:767px){
    .container{padding:0 .75rem}
    .nav-container{padding:0 .875rem}
    /* Swap navbar: hide desktop, show mobile */
    .nav-desktop{display:none}
    .nav-mobile{display:flex}
    .page-header{padding:1.5rem 0 1.25rem}
    .page-title{font-size:1.5rem}.page-subtitle{font-size:.875rem}
    .toolbar{padding:1rem 0;gap:.625rem}
    .search-box{min-width:0;width:100%;flex:none}
    .filter-group{gap:.5rem;width:100%}
    .filter-btn{padding:.5rem .875rem;font-size:.8125rem;flex:1;text-align:center;justify-content:center}
    .product-count{font-size:.8125rem}

    /* 3 KOLOM SHOPEE */
    .products-grid{grid-template-columns:repeat(3,1fr);gap:.5rem}

    /* Card compact */
    .product-card{border-radius:10px}
    .product-card:hover{transform:none;box-shadow:none;border-color:var(--border)}
    .product-info{padding:.5rem .5rem .625rem}
    .product-origin{display:none}
    .product-desc{display:none}
    .product-name{font-size:.75rem;font-weight:600;margin-bottom:.25rem;-webkit-line-clamp:2}
    .product-footer{padding-top:.375rem;margin-bottom:.5rem;flex-direction:column;align-items:flex-start;gap:0;border-top-color:#f0f0f0}
    .product-price{font-size:.875rem}
    .product-price small{display:none}
    .product-stock{display:none}
    .stock-badge{font-size:.55rem;padding:2px 5px;top:5px;right:5px;border-radius:4px;letter-spacing:0}

    /* Actions compact */
    .product-actions{gap:.3rem;flex-wrap:nowrap}
    .quantity-wrapper{border-radius:7px}
    .qty-btn{width:26px;height:27px;font-size:.875rem}
    .quantity-input{width:26px;font-size:.75rem}
    .btn-add-cart{padding:.4375rem .375rem;font-size:.7rem;border-radius:7px;gap:.2rem}
    .btn-add-cart:hover{transform:none}

    /* Load more */
    .btn-load-more{width:100%;justify-content:center;padding:.75rem 2rem;font-size:.875rem}
    .load-more-wrap{margin-top:1.5rem}
    .toast{bottom:1rem;right:.75rem;left:.75rem;min-width:auto}
}

/* Extra small < 400px */
@media(max-width:399px){
    .products-grid{gap:.375rem}
    .product-info{padding:.375rem .375rem .5rem}
    .product-name{font-size:.6875rem}
    .product-price{font-size:.8125rem}
    .qty-btn{width:22px;height:24px;font-size:.8rem}
    .quantity-input{width:22px;font-size:.7rem}
    .btn-add-cart{font-size:.65rem;padding:.375rem .3rem}
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a href="../index.php" class="nav-logo">
      <span class="logo-icon">🍎</span>
      <span class="logo-text">Buah Segar</span>
    </a>

    <!-- DESKTOP: semua tombol tampil -->
    <div class="nav-desktop">
      <span class="nav-greeting">Halo, <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong></span>
      <?php if (isAdmin()): ?>
        <a href="../admin/dashboard.php" class="nav-btn outline">⚙️ Dashboard</a>
      <?php endif; ?>
      <a href="my_orders.php" class="nav-btn outline">📋 Pesanan Saya</a>
      <a href="cart.php" class="nav-btn outline" id="cartLink">
        🛒 Keranjang
        <?php if ($cart_count > 0): ?>
        <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>
      <a href="../auth/logout.php" class="nav-btn solid">Logout</a>
    </div>

    <!-- MOBILE: ikon keranjang + hamburger -->
    <div class="nav-mobile">
      <a href="cart.php" class="cart-icon-btn" id="cartLinkMobile">
        🛒
        <?php if ($cart_count > 0): ?>
        <span class="cart-badge" id="cartBadgeMobile"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>
      <div class="mobile-menu-wrap">
        <button class="hamburger-btn" id="hamburgerBtn">
          <span></span><span></span><span></span>
        </button>
        <div class="mobile-menu" id="mobileMenu">
          <div class="m-user">
            Halo,<strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
          </div>
          <a href="my_orders.php">📋 Pesanan Saya</a>
          <?php if (isAdmin()): ?>
          <a href="../admin/dashboard.php">⚙️ Dashboard</a>
          <?php endif; ?>
          <a href="../auth/logout.php" class="logout">🚪 Logout</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<div class="page-header">
  <div class="container">
    <h1 class="page-title">Katalog Buah Segar</h1>
    <p class="page-subtitle">Pilih buah favorit Anda dan pesan sekarang!</p>
  </div>
</div>

<div class="container">
  <div class="toolbar">
    <div class="search-box">
      <span class="search-icon">🔍</span>
      <form method="GET" action="" style="margin:0">
        <input type="text" name="search" class="search-input" placeholder="Cari buah..."
               value="<?php echo htmlspecialchars($search); ?>">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
      </form>
    </div>
    <div class="filter-group">
      <a href="?filter=semua<?php echo $search?'&search='.urlencode($search):''; ?>"
         class="filter-btn <?php echo $filter==='semua'?'active':''; ?>">Semua</a>
      <a href="?filter=lokal<?php echo $search?'&search='.urlencode($search):''; ?>"
         class="filter-btn <?php echo $filter==='lokal'?'active':''; ?>">Lokal</a>
      <a href="?filter=impor<?php echo $search?'&search='.urlencode($search):''; ?>"
         class="filter-btn <?php echo $filter==='impor'?'active':''; ?>">Impor</a>
    </div>
    <div class="product-count">
      Menampilkan <strong id="shownCount"><?php echo count($products); ?></strong>
      dari <strong><?php echo $total_products; ?></strong> produk
    </div>
  </div>
</div>

<div class="container">
  <div class="products-section">
    <?php if (count($products) > 0): ?>
    <div class="products-grid" id="productsGrid">
      <?php foreach ($products as $product):
        $stock = floatval($product['stok_kg']);
        $is_oos = $stock <= 0;
        $is_limited = !$is_oos && $stock <= 10;
        $badge_text = $is_oos?'Habis':($is_limited?'Terbatas':'Tersedia');
        $badge_cls  = $is_oos?'out-of-stock':($is_limited?'limited':'');
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
          <p class="product-desc"><?php echo htmlspecialchars($product['deskripsi']??'Buah segar berkualitas premium'); ?></p>
          <div class="product-footer">
            <span class="product-price">Rp <?php echo number_format($product['harga_kg'],0,',','.'); ?><small>/kg</small></span>
            <span class="product-stock">Stok: <?php echo $stock; ?> kg</span>
          </div>
          <div class="product-actions">
            <div class="quantity-wrapper">
              <button type="button" class="qty-btn qty-minus" data-product-id="<?php echo $product['id']; ?>" <?php echo $is_oos?'disabled':''; ?>>−</button>
              <input type="number" class="quantity-input" min="1" step="1" value="1"
                     max="<?php echo (int)$stock; ?>" data-product-id="<?php echo $product['id']; ?>"
                     <?php echo $is_oos?'disabled':''; ?>>
              <button type="button" class="qty-btn qty-plus" data-product-id="<?php echo $product['id']; ?>" <?php echo $is_oos?'disabled':''; ?>>+</button>
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
      <?php endforeach; ?>
    </div>

    <div class="load-more-wrap" id="loadMoreWrap"
         style="<?php echo count($products)>=$total_products?'display:none':''; ?>">
      <button class="btn-load-more" id="btnLoadMore"
              data-offset="<?php echo count($products); ?>"
              data-search="<?php echo htmlspecialchars($search); ?>"
              data-filter="<?php echo htmlspecialchars($filter); ?>"
              data-total="<?php echo $total_products; ?>">
        <span id="loadMoreText">🔄 Muat Lebih Banyak</span>
        <span class="spinner" id="loadMoreSpinner"
              style="border-color:rgba(45,134,89,.25);border-top-color:var(--primary)"></span>
      </button>
      <div class="load-more-count" id="loadMoreCount">
        Menampilkan <?php echo count($products); ?> dari <?php echo $total_products; ?> produk
      </div>
    </div>

    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">🔍</div>
      <h2 class="empty-title">Produk Tidak Ditemukan</h2>
      <p class="empty-text">Tidak ada produk yang sesuai.<br>Coba kata kunci lain atau hapus filter.</p>
      <a href="catalog.php" class="nav-btn solid" style="display:inline-flex;margin-top:.5rem">Lihat Semua</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<template id="tmplSkeleton">
  <div class="skeleton-card">
    <div class="skeleton-img"></div>
    <div class="skeleton-body">
      <div class="skeleton-line"></div>
      <div class="skeleton-line w60"></div>
      <div class="skeleton-line w40"></div>
    </div>
  </div>
</template>

<footer class="footer">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-logo">🍎 Buah Segar</div>
      <p class="footer-copy">&copy; 2026 Buah Segar. Kesegaran Alami Setiap Hari.</p>
    </div>
  </div>
</footer>

<script>
function initQty(root){
    root=root||document;
    root.querySelectorAll('.qty-minus').forEach(b=>b.addEventListener('click',function(){
        const id=this.dataset.productId,inp=document.querySelector('.quantity-input[data-product-id="'+id+'"]');
        const min=parseInt(inp.min)||1;if(parseInt(inp.value)>min)inp.value=parseInt(inp.value)-1;syncMinus(id);
    }));
    root.querySelectorAll('.qty-plus').forEach(b=>b.addEventListener('click',function(){
        const id=this.dataset.productId,inp=document.querySelector('.quantity-input[data-product-id="'+id+'"]');
        const max=parseInt(inp.max),cur=parseInt(inp.value)||1;if(!max||cur<max)inp.value=cur+1;syncMinus(id);
    }));
    root.querySelectorAll('.quantity-input').forEach(inp=>{
        inp.addEventListener('change',function(){
            const min=parseInt(this.min)||1,max=parseInt(this.max);
            let v=parseInt(this.value)||min;if(v<min)v=min;if(max&&v>max)v=max;this.value=v;syncMinus(this.dataset.productId);
        });syncMinus(inp.dataset.productId);
    });
}
function syncMinus(id){
    const inp=document.querySelector('.quantity-input[data-product-id="'+id+'"]');
    const btn=document.querySelector('.qty-minus[data-product-id="'+id+'"]');
    if(inp&&btn)btn.disabled=parseInt(inp.value)<=(parseInt(inp.min)||1);
}
initQty();

function showToast(msg,type){
    const el=document.createElement('div');
    el.className='toast'+(type==='error'?' error':'');
    el.innerHTML='<div class="toast-icon">'+(type==='error'?'❌':'✅')+'</div><div class="toast-msg">'+msg+'</div>';
    document.body.appendChild(el);setTimeout(()=>el.remove(),3500);
}
function updateBadge(count){
    const link=document.getElementById('cartLink');
    let badge=document.getElementById('cartBadge');
    if(count>0){
        if(!badge){badge=document.createElement('span');badge.className='cart-badge';badge.id='cartBadge';link.appendChild(badge);}
        badge.textContent=count;
    }else if(badge)badge.remove();
}
function initCart(root){
    root=root||document;
    root.querySelectorAll('.btn-add-cart').forEach(btn=>btn.addEventListener('click',async function(){
        if(this.disabled)return;
        const pid=this.dataset.productId,name=this.dataset.productName;
        const inp=document.querySelector('.quantity-input[data-product-id="'+pid+'"]');
        const qty=parseInt(inp?.value)||1;
        this.disabled=true;this.classList.add('loading');
        const span=this.querySelector('.btn-text'),spin=this.querySelector('.spinner');
        span.style.opacity='0';spin.style.display='block';
        try{
            const fd=new FormData();fd.append('product_id',pid);fd.append('quantity',qty);
            const res=await fetch('../api/add_to_cart.php',{method:'POST',body:fd});
            const data=await res.json();
            if(data.success){
                updateBadge(data.data.cart_count);
                if(inp){inp.value=1;syncMinus(pid);}
                const orig=span.dataset.orig||span.textContent;span.dataset.orig=orig;
                span.textContent='✓';span.style.opacity='1';
                showToast(name+' ditambahkan!');
                setTimeout(()=>{span.textContent=orig;},2000);
            }else{showToast(data.message,'error');}
        }catch(e){showToast('Terjadi kesalahan','error');}
        finally{
            this.disabled=false;this.classList.remove('loading');span.style.opacity='1';spin.style.display='none';
        }
    }));
}
initCart();

const btnMore=document.getElementById('btnLoadMore');
if(btnMore){
    btnMore.addEventListener('click',async function(){
        const offset=parseInt(this.dataset.offset),total=parseInt(this.dataset.total);
        const search=this.dataset.search,filter=this.dataset.filter,limit=12;
        const grid=document.getElementById('productsGrid'),tmpl=document.getElementById('tmplSkeleton');
        const sw=document.createElement('div');sw.id='skelWrap';sw.style.cssText='display:contents';
        for(let i=0;i<Math.min(6,total-offset);i++)sw.appendChild(tmpl.content.cloneNode(true));
        grid.appendChild(sw);
        this.disabled=true;
        document.getElementById('loadMoreText').style.opacity='0';
        document.getElementById('loadMoreSpinner').style.display='block';
        try{
            const qs=new URLSearchParams({offset,limit,search,filter});
            const res=await fetch('../api/load_more_products.php?'+qs);
            const data=await res.json();
            document.getElementById('skelWrap')?.remove();
            if(data.success&&data.html){
                const tmp=document.createElement('div');tmp.innerHTML=data.html;
                const cards=[...tmp.querySelectorAll('.product-card')];
                cards.forEach(c=>grid.appendChild(c));
                initQty(grid);initCart(grid);
                const newOffset=offset+cards.length;this.dataset.offset=newOffset;
                document.getElementById('shownCount').textContent=newOffset;
                document.getElementById('loadMoreCount').textContent='Menampilkan '+newOffset+' dari '+data.total+' produk';
                if(newOffset>=data.total){
                    document.getElementById('loadMoreWrap').innerHTML='<p class="load-more-done">✅ Semua '+data.total+' produk sudah ditampilkan</p>';
                    return;
                }
            }else{showToast('Gagal memuat produk','error');}
        }catch(e){document.getElementById('skelWrap')?.remove();showToast('Terjadi kesalahan','error');}
        finally{
            if(this.isConnected){this.disabled=false;document.getElementById('loadMoreText').style.opacity='1';document.getElementById('loadMoreSpinner').style.display='none';}
        }
    });
}

const si=document.querySelector('.search-input');
if(si){let t;si.addEventListener('input',function(){clearTimeout(t);t=setTimeout(()=>this.form.submit(),500);});}

// Hamburger menu
const hamburgerBtn=document.getElementById('hamburgerBtn');
const mobileMenu=document.getElementById('mobileMenu');
if(hamburgerBtn){
    hamburgerBtn.addEventListener('click',function(e){
        e.stopPropagation();
        this.classList.toggle('open');
        mobileMenu.classList.toggle('open');
    });
    document.addEventListener('click',function(e){
        if(!hamburgerBtn.contains(e.target)&&!mobileMenu.contains(e.target)){
            hamburgerBtn.classList.remove('open');
            mobileMenu.classList.remove('open');
        }
    });
}

// Sync mobile cart badge
function updateBadgeMobile(count){
    const link=document.getElementById('cartLinkMobile');
    let badge=document.getElementById('cartBadgeMobile');
    if(!link)return;
    if(count>0){
        if(!badge){badge=document.createElement('span');badge.className='cart-badge';badge.id='cartBadgeMobile';link.appendChild(badge);}
        badge.textContent=count;
    }else if(badge)badge.remove();
}
const origUpdateBadge=updateBadge;
window.updateBadge=function(count){origUpdateBadge(count);updateBadgeMobile(count);};
</script>
</body>
</html>