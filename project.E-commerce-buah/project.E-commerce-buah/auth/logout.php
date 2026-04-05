<?php
/**
 * ================================================
 * LOGOUT HANDLER - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: auth/logout.php
 * Description: Destroy session dan redirect ke halaman home
 * dengan flash message "Berhasil logout"
 * ================================================
 */

// Start session
session_start();

// Simpan info untuk flash message (optional)
$was_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$username = $_SESSION['username'] ?? 'User';

// Unset semua session variables
$_SESSION = array();

// Destroy session cookie jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Start new session untuk flash message
session_start();

// Set flash message
if ($was_logged_in) {
    $_SESSION['flash_message'] = 'Berhasil logout. Sampai jumpa lagi!';
    $_SESSION['flash_type'] = 'success';
}

// Redirect ke halaman home
header("Location: ../index.php");
exit();

/**
 * ================================================
 * ALTERNATIVE: Logout dengan Confirmation Page
 * ================================================
 * Jika ingin tampilkan halaman konfirmasi logout,
 * uncomment code dibawah dan comment code diatas
 */

/*
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Buah Segar</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DM Sans', sans-serif; 
            background: linear-gradient(135deg, #FAFAFA 0%, #E8F5ED 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .logout-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            text-align: center;
            max-width: 400px;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .logout-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: wave 1s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
        .logout-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1A1A1A;
            margin-bottom: 0.5rem;
        }
        .logout-message {
            color: #666666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: #2D8659;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #1F5F3F;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(45, 134, 89, 0.3);
        }
        .redirect-info {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="logout-icon">👋</div>
        <h1 class="logout-title">Berhasil Logout</h1>
        <p class="logout-message">
            Terima kasih telah menggunakan Buah Segar.<br>
            Sampai jumpa lagi, <?php echo htmlspecialchars($username); ?>!
        </p>
        <a href="../index.php" class="btn">Kembali ke Beranda</a>
        <p class="redirect-info">Anda akan diarahkan otomatis dalam 3 detik...</p>
    </div>
    <script>
        // Auto redirect setelah 3 detik
        setTimeout(function() {
            window.location.href = '../index.php';
        }, 3000);
    </script>
</body>
</html>
*/
?>