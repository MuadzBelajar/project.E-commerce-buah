<?php
/**
 * ================================================
 * HOMEPAGE - BUAH SEGAR E-COMMERCE
 * ================================================
 */
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$user = null;

$featured_products = fetchAll("
    SELECT * FROM buah 
    WHERE status = 'active' AND stok_kg > 0 
    ORDER BY RAND() 
    LIMIT 6
");
if (empty($featured_products)) {
    $featured_products = fetchAll("SELECT * FROM buah WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
}

function getProductImage($filename) {
    if (empty($filename)) return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600';
    if (filter_var($filename, FILTER_VALIDATE_URL)) return $filename;
    return 'assets/images/products/' . basename($filename);
}
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
    <title>Buah Segar — Kesegaran Alami Setiap Hari</title>
    <meta name="description" content="Toko buah online terpercaya dengan buah segar pilihan terbaik dari petani lokal dan impor berkualitas premium.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:       #2D8659;
            --primary-dark:  #1F5F3F;
            --primary-light: #E8F5ED;
            --secondary:     #FF8C42;
            --accent:        #FFD166;
            --text:          #1A1A1A;
            --text-light:    #666666;
            --text-lighter:  #999999;
            --bg:            #FFFFFF;
            --bg-alt:        #FAFAFA;
            --border:        #E5E5E5;
            --font-display:  'Playfair Display', serif;
            --font-body:     'DM Sans', sans-serif;
            --shadow-sm:  0 2px 8px rgba(0,0,0,.04);
            --shadow-md:  0 4px 16px rgba(0,0,0,.08);
            --shadow-lg:  0 8px 32px rgba(0,0,0,.12);
            --shadow-xl:  0 16px 48px rgba(0,0,0,.16);
            --t:          300ms cubic-bezier(.4,0,.2,1);
            --radius-md:  12px;
            --radius-lg:  16px;
            --radius-xl:  24px;
        }
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: var(--font-body); color: var(--text); background: var(--bg); line-height: 1.6; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        img { max-width: 100%; height: auto; display: block; }
        a { text-decoration: none; color: inherit; transition: var(--t); }
        ul { list-style: none; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }

        /* ── NAVBAR ── */
        .navbar { position: fixed; top: 0; left: 0; right: 0; background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); z-index: 1000; }
        .nav-inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 68px; gap: 1rem; }
        .nav-logo { display: flex; align-items: center; gap: .625rem; font-weight: 700; }
        .logo-fruit { font-size: 1.75rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }
        .logo-text { font-family: var(--font-display); color: var(--primary); font-size: 1.375rem; }
        .nav-menu { display: flex; gap: 2.5rem; }
        .nav-link { font-weight: 500; color: var(--text); padding: .25rem 0; position: relative; font-size: .9375rem; }
        .nav-link::after { content:''; position:absolute; bottom:0; left:0; width:0; height:2px; background:var(--primary); transition:width var(--t); }
        .nav-link:hover::after, .nav-link.active::after { width:100%; }
        .nav-actions { display: flex; align-items: center; gap: .75rem; flex-shrink: 0; }
        .nav-cta { padding: .5rem 1.25rem; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 600; font-size: .875rem; transition: var(--t); }
        .nav-cta:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: .25rem; }
        .hamburger span { width: 24px; height: 2px; background: var(--text); border-radius: 2px; transition: var(--t); display: block; }
        .hamburger.open span:nth-child(1) { transform: rotate(45deg) translate(5px,5px); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: rotate(-45deg) translate(5px,-5px); }

        /* ── HERO ── */
        .hero { min-height: 100vh; display: flex; align-items: center; padding: 100px 2rem 4rem; background: linear-gradient(135deg, #f0faf4 0%, #fff 60%); }
        .hero-inner { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; width: 100%; }
        .hero-text { animation: fadeUp .9s ease-out; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .hero-tag { display: inline-flex; align-items: center; gap: .5rem; background: var(--primary-light); color: var(--primary); padding: .375rem .875rem; border-radius: 99px; font-size: .8125rem; font-weight: 600; margin-bottom: 1.25rem; }
        .hero-title { font-family: var(--font-display); font-size: clamp(2.25rem, 5vw, 4rem); font-weight: 800; line-height: 1.1; color: var(--text); margin-bottom: 1.25rem; letter-spacing: -.02em; }
        .title-accent { color: var(--primary); position: relative; display: inline-block; }
        .title-accent::after { content:''; position:absolute; bottom:6px; left:0; width:100%; height:14px; background:var(--accent); opacity:.3; z-index:-1; border-radius:4px; }
        .hero-sub { font-size: 1.0625rem; color: var(--text-light); margin-bottom: 2rem; line-height: 1.8; max-width: 480px; }
        .hero-btns { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .875rem 1.75rem; border-radius: var(--radius-md); font-weight: 600; font-size: 1rem; transition: var(--t); cursor: pointer; border: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .btn-outline { background: transparent; color: var(--text); border: 2px solid var(--border); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .btn-white { background: white; color: var(--primary); }
        .btn-white:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: var(--shadow-lg); }
        .hero-stats { display: flex; gap: 2rem; margin-top: 2.5rem; }
        .stat { }
        .stat-num { font-family: var(--font-display); font-size: 1.75rem; font-weight: 700; color: var(--primary); display: block; }
        .stat-label { font-size: .8125rem; color: var(--text-lighter); }

        /* Hero visual */
        .hero-visual { position: relative; height: 520px; animation: fadeRight .9s ease-out .2s backwards; }
        @keyframes fadeRight { from{opacity:0;transform:translateX(24px)} to{opacity:1;transform:translateX(0)} }
        .fruit-card { position: absolute; border-radius: var(--radius-xl); background: white; box-shadow: var(--shadow-xl); overflow: hidden; transition: var(--t); }
        .fruit-card img { width:100%; height:100%; object-fit:cover; transition: transform .5s ease; }
        .fruit-card:hover { box-shadow: 0 20px 60px rgba(0,0,0,.18); }
        .fruit-card:hover img { transform: scale(1.07); }
        .fc-1 { width:240px; height:280px; top:0; left:20px; animation: fc1 6s ease-in-out infinite; z-index:3; }
        .fc-2 { width:220px; height:260px; top:100px; right:0; animation: fc2 7s ease-in-out infinite; z-index:2; }
        .fc-3 { width:200px; height:240px; bottom:0; left:80px; animation: fc3 8s ease-in-out infinite; z-index:1; }
        @keyframes fc1 { 0%,100%{transform:rotate(-2deg) translateY(0)} 50%{transform:rotate(2deg) translateY(-16px)} }
        @keyframes fc2 { 0%,100%{transform:rotate(3deg) translateY(0)} 50%{transform:rotate(-1deg) translateY(-12px)} }
        @keyframes fc3 { 0%,100%{transform:rotate(1deg) translateY(0)} 50%{transform:rotate(-2deg) translateY(-20px)} }
       
        /* ── FEATURES ── */
        .features { padding: 5rem 0; background: var(--bg-alt); }
        .features-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5rem; }
        .feature-card { text-align:center; padding: 2rem 1.5rem; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); transition: var(--t); }
        .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .feature-icon { font-size: 2.5rem; margin-bottom: 1rem; }
        .feature-card h3 { font-family: var(--font-display); font-size: 1.125rem; margin-bottom: .5rem; }
        .feature-card p { color: var(--text-light); font-size: .875rem; line-height: 1.6; }

        /* ── SECTION HEADER ── */
        .section-header { text-align: center; margin-bottom: 3rem; }
        .section-label { display: inline-block; background: var(--primary-light); color: var(--primary); padding: .25rem .875rem; border-radius: 99px; font-size: .8125rem; font-weight: 600; margin-bottom: .875rem; }
        .section-title { font-family: var(--font-display); font-size: clamp(1.75rem, 4vw, 2.75rem); font-weight: 700; color: var(--text); margin-bottom: .75rem; }
        .section-sub { font-size: 1rem; color: var(--text-light); }

        /* ── PRODUCTS SHOWCASE ── */
        .products-showcase { padding: 5rem 0; }

        /* ── PRODUCT GRID — DESKTOP 4 col, tablet 3, mobile 3 ── */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
            justify-items: center;
        }
        .product-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: var(--t);
            cursor: pointer;
            width: 100%;
        }
        /* Center last row when it has fewer items than columns */
        .products-grid::after {
            content: '';
            grid-column: span 2;
        }
        .product-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: transparent; }

        /* Gambar square aspect ratio */
        .product-image {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: var(--bg-alt);
        }
        .product-image img { width:100%; height:100%; object-fit:cover; transition: transform .5s ease; }
        .product-card:hover .product-image img { transform: scale(1.08); }
        .product-badge {
            position: absolute; top: 10px; right: 10px;
            padding: 4px 10px;
            background: var(--secondary); color: white;
            font-size: .7rem; font-weight: 700;
            border-radius: 6px; text-transform: uppercase; letter-spacing: .3px;
        }
        .product-overlay {
            position: absolute; inset: 0;
            background: rgba(45,134,89,.88);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: var(--t);
        }
        .product-card:hover .product-overlay { opacity: 1; }
        .quick-view {
            padding: .625rem 1.25rem; background: white; color: var(--primary);
            border-radius: var(--radius-md); font-weight: 600; font-size: .875rem;
            transform: translateY(8px); transition: var(--t);
        }
        .product-card:hover .quick-view { transform: translateY(0); }

        .product-info { padding: 1rem; }
        .product-name {
            font-size: 1rem; font-weight: 600; color: var(--text);
            margin-bottom: .375rem;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .product-origin { font-size: .8125rem; color: var(--text-light); margin-bottom: .75rem; }
        .product-footer { display: flex; justify-content: space-between; align-items: center; }
        .product-price { font-size: 1.1875rem; font-weight: 700; color: var(--primary); }
        .product-price small { font-size: .8125rem; font-weight: 400; color: var(--text-light); }
        .product-stock { font-size: .8125rem; color: var(--text-lighter); }

        .section-cta { text-align: center; }

        /* ── ABOUT ── */
        .about { padding: 5rem 0; background: var(--primary-light); }
        .about-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
        .about-title { font-family: var(--font-display); font-size: clamp(1.75rem, 3.5vw, 2.5rem); font-weight: 700; margin-bottom: 1.25rem; }
        .about-desc { color: var(--text-light); line-height: 1.8; margin-bottom: 1rem; font-size: 1rem; }
        .about-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; margin-top: 2rem; }
        .stat-card { text-align: center; padding: 1.25rem 1rem; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); }
        .stat-num-lg { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--primary); display: block; }
        .stat-lbl { font-size: .8125rem; color: var(--text-light); display: block; }
        .about-img { border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-xl); aspect-ratio: 4/3; }
        .about-img img { width:100%; height:100%; object-fit:cover; transition: transform .5s ease; }
        .about-img:hover img { transform: scale(1.04); }

        /* ── CTA BANNER ── */
        .cta-banner { padding: 5rem 0; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }
        .cta-inner { text-align: center; max-width: 640px; margin: 0 auto; }
        .cta-title { font-family: var(--font-display); font-size: clamp(1.75rem, 4vw, 2.75rem); font-weight: 700; margin-bottom: 1rem; }
        .cta-sub { font-size: 1.0625rem; margin-bottom: 2rem; opacity: .9; }

        /* ── FOOTER ── */
        .footer { background: var(--text); color: white; padding: 4rem 0 1.5rem; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr repeat(3,1fr); gap: 3rem; margin-bottom: 3rem; }
        .footer-brand-logo { display: flex; align-items: center; gap: .5rem; font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; margin-bottom: .75rem; }
        .footer-tagline { color: rgba(255,255,255,.6); font-size: .875rem; }
        .footer-col h4 { font-size: 1rem; margin-bottom: 1rem; }
        .footer-col ul { display: flex; flex-direction: column; gap: .625rem; }
        .footer-col a { color: rgba(255,255,255,.65); font-size: .875rem; }
        .footer-col a:hover { color: white; padding-left: 4px; }
        .footer-col li { color: rgba(255,255,255,.65); font-size: .875rem; }
        .footer-bottom { text-align: center; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,.1); color: rgba(255,255,255,.45); font-size: .875rem; }

        /* ══════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════ */

        /* Tablet: 768–1023px */
        @media (max-width: 1023px) {
            .features-grid { grid-template-columns: repeat(2,1fr); }
            .products-grid { grid-template-columns: repeat(3,1fr); gap: 1rem; }
            .about-inner { grid-template-columns: 1fr; }
            .about-img { order: -1; max-height: 320px; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
            .hero-inner { gap: 2.5rem; }
            .hero-visual { height: 420px; }
            .fc-1 { width:200px; height:240px; }
            .fc-2 { width:185px; height:220px; }
            .fc-3 { width:170px; height:205px; }
        }

        /* Mobile: <768px */
        @media (max-width: 767px) {
            .container { padding: 0 1rem; }

            /* Navbar mobile */
            .nav-inner { padding: 0 1rem; height: 60px; }
            .nav-menu {
                position: fixed; top: 60px; left: -100%; width: 100%;
                height: calc(100vh - 60px); background: white;
                flex-direction: column; align-items: center; justify-content: center;
                gap: 2rem; transition: left .3s ease; z-index: 999;
                box-shadow: var(--shadow-lg);
            }
            .nav-menu.open { left: 0; }
            .nav-link { font-size: 1.125rem; }
            .hamburger { display: flex; }
            .nav-actions .nav-cta { padding: .4375rem 1rem; font-size: .8125rem; }

            /* Hero mobile — stacked, compact */
            .hero { padding: 80px 1rem 3rem; min-height: auto; }
            .hero-inner { grid-template-columns: 1fr; gap: 0; }
            .hero-visual { display: none; } /* Sembunyikan floating cards di mobile */
            .hero-title { font-size: 2.125rem; }
            .hero-sub { font-size: .9375rem; }
            .hero-btns { gap: .75rem; }
            .btn { padding: .75rem 1.375rem; font-size: .9375rem; }
            .hero-stats { gap: 1.5rem; flex-wrap: wrap; }
            .stat-num { font-size: 1.375rem; }

            /* Features mobile — 2 kolom */
            .features { padding: 3rem 0; }
            .features-grid { grid-template-columns: repeat(2,1fr); gap: 1rem; }
            .feature-card { padding: 1.25rem 1rem; }
            .feature-icon { font-size: 2rem; margin-bottom: .75rem; }
            .feature-card h3 { font-size: 1rem; }

            /* ★ PRODUCTS GRID MOBILE — 3 KOLOM SHOPEE-LIKE ★ */
            .products-showcase { padding: 3rem 0; }
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            .product-card { border-radius: 10px; }
            .product-card:hover { transform: none; } /* No hover on touch */
            .product-info { padding: .5rem .5rem .625rem; }
            .product-name { font-size: .75rem; font-weight: 600; -webkit-line-clamp: 2; margin-bottom: .25rem; }
            .product-origin { display: none; } /* Sembunyikan di mobile */
            .product-price { font-size: .9375rem; font-weight: 700; }
            .product-price small { display: none; } /* Sembunyikan "/kg" */
            .product-stock { display: none; } /* Sembunyikan stok */
            .product-badge { font-size: .6rem; padding: 2px 6px; top: 6px; right: 6px; }
            .product-overlay { display: none; } /* No overlay on mobile */
            .section-header { margin-bottom: 1.5rem; }
            .section-title { font-size: 1.5rem; }

            /* About mobile */
            .about { padding: 3rem 0; }
            .about-inner { grid-template-columns: 1fr; gap: 1.5rem; }
            .about-img { order: -1; aspect-ratio: 16/9; }
            .about-stats { grid-template-columns: repeat(3,1fr); gap: .75rem; }
            .stat-num-lg { font-size: 1.5rem; }

            /* CTA */
            .cta-banner { padding: 3rem 0; }
            .cta-title { font-size: 1.75rem; }

            /* Footer mobile */
            .footer { padding: 3rem 0 1.25rem; }
            .footer-grid { grid-template-columns: 1fr; gap: 1.75rem; }
        }

        /* Small mobile: <480px */
        @media (max-width: 479px) {
            .hero-title { font-size: 1.875rem; }
            .features-grid { grid-template-columns: repeat(2,1fr); }
            .about-stats { grid-template-columns: repeat(3,1fr); }
        }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="nav-logo">
            <span class="logo-fruit">🍎</span>
            <span class="logo-text">Buah Segar</span>
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="#home"     class="nav-link active">Beranda</a></li>
            <li><a href="#products" class="nav-link">Produk</a></li>
            <li><a href="#about"    class="nav-link">Tentang</a></li>
            <li><a href="pages/catalog.php" class="nav-link">Belanja</a></li>
        </ul>
        <div class="nav-actions">
            <?php if ($user): ?>
                <span style="font-size:.875rem;color:var(--text-light)">Halo, <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong></span>
                <a href="auth/logout.php" class="nav-cta">Logout</a>
            <?php else: ?>
                <a href="auth/login.php" class="nav-cta">Masuk</a>
            <?php endif; ?>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- ── HERO ── -->
<section id="home" class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <h1 class="hero-title">
                Kesegaran<br>
                <span class="title-accent">Alami</span><br>
                Setiap Hari
            </h1>
            <p class="hero-sub">Buah pilihan terbaik dari petani lokal & impor berkualitas premium, dikirim langsung ke rumah Anda.</p>
            <div class="hero-btns">
                <a href="pages/catalog.php" class="btn btn-primary">
                    Belanja Sekarang
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#products" class="btn btn-outline">Lihat Produk</a>
            </div>
           
        </div>
        <div class="hero-visual">
            <div class="fruit-card fc-1">
                <img src="https://images.unsplash.com/photo-1553279768-865429fa0078?w=600" alt="Mangga" loading="lazy">
                </div>
            <div class="fruit-card fc-2">
                <img src="https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600" alt="Apel" loading="lazy">
                </div>
            <div class="fruit-card fc-3">
                <img src="https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=600" alt="Jeruk" loading="lazy">
                </div>
        </div>
    </div>
</section>

<!-- ── FEATURES ── -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon">🌿</div><h3>100% Segar</h3><p>Dipetik langsung dari kebun dan dikirim dalam kondisi terbaik.</p></div>
            <div class="feature-card"><div class="feature-icon">✨</div><h3>Kualitas Premium</h3><p>Seleksi ketat untuk menjamin buah terbaik sampai ke tangan Anda.</p></div>
            <div class="feature-card"><div class="feature-icon">🚚</div><h3>Pengiriman Cepat</h3><p>Dikirim same-day untuk area Makassar dan sekitarnya.</p></div>
            <div class="feature-card"><div class="feature-icon">💯</div><h3>Garansi Uang Kembali</h3><p>Tidak puas? Kami kembalikan 100% uang Anda.</p></div>
        </div>
    </div>
</section>

<!-- ── PRODUCTS SHOWCASE ── -->
<section id="products" class="products-showcase">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Buah Pilihan Minggu Ini</h2>
            <p class="section-sub">Kesegaran yang tidak boleh Anda lewatkan</p>
        </div>
        <div class="products-grid">
            <?php if (count($featured_products) > 0): ?>
                <?php foreach ($featured_products as $i => $p):
                    $badge = getProductBadge($i, $p['kategori']);
                    $stock = floatval($p['stok_kg']);
                ?>
                <a href="pages/catalog.php" class="product-card">
                    <?php if ($badge): ?>
                    <div class="product-badge"><?php echo $badge; ?></div>
                    <?php endif; ?>
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars(getProductImage($p['gambar'])); ?>"
                             alt="<?php echo htmlspecialchars($p['nama_buah']); ?>"
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600'">
                        <div class="product-overlay">
                            <span class="quick-view">Lihat Detail</span>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($p['nama_buah']); ?></div>
                        <div class="product-origin"><?php echo $p['kategori'] === 'impor' ? 'Impor' : 'Lokal'; ?> · <?php echo htmlspecialchars($p['asal']); ?></div>
                        <div class="product-footer">
                            <span class="product-price">Rp <?php echo number_format($p['harga_kg'], 0, ',', '.'); ?><small>/kg</small></span>
                            <span class="product-stock">Stok: <?php echo $stock; ?> kg</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-light)">
                    Belum ada produk tersedia. <a href="pages/catalog.php" style="color:var(--primary)">Lihat semua produk</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="section-cta">
            <a href="pages/catalog.php" class="btn btn-primary">
                Lihat Semua Produk
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ── ABOUT ── -->
<section id="about" class="about">
    <div class="container">
        <div class="about-inner">
            <div>
                <h2 class="about-title">Dari Kebun ke Meja Anda</h2>
                <p class="about-desc">Kami percaya bahwa buah segar adalah kunci gaya hidup sehat. Sejak 2020, kami berkomitmen menghadirkan buah pilihan dari petani lokal dan impor berkualitas premium langsung ke rumah Anda.</p>
                <p class="about-desc">Setiap buah melalui seleksi ketat untuk memastikan kesegaran, rasa, dan nutrisi tetap terjaga.</p>
                <div class="about-stats">
                    <div class="stat-card"><span class="stat-num-lg">5000+</span><span class="stat-lbl">Pelanggan</span></div>
                    <div class="stat-card"><span class="stat-num-lg">50+</span><span class="stat-lbl">Jenis Buah</span></div>
                    <div class="stat-card"><span class="stat-num-lg">100%</span><span class="stat-lbl">Terjamin</span></div>
                </div>
            </div>
            <div class="about-img">
                <img src="https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=800" alt="Fresh Fruits" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ── CTA BANNER ── -->
