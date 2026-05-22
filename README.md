# 🍎 Buah Segar — E-Commerce Penjualan Buah Segar
Buah Segar adalah aplikasi web e-commerce berbasis **PHP Native** untuk penjualan buah segar secara online. Aplikasi ini menyediakan katalog produk dengan filter dan pencarian, sistem keranjang belanja berbasis database, proses checkout dengan manajemen stok otomatis, serta dashboard admin untuk mengelola produk dan pesanan secara real-time. Tujuan pembelajaran dari proyek ini adalah implementasi sistem web full-stack lengkap: mulai dari autentikasi berbasis peran, operasi CRUD yang aman, hingga penanganan transaksi database yang konsisten.

<img width="1366" height="687" alt="image" src="https://github.com/user-attachments/assets/ce302b28-ee1e-418f-8f18-d1d501ac0f7f" />

---

## 🛠️ Tech Stack

| Lapisan | Teknologi |
|---|---|
| **Backend** | PHP 8.x, PDO (Prepared Statements) |
| **Frontend** | HTML5 Semantik, CSS3 (Flexbox/Grid/Variables), Vanilla JavaScript (ES6) |
| **Database** | MySQL (dengan Foreign Key, View, dan Transaction) |
| **Tools** | XAMPP / Laragon, Git, VS Code |

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

Ikuti langkah berikut secara berurutan. Siapa pun yang belum pernah melihat kode ini sebelumnya dapat langsung menjalankannya.

### 1. Prasyarat Sistem

Pastikan perangkat Anda sudah terinstal:
- **XAMPP** atau **Laragon** (PHP 8.x + MySQL sudah tercakup)
- **Git**

### 2. Clone Repository

Buka terminal, masuk ke direktori `htdocs` (XAMPP) atau `www` (Laragon), lalu jalankan:

```bash
git clone https://github.com/username/project-ecommerce-buah.git project.E-commerce-buah
cd project.E-commerce-buah
```

### 3. Setup Database

1. Pastikan Apache dan MySQL sudah berjalan di XAMPP/Laragon.
2. Buka **phpMyAdmin** di browser: `http://localhost/phpmyadmin`
3. Klik tab **SQL**, lalu import seluruh isi file berikut:

```
database/buah_segar.sql
```

File SQL ini akan otomatis:
- Membuat database `buah_segar`
- Membuat tabel `users`, `buah`, `orders`, `order_items`, `cart`
- Membuat view `v_order_details`
- Mengisi data awal (12 produk buah + 4 akun pengguna)

### 4. Konfigurasi Koneksi Database

