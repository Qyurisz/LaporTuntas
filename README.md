# Lapor Tuntas

**Lapor Tuntas** adalah aplikasi web pelaporan masalah fasilitas umum berbasis peta interaktif (*map-first*) dan *mobile-first* yang memudahkan warga melapor secara cepat tanpa perlu registrasi atau login. Setiap laporan menghasilkan ID tiket unik lengkap dengan QR Code sehingga warga dapat memantau progres penanganan oleh instansi terkait secara transparan dari awal hingga tuntas.

---

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13
- **Frontend:** Blade Templates, Tailwind CSS v4, Alpine.js 3, Vite 8
- **Peta & Geospasial:** Leaflet.js, Leaflet.MarkerCluster, OpenStreetMap / Esri World Imagery / CARTO Tile Layers, Nominatim Reverse Geocoding, HTML5 Geolocation API
- **Database:** MySQL 8.0 (serta SQLite *in-memory* untuk pengujian otomatis)
- **Utilitas & Pengujian:** `qrcode` (unduh kartu tiket PNG ber-QR), HTML5 Canvas (kompresi foto otomatis di browser), PHPUnit 12

---

## Cara Menjalankan Project Ini

### Prasyarat
- PHP >= 8.3 & Composer
- MySQL >= 8.0 (tahap pengembangan awal)
- Node.js & NPM

### Langkah Instalasi

1. **Clone repositori dan masuk ke folder project**
   ```bash
   git clone https://github.com/Qyurisz/LaporTuntas.git
   cd LaporTuntas
   ```

2. **Install dependensi PHP dan JavaScript**
   ```bash
   composer install
   npm install
   ```

3. **Salin file konfigurasi `.env` dan buat *application key***
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi koneksi database MySQL di file `.env`**
   Buat database bernama `lapor_tuntas` di MySQL, lalu sesuaikan bagian berikut pada `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lapor_tuntas
   DB_USERNAME=root
   DB_PASSWORD=password_mysql_anda
   ```

5. **Jalankan migrasi tabel dan *seeder* data master**
   ```bash
   php artisan migrate --seed
   ```

6. **Build aset frontend dan jalankan server lokal**
   ```bash
   npm run build
   php artisan serve
   ```
   *(Atau jalankan `composer run dev` untuk menyalakan server Laravel dan Vite secara bersamaan.)*

7. **Akses aplikasi di browser**
   - Halaman Utama Peta Warga: `http://localhost:8000`
   - Panel Petugas & Admin: `http://localhost:8000/petugas/login`
     - **Admin / Moderator:** `admin@laportuntas.id` | Password: `password`
     - **Petugas Lapangan:** `petugas.pu@laportuntas.id` | Password: `password`

---

## Progress Saat Ini

- [x] **Halaman Utama Peta Interaktif (*Map-First Landing Page*):** Tampilan peta jalan asli, citra satelit, dan mode terang berbasis titik GPS pengguna, filter jangkauan radius (`1 km`, `5 km`, `15 km`), filter kategori & semua status laporan (`Belum diproses`, `Ditugaskan`, `Diproses`, `Selesai`), *marker clustering*, kartu *callout* detail singkat, serta daftar laporan responsif di *desktop sidebar* dan *mobile drawer*.
- [x] **Formulir Pelaporan Warga Tanpa Login:** Modal pembuatan laporan cepat dengan fitur unggah 1–5 foto bukti (dikompresi otomatis di sisi klien), pemilihan kategori masalah, penentuan titik koordinat GPS presisi melalui peta mini (*draggable marker* & deteksi nama jalan otomatis), serta pembatasan pengiriman (*rate limiting*).
- [x] **Halaman Bukti Tiket & Kartu QR Code:** Pembuatan ID tiket unik non-sekuensial (`LT-XXXX-XXXX`), tombol salin tiket, cetak/PDF, serta unduh kartu tiket berformat PNG lengkap dengan QR Code pelacakan.
- [x] **Halaman Lacak Tiket Publik:** Pencarian progres laporan menggunakan ID tiket tanpa login, informasi instansi & petugas penangan, perbandingan foto bukti *Sebelum* dan *Sesudah* perbaikan, peta titik lokasi masalah, serta linimasa (*timeline*) riwayat penanganan secara transparan.
- [x] **Panel Operasional Admin & Petugas (RBAC):** Halaman autentikasi khusus staf, *dashboard* metrik ringkasan laporan, penugasan laporan ke instansi dan petugas lapangan (khusus Admin), pembaruan status penanganan beserta unggah foto bukti penyelesaian, serta fitur moderasi penolakan laporan tidak valid/spam.
- [x] **Integrasi Database MySQL & Pengujian Otomatis:** Struktur migrasi database relasional bersih tanpa data simulasi *hardcode*, *seeder* data master (instansi, kategori, akun RBAC), serta *feature test* alur pelaporan hingga tuntas (`php artisan test`).