<section class="cta-banner">
    <div class="container">
        <div class="cta-inner">
            <h2 class="cta-title">Siap Hidup Lebih Sehat?</h2>
            <p class="cta-sub">Mulai belanja buah segar hari ini dan rasakan perbedaannya</p>
            <a href="pages/catalog.php" class="btn btn-white">
                Mulai Belanja
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand-logo">🍎 <span style="font-family:var(--font-display)">Buah Segar</span></div>
                <p class="footer-tagline">Kesegaran Alami Setiap Hari</p>
            </div>
            <div class="footer-col">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#products">Produk</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="pages/catalog.php">Belanja</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="#">Cara Pesan</a></li>
                    <li><a href="#">Pengiriman</a></li>
                    <li><a href="#">Pembayaran</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Kontak</h4>
                <ul>
                    <li>📍 Makassar, Sulawesi Selatan</li>
                    <li>📞 0812-3456-7890</li>
                    <li>📧 info@buahsegar.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; 2026 Buah Segar. All rights reserved.</div>
    </div>
</footer>

<script>
    // Hamburger menu
    const hamburger = document.getElementById('hamburger');
    const navMenu   = document.getElementById('navMenu');
    hamburger.addEventListener('click', (e) => {
        e.stopPropagation();
        hamburger.classList.toggle('open');
        navMenu.classList.toggle('open');
        document.body.style.overflow = navMenu.classList.contains('open') ? 'hidden' : '';
    });
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
            hamburger.classList.remove('open');
            navMenu.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
    navMenu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('open');
            navMenu.classList.remove('open');
            document.body.style.overflow = '';
        });
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(a.getAttribute('href'));
            if (target) window.scrollTo({ top: target.offsetTop - 68, behavior: 'smooth' });
        });
    });

    // Navbar shadow on scroll
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        navbar.style.boxShadow = window.scrollY > 60 ? '0 2px 16px rgba(0,0,0,.08)' : '';
    });
</script>
</body>
</html>