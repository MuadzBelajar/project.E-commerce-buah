<?php
/**
 * ================================================
 * ADMIN DASHBOARD - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: admin/dashboard.php
 * Description: Halaman dashboard admin dengan
 *              statistics, recent orders, dan quick actions
 * ================================================
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Hanya admin yang bisa akses
requireAdmin();

// Get admin data
$admin = getLoggedInUser();

// ================================================
// FETCH STATISTICS
// ================================================

// Total Orders
$total_orders = fetchOne("SELECT COUNT(*) as total FROM orders")['total'] ?? 0;

// Total Products
$total_products = fetchOne("SELECT COUNT(*) as total FROM buah WHERE status = 'active'")['total'] ?? 0;

// Total Users (customers only)
$total_users = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")['total'] ?? 0;

// Total Revenue
$revenue_data = fetchOne("SELECT SUM(total_harga) as total FROM orders WHERE status != 'cancelled'")['total'] ?? 0;
$total_revenue = $revenue_data;

// Pending Orders
$pending_orders = fetchOne("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'")['total'] ?? 0;

// Low Stock Products (stok <= 10)
$low_stock_products = fetchAll("SELECT * FROM buah WHERE stok_kg <= 10 AND status = 'active' ORDER BY stok_kg ASC LIMIT 5");

// Recent Orders (last 10)
$recent_orders = fetchAll("SELECT o.*, u.nama_lengkap, u.username 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          ORDER BY o.created_at DESC 
                          LIMIT 10");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Buah Segar</title>
    
    <!-- Fonts -->
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
            --color-background: #F5F7FA;
            --color-white: #FFFFFF;
            --color-border: #E5E5E5;
            --color-error: #DC2626;
            --color-success: #16A34A;
            --color-warning: #D97706;
            --color-info: #2563EB;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: var(--color-background); color: var(--color-text); line-height: 1.6; }
        
        /* SIDEBAR */
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--color-white); border-right: 1px solid var(--color-border); position: fixed; height: 100vh; overflow-y: auto; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--color-border); }
        .sidebar-logo { display: flex; align-items: center; gap: 0.75rem; font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--color-primary); }
        .sidebar-logo span { font-size: 1.5rem; }
        .sidebar-nav { padding: 1rem 0; }
        .nav-section { padding: 0 1rem; margin-bottom: 1.5rem; }
        .nav-section-title { font-size: 0.75rem; font-weight: 600; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; padding: 0 0.75rem; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; margin: 0.25rem 0.75rem; border-radius: 10px; color: var(--color-text-light); text-decoration: none; font-weight: 500; transition: var(--transition); }
        .nav-item:hover { background: var(--color-primary-light); color: var(--color-primary); }
        .nav-item.active { background: var(--color-primary); color: white; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); position: absolute; bottom: 0; width: 100%; background: var(--color-white); }
        .user-info { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar { width: 40px; height: 40px; background: var(--color-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1rem; flex-shrink: 0; }
        .user-details { flex: 1; min-width: 0; }
        .user-name { font-weight: 600; font-size: 0.875rem; color: var(--color-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.75rem; color: var(--color-text-lighter); text-transform: capitalize; }
        
        /* MOBILE HEADER */
        .mobile-header { display: none; position: fixed; top: 0; left: 0; right: 0; height: 60px; background: var(--color-white); border-bottom: 1px solid var(--color-border); z-index: 101; padding: 0 1rem; align-items: center; justify-content: space-between; }
        .mobile-toggle { width: 40px; height: 40px; border: none; background: none; cursor: pointer; display: flex; flex-direction: column; gap: 6px; justify-content: center; align-items: center; }
        .mobile-toggle span { width: 24px; height: 2px; background: var(--color-text); transition: var(--transition); }
        .mobile-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(6px, 6px); }
        .mobile-toggle.active span:nth-child(2) { opacity: 0; }
        .mobile-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(6px, -6px); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 99; }
        
        /* MAIN CONTENT */
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; transition: margin-left 0.3s ease; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--color-text); }
        .page-actions { display: flex; gap: 1rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: var(--transition); border: none; cursor: pointer; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-primary:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-secondary { background: white; color: var(--color-text); border: 1px solid var(--color-border); }
        .btn-secondary:hover { background: var(--color-background); }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.8125rem; }
        
        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--color-white); border-radius: 16px; padding: 1.5rem; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); transition: var(--transition); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-icon.blue { background: #DBEAFE; }
        .stat-icon.green { background: #D1FAE5; }
        .stat-icon.orange { background: #FFEDD5; }
        .stat-icon.purple { background: #F3E8FF; }
        .stat-badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .stat-badge.pending { background: #FEF3C7; color: var(--color-warning); }
        .stat-value { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.875rem; color: var(--color-text-light); }
        
        /* DASHBOARD GRID */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: var(--color-white); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); overflow: hidden; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--color-text); }
        .card-body { padding: 1.5rem; }
        .card-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); background: var(--color-background); }
        
        /* TABLE */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--color-border); }
        .data-table td { padding: 1rem; border-bottom: 1px solid var(--color-border); font-size: 0.875rem; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background: var(--color-background); }
        .order-id { font-weight: 600; color: var(--color-primary); }
        .customer-name { font-weight: 600; color: var(--color-text); }
        .customer-email { font-size: 0.75rem; color: var(--color-text-lighter); }
        .price { font-weight: 600; color: var(--color-text); }
        .status-badge { padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: #FEF3C7; color: var(--color-warning); }
        .status-processing { background: #DBEAFE; color: var(--color-info); }
        .status-shipped { background: #E0E7FF; color: #4F46E5; }
        .status-delivered { background: #D1FAE5; color: var(--color-success); }
        .status-cancelled { background: #FEE2E2; color: var(--color-error); }
        
        /* ALERTS */
        .alert-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #FEF3C7; border-radius: 10px; margin-bottom: 0.75rem; }
        .alert-item:last-child { margin-bottom: 0; }
        .alert-icon { font-size: 1.25rem; }
        .alert-content { flex: 1; }
        .alert-title { font-weight: 600; font-size: 0.875rem; color: var(--color-text); }
        .alert-text { font-size: 0.8125rem; color: var(--color-text-light); }
        .alert-stock { font-weight: 600; color: var(--color-error); }
        
        /* QUICK ACTIONS */
        .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1.5rem; }
        .quick-action { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; background: var(--color-white); border-radius: 12px; border: 1px solid var(--color-border); text-decoration: none; color: var(--color-text); transition: var(--transition); }
        .quick-action:hover { background: var(--color-primary); color: white; border-color: var(--color-primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .quick-action-icon { width: 48px; height: 48px; background: var(--color-primary-light); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: var(--transition); }
        .quick-action:hover .quick-action-icon { background: rgba(255,255,255,0.2); }
        .quick-action-text { font-weight: 600; }
        .quick-action-desc { font-size: 0.8125rem; opacity: 0.8; }
        
        /* FLASH MESSAGE */
        .flash-message { position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: var(--shadow-lg); animation: slideIn 0.3s ease-out; z-index: 9999; max-width: 400px; }
        .flash-success { background: #D1FAE5; color: var(--color-success); border: 1px solid #86EFAC; }
        .flash-error { background: #FEE2E2; color: var(--color-error); border: 1px solid #FCA5A5; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(100px); } to { opacity: 1; transform: translateX(0); } }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .mobile-header { display: flex; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1rem; margin-top: 60px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .quick-actions { grid-template-columns: 1fr; }
            .page-title { font-size: 1.5rem; }
            .btn { width: 100%; justify-content: center; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .main-content { padding: 0.5rem; }
        }

        /* RESET MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9998; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; border-radius: 16px; padding: 2rem; max-width: 420px; width: 90%; box-shadow: var(--shadow-lg); animation: modalIn .25s ease-out; }
        @keyframes modalIn { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
        .modal-icon { font-size: 3rem; text-align: center; margin-bottom: 1rem; }
        .modal-title { font-family: var(--font-display); font-size: 1.375rem; font-weight: 700; text-align: center; margin-bottom: .5rem; color: var(--color-text); }
        .modal-text { color: var(--color-text-light); text-align: center; font-size: .9rem; margin-bottom: 1.5rem; line-height: 1.6; }
        .modal-text strong { color: var(--color-error); }
        .modal-actions { display: flex; gap: .75rem; }
        .btn-danger { background: var(--color-error); color: white; }
        .btn-danger:hover { background: #b91c1c; transform: translateY(-1px); }
        .modal-loading { display: none; text-align: center; padding: .5rem; font-size: .875rem; color: var(--color-text-light); }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="mobile-toggle" id="mobileToggle">
            <span></span><span></span><span></span>
        </button>
        <div class="sidebar-logo">
            <span>🍎</span>
            Buah Segar
        </div>
        <div style="width: 40px;"></div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <span>🍎</span>
                    Buah Segar
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Menu Utama</div>
                    <a href="dashboard.php" class="nav-item active">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a>
                    <a href="products.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Produk
                    </a>
                    <a href="orders.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Pesanan
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Lainnya</div>
                    <a href="../pages/catalog.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Lihat Website
                    </a>
                    <a href="../auth/logout.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Logout
                    </a>
                </div>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($admin['nama_lengkap'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($admin['nama_lengkap']); ?></div>
                        <div class="user-role"><?php echo $admin['role']; ?></div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Flash Message -->
            <?php 
            $flash = getFlashMessage();
            if ($flash): 
            ?>
            <div class="flash-message flash-<?php echo $flash['type']; ?>" id="flashMessage">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p style="color: var(--color-text-light);">Selamat datang kembali, <?php echo htmlspecialchars($admin['nama_lengkap']); ?>!</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-secondary" id="btnResetPesanan" onclick="document.getElementById('resetModal').classList.add('open')">
                         Reset Data Pesanan
                    </button>
                    <a href="products.php?action=add" class="btn btn-primary">+ Tambah Produk</a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon blue">📦</div>
                        <?php if ($pending_orders > 0): ?>
                        <span class="stat-badge pending"><?php echo $pending_orders; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon green">🍎</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Produk Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon purple">👥</div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Pelanggan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon orange">💰</div>
                    </div>
                    <div class="stat-value"><?php echo formatRupiah($total_revenue); ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Pesanan Terbaru</h2>
                        <a href="orders.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_orders) > 0): ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><span class="order-id">#<?php echo $order['id']; ?></span></td>
                                        <td>
                                            <div class="customer-name"><?php echo htmlspecialchars($order['nama_pemesan']); ?></div>
                                            <div class="customer-email"><?php echo htmlspecialchars($order['username']); ?></div>
                                        </td>
                                        <td class="price"><?php echo formatRupiah($order['total_harga']); ?></td>
                                        <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                                        <td><?php echo formatTanggal($order['created_at'], 'pendek'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--color-text-light);">
                                            Belum ada pesanan
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Low Stock Alerts -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h2 class="card-title">Status Buah</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($low_stock_products) > 0): ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                <div class="alert-item">
                                    <span class="alert-icon">🍎</span>
                                    <div class="alert-content">
                                        <div class="alert-title"><?php echo htmlspecialchars($product['nama_buah']); ?></div>
                                        <div class="alert-text">Sisa stok: <span class="alert-stock"><?php echo $product['stok_kg']; ?> kg</span></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--color-text-light); padding: 1rem;">
                                    ✅ Semua stok dalam kondisi aman
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="products.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">Kelola Produk</a>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Aksi Cepat</h2>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="products.php?action=add" class="quick-action">
                                    <div class="quick-action-icon">➕</div>
                                    <div>
                                        <div class="quick-action-text">Tambah Produk</div>
                                        <div class="quick-action-desc">Buah baru</div>
                                    </div>
                                </a>
                                <a href="orders.php" class="quick-action">
                                    <div class="quick-action-icon">📋</div>
                                    <div>
                                        <div class="quick-action-text">Kelola Pesanan</div>
                                        <div class="quick-action-desc">Update status</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- RESET MODAL -->
    <div class="modal-overlay" id="resetModal">
        <div class="modal-box">
            <div class="modal-icon">⚠️</div>
            <div class="modal-title">Reset Semua Pesanan?</div>
            <div class="modal-text">
                Tindakan ini akan <strong>menghapus seluruh data pesanan</strong> dan mereset ID ke awal.<br>
                Data yang dihapus <strong>tidak dapat dikembalikan.</strong>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" style="flex:1" onclick="document.getElementById('resetModal').classList.remove('open')">
                    Batal
                </button>
                <button class="btn btn-danger" style="flex:1" id="btnConfirmReset">
                    Ya, Reset Sekarang
                </button>
            </div>
            <div class="modal-loading" id="resetLoading"> Memproses...</div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                this.classList.toggle('active');
            });
            
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                this.classList.remove('active');
                mobileToggle.classList.remove('active');
            });
            
            // Close on nav item click (mobile)
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                        mobileToggle.classList.remove('active');
                    }
                });
            });
        }
        
        // Auto hide flash message
        setTimeout(function() {
            const flash = document.getElementById('flashMessage');
            if (flash) {
                flash.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => flash.remove(), 300);
            }
        }, 5000);

        // Reset pesanan
        document.getElementById('btnConfirmReset').addEventListener('click', async function() {
            this.disabled = true;
            this.textContent = 'Mereset...';
            document.getElementById('resetLoading').style.display = 'block';
            try {
                const res  = await fetch('reset_orders.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('resetModal').classList.remove('open');
                    // Show success flash
                    const flash = document.createElement('div');
                    flash.className = 'flash-message flash-success';
                    flash.textContent = '✅ ' + data.message;
                    document.body.appendChild(flash);
                    setTimeout(() => { flash.remove(); location.reload(); }, 2000);
                } else {
                    alert('Gagal: ' + data.message);
                    this.disabled = false;
                    this.textContent = 'Ya, Reset Sekarang';
                    document.getElementById('resetLoading').style.display = 'none';
                }
            } catch(e) {
                alert('Terjadi kesalahan koneksi');
                this.disabled = false;
                this.textContent = 'Ya, Reset Sekarang';
                document.getElementById('resetLoading').style.display = 'none';
            }
        });

        // Close modal on overlay click
        document.getElementById('resetModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });
    </script>
</body>
</html>