<?php
/**
 * ================================================
 * ADMIN ORDERS - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: admin/orders.php
 * Description: Order management dengan listing, 
 *              detail view, dan status update
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
// HANDLE ACTIONS
// ================================================

$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = '';

// UPDATE Order Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        try {
            execute("UPDATE orders SET status = ? WHERE id = ?", [$new_status, $order_id]);
            $message = 'Status pesanan berhasil diupdate!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Gagal update status: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// DELETE Order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    
    try {
        execute("DELETE FROM order_items WHERE order_id = ?", [$order_id]);
        execute("DELETE FROM orders WHERE id = ?", [$order_id]);
        
        $message = 'Pesanan berhasil dihapus!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Gagal menghapus pesanan: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Filter orders
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT o.*, u.nama_lengkap, u.username, u.no_telepon as user_phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (o.nama_pemesan LIKE ? OR o.id LIKE ? OR u.username LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY o.created_at DESC";

$orders = fetchAll($sql, $params);

// Get order counts by status
$status_counts = fetchAll("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$count_by_status = [];
foreach ($status_counts as $sc) {
    $count_by_status[$sc['status']] = $sc['count'];
}

// View order detail
$order_detail = null;
$order_items = [];
if ($action === 'view' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $order_detail = fetchOne("SELECT o.*, u.nama_lengkap, u.username, u.email 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id 
                             WHERE o.id = ?", [$order_id]);
    
    if ($order_detail) {
        $order_items = fetchAll("SELECT oi.*, b.gambar, b.nama_buah 
                                FROM order_items oi 
                                JOIN buah b ON oi.buah_id = b.id 
                                WHERE oi.order_id = ?", [$order_id]);
    } else {
        $action = 'list';
        $message = 'Pesanan tidak ditemukan!';
        $message_type = 'error';
    }
}

/**
 * Helper function to get image path
 * Returns relative path from current file location
 */
