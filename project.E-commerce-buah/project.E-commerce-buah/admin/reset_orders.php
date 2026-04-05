<?php
/**
 * ================================================
 * RESET ORDERS - BUAH SEGAR E-COMMERCE
 * ================================================
 * File: admin/reset_orders.php
 * Description: Reset semua data pesanan (admin only)
 * Method: POST
 * ================================================
 */

session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu'
    ]);
    exit();
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses ditolak - hanya admin yang bisa reset pesanan'
    ]);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    $pdo = getConnection();
    
    if (!$pdo) {
        throw new Exception('Koneksi database gagal');
    }
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Truncate tables
    $pdo->exec("TRUNCATE TABLE order_items");
    $pdo->exec("TRUNCATE TABLE orders");
    
    // Reset AUTO_INCREMENT
    $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo json_encode([
        'success' => true,
        'message' => 'Data pesanan berhasil direset!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal reset pesanan: ' . $e->getMessage()
    ]);
    
    error_log("Reset Orders Error: " . $e->getMessage());
}
?>