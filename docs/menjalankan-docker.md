# Menjalankan Aplikasi dengan Docker (Production-like)

Setup ini menjalankan seluruh stack di dalam container yang saling terhubung
(app ⇄ db ⇄ minio ⇄ onlyoffice) — tanpa bergantung ke service di host.

## Arsitektur

| Service | Image | Port host | Fungsi |
|---|---|---|---|
| `app` | build `docker/php/Dockerfile` | (internal 9000) | PHP-FPM + queue worker + scheduler (supervisor) |
| `nginx` | nginx:latest | **8000** | Web server → app:9000 |
| `db` | mysql:8.0 | 3307 | Database |
| `phpmyadmin` | phpmyadmin | 8080 | GUI DB |
| `minio` | minio/minio | 9003 (API), 9004 (console) | Object storage |
| `minio-createbucket` | minio/mc | — | Bikin bucket `applicationletters` sekali jalan |
| `onlyoffice` | onlyoffice/documentserver | 8010 | Konversi DOCX→PDF |

Akses aplikasi: **http://localhost:8000**

## Konfigurasi

- Container `app` memakai **`.env.docker`** (via `env_file`), bukan `.env` host.
  Host DB/MinIO/OnlyOffice sudah diarahkan ke nama service (`db`, `minio`, `onlyoffice`).
- `.env` host tetap dipakai saat menjalankan aplikasi **non-container** (mis. `php artisan serve`).

## Cara menjalankan

```bash
# Build & jalankan semua service
docker compose up -d --build

# Lihat log app (php-fpm + queue + scheduler)
docker compose logs -f app

# Migrasi dijalankan otomatis oleh entrypoint saat app start.
```

## Seeding data awal (WAJIB untuk instalasi baru)

Migrasi hanya membuat struktur tabel (kosong). Untuk mengisi data awal — role,
department, position, **user login**, participant type, file type, dan grup file type —
jalankan seeder setelah container `app` jalan:

```bash
docker compose exec app php artisan db:seed --force
```

Seeder yang dijalankan (berurutan): `RoleAndPermissionSeeder`, `DepartmentSeeder`,
`PositionSeeder`, `UserSeeder`, `ParticipantTypeSeeder`, `FileTypeSeeder`,
`GroupFileTypeSeeder`.

Seeder tertentu saja (opsional):
```bash
docker compose exec app php artisan db:seed --class=GroupFileTypeSeeder --force
```

### Kredensial login (hasil UserSeeder)

Semua user default berpassword **`admin123`**. Beberapa akun:

| Email | Peran |
|---|---|
| `admin@gmail.com` | Admin |
| `adminzul@gmail.com` | Admin |
| `lp2m@gmail.com` | LP2M |
| `dekansaintek@gmail.com` | Dekan |
| `kabagsaintek@gmail.com` | Kabag |
| `bendaharasaintek@gmail.com` | Bendahara/Finance |
| `ketupelsaintek@gmail.com` | Ketua Pelaksana |

> ⚠️ Ini kredensial default untuk dev/staging. **Ganti password** untuk produksi.

## Cara mengakses (URL)

Semua diakses dari **browser di komputer host** (laptop/PC tempat Docker jalan).
Port di bawah adalah port HOST (kolom kiri pada `ports:` di compose).

### 1. Aplikasi (yang utama)

```
http://localhost:8000
```
- Ini pintu masuk aplikasi. Request → nginx (port 8000) → PHP-FPM (`app`).
- Kalau Docker jalan di VPS/server lain, ganti `localhost` dengan IP/domain server,
  mis. `http://192.168.1.10:8000`.
- Pastikan `APP_URL` di `.env.docker` sesuai (default `http://localhost:8000`).

### 2. MinIO (object storage)

MinIO punya **dua URL berbeda** — jangan tertukar:

| Kegunaan | URL | Login |
|---|---|---|
| **Console (GUI)** — lihat/kelola file & bucket | `http://localhost:9004` | user: `minioadmin` / pass: `minioadmin` |
| **API (S3)** — dipakai aplikasi, bukan untuk dibuka di browser | `http://localhost:9003` | — |

- Yang kamu buka di browser untuk **melihat file** adalah **Console: http://localhost:9004**.
- URL `:9003` (API) kalau dibuka di browser hanya menampilkan respons S3 mentah — itu normal,
  memang bukan halaman untuk manusia. Aplikasi memakainya lewat `MINIO_ENDPOINT` (internal
  `http://minio:9000`, bukan port host ini).
- Bucket default: **`applicationletters`** (dibuat otomatis oleh service `minio-createbucket`).

### 3. OnlyOffice (Document Server)

```
http://localhost:8010
```
- **Cek kesiapan** (paling penting): buka `http://localhost:8010/healthcheck`
  → harus menampilkan **`true`**. Kalau masih `false`/error, tunggu 1-2 menit
  (OnlyOffice butuh waktu boot setelah container start).
- Membuka `http://localhost:8010` langsung akan menampilkan halaman welcome OnlyOffice —
  itu tanda server hidup.
- Aplikasi memakainya lewat URL **internal** `http://onlyoffice/` (dari `.env.docker`),
  bukan port host `8010`. Port `8010` hanya untuk kamu cek manual dari browser.

### 4. phpMyAdmin (opsional, lihat database)

```
http://localhost:8080
```
- Login: server sudah otomatis ke `db`. User `root` / pass `root123`
  (atau `sirapel` / `sirapel123`).

