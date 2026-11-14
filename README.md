# E-Voucher — Monitor Voucher Asesi LSP

Aplikasi web untuk memantau dan mengelola voucher asesi: kuota, pemakaian, dan masa berlaku.
Data tersimpan di database MySQL dan **tersinkron otomatis** ke semua pengguna yang sedang membuka aplikasi
(diperbarui sekitar setiap 3 detik, tanpa perlu refresh).

**Stack:** Laravel 13 · PHP 8.3+ · MySQL/MariaDB · tanpa build Node (CSS/JS polos di `public/`).

## Halaman

| Halaman | Isi |
|---|---|
| **Beranda** (`/`) | Ringkasan: voucher aktif, total kuota, terpakai, sisa; daftar yang perlu perhatian; aktivitas terbaru; tabel semua voucher |
| **Kelola** (`/kelola`) | Tambah/edit/hapus voucher, catat & hapus pemakaian, cari & filter status, ekspor Excel |
| **Pengguna** (`/pengguna`) | Khusus admin: tambah pengguna, reset password, nonaktifkan/aktifkan, hapus pengguna |

Akun awal: **`admin` / `admin12345`**. Segera ganti lewat menu profil (kanan atas) → *Ganti password*.

## Struktur database

| Tabel | Isi |
|---|---|
| `users` | Akun pengguna (`name`, `username`, `avatar` foto profil, `password`, `is_admin`, `is_active`) |
| `vouchers` | Klien, kode, no. WhatsApp (`telepon`), kuota, tanggal mulai, masa berlaku (bulan), tanggal berakhir, catatan |
| `pemakaian` | Catatan per voucher: jenis (`pakai` / `koreksi`), tanggal, jumlah asesi, keterangan, dicatat oleh |
| `app_state` | Nomor revisi data, dipakai untuk sinkronisasi realtime |
| `sessions`, `cache`, `jobs`, … | Tabel bawaan Laravel |

Skema ada di `database/migrations/`. File `database/voucher_lsp.sql` berisi dump siap impor (struktur + akun admin); setelah impor jalankan `php artisan migrate --force` agar kolom terbaru ikut ditambahkan.

## Menjalankan di Laragon (lokal)

1. Pastikan Apache & MySQL Laragon menyala.
2. Database `voucher_lsp` sudah dibuat. Jika belum: `php artisan migrate --seed`
3. Buka **http://project.voucher.test**

## Upload ke hosting (cPanel / shared hosting)

1. **Siapkan file.** Di komputer lokal jalankan:
   ```
   composer install --no-dev --optimize-autoloader
   ```
   Lalu zip seluruh folder project (termasuk `vendor/`, kecuali `.env` dan `Monitor Voucher Asesi LSP.html`).

2. **Buat database** di cPanel → *MySQL Databases*: buat database dan user, lalu beri user tersebut akses *ALL PRIVILEGES*.

3. **Impor tabel** lewat phpMyAdmin → pilih database → *Import* → `database/voucher_lsp.sql`, lalu (via SSH) `php artisan migrate --force` untuk menambah kolom terbaru.
   (Atau, jika hosting punya SSH: `php artisan migrate --seed --force`.)

4. **Upload & ekstrak** zip ke folder di hosting. Pilih salah satu:
   - **Disarankan:** ekstrak ke `/home/USER/voucher` lalu arahkan document root domain/subdomain ke `/home/USER/voucher/public`.
   - **Jika document root tidak bisa diubah:** ekstrak langsung ke `public_html`. File `.htaccess` di root project otomatis meneruskan request ke `public/` dan memblokir akses ke `.env`, `vendor/`, dll.

5. **Buat file `.env`** di root project (salin dari `.env.example`) dan isi:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domain-anda.com
   APP_KEY=            # lihat langkah 6
   DB_HOST=localhost
   DB_DATABASE=nama_database
   DB_USERNAME=user_database
   DB_PASSWORD=password_database
   ```

6. **APP_KEY.** Dengan SSH: `php artisan key:generate`. Tanpa SSH: jalankan `php artisan key:generate --show` di lokal lalu tempel hasilnya ke `.env` hosting.

7. Pastikan folder `storage/` dan `bootstrap/cache/` bisa ditulis (permission 755/775).

8. (Opsional, via SSH) percepat aplikasi: `php artisan config:cache && php artisan route:cache && php artisan view:cache`

9. Buka domain, login dengan `admin` / `admin12345`, lalu **langsung ganti password**.

## Catatan

- Status voucher: **Over kuota** (pemakaian > kuota), **Habis**, **Kedaluwarsa**, **Kuota menipis**, **Segera berakhir**, **Aman**.
- **Notifikasi** (ikon lonceng & kartu Beranda) ada 2 kategori:
  - **Kuota**: sisa ≤ `VOUCHER_NOTIF_SISA` asesi (default 10) atau sudah over kuota.
  - **Masa berlaku**: berakhir dalam ≤ `VOUCHER_NOTIF_HARI` hari (default 30).
  Ubah angkanya di `.env`, lalu jalankan `php artisan config:clear`.
- **Update sisa** (halaman Kelola → detail voucher) ada 2 kategori:
  - **Sisa kuota**: isi sisa yang benar. Selisihnya dicatat sebagai baris *Koreksi* di riwayat.
  - **Masa berlaku**: ubah tanggal berakhir secara langsung.
- Masa berlaku default = tanggal mulai + N bulan − 1 hari. Tanggalnya bisa diubah manual di form voucher.
- **WhatsApp**: isi *No. WhatsApp* di form voucher (format 08xx / 8xx / +62 diterima). Menyimpan voucher **tidak** membuka WhatsApp; pesan info voucher (sisa kuota & masa berlaku terbaru) baru dibuat saat tombol **Kirim info WA** di detail voucher diklik.
- **Profil**: klik nama di kanan atas → *Profil saya* untuk ubah nama & upload foto (JPG/PNG/WEBP, maks. 2 MB). Foto disimpan di `public/uploads/avatars` (tidak ikut ke Git; folder harus bisa ditulis di hosting).
- **Testing**: `php artisan test` (memakai database MySQL `voucher_lsp_test`, buat dulu bila belum ada).
- Sinkronisasi memakai *polling* ringan: browser hanya menanyakan nomor revisi. Jika ada perubahan, yang dikirim hanya **ringkasan** tiap voucher (total terpakai & jumlah catatan) plus 8 aktivitas terbaru. Riwayat pemakaian lengkap tetap tersimpan di database, tapi baru diambil (`/data/riwayat`) saat sebuah voucher dibuka atau saat ekspor Excel. Cara ini berjalan di shared hosting tanpa layanan tambahan (WebSocket/Pusher).
- Ekspor Excel memuat library dari CDN, jadi butuh koneksi internet.
