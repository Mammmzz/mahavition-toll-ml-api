# E-Toll Gate System - Automatic License Plate Recognition

Sistem gerbang tol otomatis dengan pengenalan plat nomor dan klasifikasi kendaraan secara real-time.

## Fitur Utama

- Deteksi plat nomor kendaraan otomatis menggunakan YOLO dan EasyOCR
- Klasifikasi jenis kendaraan (Mobil, Bus, Truk)
- Integrasi dengan API untuk validasi dan transaksi
- Mendukung input dari gambar, video, dan webcam
- Tampilan UI modern dengan animasi
- Sistem tracking kendaraan
- Pencegahan transaksi duplikat
- Simulasi palang pintu tol
- Pemilihan webcam (jika tersedia lebih dari satu)
- Statistik dan histori transaksi

## Komponen Sistem

### 1. Aplikasi Python (toll_gate_app.py)
- Antarmuka pengguna dengan Tkinter
- Deteksi plat nomor dengan YOLO dan EasyOCR
- Klasifikasi kendaraan dengan YOLOv8
- Integrasi dengan API backend

### 2. Backend Laravel API (toll-api)
- Manajemen pengguna dan saldo
- Validasi plat nomor
- Pencatatan transaksi
- Pengelolaan tarif berdasarkan jenis kendaraan

## Teknologi yang Digunakan

- **Python**: Bahasa pemrograman utama untuk aplikasi client-side
- **Tkinter**: Library GUI untuk Python
- **OpenCV**: Pemrosesan gambar dan video
- **YOLO (Ultralytics)**: Deteksi objek untuk plat nomor dan kendaraan
- **EasyOCR**: Pengenalan karakter pada plat nomor
- **Laravel**: Framework PHP untuk backend API
- **MySQL**: Database untuk menyimpan data pengguna dan transaksi

## Instalasi dan Penggunaan

### Persyaratan
- Python 3.8+
- PHP 8.1+
- MySQL/MariaDB
- LAMPP/XAMPP (untuk pengembangan lokal)

### Aplikasi Python
1. Clone repository
2. Masuk ke direktori `plat_nomor_backend`
3. Buat virtual environment: `python -m venv venv`
4. Aktifkan virtual environment:
   - Windows: `venv\Scripts\activate`
   - Linux/Mac: `source venv/bin/activate`
5. Install dependencies: `pip install -r requirements.txt`
6. Jalankan aplikasi: `python toll_gate_app.py`

### Backend Laravel
1. Masuk ke direktori `toll-api`
2. Install dependencies: `composer install`
3. Salin `.env.example` ke `.env` dan sesuaikan konfigurasi database
4. Generate key aplikasi: `php artisan key:generate`
5. Jalankan migrasi dan seeder: `php artisan migrate:fresh --seed`
6. Jalankan server: `php artisan serve --port=8080`

## Pengembangan Lanjutan

- Integrasi dengan sistem pembayaran elektronik nyata
- Implementasi autentikasi dan otorisasi yang lebih kuat
- Peningkatan akurasi deteksi plat nomor untuk kondisi pencahayaan yang buruk
- Dashboard admin untuk monitoring dan manajemen
- Aplikasi mobile untuk pengguna