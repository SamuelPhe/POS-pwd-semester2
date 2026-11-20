# Point of Sale (POS) — Semester 2

Aplikasi kasir berbasis web untuk proyek perkuliahan pengembangan web.

## Fitur
- Login Admin dan Kasir.
- Dashboard berdasarkan peran pengguna.
- Pengelolaan produk dan kategori.
- Pemantauan serta penyesuaian stok inventaris.
- Pencatatan transaksi penjualan, pembayaran, dan kembalian.
- Riwayat dan detail transaksi.

## Teknologi
PHP, MySQL/MariaDB, MySQLi, HTML, CSS, dan JavaScript.

## Menjalankan secara lokal
1. Siapkan PHP 8.x, MySQL/MariaDB, dan web server lokal seperti XAMPP.
2. Import `database/schema.sql` ke MySQL/MariaDB.
3. Atur environment variable pada environment PHP/Apache: `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_DATABASE` (default `pos`).
4. Letakkan folder proyek di document root web server, lalu buka `login.php`.
5. Buat akun Admin sendiri. Hasilkan hash password dengan:
   ```bash
   php -r "echo password_hash('GANTI_DENGAN_PASSWORD_UNIK', PASSWORD_DEFAULT), PHP_EOL;"
   ```
   Masukkan hash hasilnya ke kolom `users.password` bersama username dan role `admin`.

## Catatan
Ini proyek akademik, bukan aplikasi siap produksi. Kode autentikasi dan beberapa endpoint telah dirapikan, tetapi perlindungan CSRF, validasi menyeluruh, konfigurasi session/cookie, serta pengujian transaksi masih perlu ditingkatkan. Tidak ada kredensial atau akun demo yang disertakan.
