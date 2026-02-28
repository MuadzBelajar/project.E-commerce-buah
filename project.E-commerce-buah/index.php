<?php
/**
 * ================================================
 * HOMEPAGE - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: index.php
 * Updated: Integrasi database untuk buah pilihan
 * ================================================
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get user if logged in
$user = null;

// ================================================
// FETCH FEATURED PRODUCTS (Buah Pilihan Minggu Ini)
// ================================================

// Ambil 4 produk aktif dengan stok > 0, urutkan random
$featured_products = fetchAll("
    SELECT * FROM buah 
    WHERE status = 'active' AND stok_kg > 0 
    ORDER BY RAND() 
    LIMIT 4
");

// Fallback: jika tidak ada hasil random, ambil yang terbaru
if (empty($featured_products)) {
    $featured_products = fetchAll("
        SELECT * FROM buah 
        WHERE status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 4
    ");
}

/**
 * Helper function untuk gambar produk
 */
function getProductImage($filename) {
    if (empty($filename)) {
        return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600';
    }
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    return 'assets/images/products/' . basename($filename);
}

/**
 * Helper untuk badge (Populer/Premium)
 */
function getProductBadge($index, $kategori) {
    if ($index === 0) return 'Populer';
    if ($index === 1) return 'Premium';
    if ($kategori === 'impor') return 'Impor';
    return '';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>muasmuis shop</title>
    <meta name="description" content="Toko buah online terpercaya dengan buah segar pilihan terbaik dari petani lokal dan impor berkualitas premium.">
    
    <!-- Fonts - Distinctive Typography -->
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
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;
            --spacing-3xl: 6rem;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.16);
            --transition-fast: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { font-family: var(--font-body); color: var(--color-text); background: var(--color-background); line-height: 1.6; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; overflow-x: hidden; }
        img { max-width: 100%; height: auto; display: block; }
        a { text-decoration: none; color: inherit; transition: var(--transition-base); }
        ul { list-style: none; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 var(--spacing-lg); }
        .navbar { position: fixed; top: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--color-border); z-index: 1000; transition: var(--transition-base); }
        .nav-container { max-width: 1280px; margin: 0 auto; padding: 0 var(--spacing-lg); display: flex; align-items: center; justify-content: space-between; height: 80px; }
        .nav-logo { display: flex; align-items: center; gap: var(--spacing-sm); font-weight: 700; font-size: 1.25rem; }
        .logo-fruit { font-size: 1.75rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-5px); } }
        .logo-text { font-family: var(--font-display); color: var(--color-primary); }
        .nav-menu { display: flex; gap: var(--spacing-xl); }
        .nav-link { position: relative; font-weight: 500; color: var(--color-text); padding: var(--spacing-xs) 0; }
        .nav-link::after { content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px; background: var(--color-primary); transition: width var(--transition-base); }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }
        .nav-actions { display: flex; align-items: center; gap: var(--spacing-md); }
        .cart-btn { position: relative; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: var(--radius-md); background: var(--color-background-alt); transition: var(--transition-base); }
        .cart-btn:hover { background: var(--color-primary-light); color: var(--color-primary); transform: translateY(-2px); }
        .cart-count { position: absolute; top: -4px; right: -4px; background: var(--color-secondary); color: white; font-size: 0.75rem; font-weight: 600; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .login-btn { padding: var(--spacing-sm) var(--spacing-lg); background: var(--color-primary); color: white; border-radius: var(--radius-md); font-weight: 600; transition: var(--transition-base); }
        .login-btn:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .mobile-menu-toggle { display: none; flex-direction: column; gap: 4px; background: none; border: none; cursor: pointer; }
        .mobile-menu-toggle span { width: 24px; height: 2px; background: var(--color-text); transition: var(--transition-base); }
        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: var(--spacing-3xl) var(--spacing-lg); position: relative; overflow: hidden; background: linear-gradient(135deg, #FAFAFA 0%, #FFFFFF 100%); }
        .hero-content { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-3xl); align-items: center; }
        .hero-text { animation: fadeInUp 1s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .hero-title { font-family: var(--font-display); font-size: 4.5rem; font-weight: 800; line-height: 1.1; color: var(--color-text); margin-bottom: var(--spacing-md); letter-spacing: -0.02em; }
        .title-accent { color: var(--color-primary); position: relative; display: inline-block; }
        .title-accent::after { content: ''; position: absolute; bottom: 8px; left: 0; width: 100%; height: 20px; background: var(--color-accent); opacity: 0.3; z-index: -1; }
        .hero-subtitle { font-size: 1.125rem; color: var(--color-text-light); margin-bottom: var(--spacing-xl); line-height: 1.8; }
        .hero-cta { display: flex; gap: var(--spacing-md); }
        .btn { display: inline-flex; align-items: center; gap: var(--spacing-sm); padding: 1rem 2rem; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; transition: var(--transition-base); cursor: pointer; border: none; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-primary:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .btn-secondary { background: transparent; color: var(--color-text); border: 2px solid var(--color-border); }
        .btn-secondary:hover { background: var(--color-background-alt); border-color: var(--color-primary); color: var(--color-primary); }
        .btn-white { background: white; color: var(--color-primary); }
        .btn-white:hover { background: var(--color-primary-light); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .hero-image { position: relative; height: 600px; animation: fadeInRight 1s ease-out 0.3s backwards; }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        .fruit-card { position: absolute; width: 280px; height: 320px; border-radius: var(--radius-xl); background: white; box-shadow: var(--shadow-xl); overflow: hidden; transition: var(--transition-slow); }
        .fruit-card img { width: 100%; height: 100%; object-fit: cover; transition: var(--transition-slow); }
        .fruit-card:hover { transform: translateY(-10px) rotate(2deg); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); }
        .fruit-card:hover img { transform: scale(1.1); }
        .fruit-label { position: absolute; bottom: 20px; left: 20px; right: 20px; padding: var(--spacing-sm) var(--spacing-md); background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: var(--radius-md); font-weight: 600; text-align: center; }
        .card-1 { top: 0; left: 0; animation: floatCard1 6s ease-in-out infinite; z-index: 3; }
        .card-2 { top: 120px; right: 0; animation: floatCard2 7s ease-in-out infinite; z-index: 2; }
        .card-3 { bottom: 0; left: 60px; animation: floatCard3 8s ease-in-out infinite; z-index: 1; }
        @keyframes floatCard1 { 0%, 100% { transform: translateY(0px) rotate(-2deg); } 50% { transform: translateY(-20px) rotate(2deg); } }
        @keyframes floatCard2 { 0%, 100% { transform: translateY(0px) rotate(3deg); } 50% { transform: translateY(-15px) rotate(-1deg); } }
        @keyframes floatCard3 { 0%, 100% { transform: translateY(0px) rotate(1deg); } 50% { transform: translateY(-25px) rotate(-3deg); } }
        .hero-scroll { position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; gap: var(--spacing-xs); color: var(--color-text-light); font-size: 0.875rem; animation: bounce 2s ease-in-out infinite; }
        @keyframes bounce { 0%, 100% { transform: translateX(-50%) translateY(0); } 50% { transform: translateX(-50%) translateY(-10px); } }
        .features { padding: var(--spacing-3xl) 0; background: var(--color-background-alt); }
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-xl); }
        .feature-item { text-align: center; padding: var(--spacing-xl); background: white; border-radius: var(--radius-lg); transition: var(--transition-base); }
        .feature-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .feature-icon { font-size: 3rem; margin-bottom: var(--spacing-md); }
        .feature-item h3 { font-family: var(--font-display); font-size: 1.25rem; margin-bottom: var(--spacing-sm); color: var(--color-text); }
        .feature-item p { color: var(--color-text-light); font-size: 0.9375rem; line-height: 1.6; }
        .products-showcase { padding: var(--spacing-3xl) 0; }
        .section-header { text-align: center; margin-bottom: var(--spacing-3xl); }
        .section-title { font-family: var(--font-display); font-size: 3rem; font-weight: 700; margin-bottom: var(--spacing-md); color: var(--color-text); }
        .section-subtitle { font-size: 1.125rem; color: var(--color-text-light); }
        .products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-xl); margin-bottom: var(--spacing-2xl); }
        .product-card { background: white; border-radius: var(--radius-lg); overflow: hidden; transition: var(--transition-base); border: 1px solid var(--color-border); }
        .product-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); border-color: transparent; }
        .product-image { position: relative; width: 100%; height: 280px; overflow: hidden; background: var(--color-background-alt); }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: var(--transition-slow); }
        .product-card:hover .product-image img { transform: scale(1.1); }
        .product-badge { position: absolute; top: 16px; right: 16px; padding: 6px 12px; background: var(--color-secondary); color: white; font-size: 0.75rem; font-weight: 600; border-radius: var(--radius-sm); text-transform: uppercase; letter-spacing: 0.5px; }
        .product-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(45, 134, 89, 0.9); display: flex; align-items: center; justify-content: center; opacity: 0; transition: var(--transition-base); }
        .product-card:hover .product-overlay { opacity: 1; }
        .quick-view { padding: var(--spacing-sm) var(--spacing-lg); background: white; color: var(--color-primary); border-radius: var(--radius-md); font-weight: 600; transform: translateY(10px); transition: var(--transition-base); }
        .product-card:hover .quick-view { transform: translateY(0); }
        .product-info { padding: var(--spacing-md); }
        .product-name { font-size: 1.125rem; font-weight: 600; margin-bottom: var(--spacing-xs); color: var(--color-text); }
        .product-origin { font-size: 0.875rem; color: var(--color-text-light); margin-bottom: var(--spacing-md); }
        .product-footer { display: flex; justify-content: space-between; align-items: center; }
        .product-price { font-size: 1.25rem; font-weight: 700; color: var(--color-primary); }
        .product-price small { font-size: 0.875rem; font-weight: 400; color: var(--color-text-light); }
        .product-stock { font-size: 0.875rem; color: var(--color-text-lighter); }
        .section-cta { text-align: center; }
        .about { padding: var(--spacing-3xl) 0; background: var(--color-primary-light); }
        .about-content { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-3xl); align-items: center; }
        .about-title { font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; margin-bottom: var(--spacing-lg); color: var(--color-text); }
        .about-description { font-size: 1.0625rem; line-height: 1.8; color: var(--color-text-light); margin-bottom: var(--spacing-md); }
        .about-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-lg); margin-top: var(--spacing-xl); }
        .stat-item { text-align: center; padding: var(--spacing-lg); background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); }
        .stat-number { display: block; font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; color: var(--color-primary); margin-bottom: var(--spacing-xs); }
        .stat-label { display: block; font-size: 0.875rem; color: var(--color-text-light); }
        .about-image { border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-xl); }
        .about-image img { width: 100%; height: 100%; object-fit: cover; transition: var(--transition-slow); }
        .about-image:hover img { transform: scale(1.05); }
        .cta-section { padding: var(--spacing-3xl) 0; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: white; }
        .cta-content { text-align: center; max-width: 700px; margin: 0 auto; }
        .cta-title { font-family: var(--font-display); font-size: 3rem; font-weight: 700; margin-bottom: var(--spacing-md); }
        .cta-text { font-size: 1.25rem; margin-bottom: var(--spacing-xl); opacity: 0.9; }
        .footer { background: var(--color-text); color: white; padding: var(--spacing-3xl) 0 var(--spacing-lg); }
        .footer-content { display: grid; grid-template-columns: 1.5fr 2.5fr; gap: var(--spacing-3xl); margin-bottom: var(--spacing-2xl); }
        .footer-logo { display: flex; align-items: center; gap: var(--spacing-sm); font-size: 1.5rem; font-weight: 700; margin-bottom: var(--spacing-md); }
        .footer-tagline { color: rgba(255, 255, 255, 0.7); }
        .footer-links { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-xl); }
        .footer-column h4 { margin-bottom: var(--spacing-md); font-size: 1.125rem; }
        .footer-column ul { display: flex; flex-direction: column; gap: var(--spacing-sm); }
        .footer-column a { color: rgba(255, 255, 255, 0.7); transition: var(--transition-base); }
        .footer-column a:hover { color: white; transform: translateX(4px); }
        .footer-bottom { text-align: center; padding-top: var(--spacing-lg); border-top: 1px solid rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.5); }
        @media (max-width: 1024px) {
            .hero-title { font-size: 3.5rem; }
            .products-grid { grid-template-columns: repeat(2, 1fr); }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .nav-menu { position: fixed; top: 80px; left: -100%; width: 100%; height: calc(100vh - 80px); background: white; flex-direction: column; align-items: center; justify-content: center; gap: var(--spacing-lg); transition: left 0.3s ease; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1); z-index: 999; }
            .nav-menu.active { left: 0; }
            .nav-link { font-size: 1.25rem; padding: var(--spacing-sm); }
            .mobile-menu-toggle { display: flex; }
            .mobile-menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
            .mobile-menu-toggle.active span:nth-child(2) { opacity: 0; }
            .mobile-menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }
            .hero-content { grid-template-columns: 1fr; text-align: center; }
            .hero-title { font-size: 2.5rem; }
            .hero-image { height: 400px; }
            .fruit-card { width: 200px; height: 240px; }
            .products-grid { grid-template-columns: 1fr; }
            .feature-grid { grid-template-columns: 1fr; }
            .about-content { grid-template-columns: 1fr; }
            .footer-content { grid-template-columns: 1fr; }
            .footer-links { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .hero-title { font-size: 2rem; }
            .hero-cta { flex-direction: column; }
            .section-title { font-size: 2rem; }
            .cta-title { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <span class="logo-fruit">🍎</span>
                <span class="logo-text">Buah Segar</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home" class="nav-link active">Beranda</a></li>
                <li><a href="#products" class="nav-link">Produk</a></li>
                <li><a href="#about" class="nav-link">Tentang</a></li>
                <li><a href="pages/catalog.php" class="nav-link">Belanja</a></li>
            </ul>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <span style="font-size: 0.875rem; color: var(--color-text-light);">Halo, <?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
                    <a href="pages/catalog.php" class="cart-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <span class="cart-count">0</span>
                    </a>
                    <a href="auth/logout.php" class="login-btn">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="login-btn">Masuk</a>
                <?php endif; ?>
            </div>
            <button class="mobile-menu-toggle">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>
    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Kesegaran<br/><span class="title-accent">Alami</span><br/>Setiap Hari</h1>
                <p class="hero-subtitle">Buah pilihan terbaik dari petani lokal dan impor berkualitas premium,<br/>dikirim langsung ke rumah Anda dengan kesegaran terjaga.</p>
                <div class="hero-cta">
                    <a href="pages/catalog.php" class="btn btn-primary">Belanja Sekarang<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                    <a href="#products" class="btn btn-secondary">Lihat Produk</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="fruit-card card-1"><img src="https://images.unsplash.com/photo-1553279768-865429fa0078?w=600" alt="Mangga" loading="lazy"></div>
                <div class="fruit-card card-2"><img src="https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600" alt="Apel" loading="lazy"></div>
                <div class="fruit-card card-3"><img src="https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=600" alt="Jeruk" loading="lazy"></div>
            </div>
        </div>
    </section>
    <section class="features">
        <div class="container">
            <div class="feature-grid">
                <div class="feature-item"><div class="feature-icon">🌿</div><h3>100% Segar</h3><p>Dipetik langsung dari kebun dan dikirim dalam kondisi terbaik</p></div>
                <div class="feature-item"><div class="feature-icon">✨</div><h3>Kualitas Premium</h3><p>Seleksi ketat untuk menjamin buah terbaik sampai ke tangan Anda</p></div>
                <div class="feature-item"><div class="feature-icon">🚚</div><h3>Pengiriman Cepat</h3><p>Dikirim same-day untuk area Makassar dan sekitarnya</p></div>
                <div class="feature-item"><div class="feature-icon">💯</div><h3>Garansi Uang Kembali</h3><p>Tidak puas? Kami kembalikan 100% uang Anda</p></div>
            </div>
        </div>
    </section>
    <section id="products" class="products-showcase">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Buah Pilihan Minggu Ini</h2>
                <p class="section-subtitle">Kesegaran yang tidak boleh Anda lewatkan</p>
            </div>
            <div class="products-grid">
                <?php if (count($featured_products) > 0): ?>
                    <?php foreach ($featured_products as $index => $product): 
                        $badge = getProductBadge($index, $product['kategori']);
                        $stock = floatval($product['stok_kg']);
                    ?>
                    <div class="product-card" data-product="<?php echo $product['id']; ?>">
                        <?php if ($badge): ?>
                        <div class="product-badge"><?php echo $badge; ?></div>
                        <?php endif; ?>
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars(getProductImage($product['gambar'])); ?>" 
                                 alt="<?php echo htmlspecialchars($product['nama_buah']); ?>" 
                                 loading="lazy"
                                 onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600'">
                            <div class="product-overlay">
                                <a href="pages/catalog.php" class="quick-view">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['nama_buah']); ?></h3>
                            <p class="product-origin">
                                <?php echo $product['kategori'] === 'impor' ? 'Impor' : 'Lokal'; ?> 
                                (<?php echo htmlspecialchars($product['asal']); ?>)
                            </p>
                            <div class="product-footer">
                                <span class="product-price">
                                    Rp <?php echo number_format($product['harga_kg'], 0, ',', '.'); ?><small>/kg</small>
                                </span>
                                <span class="product-stock">Stok: <?php echo $stock; ?> kg</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback jika tidak ada produk -->
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <p>Belum ada produk tersedia. <a href="pages/catalog.php">Lihat semua produk</a></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="section-cta">
                <a href="pages/catalog.php" class="btn btn-primary">
                    Lihat Semua Produk
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="about-title">Dari Kebun ke Meja Anda</h2>
                    <p class="about-description">Kami percaya bahwa buah segar adalah kunci gaya hidup sehat. Sejak 2020, kami telah berkomitmen menghadirkan buah-buahan pilihan terbaik dari petani lokal dan impor berkualitas premium langsung ke rumah Anda.</p>
                    <p class="about-description">Setiap buah yang kami jual melalui proses seleksi ketat untuk memastikan kesegaran, rasa, dan nutrisi tetap terjaga. Kepuasan Anda adalah prioritas kami.</p>
                    <div class="about-stats">
                        <div class="stat-item"><span class="stat-number">5000+</span><span class="stat-label">Pelanggan Puas</span></div>
                        <div class="stat-item"><span class="stat-number">50+</span><span class="stat-label">Jenis Buah</span></div>
                        <div class="stat-item"><span class="stat-number">100%</span><span class="stat-label">Kualitas Terjamin</span></div>
                    </div>
                </div>
                <div class="about-image"><img src="https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=800" alt="Fresh Fruits" loading="lazy"></div>
            </div>
        </div>
    </section>
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Siap Hidup Lebih Sehat?</h2>
                <p class="cta-text">Mulai belanja buah segar hari ini dan rasakan perbedaannya</p>
                <a href="pages/catalog.php" class="btn btn-white">Mulai Belanja<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo"><span class="logo-fruit">🍎</span><span class="logo-text">Buah Segar</span></div>
                    <p class="footer-tagline">Kesegaran Alami Setiap Hari</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column"><h4>Navigasi</h4><ul><li><a href="#home">Beranda</a></li><li><a href="#products">Produk</a></li><li><a href="#about">Tentang Kami</a></li><li><a href="pages/catalog.php">Belanja</a></li></ul></div>
                    <div class="footer-column"><h4>Bantuan</h4><ul><li><a href="#">Cara Pesan</a></li><li><a href="#">Pengiriman</a></li><li><a href="#">Pembayaran</a></li><li><a href="#">FAQ</a></li></ul></div>
                    <div class="footer-column"><h4>Kontak</h4><ul><li>📍 Makassar, Sulawesi Selatan</li><li>📞 0812-3456-7890</li><li>📧 info@buahsegar.com</li></ul></div>
                </div>
            </div>
            <div class="footer-bottom"><p>&copy; 2026 Buah Segar. All rights reserved.</p></div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const navMenu = document.querySelector('.nav-menu');
            if (mobileToggle && navMenu) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navMenu.classList.toggle('active');
                    mobileToggle.classList.toggle('active');
                    document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
                });
                document.addEventListener('click', function(e) {
                    if (!mobileToggle.contains(e.target) && !navMenu.contains(e.target)) {
                        navMenu.classList.remove('active');
                        mobileToggle.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        mobileToggle.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
                        document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                        if (this.classList.contains('nav-link')) this.classList.add('active');
                    }
                });
            });
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', () => {
                navbar.style.boxShadow = window.pageYOffset > 100 ? '0 2px 16px rgba(0, 0, 0, 0.08)' : 'none';
            });
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                cartCount.textContent = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
            }
        });
    </script>
</body>
</html>