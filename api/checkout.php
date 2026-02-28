<?php
/**
 * API: Checkout
 * - Terima nama_pemesan, no_telepon, alamat_kirim dari form
 * - Buat order di DB, kurangi stok, set inactive jika 0
 * - Kosongkan session cart
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

if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}

// Ambil & validasi input form
$nama_pemesan = trim($_POST['nama_pemesan'] ?? '');
$no_telepon   = trim($_POST['no_telepon']   ?? '');
$alamat_kirim = trim($_POST['alamat_kirim'] ?? '');

if (empty($nama_pemesan)) {
    echo json_encode(['success' => false, 'message' => 'Nama penerima harus diisi']);
    exit;
}
if (empty($no_telepon)) {
    echo json_encode(['success' => false, 'message' => 'Nomor telepon harus diisi']);
    exit;
}
if (empty($alamat_kirim)) {
    echo json_encode(['success' => false, 'message' => 'Alamat pengiriman harus diisi']);
    exit;
}

$user = getLoggedInUser();

try {
    // 1. Validasi stok semua item
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $product = fetchOne("SELECT stok_kg, nama_buah, status FROM buah WHERE id = ?", [$product_id]);
        if (!$product || $product['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => $item['nama_buah'] . ' sudah tidak tersedia']);
            exit;
        }
        if ($item['qty'] > floatval($product['stok_kg'])) {
            echo json_encode(['success' => false, 'message' => 'Stok ' . $item['nama_buah'] . ' tidak mencukupi (sisa ' . $product['stok_kg'] . ' kg)']);
            exit;
        }
    }

    // 2. Hitung total
    $total_harga = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += $item['harga_kg'] * $item['qty'];
    }

    // 3. Insert orders — gunakan data dari form
    execute(
        "INSERT INTO orders (user_id, nama_pemesan, no_telepon, alamat_kirim, total_harga, status, created_at)
         VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
        [$user['id'], $nama_pemesan, $no_telepon, $alamat_kirim, $total_harga]
    );

    $order_id = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];

    // 4. Insert order_items + kurangi stok
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $subtotal = $item['harga_kg'] * $item['qty'];

        execute(
            "INSERT INTO order_items (order_id, buah_id, nama_buah, jumlah_kg, harga_kg, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$order_id, $product_id, $item['nama_buah'], $item['qty'], $item['harga_kg'], $subtotal]
        );

        execute("UPDATE buah SET stok_kg = stok_kg - ? WHERE id = ?", [$item['qty'], $product_id]);

        // Stok habis → inactive
        $updated = fetchOne("SELECT stok_kg FROM buah WHERE id = ?", [$product_id]);
        if ($updated && floatval($updated['stok_kg']) <= 0) {
            execute("UPDATE buah SET stok_kg = 0, status = 'inactive' WHERE id = ?", [$product_id]);
        }
    }

    // 5. Kosongkan cart
    $_SESSION['cart'] = [];

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat!',
        'data'    => ['order_id' => $order_id]
    ]);

} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.']);
}