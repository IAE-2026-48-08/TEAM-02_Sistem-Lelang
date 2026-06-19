# 🏷️ Sistem Lelang - Team 02

Sistem Lelang adalah aplikasi berbasis mikroservis (Microservices) yang dibangun menggunakan kerangka kerja Laravel. Sistem ini memisahkan fungsionalitas bisnis menjadi beberapa layanan independen untuk memastikan skalabilitas, kemudahan pemeliharaan, dan keandalan sistem.

---

## 🏗️ Arsitektur & Struktur Layanan

Proyek ini menggunakan **Nginx** sebagai API Gateway sentral yang merutekan semua permintaan klien ke masing-masing layanan backend[cite: 1]. Seluruh ekosistem diorkestrasikan menggunakan Docker Compose[cite: 1].

Sistem ini terdiri dari 3 layanan utama:

1. **Service Katalog (`/service-katalog`)**[cite: 1]
   * Menangani manajemen data barang/item yang akan dilelang.
   * Path Gateway: `http://localhost/katalog`

2. **Service Penawaran (`/service-penawaran`)**[cite: 1]
   * Menangani proses *bidding* (penawaran harga) dari peserta lelang.
   * Path Gateway: `http://localhost/penawaran`

3. **Service Winner (`/service-winner`)**[cite: 1]
   * Menangani penentuan pemenang lelang dan pembuatan *invoice* (tagihan).
   * Path Gateway: `http://localhost/winner`

*Catatan: Sistem ini juga terintegrasi dengan layanan SSO (Single Sign-On), RabbitMQ untuk antrean pesan (Message Broker), dan SOAP Audit Service*[cite: 1].

---

## 🚀 Prasyarat Sistem

Sebelum menjalankan proyek ini, pastikan sistem Anda telah terinstal:
* [Docker](https://www.docker.com/get-started)
* [Docker Compose](https://docs.docker.com/compose/install/)

---

## 🛠️ Cara Menjalankan Proyek (Deployment)

Ikuti langkah-langkah berikut untuk menjalankan seluruh layanan di mesin lokal Anda:

### 1. Konfigurasi Environment
Salin file `.env.example` menjadi `.env` di setiap direktori layanan[cite: 1]. Buka terminal dan jalankan:
```bash
# Copy env untuk Service Katalog
cp service-katalog/.env.example service-katalog/.env

# Copy env untuk Service Penawaran
cp service-penawaran/.env.example service-penawaran/.env

# Copy env untuk Service Winner
cp service-winner/.env.example service-winner/.env

```

Sesuaikan konfigurasi *environment* (seperti `DB_CONNECTION`, kredensial RabbitMQ, JWT, dll) di masing-masing file `.env` jika diperlukan.

### 2. Build dan Jalankan Container

Dari direktori utama (tempat file `docker-compose.yml` berada), jalankan perintah berikut untuk membangun dan menjalankan semua container:

```bash
docker-compose up -d --build

```

### 3. Migrasi Database & Seeding (Opsional)

Jika Anda menggunakan database SQLite atau relasional lainnya dan perlu melakukan migrasi, jalankan:

```bash
docker exec -it team02-katalog php artisan migrate --seed
docker exec -it team02-penawaran php artisan migrate --seed
docker exec -it team02-winner php artisan migrate --seed

```

---

## 📚 Dokumentasi API (Swagger)

Proyek ini menggunakan `l5-swagger` untuk dokumentasi API. Karena berjalan di balik API Gateway Nginx, Anda harus menghasilkan (generate) file dokumentasi secara manual di dalam container.

### Generate Swagger JSON

Jalankan perintah berikut di terminal root Anda:

```bash
docker exec -it team02-katalog php artisan l5-swagger:generate
docker exec -it team02-penawaran php artisan l5-swagger:generate
docker exec -it team02-winner php artisan l5-swagger:generate

```

### Akses Endpoint Swagger

Setelah di-generate, buka browser Anda dan akses rute berikut untuk melihat dan mencoba API:

* **Katalog API:** `http://localhost/katalog/api/documentation`
* **Penawaran API:** `http://localhost/penawaran/api/documentation`
* **Winner API:** `http://localhost/winner/api/documentation`

---
