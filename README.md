# Sistem Manajemen Kost

Aplikasi web untuk mengelola kost (boarding house) yang dibangun dengan PHP dan MySQL. Aplikasi ini memungkinkan pengelola kost untuk mengelola data penghuni, kamar, tagihan, dan pembayaran dengan mudah.

## Fitur Utama

### Halaman Depan
- **Kamar Tersedia**: Menampilkan kamar yang masih kosong beserta harga sewanya
- **Pembayaran Mendatang**: Menampilkan kamar yang sebentar lagi harus bayar (7 hari ke depan)
- **Pembayaran Terlambat**: Menampilkan kamar yang terlambat bayar (lebih dari 30 hari)
- **Statistik Singkat**: Overview jumlah kamar tersedia, pembayaran mendatang, dan terlambat

### Admin Panel
- **Dashboard**: Overview statistik dan aktivitas terbaru
- **Data Penghuni**: CRUD operasi untuk mengelola data penghuni kost
- **Data Kamar**: CRUD operasi untuk mengelola data kamar dan harga sewa
- **Data Barang**: Mengelola barang tambahan yang dikenai biaya
- **Penempatan Kamar**: Mengatur penempatan penghuni ke kamar tertentu
- **Barang Bawaan**: Mengatur barang apa saja yang dibawa oleh setiap penghuni
- **Tagihan**: Melihat dan mengelola tagihan bulanan
- **Pembayaran**: Mencatat pembayaran dari penghuni
- **Generate Tagihan**: Membuat tagihan otomatis untuk semua penghuni aktif

## Struktur Database

### Tabel Utama
1. **tb_penghuni**: Data penghuni kost (nama, KTP, HP, tanggal masuk/keluar)
2. **tb_kamar**: Data kamar (nomor, harga sewa)
3. **tb_barang**: Data barang tambahan (nama, harga)
4. **tb_kmr_penghuni**: Penempatan penghuni ke kamar (relasi many-to-many)
5. **tb_brng_bawaan**: Barang yang dibawa penghuni (relasi many-to-many)
6. **tb_tagihan**: Tagihan bulanan (bulan, jumlah tagihan)
7. **tb_bayar**: Data pembayaran (jumlah bayar, status lunas/cicil)

## Instalasi

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- XAMPP/WAMP/MAMP (untuk development)

### Langkah Instalasi

1. **Clone atau download project**
   ```bash
   git clone [repository-url]
   cd UAS_30122007_MuhammadFakhri
   ```

2. **Setup Database**
   - Buka phpMyAdmin atau MySQL client
   - Import file `database.sql` untuk membuat database dan tabel
   - Database akan dibuat dengan nama `kost_management`

3. **Konfigurasi Database**
   - Edit file `config/database.php`
   - Sesuaikan host, username, password sesuai dengan konfigurasi MySQL Anda
   ```php
   $host = 'localhost';
   $dbname = 'kost_management';
   $username = 'root';  // sesuaikan dengan username MySQL Anda
   $password = '';      // sesuaikan dengan password MySQL Anda
   ```

4. **Akses Aplikasi**
   - Letakkan folder project di direktori web server (htdocs untuk XAMPP)
   - Buka browser dan akses: `http://localhost/UAS_30122007_MuhammadFakhri`

## Cara Penggunaan

### 1. Halaman Depan
- Akses `index.php` untuk melihat overview kost
- Informasi kamar tersedia, pembayaran mendatang, dan terlambat ditampilkan
- Klik "Admin Panel" untuk masuk ke sistem admin

### 2. Admin Panel
- Akses `admin/index.php` untuk dashboard admin
- Gunakan menu sidebar untuk navigasi ke berbagai fitur

### 3. Mengelola Data
- **Tambah Penghuni**: Masuk ke menu "Data Penghuni" → klik "Tambah Penghuni"
- **Tambah Kamar**: Masuk ke menu "Data Kamar" → klik "Tambah Kamar"
- **Penempatan**: Masuk ke menu "Penempatan Kamar" → klik "Tambah Penempatan"
- **Generate Tagihan**: Masuk ke menu "Generate Tagihan" → pilih bulan → klik "Generate Tagihan"
- **Input Pembayaran**: Masuk ke menu "Pembayaran" → klik "Tambah Pembayaran"

### 4. Workflow Operasional
1. Tambah data penghuni baru
2. Tambah data kamar (jika belum ada)
3. Lakukan penempatan penghuni ke kamar
4. Tambah barang bawaan (jika ada)
5. Generate tagihan bulanan
6. Input pembayaran dari penghuni

## Fitur Khusus

### Perhitungan Tagihan Otomatis
- Tagihan = Harga sewa kamar + Total harga barang bawaan
- Generate tagihan otomatis untuk semua penghuni aktif
- Sistem mencegah duplikasi tagihan untuk bulan yang sama

### Status Pembayaran
- **Lunas**: Total pembayaran >= jumlah tagihan
- **Cicil**: Total pembayaran > 0 tapi < jumlah tagihan
- **Belum Bayar**: Total pembayaran = 0

### Pindah Kamar
- Jika penghuni pindah kamar, isi tanggal keluar di penempatan lama
- Buat penempatan baru untuk kamar yang baru
- Tagihan akan dihitung berdasarkan penempatan aktif

### Keluar Kost
- Isi tanggal keluar di data penghuni
- Isi tanggal keluar di penempatan kamar
- Penghuni tidak akan muncul di generate tagihan berikutnya

## Keamanan

- Validasi input pada semua form
- Prepared statements untuk mencegah SQL injection
- Escape output untuk mencegah XSS
- Validasi file upload (jika ada)

## Troubleshooting

### Error Koneksi Database
- Pastikan MySQL server berjalan
- Periksa konfigurasi di `config/database.php`
- Pastikan database `kost_management` sudah dibuat

### Error Import Database
- Pastikan MySQL versi kompatibel
- Coba import manual melalui phpMyAdmin
- Periksa permission database user

### Halaman Tidak Muncul
- Pastikan web server (Apache) berjalan
- Periksa path file di browser
- Periksa error log web server

## Kontributor

- **Nama**: Muhammad Fakhri
- **NIM**: 30122007
- **Mata Kuliah**: UAS Pemrograman Web

## Lisensi

Project ini dibuat untuk keperluan akademis. Silakan digunakan dan dimodifikasi sesuai kebutuhan.

## Update Log

### v1.0.0 (2024)
- Initial release
- Fitur dasar manajemen kost
- Admin panel lengkap
- Generate tagihan otomatis
- Sistem pembayaran 