<?php
/**
 * ================================================
 * ORDER DETAIL - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: pages/order_detail.php
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

$user       = getLoggedInUser();
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$order_id   = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header('Location: my_orders.php');
    exit;
}

// Ambil order — pastikan milik user ini
$order = fetchOne(
    "SELECT * FROM orders WHERE id = ? AND user_id = ?",
    [$order_id, $user['id']]
);

if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// Ambil items
$items = fetchAll(
    "SELECT oi.*, b.gambar FROM order_items oi
     LEFT JOIN buah b ON oi.buah_id = b.id
     WHERE oi.order_id = ?",
    [$order_id]
);

// Helper image
function getItemImage($filename) {
    if (empty($filename)) return 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=80';
    if (filter_var($filename, FILTER_VALIDATE_URL)) return $filename;
    return '../assets/images/products/' . basename($filename);
}

// Status info
function statusInfo($status) {
    return match($status) {
        'pending'    => ['label' => 'Menunggu Konfirmasi', 'color' => '#D97706', 'bg' => '#FEF3C7', 'icon' => '⏳', 'desc' => 'Pesananmu sudah masuk dan menunggu konfirmasi dari admin.'],
        'processing' => ['label' => 'Sedang Diproses',    'color' => '#2563EB', 'bg' => '#DBEAFE', 'icon' => '⚙️', 'desc' => 'Admin sedang mempersiapkan pesananmu.'],
        'shipped'    => ['label' => 'Dalam Pengiriman',   'color' => '#4F46E5', 'bg' => '#E0E7FF', 'icon' => '🚚', 'desc' => 'Pesananmu sedang dalam perjalanan ke alamatmu.'],
        'delivered'  => ['label' => 'Pesanan Selesai',    'color' => '#16A34A', 'bg' => '#D1FAE5', 'icon' => '✅', 'desc' => 'Pesananmu telah sampai. Terima kasih sudah belanja!'],
        'cancelled'  => ['label' => 'Dibatalkan',         'color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => '❌', 'desc' => 'Pesanan ini telah dibatalkan.'],
        default      => ['label' => ucfirst($status),     'color' => '#666',    'bg' => '#f5f5f5', 'icon' => '📦', 'desc' => ''],
    };
}

// Status timeline steps
$timeline_steps = ['pending', 'processing', 'shipped', 'delivered'];
$current_index  = array_search($order['status'], $timeline_steps);

$si = statusInfo($order['status']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order_id; ?> - Buah Segar</title>
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
        .btn-nav { padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: var(--transition); border: 2px solid var(--color-primary); }
        .btn-nav.outline { background: white; color: var(--color-primary); }
        .btn-nav.outline:hover { background: var(--color-primary-light); }
        .btn-nav.solid { background: var(--color-primary); color: white; }
        .btn-nav.solid:hover { background: var(--color-primary-dark); }
        .cart-wrap { position: relative; }
        .cart-badge { position: absolute; top: -8px; right: -8px; background: var(--color-error); color: white; font-size: 0.6rem; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }

        /* PAGE HEADER */
        .page-header { padding: 2rem 0 1.5rem; display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .page-header-left { }
        .breadcrumb { font-size: 0.85rem; color: var(--color-text-lighter); margin-bottom: 0.5rem; }
        .breadcrumb a { color: var(--color-primary); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .page-title { font-family: var(--font-display); font-size: 1.75rem; font-weight: 700; }

        /* LAYOUT */
        .detail-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; padding-bottom: 3rem; }

        /* CARD */
        .card { background: white; border-radius: 16px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.25rem; }
        .card:last-child { margin-bottom: 0; }
        .card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-weight: 700; font-size: 0.95rem; color: var(--color-text); display: flex; align-items: center; gap: 0.5rem; }
        .card-body { padding: 1.25rem 1.5rem; }

        /* STATUS BANNER */
        .status-banner { border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 1rem; border: 1px solid; }
        .status-banner-icon { font-size: 2rem; flex-shrink: 0; }
        .status-banner-label { font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem; }
        .status-banner-desc { font-size: 0.875rem; opacity: 0.85; }

        /* TIMELINE */
        .timeline { display: flex; align-items: flex-start; gap: 0; margin-bottom: 0.5rem; }
        .timeline-step { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 14px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--color-border);
            z-index: 0;
        }
        .timeline-step.done:not(:last-child)::after { background: var(--color-primary); }
        .step-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--color-border); background: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; position: relative; z-index: 1; flex-shrink: 0; }
        .timeline-step.done .step-dot { background: var(--color-primary); border-color: var(--color-primary); color: white; }
        .timeline-step.current .step-dot { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(45,134,89,0.2); }
        .step-label { font-size: 0.7rem; color: var(--color-text-lighter); text-align: center; margin-top: 0.375rem; font-weight: 500; }
        .timeline-step.done .step-label,
        .timeline-step.current .step-label { color: var(--color-primary); font-weight: 600; }

        /* ITEMS LIST */
        .item-row { display: flex; align-items: center; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--color-border); }
        .item-row:last-child { border-bottom: none; padding-bottom: 0; }
        .item-row:first-child { padding-top: 0; }
        .item-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; background: var(--color-background); flex-shrink: 0; }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; font-size: 0.95rem; }
        .item-meta { font-size: 0.8rem; color: var(--color-text-lighter); }
        .item-sub { font-weight: 700; color: var(--color-primary); font-size: 0.95rem; white-space: nowrap; }

        /* INFO ROWS */
        .info-row { display: flex; gap: 1rem; padding: 0.625rem 0; border-bottom: 1px solid var(--color-border); font-size: 0.9rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--color-text-lighter); min-width: 130px; flex-shrink: 0; }
        .info-value { font-weight: 600; color: var(--color-text); }

        /* TOTAL */
        .total-section { padding: 1rem 1.5rem; background: #fafafa; border-top: 2px solid var(--color-border); }
        .total-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.375rem 0; }
        .total-row.grand { font-weight: 700; font-size: 1.1rem; color: var(--color-primary); border-top: 1px solid var(--color-border); padding-top: 0.75rem; margin-top: 0.375rem; }

        /* SIDEBAR STICKY */
        .sidebar-sticky { position: sticky; top: 90px; }

        /* ORDER ID CARD */
        .order-id-card { background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); border-radius: 16px; padding: 1.5rem; color: white; margin-bottom: 1.25rem; text-align: center; }
        .order-id-label { font-size: 0.8rem; opacity: 0.8; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .order-id-number { font-family: var(--font-display); font-size: 2.5rem; font-weight: 700; }
        .order-id-date { font-size: 0.8rem; opacity: 0.75; margin-top: 0.375rem; }

        /* ACTION BUTTONS */
        .action-btns { display: flex; flex-direction: column; gap: 0.75rem; }
        .btn-action { display: block; padding: 0.875rem; border-radius: 10px; text-align: center; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: var(--transition); border: 2px solid var(--color-primary); }
        .btn-action.primary { background: var(--color-primary); color: white; }
        .btn-action.primary:hover { background: var(--color-primary-dark); }
        .btn-action.outline { background: white; color: var(--color-primary); }
        .btn-action.outline:hover { background: var(--color-primary-light); }

        /* CANCELLED NOTE */
        .cancelled-note { background: #FEE2E2; border: 1px solid #FECACA; border-radius: 12px; padding: 1rem 1.25rem; font-size: 0.875rem; color: var(--color-error); margin-bottom: 0; }

        @media (max-width: 768px) {
            .detail-layout { grid-template-columns: 1fr; }
            .sidebar-sticky { position: static; }
        }
        @media (max-width: 480px) {
            .container { padding: 0 1rem; }
            .page-title { font-size: 1.5rem; }
            .timeline { gap: 0; }
            .step-label { font-size: 0.65rem; }
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
            <div class="page-header-left">
                <div class="breadcrumb">
                    <a href="my_orders.php">← Pesanan Saya</a>
                </div>
                <h1 class="page-title">Detail Pesanan</h1>
            </div>
        </div>

        <div class="detail-layout">
            <!-- LEFT COLUMN -->
            <div>

                <!-- STATUS BANNER -->
                <div class="status-banner" style="background:<?php echo $si['bg']; ?>; border-color:<?php echo $si['color']; ?>20; color:<?php echo $si['color']; ?>">
                    <div class="status-banner-icon"><?php echo $si['icon']; ?></div>
                    <div>
                        <div class="status-banner-label"><?php echo $si['label']; ?></div>
                        <div class="status-banner-desc"><?php echo $si['desc']; ?></div>
                    </div>
                </div>

                <!-- TIMELINE (hanya kalau tidak cancelled) -->
                <?php if ($order['status'] !== 'cancelled'): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📍 Status Pesanan</span>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php
                            $step_labels = ['Menunggu', 'Diproses', 'Dikirim', 'Selesai'];
                            $step_icons  = ['⏳', '⚙️', '🚚', '✅'];
                            foreach ($timeline_steps as $i => $step):
                                $is_done    = ($current_index !== false && $i <= $current_index);
                                $is_current = ($i === $current_index);
                                $cls = $is_done ? 'done' : '';
                                $cls .= $is_current ? ' current' : '';
                            ?>
                            <div class="timeline-step <?php echo trim($cls); ?>">
                                <div class="step-dot"><?php echo $is_done ? '✓' : ($i + 1); ?></div>
                                <div class="step-label"><?php echo $step_labels[$i]; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ITEM PESANAN -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🍃 Item Pesanan</span>
                        <span style="font-size:0.8rem; color:var(--color-text-lighter)"><?php echo count($items); ?> jenis buah</span>
                    </div>
                    <div class="card-body" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
                        <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <img src="<?php echo htmlspecialchars(getItemImage($item['gambar'])); ?>"
                                 alt="<?php echo htmlspecialchars($item['nama_buah']); ?>"
                                 class="item-img"
                                 onerror="this.src='https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=80'">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['nama_buah']); ?></div>
                                <div class="item-meta"><?php echo $item['jumlah_kg']; ?> kg × Rp <?php echo number_format($item['harga_kg'], 0, ',', '.'); ?>/kg</div>
                            </div>
                            <div class="item-sub">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Ongkos Kirim</span>
                            <span style="color: var(--color-primary); font-weight: 600;">Gratis</span>
                        </div>
                        <div class="total-row grand">
                            <span>Total Pembayaran</span>
                            <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- INFO PENGIRIMAN -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📦 Informasi Pengiriman</span>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Nama Penerima</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['nama_pemesan']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nomor Telepon</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['no_telepon']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Alamat Pengiriman</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($order['alamat_kirim'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal Pesan</span>
                            <span class="info-value"><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (SIDEBAR) -->
            <div>
                <div class="sidebar-sticky">

                    <!-- ORDER ID CARD -->
                    <div class="order-id-card">
                        <div class="order-id-label">Nomor Pesanan</div>
                        <div class="order-id-number">#<?php echo $order['id']; ?></div>
                        <div class="order-id-date"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="card">
                        <div class="card-body">
                            <div class="action-btns">
                                <a href="catalog.php" class="btn-action primary"> Belanja Lagi</a>
                                <a href="my_orders.php" class="btn-action outline"> Semua Pesanan</a>
                            </div>
                        </div>
                    </div>

                    <!-- INFO NOTE -->
                    <?php if ($order['status'] === 'cancelled'): ?>
                    <div class="cancelled-note">
                        ❌ Pesanan ini telah dibatalkan dan tidak dapat diproses kembali.
                    </div>
                    <?php elseif ($order['status'] === 'pending'): ?>
                    <div class="card">
                        <div class="card-body" style="font-size: 0.85rem; color: var(--color-text-light); line-height: 1.6;">
                            💡 <strong>Informasi:</strong> Status pesananmu akan diperbarui secara otomatis sesuai proses dari admin. Kamu bisa refresh halaman ini kapan saja.
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</body>
</html>