function getImagePath($filename) {
    // Default placeholder
    if (empty($filename)) {
        return 'https://via.placeholder.com/60';
    }
    
    // If it's already a URL, return as-is
    if (filter_var($filename, FILTER_VALIDATE_URL)) {
        return $filename;
    }
    
    // Normalize: extract just the filename (remove any path)
    $clean_filename = basename($filename);
    
    // Return relative path from admin folder to assets
    return '../assets/images/products/' . $clean_filename;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Buah Segar Admin</title>
    
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .page-title { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--color-text); }
        .page-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: var(--transition); border: none; cursor: pointer; white-space: nowrap; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-primary:hover { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-secondary { background: white; color: var(--color-text); border: 1px solid var(--color-border); }
        .btn-secondary:hover { background: var(--color-background); }
        .btn-danger { background: var(--color-error); color: white; }
        .btn-danger:hover { background: #B91C1C; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.8125rem; }
        
        /* ALERT */
        .alert { padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { background: #D1FAE5; color: var(--color-success); border: 1px solid #86EFAC; }
        .alert-error { background: #FEE2E2; color: var(--color-error); border: 1px solid #FCA5A5; }
        
        /* FILTER BAR */
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.5rem 1rem; border: 1px solid var(--color-border); background: white; border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: var(--color-text-light); text-decoration: none; transition: var(--transition); }
        .filter-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .filter-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .filter-count { background: rgba(0,0,0,0.1); padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.25rem; }
        .search-box { position: relative; flex: 1; max-width: 300px; }
        .search-input { width: 100%; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--color-border); border-radius: 8px; font-size: 0.875rem; }
        .search-input:focus { outline: none; border-color: var(--color-primary); }
        .search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-lighter); }
        
        /* CARD */
        .card { background: var(--color-white); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); overflow: hidden; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--color-text); }
        .card-body { padding: 1.5rem; }
        
        /* TABLE */
        .table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .data-table th { text-align: left; padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
        .data-table td { padding: 1rem; border-bottom: 1px solid var(--color-border); font-size: 0.875rem; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background: var(--color-background); }
        .order-id { font-weight: 600; color: var(--color-primary); }
        .customer-name { font-weight: 600; color: var(--color-text); }
        .customer-info { font-size: 0.75rem; color: var(--color-text-lighter); }
        .price { font-weight: 600; color: var(--color-text); }
        .status-badge { padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: #FEF3C7; color: var(--color-warning); }
        .status-processing { background: #DBEAFE; color: var(--color-info); }
        .status-shipped { background: #E0E7FF; color: #4F46E5; }
        .status-delivered { background: #D1FAE5; color: var(--color-success); }
        .status-cancelled { background: #FEE2E2; color: var(--color-error); }
        .actions { display: flex; gap: 0.5rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: var(--transition); font-size: 0.875rem; text-decoration: none; }
        .action-btn.view { background: #DBEAFE; color: var(--color-primary); }
        .action-btn.view:hover { background: var(--color-primary); color: white; }
        .action-btn.delete { background: #FEE2E2; color: var(--color-error); }
        .action-btn.delete:hover { background: var(--color-error); color: white; }
        
        /* ORDER DETAIL */
        .detail-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem; }
        .detail-section { margin-bottom: 1.5rem; }
        .detail-section-title { font-weight: 600; font-size: 0.875rem; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; }
        .detail-card { background: var(--color-background); border-radius: 12px; padding: 1.25rem; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--color-border); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: var(--color-text-light); }
        .detail-value { font-weight: 600; color: var(--color-text); }
        .detail-value.large { font-size: 1.25rem; color: var(--color-primary); }
        
        /* ITEMS LIST */
        .items-list { display: flex; flex-direction: column; gap: 1rem; }
        .item-row { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--color-background); border-radius: 12px; }
        .item-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; background: white; border: 1px solid var(--color-border); }
        .item-details { flex: 1; }
        .item-name { font-weight: 600; color: var(--color-text); }
        .item-meta { font-size: 0.75rem; color: var(--color-text-lighter); }
        .item-price { text-align: right; }
        .item-total { font-weight: 600; color: var(--color-text); }
        
        /* STATUS FORM */
        .status-form { display: flex; gap: 1rem; align-items: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); flex-wrap: wrap; }
        .form-select { padding: 0.5rem 1rem; border: 1px solid var(--color-border); border-radius: 8px; font-size: 0.875rem; font-family: var(--font-body); }
        
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 3rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-text { color: var(--color-text-light); margin-bottom: 1.5rem; }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .mobile-header { display: flex; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1rem; margin-top: 60px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-title { font-size: 1.5rem; }
            .page-actions { width: 100%; }
            .btn { width: 100%; justify-content: center; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            .detail-grid { grid-template-columns: 1fr; }
            .status-form { flex-direction: column; align-items: stretch; }
            .status-form .btn { width: 100%; justify-content: center; }
        }
        
        @media (max-width: 480px) {
            .main-content { padding: 0.5rem; }
            .page-title { font-size: 1.25rem; }
        }
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
                    <a href="dashboard.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a>
                    <a href="products.php" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Produk
                    </a>
                    <a href="orders.php" class="nav-item active">
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
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span><?php echo $message; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        <?php echo $action === 'view' ? 'Detail Pesanan' : 'Kelola Pesanan'; ?>
                    </h1>
                </div>
                <div class="page-actions">
                    <?php if ($action === 'view'): ?>
                    <a href="orders.php" class="btn btn-secondary">← Kembali</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($action === 'list'): ?>
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <a href="orders.php" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        Semua<span class="filter-count"><?php echo array_sum($count_by_status); ?></span>
                    </a>
                    <a href="orders.php?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Menunggu<span class="filter-count"><?php echo $count_by_status['pending'] ?? 0; ?></span>
                    </a>
                    <a href="orders.php?status=processing" class="filter-btn <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                        Diproses<span class="filter-count"><?php echo $count_by_status['processing'] ?? 0; ?></span>
                    </a>
                    <a href="orders.php?status=shipped" class="filter-btn <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                        Dikirim<span class="filter-count"><?php echo $count_by_status['shipped'] ?? 0; ?></span>
                    </a>
                    <a href="orders.php?status=delivered" class="filter-btn <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                        Selesai<span class="filter-count"><?php echo $count_by_status['delivered'] ?? 0; ?></span>
                    </a>
                </div>
                <form method="GET" class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" class="search-input" placeholder="Cari pesanan..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Orders List -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <?php if (count($orders) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><span class="order-id">#<?php echo $order['id']; ?></span></td>
                                    <td>
                                        <div class="customer-name"><?php echo htmlspecialchars($order['nama_pemesan']); ?></div>
                                        <div class="customer-info"><?php echo htmlspecialchars($order['username']); ?> • <?php echo $order['no_telepon']; ?></div>
                                    </td>
                                    <td class="price"><?php echo formatRupiah($order['total_harga']); ?></td>
                                    <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span></td>
                                    <td><?php echo formatTanggal($order['created_at'], 'pendek'); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="action-btn view" title="Lihat Detail">✍🏻</a>
                                            <a href="orders.php?delete=<?php echo $order['id']; ?>" class="action-btn delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus pesanan ini?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3 class="empty-title">Belum Ada Pesanan</h3>
                        <p class="empty-text">Pesanan dari pelanggan akan muncul di sini</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($action === 'view' && $order_detail): ?>
            <!-- Order Detail -->
            <div class="detail-grid">
                <!-- Left Column -->
                <div>
                    <!-- Order Info -->
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <div class="card-header">
                            <h2 class="card-title">Informasi Pesanan</h2>
                        </div>
                        <div class="card-body">
                            <div class="detail-section">
                                <div class="detail-section-title">Detail Order</div>
                                <div class="detail-card">
                                    <div class="detail-row">
                                        <span class="detail-label">Order ID</span>
                                        <span class="detail-value">#<?php echo $order_detail['id']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Tanggal</span>
                                        <span class="detail-value"><?php echo formatTanggal($order_detail['created_at'], 'lengkap'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Status</span>
                                        <span class="detail-value"><span class="status-badge status-<?php echo $order_detail['status']; ?>"><?php echo $order_detail['status']; ?></span></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-section-title">Informasi Pelanggan</div>
                                <div class="detail-card">
                                    <div class="detail-row">
                                        <span class="detail-label">Nama</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($order_detail['nama_pemesan']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Telepon</span>
                                        <span class="detail-value"><?php echo $order_detail['no_telepon']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Alamat</span>
                                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($order_detail['alamat_kirim'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($order_detail['catatan'])): ?>
                            <div class="detail-section">
                                <div class="detail-section-title">Catatan</div>
                                <div class="detail-card">
                                    <p><?php echo nl2br(htmlspecialchars($order_detail['catatan'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Update Status -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Update Status</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="status-form">
                                <input type="hidden" name="order_id" value="<?php echo $order_detail['id']; ?>">
                                <select name="status" class="form-select">
                                    <option value="pending" <?php echo $order_detail['status'] === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="processing" <?php echo $order_detail['status'] === 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="shipped" <?php echo $order_detail['status'] === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="delivered" <?php echo $order_detail['status'] === 'delivered' ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="cancelled" <?php echo $order_detail['status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Items -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Item Pesanan</h2>
                        </div>
                        <div class="card-body">
                            <div class="items-list">
                                <?php foreach ($order_items as $item): ?>
                                <div class="item-row">
                                    <!-- ✅ FIXED: Using getImagePath() helper function -->
                                    <img src="<?php echo htmlspecialchars(getImagePath($item['gambar'])); ?>" 
                                         alt="<?php echo htmlspecialchars($item['nama_buah']); ?>" 
                                         class="item-img" 
                                         onerror="this.src='https://via.placeholder.com/60'">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['nama_buah']); ?></div>
                                        <div class="item-meta"><?php echo $item['jumlah_kg']; ?> kg × <?php echo formatRupiah($item['harga_kg']); ?>/kg</div>
                                    </div>
                                    <div class="item-price">
                                        <div class="item-total"><?php echo formatRupiah($item['subtotal']); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="detail-section" style="margin-top: 1.5rem; margin-bottom: 0;">
                                <div class="detail-card">
                                    <div class="detail-row">
                                        <span class="detail-label">Subtotal</span>
                                        <span class="detail-value"><?php echo formatRupiah($order_detail['total_harga']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Ongkir</span>
                                        <span class="detail-value">Gratis</span>
                                    </div>
                                    <div class="detail-row" style="border-top: 2px solid var(--color-border); padding-top: 0.75rem; margin-top: 0.5rem;">
                                        <span class="detail-label">Total</span>
                                        <span class="detail-value large"><?php echo formatRupiah($order_detail['total_harga']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
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
        
        // Auto-hide alert
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>