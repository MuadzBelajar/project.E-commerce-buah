<?php
/**
 * API: Add to Cart
 * Session-based cart
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

if ($product_id <= 0 || $qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

// Ambil produk dari DB
$product = fetchOne("SELECT * FROM buah WHERE id = ? AND status = 'active'", [$product_id]);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan atau tidak aktif']);
    exit;
}

$stok = floatval($product['stok_kg']);

// Init cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek qty yang sudah ada di cart
$already_in_cart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['qty'] : 0;
$new_qty = $already_in_cart + $qty;

if ($new_qty > $stok) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi (tersisa ' . $stok . ' kg)']);
    exit;
}

// Simpan ke session
$_SESSION['cart'][$product_id] = [
    'product_id' => $product_id,
    'nama_buah'  => $product['nama_buah'],
    'harga_kg'   => floatval($product['harga_kg']),
    'qty'        => $new_qty,
    'gambar'     => $product['gambar'],
    'stok_max'   => $stok,
];

$cart_count = count($_SESSION['cart']);

echo json_encode([
    'success' => true,
    'message' => 'Produk ditambahkan ke keranjang',
    'data'    => ['cart_count' => $cart_count]
]);