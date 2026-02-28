# 🍎 BUAH SEGAR E-COMMERCE

Website e-commerce penjualan buah segar berbasis PHP native dengan fitur CRUD lengkap, sistem autentikasi role-based, dan responsive design.

---


### 1. STRUKTUR HTML
- **Semantic HTML**: Penggunaan tag `&lt;header&gt;`, `&lt;nav&gt;`, `&lt;main&gt;`, `&lt;section&gt;`, `&lt;article&gt;`, `&lt;aside&gt;`, `&lt;footer&gt;`
- **Form Validation**: Atribut `required`, `min`, `step`, `type="number"`, `accept="image/*"`
- **Accessibility**: `alt` text, `aria-label`, keyboard navigation, focus states
- **SEO Friendly**: Meta tags, descriptive title, heading hierarchy (h1→h2→h3)

### 2. DESAIN CSS
- **Responsiveness**: Media queries untuk mobile (&lt;768px), tablet (768-1024px), desktop (&gt;1024px)
- **Visual Consistency**: CSS variables, typography (Playfair Display + DM Sans), spacing system
- **Modern CSS**: Flexbox, CSS Grid, transitions, animations
- **Cross-browser**: Compatible dengan Chrome, Firefox, Safari, Edge

### 3. CRUD OPERATIONS
| Operasi | Fitur |
|---------|-------|
| **Create** | Tambah produk dengan upload gambar, auto-generate slug |
| **Read** | List produk, filter pesanan by status, search functionality |
| **Update** | Edit produk (pre-filled form), update status pesanan |
| **Delete** | Hapus produk + file gambar dengan konfirmasi |
| **Error Handling** | Try-catch database, form validation, user-friendly messages |

### 4. DATABASE INTEGRATION
- **Connection**: PDO dengan UTF-8, single config file
- **Query Optimization**: Prepared statements, helper functions
- **Data Integrity**: Foreign keys, ENUM status, NOT NULL constraints
- **Transaction**: Commit/rollback untuk operasi checkout
- **Security**: SQL injection prevention, input sanitization, password bcrypt

### 5. USER EXPERIENCE
- **Loading States**: Skeleton screens, button transitions
- **Feedback System**: Flash messages, auto-hide alerts, status badges
- **Navigation**: Breadcrumb, back button, active menu states, mobile overlay
- **Performance**: Lazy loading, optimized images, efficient queries

### 6. CODE QUALITY
- **Organization**: Modular folder structure, separation of concerns
- **Documentation**: Inline comments, README, meaningful commit messages
- **Version Control**: Git dengan .gitignore, branch management
- **Testing**: Manual testing untuk CRUD, upload, checkout, login, responsive

### 7. FITUR E-COMMERCE
- **Halaman Beli**: Session-based cart, tambah/kurangi qty, hapus item
- **Tabel Harga**: Format Rupiah, harga/kg, total dinamis
- **Katalog + Upload**: Drag-drop upload, image preview, validasi 2MB, placeholder fallback
- **Stok Habis**: Badge warna berdasarkan stok (merah ≤10, kuning ≤20, hijau &gt;20), disable beli jika habis
- **Perhitungan Total**: Real-time subtotal, grand total checkout, detail breakdown

### 8. SISTEM AUTHENTIKASI
| Role | Akses |
|------|-------|
| **Admin** | Full CRUD: Create, Read, Update, Delete |
| **User** | Read only: View catalog, cart, checkout |

Keamanan: Password hashing (bcrypt), session management, role-based access control

---

## 🚀 TEKNOLOGI

| Kategori | Teknologi |
|----------|-----------|
| Backend | PHP 8.x, MySQL, PDO |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Tools | XAMPP, Git, VS Code |

---

## 📁 STRUKTUR FOLDER

<pre><code>
ecommerce-project/
├── admin/                      # Halaman admin (dashboard, kelola produk & pesanan)
│   ├── dashboard.php           # Dashboard admin dengan statistik
│   ├── orders.php              # Kelola pesanan (view, update status, delete)
│   └── products.php            # CRUD produk (tambah, edit, hapus)
├── api/                        # Endpoint AJAX untuk operasi cart
│   ├── add_to_cart.php         # Tambah item ke keranjang
│   ├── checkout.php            # Proses checkout dan kurangi stok
│   ├── remove_cart.php         # Hapus item dari keranjang
│   └── update_cart.php         # Update jumlah item di keranjang
├── assets/                     # Aset statis (gambar, CSS, JS)
│   └── images/
│       ├── hero/               # Gambar banner hero
│       └── products/           # Gambar produk buah
├── auth/                       # Sistem autentikasi
│   ├── check_session.php       # Validasi session login
│   ├── login.php               # Halaman login
│   └── logout.php              # Proses logout
├── config/                     # Konfigurasi aplikasi
│   └── database.php            # Koneksi database & helper functions
├── database/                   # File database
│   └── buah_segar.sql          # Dump struktur dan data awal
├── includes/                   # File fungsi bantuan
│   └── functions.php           # Helper format rupiah, tanggal, dll
├── pages/                      # Halaman publik untuk customer
│   ├── cart.php                # Halaman keranjang belanja
│   └── catalog.php             # Halaman katalog produk
└── index.php                   # Entry point, redirect ke catalog
</code></pre>
