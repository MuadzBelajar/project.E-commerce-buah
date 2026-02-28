<?php
/**
 * API: Remove Cart Item
 */
session_start();
require_once '../auth/check_session.php';
require_once '../includes/functions.php';

checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
}

// Hitung ulang total
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['harga_kg'] * $item['qty'];
}

echo json_encode([
    'success' => true,
    'message' => 'Item dihapus dari keranjang',
    'data'    => [
        'cart_count' => count($_SESSION['cart']),
        'cart_total' => $cart_total,
    ]
]);