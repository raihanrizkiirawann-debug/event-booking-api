# Event Booking API

REST API untuk pengelolaan event dan pemesanan tiket yang dibangun menggunakan CodeIgniter 4, PHP, dan MySQL. Project ini menyediakan autentikasi pengguna, pengelolaan event, serta sistem booking tiket dengan validasi kuota untuk membantu menjaga konsistensi data.

## Daftar Isi

* [Fitur Utama](#fitur-utama)
* [Teknologi yang Digunakan](#teknologi-yang-digunakan)
* [Persyaratan Sistem](#persyaratan-sistem)
* [Struktur Direktori](#struktur-direktori)
* [Instalasi dan Konfigurasi](#instalasi-dan-konfigurasi)
* [Database Migration dan Seeder](#database-migration-dan-seeder)
* [Menjalankan Aplikasi](#menjalankan-aplikasi)
* [Dokumentasi Endpoint API](#dokumentasi-endpoint-api)
* [Contoh Request](#contoh-request)
* [Aturan Booking](#aturan-booking)
* [Pengujian API](#pengujian-api)
* [Akun Demo](#akun-demo)
* [Keputusan Teknis dan Trade-off](#keputusan-teknis-dan-trade-off)
* [Keamanan](#keamanan)
* [Deployment](#deployment)
* [Pengembangan Selanjutnya](#pengembangan-selanjutnya)
* [Pengembang](#pengembang)

## Fitur Utama

### Autentikasi dan Pengguna

* Registrasi dan login pengguna.
* Autentikasi menggunakan JSON Web Token (JWT).
* Password disimpan menggunakan hashing.
* Role-based access control untuk role `admin` dan `user`.
* Pembatasan percobaan login untuk membantu mengurangi risiko brute-force.

### Pengelolaan Event

* CRUD event khusus admin.
* Daftar event dengan pagination.
* Pencarian event berdasarkan judul.
* Filter event berdasarkan tanggal.
* Melihat detail event.
* Validasi tanggal dan kuota event.

### Pemesanan Tiket

* Membuat booking dengan batas 1–5 tiket per transaksi.
* Melihat booking milik pengguna yang sedang login.
* Membatalkan booking oleh pemilik booking.
* Validasi ketersediaan kuota sebelum pemesanan.
* Pengelolaan kuota menggunakan database transaction dan row locking.

### Penanganan Error

* Respons error dalam format JSON yang konsisten.
* Logging terstruktur untuk membantu proses debugging.
* Validasi request pada endpoint yang relevan.

## Teknologi yang Digunakan

| Teknologi        | Kegunaan                               |
| ---------------- | -------------------------------------- |
| PHP 8.2+         | Bahasa pemrograman backend             |
| CodeIgniter 4    | Framework REST API                     |
| MySQL / MariaDB  | Database relasional                    |
| Composer         | Manajemen dependency PHP               |
| firebase/php-jwt | Pembuatan dan validasi JWT             |
| Postman          | Pengujian endpoint API                 |
| Git dan GitHub   | Version control dan repository project |

## Persyaratan Sistem

Sebelum menjalankan project, pastikan perangkat telah memiliki:

* PHP 8.2 atau lebih baru.
* Composer.
* MySQL atau MariaDB.
* Ekstensi PHP yang dibutuhkan CodeIgniter 4, termasuk `intl` dan `mbstring`.
* Ekstensi database MySQLi.
* Git (opsional, untuk clone repository).

## Struktur Direktori

Struktur direktori utama project:

```text
event-booking-api/
├── app/
│   ├── Config/
│   ├── Controllers/
│   ├── Models/
│   └── ...
├── public/
│   └── index.php
├── writable/
├── tests/
├── vendor/
├── .env.example
├── composer.json
├── composer.lock
├── spark
└── README.md
```

Keterangan:

* `app/`: kode utama aplikasi, termasuk controller, model, konfigurasi, dan logika bisnis.
* `public/`: direktori publik yang menjadi entry point aplikasi.
* `writable/`: direktori untuk kebutuhan cache, log, dan file sementara.
* `tests/`: tempat pengujian otomatis jika tersedia.
* `vendor/`: dependency yang dipasang melalui Composer dan umumnya tidak perlu di-commit.
* `.env.example`: contoh konfigurasi environment tanpa kredensial rahasia.
* `composer.json`: deklarasi dependency dan konfigurasi Composer.
* `spark`: CLI bawaan CodeIgniter 4.
* `README.md`: dokumentasi project.

Struktur aktual dapat berbeda sesuai implementasi repository.

## Instalasi dan Konfigurasi

### 1. Clone Repository

```bash
git clone https://github.com/raihanrizkiirawann-debug/event-booking-api.git
cd event-booking-api
```

### 2. Install Dependency

```bash
composer install
```

Perintah tersebut akan memasang dependency yang dibutuhkan aplikasi berdasarkan konfigurasi Composer.

### 3. Konfigurasi Environment

Buat file `.env` di root project. Jika tersedia, salin `.env.example` sebagai dasar konfigurasi.

Contoh konfigurasi environment lokal:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'
```

Jangan mengunggah file `.env` yang berisi kredensial atau secret ke repository publik.

### 4. Konfigurasi Database

Buat database MySQL dengan nama `event_booking` melalui phpMyAdmin atau MySQL.

Tambahkan konfigurasi berikut ke file `.env`:

```dotenv
database.default.hostname = localhost
database.default.database = event_booking
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Sesuaikan username dan password dengan konfigurasi MySQL pada perangkat masing-masing.

### 5. Konfigurasi JWT

Tambahkan secret JWT yang kuat dan unik ke environment sesuai nama variabel yang digunakan oleh implementasi aplikasi.

Contoh nama variabel:

```dotenv
JWT_SECRET_KEY = 'GANTI_DENGAN_SECRET_UNIK_YANG_KUAT'
```

Pastikan nama variabel tersebut sesuai dengan kode autentikasi pada project. Jangan gunakan contoh di atas sebagai secret production.

## Database Migration dan Seeder

Setelah database dibuat dan konfigurasi selesai, jalankan migration:

```bash
php spark migrate
```

Migration digunakan untuk membuat struktur tabel yang didefinisikan oleh project.

Jika `AdminSeeder` tersedia dan sudah dikonfigurasi, jalankan:

```bash
php spark db:seed AdminSeeder
```

Seeder digunakan untuk menyiapkan data awal, termasuk akun admin demo jika memang diimplementasikan di dalamnya.

Pastikan migration dan seeder berhasil dijalankan sebelum melakukan pengujian endpoint.

## Menjalankan Aplikasi

Jalankan server development CodeIgniter:

```bash
php spark serve
```

Secara default, aplikasi dapat diakses melalui:

```text
http://localhost:8080
```

Base URL tersebut digunakan untuk pengujian lokal. URL hosting publik dicantumkan pada bagian informasi deployment setelah layanan benar-benar tersedia.

## Dokumentasi Endpoint API

Endpoint berikut merupakan daftar endpoint yang didokumentasikan untuk project ini. Pastikan route aktual pada repository sesuai dengan tabel berikut.

### 1. Authentication

| Method | Endpoint        | Akses  | Keterangan                |
| ------ | --------------- | ------ | ------------------------- |
| POST   | `/api/register` | Publik | Registrasi pengguna       |
| POST   | `/api/login`    | Publik | Login dan mendapatkan JWT |

### 2. Events

| Method | Endpoint           | Akses  | Keterangan        |
| ------ | ------------------ | ------ | ----------------- |
| GET    | `/api/events`      | Publik | Daftar event      |
| GET    | `/api/events/{id}` | Publik | Detail event      |
| POST   | `/api/events`      | Admin  | Membuat event     |
| PUT    | `/api/events/{id}` | Admin  | Memperbarui event |
| DELETE | `/api/events/{id}` | Admin  | Menghapus event   |

Daftar event mendukung pagination, pencarian judul, dan filter tanggal sesuai parameter yang diimplementasikan pada controller.

### 3. Bookings

| Method | Endpoint             | Akses           | Keterangan                    |
| ------ | -------------------- | --------------- | ----------------------------- |
| GET    | `/api/bookings`      | User login      | Melihat booking milik sendiri |
| POST   | `/api/bookings`      | User login      | Membuat booking               |
| DELETE | `/api/bookings/{id}` | Pemilik booking | Membatalkan booking           |

### Autentikasi Endpoint

Untuk endpoint yang membutuhkan autentikasi, kirim JWT pada header request:

```http
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

Token diperoleh dari endpoint login. Endpoint admin harus memvalidasi role pengguna, sedangkan endpoint booking harus memastikan pengguna hanya dapat mengakses booking yang menjadi haknya.

## Contoh Request

Contoh berikut menggunakan format JSON. Sesuaikan nama field dan bentuk respons dengan implementasi aktual API.

### 1. Registrasi Pengguna

`POST /api/register`

```json
{
  "name": "Test User",
  "email": "user@example.com",
  "password": "Password123!"
}
```

### 2. Login

`POST /api/login`

```json
{
  "email": "user@example.com",
  "password": "Password123!"
}
```

Gunakan JWT yang diperoleh dari respons login untuk mengakses endpoint yang membutuhkan autentikasi.

### 3. Membuat Event

`POST /api/events`

Akses: Admin.

```json
{
  "title": "Konser Musik",
  "description": "Konser musik akhir tahun",
  "event_date": "2026-12-21 19:00:00",
  "location": "Jakarta",
  "quota": 100
}
```

### 4. Membuat Booking

`POST /api/bookings`

Akses: User yang sudah login.

```json
{
  "event_id": 1,
  "quantity": 2
}
```

Server perlu memvalidasi event, jumlah tiket, tanggal event, dan ketersediaan kuota sebelum menyimpan booking.

### 5. Melihat Daftar Event

`GET /api/events`

Contoh parameter query untuk pagination atau pencarian dapat disesuaikan dengan nama parameter yang benar-benar didukung controller.

Contoh konseptual:

```http
GET /api/events?page=1&search=konser
```

### 6. Membatalkan Booking

`DELETE /api/bookings/1`

Akses: Pemilik booking yang sudah login.

Server harus memastikan booking tersebut dimiliki oleh pengguna yang mengirim request sebelum memproses pembatalan.

## Aturan Booking

Aturan bisnis pemesanan tiket yang didokumentasikan pada project:

1. Event harus memiliki tanggal di masa depan saat booking dibuat.
2. Jumlah tiket dalam satu booking dibatasi antara 1 dan 5 tiket.
3. Booking ditolak apabila kuota yang tersedia tidak mencukupi.
4. Perubahan kuota dilakukan dalam transaksi database.
5. Row locking digunakan untuk membantu menjaga konsistensi kuota ketika terdapat pemesanan bersamaan.
6. Pengguna hanya dapat melihat booking miliknya sendiri.
7. Pengguna hanya dapat membatalkan booking miliknya sendiri.
8. Booking yang sudah dibatalkan tidak dapat dibatalkan kembali.
9. Pembatalan booking mengembalikan kuota tiket sesuai aturan implementasi.

## Pengujian API

Pengujian API dapat dilakukan menggunakan Postman.

### Pengujian Manual

1. Jalankan aplikasi dan pastikan database terhubung.
2. Registrasikan pengguna baru.
3. Login dan ambil JWT dari respons.
4. Uji endpoint daftar event dan detail event.
5. Uji endpoint admin menggunakan token akun admin.
6. Uji pembuatan booking menggunakan token user.
7. Uji pembatalan booking oleh pemilik booking.
8. Uji validasi jumlah tiket, tanggal event, dan kuota yang tidak mencukupi.
9. Uji akses tanpa token, token tidak valid, serta akses role yang tidak sesuai.

Periksa status HTTP dan response JSON pada setiap request.

### Pengujian Otomatis

Jika test suite PHPUnit telah tersedia dan dikonfigurasi, jalankan perintah yang sesuai dengan repository, misalnya:

```bash
vendor/bin/phpunit
```

Jika `composer.json` menyediakan script pengujian, gunakan script tersebut.

Jangan menganggap test otomatis telah tersedia atau berhasil sebelum benar-benar menjalankannya.

## Akun Demo

Akun demo digunakan untuk membantu proses pengujian fitur admin dan user.

### Admin

* Email: sesuaikan dengan `AdminSeeder`.
* Password: sesuaikan dengan kredensial yang ditetapkan seeder.

### User

Buat akun melalui endpoint registrasi atau gunakan akun demo yang memang disediakan.

**Catatan keamanan:** jangan mencantumkan password palsu atau kredensial production. Jika tidak ada akun demo yang aman untuk deployment publik, sediakan langkah pembuatan akun demo di lingkungan lokal saja.

## Keputusan Teknis dan Trade-off

### 1. Pencegahan Overselling

Pemesanan tiket memerlukan perlindungan terhadap kondisi ketika beberapa pengguna mencoba membeli tiket yang sama secara bersamaan.

Project menggunakan database transaction dan row locking untuk membantu menjaga konsistensi kuota.

Secara umum, alur yang perlu dijaga adalah:

1. Memvalidasi request.
2. Memulai transaksi database.
3. Mengunci baris event yang relevan.
4. Memeriksa ketersediaan kuota berdasarkan data terbaru.
5. Menyimpan booking dan memperbarui kuota.
6. Melakukan commit jika seluruh operasi berhasil atau rollback jika terjadi kegagalan.

**Trade-off:** row locking membantu mencegah dua transaksi menggunakan kuota yang sama, tetapi permintaan untuk event yang sama dapat saling menunggu. Transaksi perlu dijaga tetap singkat. Implementasi juga bergantung pada storage engine yang mendukung transaksi dan row locking, seperti InnoDB pada MySQL.

### 2. JWT dan Role-Based Access Control

JWT digunakan untuk autentikasi request, sementara role digunakan untuk membatasi akses terhadap fitur admin dan user.

**Trade-off:** autentikasi berbasis token memerlukan pengelolaan secret, masa berlaku token, dan strategi pencabutan akses yang sesuai dengan kebutuhan aplikasi.

### 3. Validasi Booking

Validasi jumlah tiket, tanggal event, dan ketersediaan kuota dilakukan di sisi server agar aturan bisnis tidak hanya bergantung pada client.

**Trade-off:** validasi tambahan meningkatkan pekerjaan pemrosesan request, tetapi membantu menjaga integritas data dan mengurangi pemesanan yang tidak valid.

### 4. Respons JSON dan Logging

Format respons JSON yang konsisten membantu client memproses hasil request. Logging membantu proses investigasi ketika terjadi error.

**Trade-off:** log perlu dikelola agar tidak menyimpan password, JWT, secret, atau informasi sensitif lainnya.

## Keamanan

Beberapa praktik keamanan yang perlu diterapkan:

* Password disimpan menggunakan hashing yang aman.
* Secret JWT disimpan melalui environment variable.
* File `.env` tidak diunggah ke repository publik.
* Endpoint admin dilindungi autentikasi dan pemeriksaan role.
* Akses booking dibatasi berdasarkan kepemilikan data.
* Input divalidasi pada sisi server.
* Error yang dikirim kepada client tidak membocorkan detail internal.
* Deployment production menggunakan HTTPS.
* Kredensial demo tidak digunakan untuk akun production.

## Deployment

Project ini membutuhkan lingkungan hosting yang mendukung PHP dan CodeIgniter 4 serta menyediakan akses ke database MySQL atau MariaDB.

Untuk deployment:

1. Siapkan hosting yang mendukung versi PHP dan ekstensi yang diperlukan.
2. Konfigurasikan database production.
3. Pasang dependency menggunakan Composer.
4. Atur environment variable dan secret JWT.
5. Jalankan migration sesuai prosedur deployment.
6. Arahkan document root web server ke direktori `public`.
7. Pastikan mode environment production digunakan.
8. Uji endpoint dan konfigurasi keamanan setelah deployment.

### Informasi Deployment

* Repository GitHub: https://github.com/raihanrizkiirawann-debug/event-booking-api
* Base URL API: **Isi dengan URL hosting yang aktif dan telah diuji.**
* Endpoint daftar event: **Isi dengan URL endpoint publik yang berhasil diuji.**

Jika hosting menggunakan subdirektori atau memerlukan `index.php` dalam URL, cantumkan URL sesuai konfigurasi aktual server.

## Pengembangan Selanjutnya

Beberapa peningkatan yang dapat dipertimbangkan:

* Menambah automated test untuk autentikasi, otorisasi, booking, dan pembatalan.
* Menambahkan pengujian concurrency untuk memvalidasi pencegahan overselling.
* Menyediakan dokumentasi interaktif melalui Swagger/OpenAPI.
* Menambahkan rate limiting yang konsisten pada endpoint sensitif.
* Meningkatkan logging, monitoring, dan penanganan error.
* Menambahkan pipeline CI/CD untuk menjalankan test secara otomatis.
* Melakukan pengujian beban pada endpoint yang sering digunakan.
* Mengevaluasi kebutuhan pagination, indeks database, dan optimasi query.

## Kontribusi

Perbaikan dan saran pengembangan dapat diajukan melalui issue atau pull request pada repository GitHub. Setiap perubahan sebaiknya diuji terlebih dahulu agar tidak merusak fitur yang sudah tersedia.

## Lisensi

Lisensi project mengikuti ketentuan yang ditetapkan oleh pemilik repository. Tambahkan file `LICENSE` jika project akan didistribusikan dengan lisensi tertentu.

## Pengembang

**Raihan Rizki Irawan**

* GitHub: https://github.com/raihanrizkiirawann-debug
* Repository: https://github.com/raihanrizkiirawann-debug/event-booking-api
