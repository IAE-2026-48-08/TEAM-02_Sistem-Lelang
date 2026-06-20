# PROMPT 1

Berdasarkan pembagian jobdesk kelompok `TEAM-02_Sistem-Lelang`, saya bertanggung jawab atas `service-katalog`. Struktur repositori bersama mengharuskan saya menyalin file proyek saya ke dalam folder `service-katalog` dan menyediakan `Dockerfile` yang menjalankan server Laravel pada port internal 8000 (tidak dibuka langsung ke luar, melainkan dibungkus Nginx Gateway pada port 80). 

Variabel `.env.example` saya juga harus disesuaikan dengan parameter tim: 


- `IAE_API_KEY`: `102022400192` 

- `CENTRAL_SERVER_URL`: `https://iae-sso.virtualfri.id` 

- `CENTRAL_TEAM_API_KEY`: `KEY-MHS-226`


Tolong buatkan panduan pembuatan file `Dockerfile` tersebut beserta perintah Git untuk melakukan push ke cabang utama.

---

# AI RESPONSE

Berikut panduan lengkap pembuatan `Dockerfile`, penyesuaian `.env.example`, dan command Git untuk push:

---

`service-katalog/Dockerfile`

```
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

# Pastikan file SQLite ada sebelum migrate
RUN mkdir -p database && touch database/database.sqlite

RUN cp .env.example .env && php artisan key:generate
RUN php artisan migrate --force

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

Penjelasan poin penting:

| **Baris** | **Fungsi** | 
| :--- | :--- |
| `EXPOSE 8000` | Port ini hanya terlihat di dalam network Docker, tidak otomatis terbuka ke host | 
| `--host=0.0.0.0` | Wajib, supaya server bisa diakses dari container lain (termasuk Nginx) | 
| Port ini tidak dipetakan ke host di `docker-compose.yml` | Inilah yang membuat service tidak bisa diakses langsung dari luar, harus lewat Gateway |

`service-katalog/.env.example`

Tambahkan baris berikut di bagian bawah file:

```
IAE_API_KEY=102022400192
CENTRAL_SERVER_URL=https://iae-sso.virtualfri.id
CENTRAL_TEAM_API_KEY=KEY-MHS-226
```

---

Command Git untuk Push

```
git add service-katalog/
git commit -m "feat(katalog): add Dockerfile internal port 8000 dan update .env.example"
git push origin main
```

---

Verifikasi Setelah Push

Setelah teammate lain juga push bagian mereka, jalankan dari root repo gabungan:

```
docker compose up --build
```

Lalu pastikan hanya port 80 yang bisa diakses dari host:

```
curl http://localhost/katalog/api/v1/items -H "X-IAE-KEY: 102022400192"
```

Dan pastikan port 8000 langsung tidak bisa diakses (harus gagal connect):

```
curl http://localhost:8000/api/v1/items
```

---

# PROMPT 2

Berdasarkan rancangan ketua tim, pengujian fungsional bisnis end-to-end yang akan dinilai oleh dosen dijalankan dengan skenario alur yang menyambung lewat Nginx Gateway (Port 80) tanpa intervensi manual luar, yaitu:

1. `GET /katalog/api/v1/items` -> Melihat katalog barang lelang.

2. `POST /penawaran/api/v1/bids` -> Mengajukan penawaran harga (bidding).

3. `GET /winner/api/v1/winners` -> Memeriksa data pemenang lelang.

4. `POST /winner/api/v1/winners` -> Proses checkout & pembuatan invoice tagihan.


Tolong buatkan ringkasan penjelasan bagaimana kaitan service saya (`service-katalog`) mendukung kelancaran core business flow ini di dalam API Gateway.

---

# AI RESPONSE

Berikut ringkasan kaitan Service Katalog dalam mendukung core business flow lintas service:

---

**Posisi Service Katalog dalam Alur Bisnis**

Service Katalog berperan sebagai sumber data awal (source of truth) untuk seluruh siklus lelang. Tanpa data dari Katalog, service lain tidak punya konteks untuk beroperasi.

```
[1] GET /katalog/api/v1/items     →  Katalog menyediakan daftar barang
        ↓ (item_id digunakan)
[2] POST /penawaran/api/v1/bids   →  Penawaran memvalidasi item ke Katalog
        ↓ (data bid terkumpul)
[3] GET /winner/api/v1/winners    →  Winner menentukan pemenang
        ↓ (item_id dipakai lagi)
