<?php
// Prevent direct access
if (!defined('DB_CONFIG_LOADED')) {
    define('DB_CONFIG_LOADED', true);
}

/**
 * ================================================
 * DATABASE CREDENTIALS
 * ================================================
 * IMPORTANT: Ganti credentials ini sesuai environment!
 * - Development: localhost
 * - Production: sesuaikan dengan hosting
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'buah_segar');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika default XAMPP/MAMP
define('DB_CHARSET', 'utf8mb4');

/**
 * ================================================
 * PDO CONNECTION OPTIONS
 * ================================================
 */

$pdo_options = [
    // Set error mode to exception untuk better error handling
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    
    // Return associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Disable emulated prepared statements (more secure)
    PDO::ATTR_EMULATE_PREPARES => false,
    
    // Persistent connection (optional, bisa di-comment jika tidak perlu)
    // PDO::ATTR_PERSISTENT => true,
];

/**
 * ================================================
 * GLOBAL PDO CONNECTION VARIABLE
 * ================================================
 */

$pdo = null;

/**
 * ================================================
 * FUNCTION: getConnection()
 * ================================================
 * Membuat dan mengembalikan PDO connection
 * Menggunakan singleton pattern agar hanya 1 koneksi
 * 
 * @return PDO PDO database connection object
 * @throws PDOException jika koneksi gagal
 */
function getConnection() {
    global $pdo;
    
    // Jika sudah ada koneksi, return yang existing
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Build DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Create PDO instance
        global $pdo_options;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Log error (jangan tampilkan ke user di production!)
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Tampilkan error yang user-friendly
        die("Koneksi database gagal. Silakan hubungi administrator.");
    }
}

/**
 * ================================================
 * FUNCTION: query()
 * ================================================
 * Execute prepared statement dengan parameters
 * 
 * @param string $sql SQL query dengan placeholders (?)
 * @param array $params Array of parameters untuk bind
 * @return PDOStatement Executed statement object
 * @throws PDOException jika query gagal
 * 
 * Example:
 * $stmt = query("SELECT * FROM users WHERE id = ?", [$user_id]);
 * $user = $stmt->fetch();
 */
function query($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw $e;
    }
}

/**
 * ================================================
 * FUNCTION: fetchOne()
 * ================================================
 * Fetch single row from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters untuk bind
 * @return array|false Single row sebagai associative array, atau false jika tidak ada
 * 
 * Example:
 * $user = fetchOne("SELECT * FROM users WHERE username = ?", ['admin']);
 */
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

/**
 * ================================================
 * FUNCTION: fetchAll()
 * ================================================
 * Fetch multiple rows from database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters untuk bind
 * @return array Array of rows, kosong jika tidak ada data
 * 
 * Example:
 * $products = fetchAll("SELECT * FROM buah WHERE status = ?", ['active']);
 */
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * ================================================
 * FUNCTION: execute()
 * ================================================
 * Execute INSERT, UPDATE, DELETE query
 * Return jumlah rows yang affected
 * 
 * @param string $sql SQL query
 * @param array $params Parameters untuk bind
 * @return int Jumlah rows yang terpengaruh
 * 
 * Example:
 * $affected = execute("UPDATE users SET status = ? WHERE id = ?", ['inactive', 5]);
 */
function execute($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->rowCount();
}

/**
 * ================================================
 * FUNCTION: lastInsertId()
 * ================================================
 * Get last inserted ID setelah INSERT query
 * 
 * @return string Last inserted ID
 * 
 * Example:
 * execute("INSERT INTO users (username, email) VALUES (?, ?)", ['john', 'john@example.com']);
 * $user_id = lastInsertId();
 */
function lastInsertId() {
    $pdo = getConnection();
    return $pdo->lastInsertId();
}

/**
 * ================================================
 * FUNCTION: beginTransaction()
 * ================================================
 * Start database transaction
 * Gunakan untuk operasi yang harus atomic (semua berhasil atau semua gagal)
 * 
 * Example:
 * beginTransaction();
 * try {
 *     execute("INSERT INTO orders ...");
 *     execute("UPDATE buah SET stok = stok - ? ...");
 *     commitTransaction();
 * } catch (Exception $e) {
 *     rollbackTransaction();
 * }
 */
function beginTransaction() {
    $pdo = getConnection();
    $pdo->beginTransaction();
}

/**
 * ================================================
 * FUNCTION: commitTransaction()
 * ================================================
 * Commit transaction (simpan semua perubahan)
 */
function commitTransaction() {
    $pdo = getConnection();
    $pdo->commit();
}

/**
 * ================================================
 * FUNCTION: rollbackTransaction()
 * ================================================
 * Rollback transaction (batalkan semua perubahan)
 */
function rollbackTransaction() {
    $pdo = getConnection();
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

/**
 * ================================================
 * FUNCTION: checkConnection()
 * ================================================
 * Test database connection
 * Return true jika berhasil, false jika gagal
 * 
 * @return bool Connection status
 */
function checkConnection() {
    try {
        $pdo = getConnection();
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * ================================================
 * FUNCTION: closeConnection()
 * ================================================
 * Close database connection
 * Biasanya tidak perlu dipanggil manual karena PHP auto-close
 * Tapi bisa berguna untuk long-running scripts
 */
function closeConnection() {
    global $pdo;
    $pdo = null;
}

/**
 * ================================================
 * AUTO-TEST CONNECTION SAAT FILE DI-INCLUDE
 * ================================================
 * Uncomment untuk auto-test koneksi setiap kali file di-load
 * Berguna untuk development, comment di production
 */

/*
if (!checkConnection()) {
    die("❌ Database connection failed! Check your credentials in config/database.php");
} else {
    // echo "✅ Database connected successfully!<br>";
}
*/

/**
 * ================================================
 * USAGE EXAMPLES
 * ================================================
 * 
 * // 1. SELECT single row
 * $user = fetchOne("SELECT * FROM users WHERE id = ?", [1]);
 * echo $user['username'];
 * 
 * // 2. SELECT multiple rows
 * $products = fetchAll("SELECT * FROM buah WHERE status = ?", ['active']);
 * foreach ($products as $product) {
 *     echo $product['nama_buah'];
 * }
 * 
 * // 3. INSERT with last ID
 * execute("INSERT INTO users (username, email, password) VALUES (?, ?, ?)", 
 *         ['john', 'john@example.com', password_hash('password', PASSWORD_DEFAULT)]);
 * $user_id = lastInsertId();
 * 
 * // 4. UPDATE
 * $affected = execute("UPDATE buah SET stok_kg = ? WHERE id = ?", [50, 1]);
 * echo "Updated {$affected} rows";
 * 
 * // 5. DELETE
 * execute("DELETE FROM cart WHERE user_id = ?", [5]);
 * 
 * // 6. Transaction (untuk checkout)
 * beginTransaction();
 * try {
 *     // Create order
 *     execute("INSERT INTO orders (user_id, total_harga) VALUES (?, ?)", [1, 100000]);
 *     $order_id = lastInsertId();
 *     
 *     // Add order items
 *     execute("INSERT INTO order_items (order_id, buah_id, jumlah_kg) VALUES (?, ?, ?)", 
 *             [$order_id, 1, 2]);
 *     
 *     // Update stock
 *     execute("UPDATE buah SET stok_kg = stok_kg - ? WHERE id = ?", [2, 1]);
 *     
 *     commitTransaction();
 *     echo "Order success!";
 *     
 * } catch (Exception $e) {
 *     rollbackTransaction();
 *     echo "Order failed: " . $e->getMessage();
 * }
 * 
 * ================================================
 */

// End of file
?>