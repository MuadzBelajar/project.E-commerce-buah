
Setting environment for using XAMPP for Windows.
USer@DESKTOP-P04P4QG c:\xampp
# mysql -u root
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 8
Server version: 10.4.32-MariaDB mariadb.org binary distribution

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> show databases;
+--------------------+
| Database           |
+--------------------+
| angkatan_4_a       |
| buah_segar         |
| information_schema |
| muadzs_db          |
| mysql              |
| online_shop        |
| performance_schema |
| perpustakaan       |
| phpmyadmin         |
+--------------------+
9 rows in set (0.646 sec)

MariaDB [(none)]> use buah_segar;
Database changed
MariaDB [buah_segar]> show tables;
+----------------------+
| Tables_in_buah_segar |
+----------------------+
| buah                 |
| order_items          |
| orders               |
| users                |
| v_order_details      |
+----------------------+
5 rows in set (0.001 sec)

MariaDB [buah_segar]> DROP DATABASE IF EXISTS buah_segar;
Query OK, 5 rows affected (0.031 sec)

MariaDB [(none)]> -- =====================================================
MariaDB [(none)]> -- DATABASE BUAH SEGAR E-COMMERCE - PLAIN TEXT PASSWORD
MariaDB [(none)]> -- =====================================================
MariaDB [(none)]> -- Schema for fruit e-commerce website
MariaDB [(none)]> -- Password: Plain Text (NO HASH)
MariaDB [(none)]> -- =====================================================
MariaDB [(none)]>
MariaDB [(none)]> -- Drop database if exists (for fresh install)
MariaDB [(none)]> DROP DATABASE IF EXISTS buah_segar;
Query OK, 0 rows affected, 1 warning (0.001 sec)

MariaDB [(none)]> CREATE DATABASE buah_segar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
Query OK, 1 row affected (0.003 sec)

