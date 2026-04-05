<?php
/**
 * ================================================
 * SESSION MIDDLEWARE - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: auth/check_session.php
 * Description: Middleware untuk session management,
 *              authentication check, dan role-based access control
 * 
 * Usage:
 * require_once '../auth/check_session.php';
 * checkLogin(); // Proteksi halaman (require login)
 * requireAdmin(); // Proteksi admin only
 * ================================================
 */

// Prevent direct access
if (!defined('SESSION_MIDDLEWARE_LOADED')) {
    define('SESSION_MIDDLEWARE_LOADED', true);
}

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ================================================
 * CONFIGURATION
 * ================================================
 */

// Session timeout (dalam detik) - 2 jam = 7200 detik
define('SESSION_TIMEOUT', 7200);

// Default redirect URLs
define('LOGIN_URL', '../auth/login.php');
define('HOME_URL', '../index.php');
define('ADMIN_DASHBOARD_URL', '../admin/dashboard.php');
define('CUSTOMER_CATALOG_URL', '../pages/catalog.php');

/**
 * ================================================
 * FUNCTION: isLoggedIn()
 * ================================================
 * Check apakah user sudah login (tanpa redirect)
 * 
 * @return bool True jika sudah login, false jika belum
 * 
 * Example:
 * if (isLoggedIn()) {
 *     echo "Welcome, " . $_SESSION['nama_lengkap'];
 * } else {
 *     echo "Please login";
 * }
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * ================================================
 * FUNCTION: checkLogin()
 * ================================================
 * Check apakah user sudah login, redirect ke login jika belum
 * 
 * @param string $redirect_url URL untuk redirect jika belum login (default: LOGIN_URL)
 * @return void
 * 
 * Example:
 * checkLogin(); // Redirect ke login.php jika belum login
 */
function checkLogin($redirect_url = LOGIN_URL) {
    if (!isLoggedIn()) {
        // Set flash message
        $_SESSION['flash_message'] = 'Silakan login terlebih dahulu';
        $_SESSION['flash_type'] = 'warning';
        
        // Save current URL untuk redirect setelah login (optional)
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect ke login page
        header("Location: " . $redirect_url);
        exit();
    }
    
    // Check session timeout
    checkSessionTimeout();
}

/**
 * ================================================
 * FUNCTION: checkRole()
 * ================================================
 * Check apakah user punya role tertentu
 * 
 * @param string $required_role Role yang dibutuhkan ('admin' atau 'customer')
 * @param string $redirect_url URL redirect jika role tidak sesuai
 * @return void
 * 
 * Example:
 * checkRole('admin'); // Hanya admin yang bisa akses
 */
function checkRole($required_role, $redirect_url = null) {
    // Pastikan user sudah login dulu
    checkLogin();
    
    // Get user role dari session
    $user_role = $_SESSION['role'] ?? '';
    
    // Check role
    if ($user_role !== $required_role) {
        // Set flash message
        $_SESSION['flash_message'] = 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.';
        $_SESSION['flash_type'] = 'error';
        
        // Redirect berdasarkan role user
        if ($redirect_url === null) {
            if ($user_role === 'admin') {
                $redirect_url = ADMIN_DASHBOARD_URL;
            } else {
                $redirect_url = CUSTOMER_CATALOG_URL;
            }
        }
        
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * ================================================
 * FUNCTION: requireAdmin()
 * ================================================
 * Shortcut untuk check admin role
 * Hanya admin yang bisa akses halaman ini
 * 
 * @return void
 * 
 * Example:
 * requireAdmin(); // Di awal file admin pages
 */
function requireAdmin() {
    checkRole('admin');
}

/**
 * ================================================
 * FUNCTION: requireCustomer()
 * ================================================
 * Shortcut untuk check customer role
 * Hanya customer yang bisa akses halaman ini
 * 
 * @return void
 * 
 * Example:
 * requireCustomer(); // Di awal file customer pages
 */
function requireCustomer() {
    checkRole('customer');
}

/**
 * ================================================
 * FUNCTION: getLoggedInUser()
 * ================================================
 * Get data user yang sedang login dari session
 * 
 * @return array|null Array berisi data user, atau null jika belum login
 * 
 * Example:
 * $user = getLoggedInUser();
 * echo "Welcome, " . $user['nama_lengkap'];
 */
function getLoggedInUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'login_time' => $_SESSION['login_time'] ?? null,
    ];
}

/**
 * ================================================
 * FUNCTION: getUserId()
 * ================================================
 * Get user ID dari session (shortcut)
 * 
 * @return int|null User ID atau null jika belum login
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * ================================================
 * FUNCTION: getUserRole()
 * ================================================
 * Get user role dari session (shortcut)
 * 
 * @return string|null 'admin', 'customer', atau null
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * ================================================
 * FUNCTION: isAdmin()
 * ================================================
 * Check apakah user adalah admin (tanpa redirect)
 * 
 * @return bool True jika admin, false jika bukan
 */
