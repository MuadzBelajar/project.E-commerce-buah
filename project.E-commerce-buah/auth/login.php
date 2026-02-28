<?php
/**
 * ================================================
 * LOGIN PAGE - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: auth/login.php
 * Description: Halaman login untuk admin & customer
 * dengan authentication dan session management
 * PLAIN TEXT PASSWORD VERSION
 * ================================================
 */

// Start session
session_start();

// Redirect jika sudah login
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../pages/catalog.php");
    }
    exit();
}

// Include database connection
require_once '../config/database.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validation
    if (empty($username)) {
        $error_message = 'Username atau email tidak boleh kosong';
    } elseif (empty($password)) {
        $error_message = 'Password tidak boleh kosong';
    } else {
        try {
            // Query user from database
            $sql = "SELECT * FROM users 
                    WHERE (username = ? OR email = ?) 
                    AND status = 'active' 
                    LIMIT 1";
            
            $user = fetchOne($sql, [$username, $username]);
            
            // Check if user exists
            if (!$user) {
                $error_message = 'Username atau email tidak ditemukan';
            } 
            // Verify password (PLAIN TEXT - NO HASH)
            elseif ($password !== $user['password']) {
                $error_message = 'Password salah';
            } 
            // Login success!
            else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Remember me (optional - untuk future development)
                if ($remember) {
                    // Set cookie untuk remember me
                    // Implementasi bisa ditambahkan nanti
                }
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../pages/catalog.php");
                }
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Buah Segar</title>
    
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
            --color-background: #FFFFFF;
            --color-border: #E5E5E5;
            --color-error: #DC2626;
            --color-success: #16A34A;
            --font-display: 'Playfair Display', serif;
            --font-body: 'DM Sans', sans-serif;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --transition: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-body); background: linear-gradient(135deg, #FAFAFA 0%, var(--color-primary-light) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-container { width: 100%; max-width: 420px; animation: fadeInUp 0.6s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .logo-icon { font-size: 3rem; margin-bottom: 0.5rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .logo-text { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--color-primary); }
        .logo-tagline { font-size: 0.875rem; color: var(--color-text-light); margin-top: 0.25rem; }
        .login-card { background: white; border-radius: 20px; padding: 2.5rem; box-shadow: var(--shadow-lg); }
        .login-title { font-family: var(--font-display); font-size: 1.75rem; font-weight: 700; color: var(--color-text); text-align: center; margin-bottom: 0.5rem; }
        .login-subtitle { text-align: center; color: var(--color-text-light); font-size: 0.9375rem; margin-bottom: 2rem; }
        .alert { padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-error { background: #FEE2E2; color: var(--color-error); border: 1px solid #FCA5A5; }
        .alert-success { background: #D1FAE5; color: var(--color-success); border: 1px solid #86EFAC; }
        .alert-icon { font-size: 1.25rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--color-text); margin-bottom: 0.5rem; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--color-text-light); font-size: 1.125rem; }
        .form-input { width: 100%; padding: 0.875rem 1rem 0.875rem 3rem; border: 2px solid var(--color-border); border-radius: 12px; font-size: 1rem; font-family: var(--font-body); transition: var(--transition); background: white; }
        .form-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1); }
        .form-input.error { border-color: var(--color-error); }
        .password-toggle { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--color-text-light); cursor: pointer; font-size: 1.125rem; padding: 0.25rem; transition: var(--transition); }
        .password-toggle:hover { color: var(--color-primary); }
        .checkbox-wrapper { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .form-checkbox { width: 1.125rem; height: 1.125rem; border: 2px solid var(--color-border); border-radius: 4px; cursor: pointer; accent-color: var(--color-primary); }
        .checkbox-label { font-size: 0.875rem; color: var(--color-text-light); cursor: pointer; }
        .btn { width: 100%; padding: 1rem; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; font-family: var(--font-body); cursor: pointer; transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .btn-primary { background: var(--color-primary); color: white; }
        .btn-primary:hover:not(:disabled) { background: var(--color-primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-primary:active:not(:disabled) { transform: translateY(0); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .spinner { width: 18px; height: 18px; border: 2px solid rgba(255, 255, 255, 0.3); border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite; display: none; }
        .btn-primary.loading .spinner { display: block; }
        .btn-primary.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .login-footer { margin-top: 1.5rem; text-align: center; }
        .footer-text { font-size: 0.875rem; color: var(--color-text-light); }
        .footer-link { color: var(--color-primary); font-weight: 600; text-decoration: none; transition: var(--transition); }
        .footer-link:hover { color: var(--color-primary-dark); text-decoration: underline; }
        .divider { margin: 1.5rem 0; text-align: center; position: relative; }
        .divider::before { content: ''; position: absolute; left: 0; top: 50%; width: 100%; height: 1px; background: var(--color-border); }
        .divider-text { position: relative; background: white; padding: 0 1rem; font-size: 0.875rem; color: var(--color-text-light); }
        .back-home { text-align: center; margin-top: 2rem; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--color-text-light); font-size: 0.875rem; text-decoration: none; transition: var(--transition); }
        .back-link:hover { color: var(--color-primary); }
        .demo-box { background: var(--color-primary-light); border: 1px solid var(--color-primary); border-radius: 12px; padding: 1rem; margin-top: 1.5rem; }
        .demo-title { font-size: 0.875rem; font-weight: 600; color: var(--color-primary-dark); margin-bottom: 0.5rem; }
        .demo-item { font-size: 0.8125rem; color: var(--color-text); margin-bottom: 0.25rem; font-family: 'Courier New', monospace; }
        @media (max-width: 480px) {
            body { padding: 1rem; }
            .login-card { padding: 2rem 1.5rem; }
            .login-title { font-size: 1.5rem; }
            .logo-text { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="logo-icon">🍎</div>
            <div class="logo-text">Buah Segar</div>
            <div class="logo-tagline">Kesegaran Alami Setiap Hari</div>
        </div>
        <div class="login-card">
            <h1 class="login-title">Selamat Datang Kembali</h1>
            <p class="login-subtitle">Silakan masuk untuk melanjutkan</p>
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username atau Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username atau email" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle" id="togglePassword">👁️</button>
                    </div>
                </div>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remember" name="remember" class="form-checkbox">
                    <label for="remember" class="checkbox-label">Ingat saya</label>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Masuk</span>
                </button>
            </form>
            
        <div class="back-home">
            <a href="../index.php" class="back-link">← Kembali ke Beranda</a>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });
        }
        const errorAlert = document.querySelector('.alert-error');
        if (errorAlert) {
            const lastInput = passwordInput.value ? passwordInput : document.getElementById('username');
            if (lastInput) lastInput.focus();
            const card = document.querySelector('.login-card');
            card.style.animation = 'shake 0.5s ease-in-out';
        }
        const style = document.createElement('style');
        style.textContent = `@keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); } 20%, 40%, 60%, 80% { transform: translateX(5px); } }`;
        document.head.appendChild(style);
    </script>
</body>
</html>