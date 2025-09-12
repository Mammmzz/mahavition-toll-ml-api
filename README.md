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
- Git
- Flutter SDK (versi terbaru)
- Dart SDK (versi terbaru)
- Android Studio / VS Code

### Cara Clone Repository

#### Windows
1. Install Git dari [https://git-scm.com/download/win](https://git-scm.com/download/win)
2. Buka Command Prompt atau Git Bash
3. Clone repository:
   ```
   git clone https://github.com/Mammmzz/odik.git
   cd odik
   ```

#### Linux
1. Install Git (jika belum):
   ```
   sudo apt install git  # Ubuntu/Debian
   sudo yum install git  # CentOS/RHEL
   ```
2. Clone repository:
   ```
   git clone https://github.com/Mammmzz/odik.git
   cd odik
   ```

### Download Files yang Tidak Ada di GitHub
Beberapa file tidak disertakan dalam repository karena ukurannya yang besar atau karena alasan keamanan. Download dan siapkan file-file berikut:

1. YOLO Models (letakkan di direktori `plat_nomor_backend`):
   - [yolov8n.pt](https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.pt)
   - [license_plate_detector.pt](https://drive.google.com/uc?id=1YD1dzYOyR9SNGr3ve3T9BAf3W3wZSzXW)

2. Laravel Environment (.env) di folder `toll-api`:
   - Gunakan template dari file .env.example (petunjuk ada di bagian Backend Laravel)

3. Database:
   - Buat database kosong sesuai dengan nama yang dikonfigurasi di `.env`
   - Struktur database akan dibuat otomatis melalui migrasi Laravel

4. Media Assets (opsional, jika perlu untuk testing):
   - Letakkan file gambar plat nomor sample di folder `plat_nomor_backend/img/`
   - Atau gunakan webcam untuk testing langsung
   
### Aplikasi Python
1. Masuk ke direktori `plat_nomor_backend`
2. Buat virtual environment:
   ```
   python -m venv venv
   ```
3. Aktifkan virtual environment:
   - Windows: `venv\Scripts\activate`
   - Linux/Mac: `source venv/bin/activate`
4. Install dependencies:
   ```
   pip install -r requirements.txt
   ```
5. Jalankan aplikasi:
   ```
   python toll_gate_app.py
   ```

### Backend Laravel
1. Masuk ke direktori `toll-api`
2. Install dependencies:
   ```
   composer install
   ```
3. Salin `.env.example` ke `.env`:
   - Windows: `copy .env.example .env`
   - Linux/Mac: `cp .env.example .env`
4. Sesuaikan konfigurasi database di file `.env`
5. Generate key aplikasi:
   ```
   php artisan key:generate
   ```
6. Jalankan migrasi dan seeder:
   ```
   php artisan migrate:fresh --seed
   ```
7. Jalankan server:
   ```
   php artisan serve --port=8080
   ```

### Aplikasi Flutter
1. Pastikan Flutter SDK sudah terinstall ([panduan instalasi Flutter](https://flutter.dev/docs/get-started/install))
2. Masuk ke direktori root project
3. **PENTING**: Folder `lib` mungkin tidak ada karena masalah .gitignore. Download folder lib dari link berikut dan letakkan di root project:
   - [Download Flutter lib folder](https://drive.google.com/file/d/1Z9dIV3bpPgS1t_hH-UdW8s8ZBfLcYzG2/view?usp=sharing)
   - Atau clone ulang setelah menarik perubahan terbaru (yang sudah memperbaiki .gitignore)
4. Install dependencies:
   ```
   flutter pub get
   ```
5. Sesuaikan endpoint API di `lib/core/services/api_service.dart` sesuai dengan konfigurasi server Laravel Anda
6. Jalankan aplikasi:
   ```
   flutter run
   ```
   
   Atau buka project dengan Android Studio/VS Code dan jalankan dari sana.

## Pengembangan Lanjutan

- Integrasi dengan sistem pembayaran elektronik nyata
- Implementasi autentikasi dan otorisasi yang lebih kuat
- Peningkatan akurasi deteksi plat nomor untuk kondisi pencahayaan yang buruk
- Dashboard admin untuk monitoring dan manajemen
- Aplikasi mobile untuk pengguna