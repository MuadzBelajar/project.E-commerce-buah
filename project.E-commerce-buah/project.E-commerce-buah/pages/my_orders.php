<?php
/**
 * ================================================
 * MY ORDERS - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: pages/my_orders.php
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

$user       = getLoggedInUser();
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Ambil semua pesanan milik user ini
$orders = fetchAll(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
    [$user['id']]
);

// Status label & style
function statusInfo($status) {
    return match($status) {
        'pending'    => ['label' => 'Menunggu',  'color' => '#D97706', 'bg' => '#FEF3C7', 'icon' => '⏳'],
        'processing' => ['label' => 'Diproses',  'color' => '#2563EB', 'bg' => '#DBEAFE', 'icon' => '⚙️'],
        'shipped'    => ['label' => 'Dikirim',   'color' => '#4F46E5', 'bg' => '#E0E7FF', 'icon' => '🚚'],
        'delivered'  => ['label' => 'Selesai',   'color' => '#16A34A', 'bg' => '#D1FAE5', 'icon' => '✅'],
        'cancelled'  => ['label' => 'Dibatalkan','color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => '❌'],
        default      => ['label' => ucfirst($status), 'color' => '#666', 'bg' => '#f5f5f5', 'icon' => '📦'],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Buah Segar</title>
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
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.12);
            --transition: 300ms cubic-bezier(0.4,0,0.2,1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: var(--color-background); color: var(--color-text); line-height: 1.6; min-height: 100vh; }
        .container { max-width: 860px; margin: 0 auto; padding: 0 1.5rem; }

        /* NAVBAR */
        .navbar { background: white; border-bottom: 1px solid var(--color-border); padding: 1.25rem 0; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-inner { max-width: 860px; margin: 0 auto; padding: 0 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { display: flex; align-items: center; gap: 0.625rem; text-decoration: none; }
        .logo-icon { font-size: 1.75rem; }
        .logo-text { font-family: var(--font-display); font-size: 1.375rem; font-weight: 700; color: var(--color-primary); }
        .nav-actions { display: flex; gap: 0.75rem; align-items: center; }
        .btn-nav { padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: var(--transition); border: 2px solid var(--color-primary); cursor: pointer; white-space: nowrap; }
        .btn-nav.outline { background: white; color: var(--color-primary); }
        .btn-nav.outline:hover { background: var(--color-primary-light); }
        .btn-nav.solid { background: var(--color-primary); color: white; }
        .btn-nav.solid:hover { background: var(--color-primary-dark); }
        .cart-wrap { position: relative; }
        .cart-badge { position: absolute; top: -8px; right: -8px; background: var(--color-error); color: white; font-size: 0.6rem; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }

        /* PAGE HEADER */
        .page-header { padding: 2rem 0 1.5rem; }
        .page-title { font-family: var(--font-display); font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-sub { color: var(--color-text-light); font-size: 0.95rem; }

        /* ORDERS LIST */
        .orders-list { display: flex; flex-direction: column; gap: 1rem; padding-bottom: 3rem; }

        /* ORDER CARD */
        .order-card { background: white; border-radius: 16px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); overflow: hidden; transition: var(--transition); }
        .order-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }

        .order-card-header { padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--color-border); flex-wrap: wrap; gap: 0.75rem; }
        .order-meta { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .order-id { font-weight: 700; font-size: 1rem; color: var(--color-primary); }
        .order-date { font-size: 0.85rem; color: var(--color-text-lighter); }
        .status-badge { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 99px; font-size: 0.8rem; font-weight: 700; }

        .order-card-body { padding: 1.25rem 1.5rem; }
        .order-items-preview { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
        .preview-item { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; }
        .preview-item .item-name { color: var(--color-text-light); }
        .preview-item .item-sub  { font-weight: 600; }
        .more-items { font-size: 0.8rem; color: var(--color-text-lighter); font-style: italic; }

        .order-card-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: #fafafa; flex-wrap: wrap; gap: 0.75rem; }
        .order-total { font-weight: 700; font-size: 1.1rem; color: var(--color-primary); }
        .order-total small { font-size: 0.8rem; color: var(--color-text-lighter); font-weight: 400; }
        .btn-detail { padding: 0.5rem 1.25rem; background: var(--color-primary); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.875rem; transition: var(--transition); }
        .btn-detail:hover { background: var(--color-primary-dark); }

        /* EMPTY STATE */
        .empty-card { background: white; border-radius: 16px; border: 1px solid var(--color-border); text-align: center; padding: 4rem 2rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.4; }
        .empty-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-text { color: var(--color-text-light); margin-bottom: 2rem; font-size: 0.95rem; }
        .btn-shop { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.875rem 2rem; background: var(--color-primary); color: white; border-radius: 10px; text-decoration: none; font-weight: 700; transition: var(--transition); }
        .btn-shop:hover { background: var(--color-primary-dark); transform: translateY(-2px); }

        @media (max-width: 600px) {
            .container { padding: 0 1rem; }
            .order-card-header { flex-direction: column; align-items: flex-start; }
            .order-card-footer { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="../index.php" class="nav-logo">
                <span class="logo-icon">🍎</span>
                <span class="logo-text">Buah Segar</span>
            </a>
            <div class="nav-actions">
                <a href="catalog.php" class="btn-nav outline">🛍️ Belanja</a>
                <div class="cart-wrap">
                    <a href="cart.php" class="btn-nav outline">🛒 Keranjang
                        <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <a href="../auth/logout.php" class="btn-nav solid">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📋 Pesanan Saya</h1>
            <p class="page-sub">Halo, <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong> — ini semua pesananmu</p>
        </div>

        <?php if (count($orders) > 0): ?>
        <div class="orders-list">
            <?php foreach ($orders as $order):
                $si = statusInfo($order['status']);
                // Ambil item-item pesanan ini
                $items = fetchAll(
                    "SELECT nama_buah, jumlah_kg, subtotal FROM order_items WHERE order_id = ?",
                    [$order['id']]
                );
                $preview = array_slice($items, 0, 2);
                $extra   = count($items) - 2;
            ?>
            <div class="order-card">
                <div class="order-card-header">
                    <div class="order-meta">
                        <span class="order-id">#<?php echo $order['id']; ?></span>
                        <span class="order-date">📅 <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?> WIB</span>
                    </div>
                    <span class="status-badge" style="background:<?php echo $si['bg']; ?>; color:<?php echo $si['color']; ?>">
                        <?php echo $si['icon']; ?> <?php echo $si['label']; ?>
                    </span>
                </div>

                <div class="order-card-body">
                    <div class="order-items-preview">
                        <?php foreach ($preview as $item): ?>
                        <div class="preview-item">
                            <span class="item-name">🍃 <?php echo htmlspecialchars($item['nama_buah']); ?> ×<?php echo $item['jumlah_kg']; ?>kg</span>
                            <span class="item-sub">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($extra > 0): ?>
                        <div class="more-items">+<?php echo $extra; ?> item lainnya</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="order-card-footer">
                    <div class="order-total">
                        Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?>
                        <small> · <?php echo count($items); ?> jenis buah</small>
                    </div>
                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-detail">Lihat Detail →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="empty-card">
            <div class="empty-icon">📋</div>
            <h2 class="empty-title">Belum Ada Pesanan</h2>
            <p class="empty-text">Kamu belum pernah memesan buah.<br>Yuk mulai belanja sekarang!</p>
            <a href="catalog.php" class="btn-shop">🍎 Mulai Belanja</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>