MariaDB [(none)]> USE buah_segar;
Database changed
MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- TABLE: users
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> CREATE TABLE users (
    ->     id INT PRIMARY KEY AUTO_INCREMENT,
    ->     username VARCHAR(50) UNIQUE NOT NULL,
    ->     email VARCHAR(100) UNIQUE NOT NULL,
    ->     password VARCHAR(255) NOT NULL,
    ->     nama_lengkap VARCHAR(100) NOT NULL,
    ->     no_telepon VARCHAR(20),
    ->     alamat TEXT,
    ->     role ENUM('admin', 'customer') DEFAULT 'customer',
    ->     status ENUM('active', 'inactive') DEFAULT 'active',
    ->     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ->     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ->     INDEX idx_username (username),
    ->     INDEX idx_email (email),
    ->     INDEX idx_role (role)
    -> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Query OK, 0 rows affected (0.035 sec)

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- TABLE: buah (products)
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> CREATE TABLE buah (
    ->     id INT PRIMARY KEY AUTO_INCREMENT,
    ->     nama_buah VARCHAR(100) NOT NULL,
    ->     slug VARCHAR(100) UNIQUE NOT NULL,
    ->     deskripsi TEXT,
    ->     harga_kg DECIMAL(10,2) NOT NULL,
    ->     stok_kg DECIMAL(10,2) DEFAULT 0,
    ->     gambar VARCHAR(255),
    ->     asal VARCHAR(50),
    ->     kategori ENUM('lokal', 'impor') DEFAULT 'lokal',
    ->     status ENUM('active', 'inactive') DEFAULT 'active',
    ->     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ->     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ->     INDEX idx_slug (slug),
    ->     INDEX idx_status (status),
    ->     INDEX idx_kategori (kategori)
    -> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Query OK, 0 rows affected (0.032 sec)

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- TABLE: orders
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> CREATE TABLE orders (
    ->     id INT PRIMARY KEY AUTO_INCREMENT,
    ->     user_id INT NOT NULL,
    ->     nama_pemesan VARCHAR(100) NOT NULL,
    ->     no_telepon VARCHAR(20) NOT NULL,
    ->     alamat_kirim TEXT NOT NULL,
    ->     total_harga DECIMAL(10,2) NOT NULL,
    ->     status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    ->     catatan TEXT,
    ->     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ->     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ->     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    ->     INDEX idx_user_id (user_id),
    ->     INDEX idx_status (status),
    ->     INDEX idx_created_at (created_at)
    -> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Query OK, 0 rows affected (0.040 sec)

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- TABLE: order_items
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> CREATE TABLE order_items (
    ->     id INT PRIMARY KEY AUTO_INCREMENT,
    ->     order_id INT NOT NULL,
    ->     buah_id INT NOT NULL,
    ->     nama_buah VARCHAR(100) NOT NULL,
    ->     harga_kg DECIMAL(10,2) NOT NULL,
    ->     jumlah_kg DECIMAL(10,2) NOT NULL,
    ->     subtotal DECIMAL(10,2) NOT NULL,
    ->     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ->     FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    ->     FOREIGN KEY (buah_id) REFERENCES buah(id) ON DELETE CASCADE,
    ->     INDEX idx_order_id (order_id),
    ->     INDEX idx_buah_id (buah_id)
    -> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Query OK, 0 rows affected (0.031 sec)

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- SEED DATA: users (PLAIN TEXT PASSWORD)
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> INSERT INTO users (username, email, password, nama_lengkap, no_telepon, alamat, role, status) VALUES
    -> ('admin', 'admin@buahsegar.com', 'admin123', 'Administrator', '081234567890', 'Jl. Admin No. 1, Makassar', 'admin', 'active'),
    -> ('budi', 'budi@email.com', 'budi123', 'Budi Santoso', '081234567891', 'Jl. Budi No. 2, Makassar', 'customer', 'active'),
    -> ('siti', 'siti@email.com', 'siti123', 'Siti Aminah', '081234567892', 'Jl. Siti No. 3, Makassar', 'customer', 'active'),
    -> ('andi', 'andi@email.com', 'andi123', 'Andi Pratama', '081234567893', 'Jl. Andi No. 4, Makassar', 'customer', 'active');
Query OK, 4 rows affected (0.004 sec)
Records: 4  Duplicates: 0  Warnings: 0

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- SEED DATA: buah (products)
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> INSERT INTO buah (nama_buah, slug, deskripsi, harga_kg, stok_kg, gambar, asal, kategori, status) VALUES
    -> ('Mangga Harum Manis', 'mangga-harum-manis', 'Mangga Harum Manis dari Probolinggo dengan rasa manis legit dan aroma khas. Dipetik langsung dari pohon untuk kesegaran maksimal.', 22000, 50, 'https://images.unsplash.com/photo-1553279768-865429fa0078?w=600', 'Probolinggo', 'lokal', 'active'),
    -> ('Apel Fuji', 'apel-fuji', 'Apel Fuji impor dari Cina dengan tekstur renyah dan rasa manis asam yang seimbang. Kaya akan vitamin dan serat.', 35000, 30, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600', 'Cina', 'impor', 'active'),
    -> ('Durian Montong', 'durian-montong', 'Durian Montong dari Medan dengan daging buah tebal, lembut, dan rasa manis yang kaya. King of Fruits!', 60000, 25, 'https://images.unsplash.com/photo-1528821154947-1aa3b1b74941?w=600', 'Medan', 'lokal', 'active'),
    -> ('Anggur Red Globe', 'anggur-red-globe', 'Anggur Red Globe impor dari Chile dengan buah besar, bulat, dan rasa manis legit. Tanpa biji.', 45000, 20, 'https://images.unsplash.com/photo-1599819177162-6bc01483f5f6?w=600', 'Chile', 'impor', 'active'),
    -> ('Jeruk Medan', 'jeruk-medan', 'Jeruk Medan segar dari Sumatera Utara dengan rasa manis asam yang menyegarkan. Tinggi vitamin C.', 18000, 40, 'https://images.unsplash.com/photo-1582979512210-99b6a53386f9?w=600', 'Medan', 'lokal', 'active'),
    -> ('Pisang Raja', 'pisang-raja', 'Pisang Raja dari Maluku dengan tekstur lembut dan rasa manis legit. Cocok untuk smoothies atau langsung dimakan.', 15000, 35, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600', 'Maluku', 'lokal', 'active'),
    -> ('Semangka Merah', 'semangka-merah', 'Semangka merah segar dengan daging buah merah muda, manis, dan sangat menyegarkan. Tinggi kandungan air.', 12000, 45, 'https://images.unsplash.com/photo-1563114773-84221bd62daa?w=600', 'Lombok', 'lokal', 'active'),
    -> ('Melon Cantaloupe', 'melon-cantaloupe', 'Melon Cantaloupe impor dengan daging oranye, manis, dan aroma harum. kaya akan vitamin A dan C.', 28000, 25, 'https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?w=600', 'Australia', 'impor', 'active'),
    -> ('Pepaya California', 'pepaya-california', 'Pepaya California segar dengan daging oranye cerah, lembut, dan manis. Baik untuk pencernaan.', 16000, 30, 'https://images.unsplash.com/photo-1600326145359-3a44909d1a39?w=600', 'Malang', 'lokal', 'active'),
    -> ('Alpukat Mentega', 'alpukat-mentega', 'Alpukat Mentega dengan tekstur lembut seperti mentega, rasa gurih, dan kaya lemak baik.', 32000, 20, 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=600', 'Ponorogo', 'lokal', 'active'),
    -> ('Nanas', 'nanas', 'Nanas segar dari Lampung dengan rasa manis asam yang khas. Tinggi vitamin C dan enzim pencernaan.', 14000, 35, 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=600', 'Lampung', 'lokal', 'active'),
    -> ('Kelapa Muda', 'kelapa-muda', 'Kelapa muda segar dari Sulawesi dengan air kelapa yang manis dan daging yang lembut.', 10000, 50, 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=600', 'Sulawesi', 'lokal', 'active');
Query OK, 12 rows affected (0.003 sec)
Records: 12  Duplicates: 0  Warnings: 0

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- SEED DATA: orders (sample orders for testing)
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> INSERT INTO orders (user_id, nama_pemesan, no_telepon, alamat_kirim, total_harga, status, catatan) VALUES
    -> (2, 'Budi Santoso', '081234567891', 'Jl. Budi No. 2, Makassar', 72000, 'delivered', 'Paling penting fresh!'),
    -> (3, 'Siti Aminah', '081234567892', 'Jl. Siti No. 3, Makassar', 90000, 'processing', 'Jangan lupa packing rapi');
Query OK, 2 rows affected (0.003 sec)
Records: 2  Duplicates: 0  Warnings: 0

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- SEED DATA: order_items
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> INSERT INTO order_items (order_id, buah_id, nama_buah, harga_kg, jumlah_kg, subtotal) VALUES
    -> (1, 1, 'Mangga Harum Manis', 22000, 2, 44000),
    -> (1, 2, 'Apel Fuji', 35000, 0.8, 28000),
    -> (2, 3, 'Durian Montong', 60000, 1, 60000),
    -> (2, 5, 'Jeruk Medan', 18000, 1.67, 30060);
Query OK, 4 rows affected (0.004 sec)
Records: 4  Duplicates: 0  Warnings: 0

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- VIEW: order_details (for viewing orders with items)
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> CREATE VIEW v_order_details AS
    -> SELECT
    ->     o.id AS order_id,
    ->     o.user_id,
    ->     u.username,
    ->     u.nama_lengkap,
    ->     o.nama_pemesan,
    ->     o.no_telepon,
    ->     o.alamat_kirim,
    ->     o.total_harga,
    ->     o.status,
    ->     o.catatan,
    ->     o.created_at,
    ->     oi.buah_id,
    ->     oi.nama_buah,
    ->     oi.harga_kg,
    ->     oi.jumlah_kg,
    ->     oi.subtotal
    -> FROM orders o
    -> JOIN users u ON o.user_id = u.id
    -> JOIN order_items oi ON o.id = oi.order_id;
Query OK, 0 rows affected (0.003 sec)

MariaDB [buah_segar]>
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> -- END OF DATABASE SCHEMA
MariaDB [buah_segar]> -- =====================================================
MariaDB [buah_segar]> UPDATE buah SET gambar = SUBSTRING_INDEX(gambar, '/', -1) WHERE gambar LIKE '%/%';
Query OK, 3 rows affected (0.001 sec)
Rows matched: 3  Changed: 3  Warnings: 0

MariaDB [buah_segar]> CREATE TABLE cart (    id INT AUTO_INCREMENT PRIMARY KEY,    user_id INT NOT NULL,    buah_id INT NOT NULL,    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,    FOREIGN KEY (buah_id) REFERENCES buah(id) ON DELETE CASCADE,    UNIQUE(user_id, buah_id));
Query OK, 0 rows affected (0.038 sec)

MariaDB [buah_segar]>