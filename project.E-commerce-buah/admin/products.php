<?php
/**
 * ================================================
 * ADMIN PRODUCTS - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: admin/products.php
 * Updated: Fix image path synchronization + Database store filename only
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

// DELETE Product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    try {
        // Get product image first
        $product = fetchOne("SELECT gambar FROM buah WHERE id = ?", [$product_id]);
        
        // Delete from database
        execute("DELETE FROM buah WHERE id = ?", [$product_id]);
        
        // Delete image file if exists and not URL
        if ($product && !empty($product['gambar']) && !filter_var($product['gambar'], FILTER_VALIDATE_URL)) {
            // Extract just filename (in case stored with path)
            $filename = basename($product['gambar']);
            deleteImage($filename, '../assets/images/products/');
        }
        
        $message = 'Produk berhasil dihapus!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Gagal menghapus produk: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// ADD/EDIT Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $nama_buah = trim($_POST['nama_buah'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga_kg = floatval($_POST['harga_kg'] ?? 0);
    $stok_kg = floatval($_POST['stok_kg'] ?? 0);
    $asal = trim($_POST['asal'] ?? '');
    $kategori = $_POST['kategori'] ?? 'lokal';
    $status = $_POST['status'] ?? 'active';
    $keep_old_image = isset($_POST['keep_old_image']) ? $_POST['keep_old_image'] : '';
    
    // Validation
    $errors = [];
    if (empty($nama_buah)) $errors[] = 'Nama buah harus diisi';
    if ($harga_kg <= 0) $errors[] = 'Harga harus lebih dari 0';
    if ($stok_kg < 0) $errors[] = 'Stok tidak boleh negatif';
    
    // Handle image upload
    // Default: keep old image (extract just filename if it has path)
    $gambar = $keep_old_image ? basename($keep_old_image) : '';
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_result = uploadImage($_FILES['gambar'], '../assets/images/products/');
        
        if ($upload_result['success']) {
            // Delete old image if exists and not URL
            if ($product_id && !empty($keep_old_image) && !filter_var($keep_old_image, FILTER_VALIDATE_URL)) {
                // Extract filename from path if necessary
                $old_filename = basename($keep_old_image);
                deleteImage($old_filename, '../assets/images/products/');
            }
            
            // Simpan HANYA filename ke database, bukan full path!
            $gambar = $upload_result['filename']; // e.g., "mangga_1234567890.jpg"
        } else {
            $errors[] = $upload_result['error'];
        }
    }
    
    if (empty($errors)) {
        try {
            $slug = generateSlug($nama_buah);
            
            if ($product_id) {
                // UPDATE
                execute("UPDATE buah SET 
                    nama_buah = ?, slug = ?, deskripsi = ?, harga_kg = ?, 
                    stok_kg = ?, gambar = ?, asal = ?, kategori = ?, status = ?
                    WHERE id = ?",
                    [$nama_buah, $slug, $deskripsi, $harga_kg, $stok_kg, $gambar, $asal, $kategori, $status, $product_id]
                );
                $message = 'Produk berhasil diupdate!';
            } else {
                // INSERT
                execute("INSERT INTO buah (nama_buah, slug, deskripsi, harga_kg, stok_kg, gambar, asal, kategori, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$nama_buah, $slug, $deskripsi, $harga_kg, $stok_kg, $gambar, $asal, $kategori, $status]
                );
                $message = 'Produk berhasil ditambahkan!';
            }
            $message_type = 'success';
            $action = 'list';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Get product for edit
$edit_product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_product = fetchOne("SELECT * FROM buah WHERE id = ?", [intval($_GET['id'])]);
    if (!$edit_product) {
        $action = 'list';
        $message = 'Produk tidak ditemukan!';
        $message_type = 'error';
    }
}

// Fetch all products
$products = fetchAll("SELECT * FROM buah ORDER BY created_at DESC");

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
    <title>Kelola Produk - Buah Segar Admin</title>
    
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
        
        /* MOBILE MENU */
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
        
        /* CARD */
        .card { background: var(--color-white); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); overflow: hidden; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--color-text); }
        .card-body { padding: 1.5rem; }
        
        /* TABLE */
        .table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .data-table th { text-align: left; padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--color-text-lighter); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
        .data-table td { padding: 1rem; border-bottom: 1px solid var(--color-border); font-size: 0.875rem; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background: var(--color-background); }
        .product-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; background: var(--color-background); }
        .product-name { font-weight: 600; color: var(--color-text); }
        .product-desc { font-size: 0.75rem; color: var(--color-text-lighter); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .price { font-weight: 600; color: var(--color-primary); white-space: nowrap; }
        .stock { font-weight: 600; }
        .stock.low { color: var(--color-error); }
        .stock.medium { color: var(--color-warning); }
        .stock.good { color: var(--color-success); }
        .badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
        .badge-lokal { background: #D1FAE5; color: var(--color-success); }
        .badge-impor { background: #DBEAFE; color: var(--color-primary); }
        .badge-active { background: #D1FAE5; color: var(--color-success); }
        .badge-inactive { background: #E5E7EB; color: var(--color-text-lighter); }
        .actions { display: flex; gap: 0.5rem; }
        .action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: var(--transition); flex-shrink: 0; }
        .action-btn.edit { background: #DBEAFE; color: var(--color-primary); }
        .action-btn.edit:hover { background: var(--color-primary); color: white; }
        .action-btn.delete { background: #FEE2E2; color: var(--color-error); }
        .action-btn.delete:hover { background: var(--color-error); color: white; }
        
        /* FORM */
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--color-text); margin-bottom: 0.5rem; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--color-border); border-radius: 10px; font-size: 0.875rem; font-family: var(--font-body); transition: var(--transition); }
        .form-input:focus, .form-textarea:focus, .form-select:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .form-hint { font-size: 0.75rem; color: var(--color-text-lighter); margin-top: 0.25rem; }
        .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); flex-wrap: wrap; }
        
        /* IMAGE UPLOAD */
        .image-upload-area { border: 2px dashed var(--color-border); border-radius: 10px; padding: 2rem; text-align: center; background: var(--color-background); cursor: pointer; transition: var(--transition); }
        .image-upload-area:hover { border-color: var(--color-primary); background: var(--color-primary-light); }
        .image-upload-area.dragover { border-color: var(--color-primary); background: var(--color-primary-light); }
        .upload-icon { font-size: 3rem; margin-bottom: 1rem; }
        .upload-text { font-weight: 600; margin-bottom: 0.5rem; }
        .upload-hint { font-size: 0.75rem; color: var(--color-text-lighter); }
        .image-preview { margin-top: 1rem; position: relative; display: inline-block; }
        .image-preview img { max-width: 200px; max-height: 200px; border-radius: 10px; }
        .image-preview-close { position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: var(--color-error); color: white; border-radius: 50%; border: none; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; }
        
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 3rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .empty-text { color: var(--color-text-light); margin-bottom: 1.5rem; }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        
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
            .card-body { padding: 1rem; }
            .form-actions { flex-direction: column-reverse; }
            .form-actions .btn { width: 100%; }
            .data-table th, .data-table td { padding: 0.75rem 0.5rem; font-size: 0.8125rem; }
            .product-img { width: 50px; height: 50px; }
        }
        
        @media (max-width: 480px) {
            .main-content { padding: 0.5rem; }
            .page-title { font-size: 1.25rem; }
            .card { border-radius: 12px; }
            .upload-icon { font-size: 2rem; }
            .image-preview img { max-width: 150px; max-height: 150px; }
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
                    <a href="products.php" class="nav-item active">
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
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span><?php echo $message; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        <?php if ($action === 'add'): ?>Tambah Produk<?php elseif ($action === 'edit'): ?>Edit Produk<?php else: ?>Kelola Produk<?php endif; ?>
                    </h1>
                </div>
                <div class="page-actions">
                    <?php if ($action !== 'list'): ?>
                    <a href="products.php" class="btn btn-secondary">← Kembali</a>
                    <?php else: ?>
                    <a href="products.php?action=add" class="btn btn-primary">+ Tambah Produk</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($action === 'list'): ?>
            <!-- Product List -->
            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <?php if (count($products) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>Nama Produk</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                    $stock_class = $product['stok_kg'] <= 10 ? 'low' : ($product['stok_kg'] <= 20 ? 'medium' : 'good');
                                ?>
                                <tr>
                                    <td>
                                        <!-- ✅ FIXED: Using getImagePath() with absolute path -->
                                        <img src="<?php echo htmlspecialchars(getImagePath($product['gambar'])); ?>" 
                                             alt="<?php echo htmlspecialchars($product['nama_buah']); ?>" 
                                             class="product-img"
                                             onerror="this.src='https://via.placeholder.com/60'">
                                    </td>
                                    <td>
                                        <div class="product-name"><?php echo htmlspecialchars($product['nama_buah']); ?></div>
                                        <div class="product-desc"><?php echo htmlspecialchars(substr($product['deskripsi'] ?? '', 0, 50)) . '...'; ?></div>
                                    </td>
                                    <td class="price"><?php echo formatRupiah($product['harga_kg']); ?>/kg</td>
                                    <td class="stock <?php echo $stock_class; ?>"><?php echo $product['stok_kg']; ?> kg</td>
                                    <td><span class="badge badge-<?php echo $product['kategori']; ?>"><?php echo ucfirst($product['kategori']); ?></span></td>
                                    <td><span class="badge badge-<?php echo $product['status']; ?>"><?php echo $product['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="action-btn edit" title="Edit">✏️</a>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="action-btn delete" title="Hapus" onclick="return confirm('Yakin ingin menghapus produk ini?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3 class="empty-title">Belum Ada Produk</h3>
                        <p class="empty-text">Mulai tambahkan produk buah Anda</p>
                        <a href="products.php?action=add" class="btn btn-primary">Tambah Produk</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" id="productForm">
                        <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                        <!-- Simpan full value untuk referensi, nanti di-process pakai basename() -->
                        <input type="hidden" name="keep_old_image" value="<?php echo htmlspecialchars($edit_product['gambar']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Buah *</label>
                                <input type="text" name="nama_buah" class="form-input" 
                                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['nama_buah']) : ''; ?>" 
                                       placeholder="Contoh: Mangga Harum Manis" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Harga per kg (Rp) *</label>
                                <input type="number" name="harga_kg" class="form-input" 
                                       value="<?php echo $edit_product ? $edit_product['harga_kg'] : ''; ?>" 
                                       placeholder="22000" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Stok (kg) *</label>
                                <input type="number" name="stok_kg" class="form-input" 
                                       value="<?php echo $edit_product ? $edit_product['stok_kg'] : ''; ?>" 
                                       placeholder="50" min="0" step="0.1" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Asal Daerah</label>
                                <input type="text" name="asal" class="form-input" 
                                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['asal']) : ''; ?>" 
                                       placeholder="Contoh: Probolinggo">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select">
                                    <option value="lokal" <?php echo ($edit_product && $edit_product['kategori'] === 'lokal') ? 'selected' : ''; ?>>Lokal</option>
                                    <option value="impor" <?php echo ($edit_product && $edit_product['kategori'] === 'impor') ? 'selected' : ''; ?>>Impor</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo ($edit_product && $edit_product['status'] === 'active') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="inactive" <?php echo ($edit_product && $edit_product['status'] === 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>
                            
                            <div class="form-group full">
                                <label class="form-label">Gambar Produk</label>
                                <input type="file" name="gambar" id="imageInput" accept="image/*" style="display: none;">
                                <div class="image-upload-area" id="uploadArea">
                                    <div class="upload-icon">📸</div>
                                    <div class="upload-text">Klik atau drag & drop untuk upload gambar</div>
                                    <div class="upload-hint">Format: JPG, PNG, GIF, WEBP (Max 2MB)</div>
                                </div>
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img id="previewImg" src="" alt="Preview">
                                    <button type="button" class="image-preview-close" id="removeImage">×</button>
                                </div>
                                <?php if ($edit_product && !empty($edit_product['gambar'])): ?>
                                <div class="image-preview" style="margin-top: 1rem;">
                                    <!-- ✅ FIXED: Using getImagePath() -->
                                    <img src="<?php echo htmlspecialchars(getImagePath($edit_product['gambar'])); ?>" alt="Current" style="max-width: 200px; border-radius: 10px;">
                                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--color-text-lighter);">Gambar saat ini (akan diganti jika upload baru)</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group full">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-textarea" placeholder="Deskripsi produk..."><?php echo $edit_product ? htmlspecialchars($edit_product['deskripsi']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="products.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_product ? '💾 Simpan Perubahan' : '➕ Tambah Produk'; ?>
                            </button>
                        </div>
                    </form>
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
        
        // Image upload handling
        const imageInput = document.getElementById('imageInput');
        const uploadArea = document.getElementById('uploadArea');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeImage = document.getElementById('removeImage');
        
        if (uploadArea && imageInput) {
            // Click to upload
            uploadArea.addEventListener('click', () => imageInput.click());
            
            // File input change
            imageInput.addEventListener('change', function(e) {
                handleFile(e.target.files[0]);
            });
            
            // Drag & drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    imageInput.files = e.dataTransfer.files;
                    handleFile(file);
                }
            });
            
            // Remove image
            if (removeImage) {
                removeImage.addEventListener('click', function() {
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    uploadArea.style.display = 'block';
                });
            }
            
            // Handle file preview
            function handleFile(file) {
                if (!file) return;
                
                // Check file type
                if (!file.type.startsWith('image/')) {
                    alert('File harus berupa gambar!');
                    return;
                }
                
                // Check file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file maksimal 2MB!');
                    imageInput.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
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