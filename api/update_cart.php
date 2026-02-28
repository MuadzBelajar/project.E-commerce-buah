<?php
/**
 * API: Update Cart Item
 */
session_start();
require_once '../auth/check_session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$qty        = intval($_POST['quantity'] ?? 1);

if ($product_id <= 0 || $qty < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$product_id])) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ada di keranjang']);
    exit;
}

// Validasi stok terkini dari DB
$product = fetchOne("SELECT stok_kg, harga_kg FROM buah WHERE id = ? AND status = 'active'", [$product_id]);

if (!$product) {
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => false, 'message' => 'Produk sudah tidak tersedia']);
    exit;
}

if ($qty > floatval($product['stok_kg'])) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi (tersisa ' . $product['stok_kg'] . ' kg)']);
    exit;
}

// Update session
$_SESSION['cart'][$product_id]['qty']      = $qty;
$_SESSION['cart'][$product_id]['stok_max'] = floatval($product['stok_kg']);

// Hitung ulang total cart
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['harga_kg'] * $item['qty'];
}

$subtotal = $product['harga_kg'] * $qty;

echo json_encode([
    'success'    => true,
    'message'    => 'Keranjang diupdate',
    'data'       => [
        'subtotal'   => $subtotal,
        'cart_total' => $cart_total,
        'cart_count' => count($_SESSION['cart']),
    ]
]);