function isAdmin() {
    return isLoggedIn() && getUserRole() === 'admin';
}

/**
 * ================================================
 * FUNCTION: isCustomer()
 * ================================================
 * Check apakah user adalah customer (tanpa redirect)
 * 
 * @return bool True jika customer, false jika bukan
 */
function isCustomer() {
    return isLoggedIn() && getUserRole() === 'customer';
}

/**
 * ================================================
 * FUNCTION: checkSessionTimeout()
 * ================================================
 * Check apakah session sudah timeout (2 jam tidak aktif)
 * Auto logout jika timeout
 * 
 * @return void
 */
function checkSessionTimeout() {
    if (!isLoggedIn()) {
        return;
    }
    
    $login_time = $_SESSION['login_time'] ?? time();
    $current_time = time();
    $elapsed_time = $current_time - $login_time;
    
    // Check timeout (default: 2 jam)
    if ($elapsed_time > SESSION_TIMEOUT) {
        // Session expired, logout
        logoutUser('Session Anda telah berakhir. Silakan login kembali.');
    }
    
    // Update login time (reset timer saat ada activity)
    $_SESSION['login_time'] = time();
}

/**
 * ================================================
 * FUNCTION: logoutUser()
 * ================================================
 * Logout user dan destroy session
 * 
 * @param string $message Flash message untuk ditampilkan
 * @return void
 */
function logoutUser($message = 'Berhasil logout') {
    // Set flash message
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = 'info';
    
    // Destroy session
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    // Redirect ke login
    header("Location: " . LOGIN_URL);
    exit();
}

/**
 * ================================================
 * FUNCTION: redirectBasedOnRole()
 * ================================================
 * Redirect user berdasarkan role mereka
 * 
 * @return void
 */
function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        header("Location: " . LOGIN_URL);
        exit();
    }
    
    $role = getUserRole();
    
    if ($role === 'admin') {
        header("Location: " . ADMIN_DASHBOARD_URL);
    } else {
        header("Location: " . CUSTOMER_CATALOG_URL);
    }
    exit();
}

/**
 * ================================================
 * FUNCTION: preventLoggedInAccess()
 * ================================================
 * Prevent user yang sudah login untuk akses halaman tertentu
 * (misalnya: login page, register page)
 * 
 * @return void
 * 
 * Example:
 * // Di login.php
 * preventLoggedInAccess(); // Redirect jika sudah login
 */
function preventLoggedInAccess() {
    if (isLoggedIn()) {
        redirectBasedOnRole();
    }
}

/**
 * ================================================
 * FUNCTION: setFlashMessage()
 * ================================================
 * Set flash message untuk ditampilkan di halaman berikutnya
 * 
 * @param string $message Pesan yang akan ditampilkan
 * @param string $type Tipe pesan: 'success', 'error', 'warning', 'info'
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * ================================================
 * FUNCTION: getFlashMessage()
 * ================================================
 * Get dan hapus flash message dari session
 * 
 * @return array|null ['message' => '...', 'type' => '...'] atau null
 */
function getFlashMessage() {
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    
    $flash = [
        'message' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type'] ?? 'info'
    ];
    
    // Hapus dari session setelah diambil
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    
    return $flash;
}

/**
 * ================================================
 * FUNCTION: displayFlashMessage()
 * ================================================
 * Display flash message HTML (jika ada)
 * 
 * @return void
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash === null) {
        return;
    }
    
    $type_classes = [
        'success' => 'flash-success',
        'error' => 'flash-error',
        'warning' => 'flash-warning',
        'info' => 'flash-info'
    ];
    
    $class = $type_classes[$flash['type']] ?? 'flash-info';
    
    echo '<div class="flash-message ' . $class . '">';
    echo htmlspecialchars($flash['message']);
    echo '</div>';
}

/**
 * ================================================
 * USAGE EXAMPLES
 * ================================================
 * 
 * // Example 1: Proteksi halaman (require login)
 * require_once '../auth/check_session.php';
 * checkLogin(); // User harus login
 * 
 * // Example 2: Admin only page
 * require_once '../auth/check_session.php';
 * requireAdmin(); // Hanya admin yang bisa akses
 * 
 * // Example 3: Customer only page
 * require_once '../auth/check_session.php';
 * requireCustomer(); // Hanya customer yang bisa akses
 * 
 * // Example 4: Conditional display di navbar
 * if (isLoggedIn()) {
 *     echo "Logout";
 * } else {
 *     echo "Login";
 * }
 * 
 * // Example 5: Get user data
 * $user = getLoggedInUser();
 * echo "Welcome, " . $user['nama_lengkap'];
 * 
 * // Example 6: Display flash message
 * displayFlashMessage();
 * 
 * ================================================
 */

// End of file
?>