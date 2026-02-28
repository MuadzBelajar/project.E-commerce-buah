<?php
/**
 * ================================================
 * HELPER FUNCTIONS - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: includes/functions.php
 * Description: Collection of reusable utility functions
 *              untuk formatting, validation, dan helper
 * ================================================
 */

// Prevent direct access
if (!defined('FUNCTIONS_LOADED')) {
    define('FUNCTIONS_LOADED', true);
}

/**
 * ================================================
 * FORMATTING FUNCTIONS
 * ================================================
 */

/**
 * Format angka menjadi format Rupiah
 * 
 * @param float|int $angka Angka yang akan diformat
 * @param bool $with_prefix Tampilkan prefix "Rp" atau tidak (default: true)
 * @return string Formatted rupiah string
 * 
 * Example:
 * formatRupiah(25000) → "Rp 25.000"
 * formatRupiah(25000, false) → "25.000"
 */
function formatRupiah($angka, $with_prefix = true) {
    $formatted = number_format($angka, 0, ',', '.');
    return $with_prefix ? "Rp " . $formatted : $formatted;
}

/**
 * Format tanggal Indonesia
 * 
 * @param string $date Date string atau timestamp
 * @param string $format Format output (default: 'lengkap')
 *                       - 'lengkap': "Senin, 17 Februari 2026"
 *                       - 'pendek': "17 Feb 2026"
 *                       - 'tanggal': "17/02/2026"
 * @return string Formatted date
 */
function formatTanggal($date, $format = 'lengkap') {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $bulan = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    $bulan_pendek = [
        'January' => 'Jan',
        'February' => 'Feb',
        'March' => 'Mar',
        'April' => 'Apr',
        'May' => 'Mei',
        'June' => 'Jun',
        'July' => 'Jul',
        'August' => 'Agt',
        'September' => 'Sep',
        'October' => 'Okt',
        'November' => 'Nov',
        'December' => 'Des'
    ];
    
    switch ($format) {
        case 'lengkap':
            $hari_nama = $hari[date('l', $timestamp)];
            $bulan_nama = $bulan[date('F', $timestamp)];
            return $hari_nama . ', ' . date('j', $timestamp) . ' ' . $bulan_nama . ' ' . date('Y', $timestamp);
            
        case 'pendek':
            $bulan_nama = $bulan_pendek[date('F', $timestamp)];
            return date('j', $timestamp) . ' ' . $bulan_nama . ' ' . date('Y', $timestamp);
            
        case 'tanggal':
            return date('d/m/Y', $timestamp);
            
        default:
            return date('Y-m-d', $timestamp);
    }
}

/**
 * Format waktu relatif (time ago)
 * 
 * @param string $datetime Datetime string
 * @return string Relative time (e.g., "2 jam yang lalu")
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return formatTanggal($timestamp, 'pendek');
    }
}

/**
 * Format berat (kilogram)
 * 
 * @param float $kg Berat dalam kilogram
 * @return string Formatted weight
 * 
 * Example:
 * formatBerat(1.5) → "1,5 kg"
 */
function formatBerat($kg) {
    return number_format($kg, 1, ',', '.') . ' kg';
}

/**
 * ================================================
 * INPUT SANITIZATION & VALIDATION
 * ================================================
 */

/**
 * Sanitize input string (clean dari XSS)
 * 
 * @param string $input Input string
 * @return string Cleaned string
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email
 * 
 * @param string $email Email address
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number Indonesia
 * 
 * @param string $phone Phone number
 * @return bool True if valid, false otherwise
 */
function isValidPhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check format: 08xx-xxxx-xxxx (10-13 digits)
    return preg_match('/^(08|62)\d{8,11}$/', $phone);
}

/**
 * Validate password strength
 * 
 * @param string $password Password
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password harus mengandung huruf besar';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password harus mengandung huruf kecil';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password harus mengandung angka';
    }
    
    return [
        'valid' => empty($errors),
        'message' => implode(', ', $errors)
    ];
}

/**
 * ================================================
 * FILE UPLOAD FUNCTIONS
 * ================================================
 */

/**
 * Upload gambar produk
 * 
 * @param array $file $_FILES array element
 * @param string $folder Target folder (default: '../assets/images/products/')
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadImage($file, $folder = '../assets/images/products/') {
    // Check if file exists
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'filename' => null, 'error' => 'Tidak ada file yang diupload'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Error saat upload file'];
    }
    
    // Validate file type (only images)
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'filename' => null, 'error' => 'File harus berupa gambar (JPG, PNG, GIF, WEBP)'];
    }
    
    // Validate file size (max 2MB)
    $max_size = 2 * 1024 * 1024; // 2MB in bytes
    if ($file['size'] > $max_size) {
        return ['success' => false, 'filename' => null, 'error' => 'Ukuran file maksimal 2MB'];
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Generate unique filename
    $new_filename = uniqid() . '-' . time() . '.' . $file_extension;
    
    // Full upload path
    $upload_path = $folder . $new_filename;
    
    // Create folder if not exists
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'error' => null];
    } else {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan file'];
    }
}

/**
 * Delete uploaded image
 * 
 * @param string $filename Filename to delete
 * @param string $folder Folder path
 * @return bool True if deleted, false otherwise
 */