Buka file `config/database.php` dan sesuaikan kredensial database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'buah_segar');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika default XAMPP/Laragon
```

### 5. Jalankan Aplikasi

Buka browser dan akses:

```
http://localhost/project.E-commerce-buah
```

### 6. Akun Login Default

> ⚠️ Password disimpan sebagai plain text di versi development ini. Jangan gunakan password yang sama di lingkungan produksi.

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Customer | `budi` | `budi123` |
| Customer | `siti` | `siti123` |
| Customer | `andi` | `andi123` |

<img width="1366" height="688" alt="image" src="https://github.com/user-attachments/assets/fd54bd89-dec3-4d58-a301-b51eebe8a3ff" />


---

## 📝 Pemetaan Rubrik Penilaian

### 1. HTML Semantik & Aksesibilitas

Seluruh halaman menggunakan tag semantik HTML5 secara konsisten: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, dan `<footer>`. Setiap gambar produk dilengkapi atribut `alt`, form dilengkapi ARIA labels, dan hierarki heading (`h1`–`h3`) diatur rapi untuk mendukung aksesibilitas dan SEO.

### 2. Desain CSS Responsif

Antarmuka dibangun dengan **CSS Variables** untuk tema warna terpusat, **Flexbox** dan **CSS Grid** untuk layout katalog produk, serta **media query** agar tampilan nyaman di ponsel, tablet, maupun desktop. Transisi dan hover effect ditambahkan untuk pengalaman pengguna yang lebih baik.

### 3. Operasi CRUD — Manajemen Produk Admin

Seluruh operasi CRUD diimplementasikan penuh di modul admin (`admin/products.php`):

| Operasi | Detail Implementasi |
|---|---|
| **Create** | Form tambah produk dengan upload gambar (drag-drop, preview, validasi 2MB) |
| **Read** | Daftar produk dengan filter kategori, pencarian, dan badge stok (merah/kuning/hijau) |
| **Update** | Form edit dengan data terisi otomatis (*pre-filled*) dari database |
| **Delete** | Hapus produk beserta file gambar fisiknya, dengan konfirmasi di sisi klien |

<img width="1366" height="686" alt="image" src="https://github.com/user-attachments/assets/19e3eb0d-dee8-4858-8284-e52e3bcf31a0" />


> **Mengapa CRUD Penting dalam Aplikasi Web?**
> CRUD (Create, Read, Update, Delete) adalah fondasi dari hampir semua aplikasi web dinamis. Tanpa CRUD, aplikasi hanya bisa menampilkan data statis — tidak bisa menerima, mengolah, atau memperbarui informasi baru. Dalam konteks Buah Segar, CRUD memastikan data produk, stok, pesanan, dan akun pengguna selalu akurat dan dapat dikelola secara real-time, persis seperti yang dibutuhkan oleh sistem informasi bisnis nyata.

### 4. Integrasi Database

- Koneksi menggunakan **PDO** dengan *emulated prepared statements* dinonaktifkan (`ATTR_EMULATE_PREPARES => false`) untuk keamanan maksimal terhadap SQL Injection.
- Helper functions (`fetchOne`, `fetchAll`, `execute`) tersentralisasi di `config/database.php` agar konsisten di seluruh aplikasi.
- **Foreign key constraints** menjaga integritas relasi antar tabel (`orders` → `users`, `order_items` → `buah`).
- Database **View** (`v_order_details`) digunakan untuk menyederhanakan query laporan pesanan yang kompleks.

### 5. Integritas Transaksi Checkout

Proses checkout di `api/checkout.php` menggunakan **database transaction** (`beginTransaction` / `commitTransaction` / `rollbackTransaction`) untuk memastikan atomisitas — jika salah satu langkah gagal, seluruh operasi dibatalkan. Alurnya:

1. Validasi stok semua item sebelum diproses.
2. Buat record `orders` baru di database.
3. Insert semua `order_items` satu per satu.
4. Kurangi stok (`stok_kg`) setiap produk yang dibeli.
5. Nonaktifkan produk (`status = 'inactive'`) jika stok habis setelah transaksi.
6. Kosongkan session cart.

### 6. Autentikasi & Role-Based Access Control (RBAC)

Sistem autentikasi dikelola melalui `auth/check_session.php`:

| Role | Hak Akses |
|---|---|
| **Admin** | Dashboard statistik, CRUD produk lengkap, kelola & update status semua pesanan |
| **Customer** | Lihat katalog, tambah ke keranjang, checkout, lihat riwayat pesanan sendiri |

Setiap halaman admin memvalidasi role melalui fungsi `requireAdmin()`. Halaman customer memvalidasi login melalui `checkLogin()`. Pengguna yang belum login atau mengakses halaman di luar hak aksesnya akan diarahkan otomatis ke halaman login.

<img width="1366" height="686" alt="image" src="https://github.com/user-attachments/assets/53712107-3f32-429b-9110-0c0388e8f432" />
<img width="1366" height="693" alt="image" src="https://github.com/user-attachments/assets/2af9919e-f188-44b2-8ead-b80b247d5381" />


### 7. Validasi Input (Dua Lapis)

**Sisi klien (HTML5):** Atribut `required`, `min`, `max`, dan `pattern` pada form mencegah data kosong atau tidak valid sebelum dikirim.

**Sisi server (PHP):** Setiap input dari form divalidasi ulang: nama produk tidak boleh kosong, harga harus lebih dari 0, stok tidak boleh negatif. Upload gambar divalidasi berdasarkan ekstensi dan ukuran maksimal 2MB. Semua query database menggunakan prepared statement — data pengguna tidak pernah langsung digabungkan ke string SQL.

### 8. User Experience

- **Toast notifications** untuk feedback sukses/error setelah setiap aksi (tambah, edit, hapus, checkout).
- **Badge counter** di ikon keranjang menampilkan jumlah item secara real-time.
- **Stok badge** berwarna: merah (habis), kuning (menipis ≤ 5 kg), hijau (tersedia).
- **Subtotal & grand total** dihitung otomatis di halaman keranjang tanpa perlu reload halaman.
- Tombol "Beli" dinonaktifkan secara otomatis jika stok produk habis.

<img width="1366" height="688" alt="image" src="https://github.com/user-attachments/assets/265e250a-3913-48c2-b5bc-24e2541b1a3a" />

## 📁 Struktur Direktori & Alur Data

```
project.E-commerce-buah/
├── admin/                      # Halaman khusus Admin
│   ├── dashboard.php           # Statistik penjualan, pesanan masuk, stok menipis
│   ├── orders.php              # Kelola pesanan: update status, filter, hapus
│   └── products.php            # CRUD produk: tambah, edit, hapus, upload gambar
├── api/                        # Endpoint AJAX (mengembalikan JSON)
│   ├── add_to_cart.php         # Tambah item ke session cart
│   ├── checkout.php            # Proses checkout dengan transaksi database
│   ├── load_more_products.php  # Lazy loading produk (infinite scroll)
│   ├── remove_cart.php         # Hapus item dari cart
│   └── update_cart.php         # Update jumlah (qty) item di cart
├── assets/
│   └── images/
│       ├── hero/               # Gambar banner landing page
│       └── products/           # File gambar produk yang diupload admin
├── auth/                       # Autentikasi & manajemen sesi
│   ├── check_session.php       # Guard: cek login & role, redirect jika tidak valid
│   ├── login.php               # Form & proses login
│   └── logout.php              # Hapus session, redirect ke login
├── config/
│   └── database.php            # Koneksi PDO + helper functions (fetchAll, execute, dll.)
├── database/
│   └── buah_segar.sql          # Skema lengkap + seed data
├── includes/
│   └── functions.php           # Utility: formatRupiah, formatTanggal, uploadImage, dll.
├── pages/                      # Halaman untuk Customer
│   ├── cart.php                # Keranjang belanja: lihat, update qty, checkout
│   ├── catalog.php             # Katalog produk: filter, search, infinite scroll
│   ├── my_orders.php           # Riwayat pesanan milik user yang login
│   └── order_detail.php        # Detail item per pesanan
└── index.php                   # Landing page dengan produk unggulan & CTA
```

**Contoh Alur Data — Proses Checkout:**

```
Customer klik "Checkout" di cart.php
    → AJAX POST ke api/checkout.php
        → check_session.php: Validasi login
        → Validasi input (nama, telepon, alamat)
        → Loop cart: cek stok tiap produk di database
        → beginTransaction()
            → INSERT ke tabel orders
            → INSERT ke tabel order_items (per item)
            → UPDATE stok_kg di tabel buah
            → Jika stok = 0, set status = 'inactive'
        → commitTransaction()
        → Kosongkan $_SESSION['cart']
        → Return JSON { success: true }
    → Redirect ke halaman my_orders.php
