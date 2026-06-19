# Sistem Lelang Service Penawaran

## Fitur Utama

* **RESTful API Standar Kontrak:** Menyediakan 3 *endpoint* utama dengan format *response wrapper* JSON yang konsisten.
* **Header Authentication:** Semua *endpoint* dilindungi menggunakan API Key khusus (`X-IAE-KEY`).
* **Interactive API Documentation:** Dilengkapi dengan Swagger UI (OpenAPI 3.0) menggunakan PHP Attributes terbaru.
* **GraphQL Implementation:** Mendukung kueri GraphQL yang dinamis beserta antarmuka GraphQL Playground untuk pengujian.
* **Dockerized Environment:** Berjalan secara terisolasi menggunakan Docker dan SQLite untuk kemudahan pengujian.

---

## Library & Teknologi

| Komponen | Teknologi / Library |
| :--- | :--- |
| **Framework** | Laravel 11 (PHP 8.2) |
| **Database** | SQLite (Bawaan) |
| **API Documentation** | `darkaonline/l5-swagger` |
| **GraphQL Engine** | `nuwave/lighthouse` |
| **GraphQL UI** | `mll-lab/laravel-graphql-playground` |

---

## Syarat Sistem

* **Docker Engine / Docker Desktop** (Berjalan di latar belakang)
* **Terminal / Windows PowerShell**
* **Git** (Opsional, untuk proses *clone*)

---

## Instalasi Lokal

**1. Clone repository dan masuk ke direktori proyek:**
```powershell
git clone <https://github.com/IAE-2026-48-08/102022400212_lelang-penawaran.git>
cd 102022400212_Penawaran-Service

```

**2. Build dan jalankan container Docker:**

```powershell
docker compose up --build -d

```

**3. Install dependensi PHP via Composer:**

```powershell
docker compose run app composer install

```

**4. Generate Application Key:**

```powershell
docker compose run app php artisan key:generate

```

**5. Siapkan Database SQLite:**

```powershell
# Khusus pengguna Windows PowerShell:
New-Item database/database.sqlite -ItemType File

```

**6. Jalankan Migrasi Database:**

```powershell
docker compose run app php artisan migrate

```

> **Info:** Setelah seluruh proses selesai, API sudah siap digunakan di `http://localhost:8000`.

---

## Dokumentasi & Penggunaan API

### Autentikasi

Seluruh *endpoint* REST maupun GraphQL dilindungi. Anda **wajib** menyertakan *header* berikut pada setiap *request*:

* **Key:** `X-IAE-KEY`
* **Value:** `102022400212`

### Swagger UI (REST API)

Akses antarmuka dokumentasi Swagger untuk menguji *endpoint* REST secara langsung.

* **URL:** `http://localhost:8000/api/documentation`

**Endpoint REST yang tersedia:**

1. `GET /api/v1/bids` (Melihat daftar semua penawaran)
2. `GET /api/v1/bids/{id}` (Melihat detail penawaran spesifik)
3. `POST /api/v1/bids` (Mengajukan penawaran baru dengan *payload* `item_id` dan `bid_amount`)

Jika Berhasil :
<img width="1919" height="987" alt="image" src="https://github.com/user-attachments/assets/4559ffdf-4dd7-452d-8592-732e352688ba" />
<img width="1431" height="678" alt="image" src="https://github.com/user-attachments/assets/7c91222c-db62-400c-afb5-aa9799438f38" />
<img width="1413" height="827" alt="image" src="https://github.com/user-attachments/assets/2b353463-ef2d-49a2-981d-16b40d4d693e" />
<img width="1432" height="245" alt="image" src="https://github.com/user-attachments/assets/cc747a29-1e82-4e6a-b653-6544fb1ef177" />
<img width="1410" height="887" alt="image" src="https://github.com/user-attachments/assets/12618c88-d113-47e8-bad0-7c6e24a2aaa8" />
<img width="1418" height="342" alt="image" src="https://github.com/user-attachments/assets/7c7ddf26-59f8-4b9a-85b8-ff80c4fbc783" />
<img width="1416" height="764" alt="image" src="https://github.com/user-attachments/assets/aa77e084-ddc3-4a71-bc65-52f13c166eff" />

Jika Gagal :
<img width="1415" height="798" alt="image" src="https://github.com/user-attachments/assets/d5811aad-89e6-4181-8eb7-62760eb980ac" />

### GraphQL Playground

Akses antarmuka Playground untuk melakukan kueri data penawaran secara dinamis.

* **URL:** `http://localhost:8000/graphql-playground`

**Cara Pengujian:**

1. Klik tombol **HTTP HEADERS** di pojok kiri bawah Playground.
2. Masukkan header keamanan:
```json
{
  "X-IAE-KEY": "102022400212"
}

```


3. Gunakan kueri berikut di panel kiri untuk mengambil data lelang:
```graphql
query {
  bids {
    id
    item_id
    bid_amount
    status
  }
}

```
Jika Berhasil :
<img width="1919" height="990" alt="image" src="https://github.com/user-attachments/assets/9cdffb61-7458-48f8-a495-6dcc727462c3" />

Jika Gagal : 
<img width="1919" height="986" alt="image" src="https://github.com/user-attachments/assets/aea30033-30e9-4250-847d-3d3843255be1" />


4. Gunakan kueri berikut di panel kiri untuk mengambil data lelang spesifik:
```graphql
query {
  bid(id: 3) {
    id
    item_id
    bid_amount
    status
    user_id
  }
}

```
Jika Berhasil :
<img width="1919" height="994" alt="image" src="https://github.com/user-attachments/assets/70abd81d-8445-4469-a617-9a40073661af" />

Jika Gagal :
<img width="1919" height="989" alt="image" src="https://github.com/user-attachments/assets/4a4a97ff-d72f-4b72-be94-fc3e711bd826" />