### Ringkasan tabel port

| Layanan | URL host | Untuk apa |
|---|---|---|
| Aplikasi | http://localhost:8000 | Buka aplikasi (utama) |
| MinIO Console | http://localhost:9004 | GUI kelola file |
| MinIO API (S3) | http://localhost:9003 | Dipakai app (bukan untuk browser) |
| OnlyOffice | http://localhost:8010 | Cek `/healthcheck` = true |
| phpMyAdmin | http://localhost:8080 | GUI database |
| MySQL | localhost:3307 | Koneksi DB client (mis. DBeaver) |

> Catatan koneksi antar-container vs host: di DALAM container, service saling memanggil
> pakai **nama service + port internal** (`db:3306`, `minio:9000`, `onlyoffice:80`).
> Dari **browser host**, pakai **port host** di tabel atas. Keduanya menunjuk service yang
> sama, hanya jalur akses berbeda.

## Catatan penting

1. **Ghostscript** sudah terpasang di image `app` (dipakai `PdfMergerService` untuk
   normalisasi PDF sebelum merge). Tidak perlu install manual.

2. **Volume `app_public`**: `public/` dibagikan dari image `app` ke `nginx`. Named volume
   hanya terisi saat pertama dibuat — **jika kamu mengubah isi `public/`** (mis. aset baru)
   lalu rebuild image, jalankan `docker compose down -v` lalu `up --build` agar volume
   ter-refresh. (Alternatif: hapus volume `app_public` saja.)

3. **APP_KEY**: `.env.docker` sudah berisi APP_KEY. Ganti untuk produksi nyata.
   Entrypoint akan generate bila kosong.

4. **Migrasi otomatis**: entrypoint menunggu DB siap lalu `migrate --force`.
   Set `RUN_MIGRATIONS=false` di environment `app` untuk melewati.

5. **MinIO**: bucket `applicationletters` dibuat otomatis oleh `minio-createbucket`.
   Konsol MinIO: http://localhost:9004 (minioadmin / minioadmin).

6. **OnlyOffice** butuh waktu ~1-2 menit untuk siap sepenuhnya setelah start.
   Cek: http://localhost:8010/healthcheck harus balas `true`.

7. **OnlyOffice `error -4` saat konversi (sudah ditangani)**: OnlyOffice punya
   proteksi SSRF yang **menolak mengunduh dokumen dari IP privat**. MinIO di network
   Docker ada di IP privat (mis. `172.18.x`), jadi converter menolak dengan
   `{"error":-4}` dan gagal generate PDF.

   **Fix**: env `ALLOW_PRIVATE_IP_ADDRESS: "true"` (+ `ALLOW_META_IP_ADDRESS`) pada
   service `onlyoffice` di `docker-compose.yml`. Entrypoint OnlyOffice menyuntik
   `allowPrivateIPAddress=true` ke `local.json` secara **in-place** (tanpa menimpa
   konfig internal). Sudah terpasang di compose.

   ⚠️ **JANGAN mount file `local.json` custom** ke
   `/etc/onlyoffice/documentserver/local.json` — file bawaan berisi konfig kritis
   (Postgres, RabbitMQ, secret, storage). Menimpanya bikin OnlyOffice **stuck saat boot**
   dengan loop `nc: port number invalid` / `Waiting for connection to the host on port`.
   Untuk kustomisasi, pakai environment variable yang disediakan image.

   Diagnosa bila `-4` muncul lagi:
   ```bash
   # log converter OnlyOffice menyebut alasan sebenarnya
   docker compose exec onlyoffice sh -c "tail -30 /var/log/onlyoffice/documentserver/converter/out.log"
   # pastikan flag ter-set
   docker compose exec onlyoffice sh -c "grep -A2 request-filtering-agent /etc/onlyoffice/documentserver/local.json"
   ```

8. **502 Bad Gateway (sudah ditangani)**: nginx me-resolve nama service `app`
   secara **dinamis per-request** (via `resolver 127.0.0.11` + `set $upstream app:9000`
   di `docker/nginx/default.conf`). Jadi saat container `app` di-recreate/rebuild dan
   mendapat IP baru, nginx otomatis mengikuti — **tidak perlu restart nginx manual**.

   Kalau suatu saat MASIH kena 502, urutan diagnosa:
   ```bash
   docker compose ps                                   # app & nginx up?
   docker compose logs nginx | grep -i "upstream"       # cek IP tujuan vs IP app
   docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' laravel_app
   docker compose exec app supervisorctl status         # php-fpm RUNNING?
   ```
   Penyebab umum lain: app baru start & php-fpm belum siap (tunggu ~10-15 detik),
   atau entrypoint app gagal (cek `docker compose logs app`).

## Cara Cek Supervisor untuk QUEUE LAravel 
# Lihat status semua program
docker compose exec app supervisorctl status

# Restart queue worker saja (tanpa restart container)
docker compose exec app supervisorctl restart laravel-queue:laravel-queue_00

# Restart scheduler
docker compose exec app supervisorctl restart laravel-scheduler

# Lihat log via supervisor
docker compose exec app supervisorctl tail -f laravel-queue stdout


## Membersihkan

```bash
docker compose down           # stop, data (volume) tetap
docker compose down -v        # stop + hapus SEMUA volume (data DB/MinIO hilang)
```
