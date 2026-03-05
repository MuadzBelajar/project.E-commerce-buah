# 🍎 BUAH SEGAR E-COMMERCE

Website e-commerce penjualan buah segar berbasis PHP native. 

**First view**: Landing page yang mengarahkan ke mode E-Commerce dengan fitur CRUD lengkap dan sistem autentikasi role-based.
<img width="1366" height="687" alt="image" src="https://github.com/user-attachments/assets/ce302b28-ee1e-418f-8f18-d1d501ac0f7f" />

<img width="1366" height="692" alt="image" src="https://github.com/user-attachments/assets/6201c394-7ef9-4577-8d45-c76f3c6c553b" />


## ✨ Fitur Utama

### 1. Struktur HTML
- Semantic HTML5 (`&lt;header&gt;`, `&lt;nav&gt;`, `&lt;main&gt;`, `&lt;section&gt;`, `&lt;article&gt;`, `&lt;footer&gt;`)
- Form validation (`required`, `pattern`, `min/max`)
- Accessibility (ARIA labels, alt text, keyboard navigation)
- SEO friendly (meta tags, heading hierarchy)

### 2. Desain CSS
- Responsive design (mobile, tablet, desktop)
- CSS variables, Flexbox/Grid, transitions
- Cross-browser compatible

### 3. CRUD Operations
| Operasi | Implementasi |
|---------|-------------|
| **Create** | Tambah produk + upload gambar |
| **Read** | List produk, filter, search |
| **Update** | Edit produk (pre-filled form) |
| **Delete** | Hapus dengan konfirmasi |
| **Error Handling** | Try-catch, user-friendly messages |

### 4. Database Integration
- PDO connection dengan prepared statements
- Foreign key constraints & data validation
- Transaction handling (checkout)
- SQL injection prevention

### 5. User Experience
- Loading indicators & skeleton screens
- Toast notifications (success/error)
- Breadcrumb & intuitive navigation
- Lazy loading images

### 6. Code Quality
- Modular folder structure
- Inline documentation
- Git version control
- Manual testing

### 7. Fitur E-Commerce
- **Navbar Beli**: Cart icon dengan badge counter
- **Katalog Produk**: Grid responsive, badge stok (merah/ kuning/hijau)
- **Upload Gambar**: Drag-drop, preview, validasi 2MB
- **Stok Management**: Disable beli jika stok habis
- **Perhitungan Otomatis**: Subtotal & grand total real-time

### 8. Sistem Autentikasi
| Role | Hak Akses |
|------|-----------|
| **Admin** | Full CRUD (Create, Read, Update, Delete) |
| **Customer** | Read only (view, cart, checkout) |

<h3>Default Login</h3>

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Customer | budi | budi123 |
| Customer | andi | andi123 |
| Customer | siti | siti123 |


## Informasi project

- **Backend**: PHP 8.x, MySQL, PDO
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Tools**: XAMPP, Git, VS Code


## 📁 Struktur Folder
<pre><code>
ecommerce-project/
├── admin/                      # Dashboard admin (Role: Admin only)
│   ├── dashboard.php           # Statistik penjualan, total pesanan, stok menipis
│   ├── orders.php              # Kelola pesanan: update status, hapus, filter by status
│   └── products.php            # CRUD produk: tambah, edit, hapus, upload gambar
├── api/                        # Endpoint AJAX untuk operasi async
│   ├── add_to_cart.php         # Tambah item ke session cart, return JSON cart_count
│   ├── checkout.php            # Proses checkout: insert order, kurangi stok, clear cart
│   ├── load_more_products.php  # Infinite scroll: return produk dalam format JSON/HTML
│   ├── remove_cart.php         # Hapus item dari cart berdasarkan product_id
│   └── update_cart.php         # Update quantity item di cart
├── assets/                     # Aset statis (gambar, CSS, JS)
│   └── images/
│       ├── hero/               # Gambar banner landing page
│       └── products/           # Gambar upload produk (local storage)
├── auth/                       # Sistem autentikasi & session management
│   ├── check_session.php       # Validasi login, redirect jika belum login, cek role
│   ├── login.php               # Form login, validasi credential, set session
│   └── logout.php              # Destroy session, redirect ke login
├── config/                     # Konfigurasi aplikasi
│   └── database.php            # Koneksi PDO, helper functions (fetchAll, fetchOne, execute)
├── database/                   # File database
│   └── buah_segar.sql          # Dump struktur tabel + seed data users & produk
├── includes/                   # Helper functions global
│   └── functions.php           # Format rupiah, format tanggal, slug generator, getCartItemCount
├── pages/                      # Halaman customer (Role: Customer/Admin)
│   ├── cart.php                # Tampilkan isi cart, update qty, tombol checkout
│   ├── catalog.php             # List produk dengan filter, search, pagination/infinite scroll
│   ├── my_orders.php           # Riwayat pesanan user yang login
│   └── order_detail.php        # Detail item pesanan (produk, qty, harga, subtotal)
└── index.php                   # Landing page: hero section, CTA ke catalog, redirect jika sudah login
</code></pre>
