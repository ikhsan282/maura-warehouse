# Maura Warehouse

Sistem Manajemen Inventori & Gudang — PHP Native + MySQLi + Bootstrap 5.

## Stack
- PHP 7.4+ (Native, no framework)
- MySQLi with prepared statements
- Bootstrap 5.3 + Bootstrap Icons (CDN)
- MySQL / MariaDB

## Instalasi

### 1. Import Database
```sql
mysql -u root -p < database/schema.sql
```

### 2. Konfigurasi Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'db_maura_warehouse');
```

### 3. Konfigurasi Aplikasi
Edit `config/config.php`:
```php
define('APP_URL', 'http://yourdomain.com/maura-warehouse');
define('MAIL_FROM', 'no-reply@yourdomain.com');
```

### 4. Upload ke cPanel
- Upload semua file ke `public_html/maura-warehouse/`
- Pastikan `.htaccess` ikut terupload
- Set permission folder: `755`, file: `644`

### 5. Login Default
| Username | Password | Peran |
|---|---|---|
| `superadmin` | `password` | Super Admin |

> **Ganti password segera setelah login pertama!**

> **Catatan:** Akun default sudah terverifikasi email. Pengguna baru yang didaftarkan melalui UI akan menerima email verifikasi via `mail()`.

---

## Struktur File
```
maura-warehouse/
├── .htaccess                  # Apache config, security headers
├── index.php                  # Entry point → redirect
├── config/
│   ├── database.php           # DB constants (ubah untuk cPanel)
│   └── config.php             # App settings, session, timezone
├── includes/
│   ├── auth.php               # Login, logout, RBAC, email verify
│   ├── functions.php          # Helpers: CSRF, flash, paginate, idr()
│   ├── header.php             # HTML head + navbar
│   ├── sidebar.php            # Sidebar navigasi (permission-aware)
│   └── footer.php             # Scripts, closing tags
├── assets/
│   ├── css/style.css          # Layout, sidebar, auth, badges
│   └── js/app.js              # Sidebar toggle, dynamic rows, confirm
├── auth/
│   ├── login.php
│   ├── logout.php
│   ├── forgot-password.php
│   ├── reset-password.php
│   └── verify-email.php
├── pages/
│   ├── dashboard.php          # Stats, stok menipis, mutasi terbaru
│   ├── items/                 # index, create, edit, view, delete
│   ├── categories/            # index (CRUD modal)
│   ├── units/                 # index (CRUD modal)
│   ├── suppliers/             # index (CRUD modal)
│   ├── locations/             # index (CRUD modal)
│   ├── stock-in/              # index, create, view, delete
│   ├── stock-out/             # index, create, view, delete
│   ├── transfers/             # index, create, view, delete
│   ├── stock/                 # index (cek stok + alert)
│   ├── reports/               # mutation, stock, stock-in, stock-out
│   ├── users/                 # index (CRUD + reset password)
│   └── roles/                 # index (permission editor per role)
└── database/
    └── schema.sql             # DDL + seed data
```

## Peran Default (RBAC)
| Peran | Akses |
|---|---|
| Super Admin | Semua fitur |
| Admin | Semua kecuali hapus user & edit role |
| Staff Gudang | Transaksi + lihat master data |
| Viewer | Read-only semua |

## Fitur
- ✅ Login / Logout dengan session
- ✅ CSRF protection di semua form
- ✅ Email verifikasi via `mail()`
- ✅ Forgot & reset password (token 1 jam)
- ✅ RBAC: roles + permissions + role_permissions
- ✅ Master data: kategori, satuan, supplier, lokasi
- ✅ Data barang dengan stok minimum & harga
- ✅ Barang masuk (dari supplier, ke lokasi)
- ✅ Barang keluar (dari lokasi, ke penerima)
- ✅ Transfer antar lokasi
- ✅ Stok per lokasi + alert menipis
- ✅ Audit trail (mutations log)
- ✅ Laporan mutasi + export CSV
- ✅ Laporan stok + export CSV
- ✅ Laporan barang masuk & keluar
- ✅ Dashboard dengan statistik real-time
- ✅ Pagination di semua list
- ✅ Responsive (Bootstrap 5)

## Keamanan
- Semua query pakai MySQLi prepared statements
- Password di-hash dengan `password_hash()` bcrypt cost=12
- CSRF token di setiap form POST
- Session `httponly` + `samesite=Strict`
- `.htaccess` blokir akses langsung ke `config/`, `includes/`, `database/`
- Validasi permission di setiap halaman (`require_perm()`)