[4] POST /winner/api/v1/winners   →  Winner update status item ke Katalog jadi SOLD
```

---

**Detail Interaksi Internal**

Tahap 1 → 2: Penawaran Memvalidasi Item ke Katalog

Saat ada request `POST /penawaran/api/v1/bids` dengan `item_id`, Service Penawaran wajib memanggil Katalog secara internal (lewat Docker network, bukan lewat Gateway) untuk memastikan:

* Item dengan `item_id` tersebut benar-benar ada

* Status item masih `OPEN` (belum `SOLD`/`CLOSED`)

Pemanggilan internal antar container:

```
http://service-katalog:8000/api/v1/items/{item_id}
```

(Pakai nama service Docker, bukan localhost, karena ini komunikasi container-ke-container di dalam team02-network)

---

Tahap 3 → 4: Winner Update Status Item di Katalog

Setelah `POST /winner/api/v1/winners` (checkout) berhasil, Service Winner wajib memanggil balik Katalog untuk update status item:

```
PATCH http://service-katalog:8000/api/v1/items/{item_id}
Body: { "auction_status": "SOLD" }
```

Ini penting supaya:

* Item yang sudah terjual tidak muncul lagi sebagai `OPEN` di `GET /katalog/api/v1/items`

* Mencegah orang lain bid ulang ke item yang sudah selesai

---

Tabel Kontribusi Katalog ke Flow

| **Tahap Bisnis** | **Peran Katalog** | **Jenis Komunikasi** |
| :--- | :--- | :--- |
| Lihat barang | Endpoint utama yang diakses client lewat Gateway | Eksternal via Nginx |
| Validasi bid | Dipanggil internal oleh Penawaran untuk cek status item | Internal Docker network |
| Update status SOLD | Dipanggil internal oleh Winner setelah checkout | Internal Docker network

**Kesimpulan**

Service Katalog tidak berdiri sendiri — ia jadi fondasi data yang terus diakses dua service lain di sepanjang siklus lelang. Tanpa validasi ke Katalog, Penawaran bisa menerima bid untuk item yang tidak ada atau sudah closed. Tanpa update balik ke Katalog, status item tidak akan konsisten setelah ada pemenang.

---

# PROMPT 3

Tolong buatkan matriks visualisasi dalam bentuk tabel Markdown untuk mendokumentasikan hasil uji coba fungsional gateway `TEAM-02` kami, termasuk pembuktian proteksi bypass jika ada pihak luar yang mencoba menembak port internal `8000` secara langsung.

---

# AI RESPONSE

Berikut matriks pengujian fungsional Gateway TEAM-02 lengkap dengan pembuktian anti-bypass:

---

**Matriks Pengujian Fungsional API Gateway — TEAM-02**

**A. Pengujian Akses via Gateway (Port 80)**

| **No** | **Service** | **Method** | **Endpoint** | **Header Wajib** | **Expected Result** | 
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Gateway | GET | `http://localhost/health` | - | `200 OK` `{"status":"ok"}` |
| 2 | Katalog | GET | `http://localhost/katalog/api/v1/items` | `X-IAE-KEY` | `200 OK`, data array items |
| 3 | Katalog | GET | `http://localhost/katalog/api/v1/items/{id}` | `X-IAE-KEY` | `200 OK`, data item spesifik |
| 4 | Katalog | POST | `http://localhost/katalog/api/v1/items/filter` | `X-IAE-KEY` | `200 OK`, data terfilter |
| 5 | Katalog | POST | `http://localhost/katalog/api/v1/items` | `X-IAE-KEY` + `Bearer JWT` | `201 Created`, `receipt_number` terisi |
| 6 | Penawaran | GET | `http://localhost/penawaran/api/v1/bids` | `X-IAE-KEY` | `200 OK`, data bids |
| 7 | Penawaran | POST | `http://localhost/penawaran/api/v1/bids` | `X-IAE-KEY` | `201 Created`, `soap_receipt_number` terisi |
| 8 | Winner | GET | `http://localhost/winner/api/v1/winners` | - | `200 OK`, data winners |
| 9 | DWinner | POST | `http://localhost/winner/api/v1/winners` | `Bearer JWT` | `201 Created`, invoice terbentuk |

**B. Pengujian Proteksi Anti-Bypass (Membuktikan Tidak Ada Akses Langsung)**

| **No** | **Target Bypass** | **Command Uji** | **Expected Result** | **Bukti Proteksi** |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Port internal Katalog | `curl http://localhost:8000/api/v1/items` | `Connection refused` / `ECONNREFUSED` | - | `200 OK` `{"status":"ok"}` | ✅ Port tidak dipetakan ke host |
| 2 | Port internal Penawaran | `curl http://localhost:8001/api/v1/bids` | `Connection refused` | `X-IAE-KEY` | `200 OK`, data array items | ✅ Port tidak dipetakan ke host |
| 3 | Port internal Winner | `curl http://localhost:8002/api/v1/winners` | `Connection refused` | `X-IAE-KEY` | `200 OK`, data item spesifik | ✅ Port tidak dipetakan ke host |
| 4 | Container Katalog langsung dari host | `docker compose exec service-katalog curl localhost:8000/api/v1/items` | `200 OK` (ini wajar, karena dari DALAM container sendiri) | ✅ Hanya bisa diakses dari dalam network Docker |

**C. Verifikasi Konfigurasi `docker-compose.yml`**

| **Service** | **Baris `ports:` di docker-compose** | **Status** | 
| :--- | :--- | :--- | 
| `nginx` | `"80:80"` (satu-satunya yang dipublikasikan) | ✅ Sesuai |
| `service-katalog` | Tidak ada baris `ports:` | ✅ Sesuai |
| `service-penawaran` | Tidak ada baris `ports:` | ✅ Sesuai |
| `service-winner` | Tidak ada baris `ports:` | ✅ Sesuai |

Cara Mengisi Kolom "Status" di Atas

Setelah kamu jalankan satu per satu, isi dengan:

* ✅ Lolos — kalau hasil sesuai expected

* ❌ Gagal — kalau tidak sesuai, catat juga pesan error-nya

Tabel ini bisa langsung kamu masukkan ke laporan/analisis Tugas Besar sebagai bukti bahwa rubrik "API Gateway & Routing Hub" (bobot 20%) sudah terpenuhi.