function deleteImage($filename, $folder = '../assets/images/products/') {
    $filepath = $folder . $filename;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * ================================================
 * URL & REDIRECT FUNCTIONS
 * ================================================
 */

/**
 * Redirect dengan flash message
 * 
 * @param string $url Target URL
 * @param string $message Flash message
 * @param string $type Message type (success, error, warning, info)
 * @return void
 */
function redirectWithMessage($url, $message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    
    header("Location: " . $url);
    exit();
}

/**
 * Get current URL
 * 
 * @return string Current page URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Generate slug from string
 * 
 * @param string $string Input string
 * @return string URL-friendly slug
 */
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * ================================================
 * PAGINATION FUNCTIONS
 * ================================================
 */

/**
 * Calculate pagination
 * 
 * @param int $total_items Total number of items
 * @param int $items_per_page Items per page (default: 10)
 * @param int $current_page Current page number (default: 1)
 * @return array Pagination data
 */
function paginate($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1,
    ];
}

/**
 * ================================================
 * CART FUNCTIONS
 * ================================================
 */

/**
 * Get cart from session
 * 
 * @return array Cart items
 */
function getCart() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return $_SESSION['cart'] ?? [];
}

/**
 * Add item to cart (session-based)
 * 
 * @param int $product_id Product ID
 * @param float $quantity Quantity in kg
 * @return bool Success status
 */
function addToCart($product_id, $quantity) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already in cart
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // Add new item if not found
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];
    }
    
    return true;
}

/**
 * Update cart item quantity
 * 
 * @param int $product_id Product ID
 * @param float $quantity New quantity
 * @return bool Success status
 */
function updateCartQuantity($product_id, $quantity) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id) {
            if ($quantity <= 0) {
                // Remove if quantity is 0 or negative
                unset($_SESSION['cart'][$key]);
            } else {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
            }
            return true;
        }
    }
    
    return false;
}

/**
 * Remove item from cart
 * 
 * @param int $product_id Product ID
 * @return bool Success status
 */
function removeFromCart($product_id) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id) {
            unset($_SESSION['cart'][$key]);
            return true;
        }
    }
    
    return false;
}

/**
 * Clear entire cart
 * 
 * @return void
 */
function clearCart() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['cart'] = [];
}

/**
 * Get cart item count
 * 
 * @return int Total items in cart
 */
function getCartItemCount() {
    $cart = getCart();
    return count($cart);
}

/**
 * Get cart total weight (kg)
 * 
 * @return float Total weight in kg
 */
function getCartTotalWeight() {
    $cart = getCart();
    $total = 0;
    
    foreach ($cart as $item) {
        $total += $item['quantity'];
    }
    
    return $total;
}

/**
 * ================================================
 * ORDER FUNCTIONS
 * ================================================
 */

/**
 * Generate unique order number
 * 
 * @return string Order number format: ORD-YYYYMMDD-XXXXX
 */
function generateOrderNumber() {
    $date = date('Ymd');
    $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    return 'ORD-' . $date . '-' . $random;
}

/**
 * Get order status badge HTML
 * 
 * @param string $status Order status
 * @return string HTML badge
 */
function getOrderStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Menunggu</span>',
        'processing' => '<span class="badge badge-info">Diproses</span>',
        'completed' => '<span class="badge badge-success">Selesai</span>',
        'cancelled' => '<span class="badge badge-danger">Dibatalkan</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * ================================================
 * DEBUG FUNCTIONS
 * ================================================
 */

/**
 * Debug print (dengan styling)
 * 
 * @param mixed $data Data to debug
 * @param bool $die Stop execution after print (default: false)
 * @return void
 */
function dd($data, $die = false) {
    echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 1rem; border-radius: 8px; overflow: auto; font-family: monospace; font-size: 14px;">';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}



/**
 * ================================================
 * USAGE EXAMPLES
 * ================================================
 * 
 * // Format Rupiah
 * echo formatRupiah(25000); // "Rp 25.000"
 * 
 * // Format Tanggal
 * echo formatTanggal('2026-02-17', 'lengkap'); // "Senin, 17 Februari 2026"
 * 
 * // Sanitize Input
 * $clean = sanitizeInput($_POST['nama']);
 * 
 * // Upload Image
 * $result = uploadImage($_FILES['gambar']);
 * if ($result['success']) {
 *     echo "File: " . $result['filename'];
 * }
 * 
 * // Redirect with Message
 * redirectWithMessage('catalog.php', 'Produk berhasil ditambahkan!', 'success');
 * 
 * // Pagination
 * $page = paginate(100, 10, 2);
 * echo "Page {$page['current_page']} of {$page['total_pages']}";
 * 
 * // Cart Operations
 * addToCart(1, 2.5);
 * $cart_count = getCartItemCount();
 * 
 * ================================================
 */

// End of file
?>