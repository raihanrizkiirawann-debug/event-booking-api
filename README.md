# Event Booking API

REST API untuk pengelolaan event dan pemesanan tiket, dibangun menggunakan CodeIgniter 4, PHP, dan MySQL.

## Fitur

* Registrasi dan login pengguna.
* Autentikasi menggunakan JSON Web Token (JWT).
* Password disimpan menggunakan hashing.
* Role-based access control: `admin` dan `user`.
* Pembatasan percobaan login untuk mengurangi brute-force.
* CRUD event khusus admin.
* Daftar event dengan pagination, pencarian judul, dan filter tanggal.
* Detail event.
* Pemesanan tiket dengan batas 1–5 tiket per transaksi.
* Pembatalan booking oleh pemilik booking.
* Pengelolaan kuota dengan database transaction dan row locking.
* Validasi tanggal event dan ketersediaan tiket.
* Respons error JSON yang konsisten.
* Logging terstruktur untuk membantu debugging.

## Teknologi

* PHP 8.2+
* CodeIgniter 4
* MySQL / MariaDB
* Composer
* JSON Web Token (`firebase/php-jwt`)
* Postman untuk pengujian API

## Persyaratan

Pastikan perangkat sudah memiliki:

* PHP 8.2 atau lebih baru.
* Composer.
* MySQL atau MariaDB.
* Ekstensi PHP yang dibutuhkan CodeIgniter 4, termasuk `intl` dan `mbstring`.
* Git (opsional, untuk mengelola source code).

## Instalasi

1. Clone repository:

   ```bash
   git clone https://github.com/raihanrizkiirawann-debug/event-booking-api.git
   cd event-booking-api
   ```

2. Instal dependency:

   ```bash
   composer install
   ```

3. Salin file konfigurasi contoh `env` menjadi `.env`:

   ```bash
   # Windows PowerShell
   Copy-Item env .env
   ```

4. Buat database MySQL bernama `event_booking`.

5. Atur `app.baseURL`, konfigurasi database, dan `JWT_SECRET_KEY` pada `.env`. Gunakan secret JWT yang kuat dan jangan pernah mengunggah `.env` ke repository.

6. Jalankan migration:

   ```bash
   php spark migrate
   ```

7. Jalankan seeder admin:

   ```bash
   php spark db:seed AdminSeeder
   ```

8. Jalankan server development:

   ```bash
   php spark serve
   ```

API lokal tersedia di `http://localhost:8080`.

> Pastikan file `env`, nama seeder, konfigurasi database, dan variabel JWT sesuai dengan source code project sebelum mengikuti instruksi ini.

## Autentikasi

Endpoint yang membutuhkan autentikasi menggunakan header:

```http
Authorization: Bearer <JWT_TOKEN>
```

Login menghasilkan token yang digunakan untuk mengakses endpoint terproteksi. Endpoint pengelolaan event hanya dapat diakses oleh admin.

## Endpoint API

Base URL lokal: `http://localhost:8080`

### Auth

| Method | Endpoint        | Keterangan                |
| ------ | --------------- | ------------------------- |
| POST   | `/api/register` | Registrasi user           |
| POST   | `/api/login`    | Login dan mendapatkan JWT |

### Events

| Method | Endpoint           | Akses  | Keterangan        |
| ------ | ------------------ | ------ | ----------------- |
| GET    | `/api/events`      | Publik | Daftar event      |
| GET    | `/api/events/{id}` | Publik | Detail event      |
| POST   | `/api/events`      | Admin  | Membuat event     |
| PUT    | `/api/events/{id}` | Admin  | Memperbarui event |
| DELETE | `/api/events/{id}` | Admin  | Menghapus event   |

Daftar event mendukung pagination, pencarian judul, dan filter tanggal sesuai parameter yang diimplementasikan pada controller.

### Bookings

| Method | Endpoint             | Akses           | Keterangan                    |
| ------ | -------------------- | --------------- | ----------------------------- |
| GET    | `/api/bookings`      | User login      | Melihat booking milik sendiri |
| POST   | `/api/bookings`      | User login      | Membuat booking               |
| DELETE | `/api/bookings/{id}` | Pemilik booking | Membatalkan booking           |

### Format request

Registrasi:

```json
{
  "name": "Test User",
  "email": "user@example.com",
  "password": "Password123!"
}
```

Login:

```json
{
  "email": "user@example.com",
  "password": "Password123!"
}
```

Membuat event (admin):

```json
{
  "title": "Konser Musik",
  "description": "Konser musik akhir tahun",
  "event_date": "2026-12-21 19:00:00",
  "location": "Jakarta",
  "quota": 100
}
```

Membuat booking (user login):

```json
{
  "event_id": 1,
  "quantity": 2
}
```

## Aturan Booking

* Event harus memiliki tanggal di masa depan saat booking dibuat.
* Jumlah tiket per booking adalah 1 sampai 5.
* Booking ditolak jika kuota tidak mencukupi.
* Kuota diperbarui dalam transaksi database dengan row locking untuk membantu mencegah overselling.
* User hanya dapat melihat booking miliknya dan membatalkan booking miliknya sendiri.
* Booking yang sudah dibatalkan tidak dapat dibatalkan kembali.
* Pembatalan booking mengembalikan kuota tiket.

## Pengujian API

API dapat diuji menggunakan Postman. Buat request sesuai tabel endpoint di atas, lalu gunakan token JWT hasil login untuk request yang membutuhkan autentikasi.

Periksa status HTTP dan response JSON untuk memastikan operasi berhasil atau gagal sesuai aturan validasi dan akses.

## Keamanan

* Jangan commit file `.env`, kredensial database, JWT secret, atau token autentikasi.
* Gunakan secret yang berbeda untuk setiap environment.
* Jangan gunakan kredensial admin contoh pada deployment publik.
* Untuk deployment, arahkan document root web server ke folder `public`.

## Pengembang

Repository: [event-booking-api](https://github.com/raihanrizkiirawann-debug/event-booking-api)
