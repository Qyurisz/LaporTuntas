# Lapor Tuntas

**Lapor Tuntas** adalah aplikasi web pelaporan masalah fasilitas umum berbasis peta interaktif (*map-first*) dan *mobile-first* yang memudahkan warga melapor secara cepat tanpa perlu registrasi atau login. Setiap laporan menghasilkan ID tiket unik lengkap dengan Kartu QR Code sehingga warga dapat memantau progres penanganan oleh instansi terkait secara transparan dari awal hingga tuntas.

- **Live Demo (Railway):** https://laportuntas-app-production.up.railway.app

---

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13
- **Frontend:** Blade Templates, Tailwind CSS v4, Alpine.js 3, Vite 8, PWA (`manifest.json` & Service Worker)
- **Peta & Geospasial:** Leaflet.js, Leaflet.MarkerCluster, OpenStreetMap / Esri World Imagery / CARTO Tile Layers, Nominatim Reverse Geocoding, HTML5 Geolocation API
- **Database:** MySQL 8.0 (serta SQLite *in-memory* untuk pengujian otomatis)
- **Utilitas & Pengujian:** `qrcode` (unduh kartu tiket PNG ber-QR), HTML5 Canvas (kompresi foto otomatis di browser), PHPUnit 12
- **Deployment:** Docker (`php:8.3-apache`), Railway Cloud (MySQL + Persistent Volumes)

---

## Fitur Utama & Progress Saat Ini

- [x] **Halaman Utama Peta Interaktif (*Map-First Landing Page*):** Tampilan *splash screen*, peta jalan asli, citra satelit, dan mode terang berbasis titik GPS pengguna, filter jangkauan radius (`500 m`, `1 km`, `5 km`), filter kategori & status laporan, *marker clustering*, kartu *callout* detail singkat, serta daftar laporan responsif di *desktop sidebar* dan *mobile drawer*.
- [x] **Formulir Pelaporan Warga Tanpa Login:** Modal pembuatan laporan cepat (< 1 menit) dengan unggah 1–5 foto bukti (kompresi otomatis di browser), pemilihan 7 kategori masalah, penentuan titik koordinat GPS presisi melalui peta mini (*draggable marker* & deteksi alamat otomatis), serta *rate limiting*.
- [x] **Deteksi Duplikat Radius 30 Meter & Galeri Dukungan Warga:** Sistem otomatis mendeteksi laporan aktif serupa dalam radius 30 meter agar tidak terjadi penumpukan tiket ganda. Warga dapat menekan **"Saya juga mengalami ini"** (1 dukungan per perangkat menggunakan `lt_device_token` dengan pencegahan *self-vote* pembuat laporan), menambahkan komentar, serta melampirkan foto bukti tambahan yang tampil di galeri halaman detail laporan.
- [x] **Halaman Bukti Tiket & Kartu QR Code:** Pembuatan ID tiket unik non-sekuensial (`LT-XXXX-XXXX`), tombol salin ID, cetak/PDF, serta unduh kartu tiket berformat PNG lengkap dengan QR Code pelacakan.
- [x] **Halaman Lacak Tiket Publik (`/lacak`):** Pencarian progres laporan menggunakan ID tiket tanpa login, informasi instansi & petugas penangan, perbandingan foto bukti *Sebelum* dan *Sesudah* perbaikan, galeri dukungan warga, peta lokasi, serta linimasa (*timeline*) riwayat status secara transparan. Laporan selesai otomatis disembunyikan dari peta setelah 7 hari, tetapi tetap tersimpan di arsip dan dapat dilacak melalui ID tiket.
- [x] **Panel Operasional Admin & Petugas Dinas (RBAC & SLA):** Autentikasi staf di `/staff/login`, dashboard terpisah untuk Admin (`/admin/dashboard`) dan masing-masing Petugas Dinas (`/staff/dashboard`), filter AJAX, penugasan laporan ke instansi (`DPU`, `DLH`, `DISHUB`, `DSDA`), pelacakan batas waktu penyelesaian (**SLA**) per kategori beserta indikator keterlambatan, kewajiban unggah foto bukti penyelesaian untuk status `Selesai`, penolakan laporan spam beserta alasan, serta **Ekspor CSV**.
- [x] **Pengujian Otomatis & Siap Kontainer:** Migrasi database relasional bersih, *seeder* data master (4 instansi, 7 kategori + SLA, 5 akun RBAC), suite pengujian fitur PHPUnit (`php artisan test`), serta konfigurasi kontainer produksi (`Dockerfile`, `docker/start.sh`, `railway.toml`).

---

## Cara Menjalankan di Lokal

### Prasyarat
- PHP >= 8.3 & Composer
- MySQL >= 8.0
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

5. **Buat *storage link*, jalankan migrasi, dan *seeder* data master**
   ```bash
   php artisan storage:link
   php artisan migrate --seed
   ```

6. **Build aset frontend dan jalankan server lokal**
   ```bash
   npm run build
   php artisan serve
   ```
   *(Atau jalankan `composer run dev` untuk menyalakan server Laravel dan Vite dev server secara bersamaan.)*

7. **Akses aplikasi di browser**
   - Halaman Utama Peta Warga: `http://localhost:8000`
   - Lacak Tiket Publik: `http://localhost:8000/lacak`
   - Login Admin & Petugas Dinas: `http://localhost:8000/staff/login`
     - **Admin / Moderator:** `admin@laportuntas.id` | Password: `password`
     - **Petugas Dinas Bina Marga & PU:** `petugas.pu@laportuntas.id` | Password: `password`
     - **Petugas Dinas Lingkungan Hidup (DLH):** `petugas.dlh@laportuntas.id` | Password: `password`
     - **Petugas Dinas Perhubungan & PJU (Dishub):** `petugas.dishub@laportuntas.id` | Password: `password`
     - **Petugas Dinas SDA & Drainase (DSDA):** `petugas.dsda@laportuntas.id` | Password: `password`

---

## Pengujian Otomatis

```bash
php artisan test
```

---

## Deployment Produksi (Docker & Railway)

Proyek ini sudah dilengkapi konfigurasi deployment produksi untuk Railway menggunakan:
- [Dockerfile](Dockerfile) — image `php:8.3-apache` lengkap dengan ekstensi PHP (`pdo_mysql`, `gd`, `exif`, `zip`, `opcache`), build aset Vite produksi, dan konfigurasi batas unggahan foto.
- [docker/start.sh](docker/start.sh) — menyiapkan direktori & permission `storage/app/public`, menunggu kesiapan MySQL, menjalankan `storage:link`, `migrate --force`, `db:seed --force`, optimasi cache Laravel, lalu menjalankan Apache.
- [railway.toml](railway.toml) — konfigurasi *healthcheck* `/up` dan *restart policy* `ON_FAILURE`.
- Dukungan *reverse proxy* HTTPS melalui `trustProxies(at: '*')` di [bootstrap/app.php](bootstrap/app.php) dan `URL::forceScheme('https')` pada environment `production` di [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php).
- **Persistent Volume** disarankan di-mount ke `/var/www/html/storage/app/public` agar file foto warga dan petugas tetap aman saat container *restart*, *redeploy*, maupun *App Sleeping*.