```
<img width="1366" height="692" alt="image" src="https://github.com/user-attachments/assets/c75d872e-f173-490f-bdc0-25025e082a70" />

---

## ⚠️ Known Issues & Rencana Pengembangan

**Batasan yang diketahui saat ini:**

- **Password Plain Text:** Password pengguna disimpan tanpa hashing di versi ini. Sebelum digunakan di lingkungan publik, wajib diganti dengan `password_hash()` (bcrypt).
- **Metode Pembayaran Simulasi:** Checkout saat ini hanya mensimulasikan pemesanan tanpa integrasi ke payment gateway nyata.
- **Cart Berbasis Session:** Data keranjang hilang jika sesi browser berakhir. Tabel `cart` sudah ada di database namun belum digunakan untuk persistensi antar sesi.

**Rencana pengembangan fase berikutnya:**

- Migrasi password ke `password_hash()` + `password_verify()` untuk keamanan produksi.
- Integrasi **Midtrans** atau **Xendit** sebagai payment gateway nyata.
- Manfaatkan tabel `cart` yang sudah ada untuk persistensi keranjang antar perangkat.
- Fitur ekspor laporan pesanan ke PDF atau Excel untuk kebutuhan admin.
- Fitur registrasi mandiri untuk pelanggan baru.

---

*Proyek ini dikembangkan untuk tujuan pembelajaran pemrograman web full-stack.*
