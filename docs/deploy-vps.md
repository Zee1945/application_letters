# Panduan Deploy ke VPS (Production)

Panduan menyiapkan & menjalankan aplikasi di VPS memakai Docker Compose.
Setup saat ini (dev-lokal) sudah berfungsi; dokumen ini fokus pada **apa yang WAJIB
diubah/disiapkan** agar aman & benar di server produksi.

> Baca juga: `docs/menjalankan-docker.md` (arsitektur, URL, seeding, troubleshooting).

---

## 0. Ringkasan Cepat (checklist)

- [ ] VPS + Docker & Docker Compose plugin terpasang
- [ ] Domain diarahkan ke IP VPS (A record)
- [ ] `.env.docker` disesuaikan: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru, `APP_URL` domain
- [ ] Ganti **semua password default** (DB, MinIO)
- [ ] Firewall: hanya buka 80/443 (+22 SSH). Tutup 3307, 8080, 9003/9004, 8010
- [ ] HTTPS (reverse proxy / certbot)
- [ ] Build & up: `docker compose up -d --build`
- [ ] Migrasi + seeding awal
- [ ] Backup volume terjadwal

---

## 1. Prasyarat VPS

```bash
# Docker + compose plugin (Ubuntu/Debian)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER      # relogin setelah ini
docker compose version             # pastikan v2 plugin ada
```
- **Spesifikasi**: OnlyOffice cukup berat. Minimal **4 GB RAM** (2 GB sangat mepet karena
  OnlyOffice + MySQL + MinIO + PHP). Disk: sisakan ruang untuk volume MinIO (file upload).
- **Git**: untuk clone repo. Atau kirim source via rsync/scp.

---

## 2. Ambil Source ke VPS

```bash
git clone <repo-url> /opt/application_letters
cd /opt/application_letters
```
Catatan: `vendor/`, `node_modules/`, `public/build` TIDAK perlu dibawa — semua di-build
di dalam image (multi-stage Dockerfile). Cukup source + file docker.

---

## 3. Konfigurasi `.env.docker` (WAJIB diubah untuk produksi)

File `.env.docker` dipakai container `app`. Sesuaikan minimal ini:

```dotenv
APP_ENV=production
APP_DEBUG=false                      # WAJIB false di produksi (jangan bocorkan stack trace)
APP_KEY=base64:...                   # GENERATE BARU (jangan pakai key dev)
APP_URL=https://app.domainmu.com     # domain produksi (https)

# Database — GANTI password default
DB_HOST=db
DB_DATABASE=application_letters
DB_USERNAME=sirapel
DB_PASSWORD=<password-kuat-baru>

# MinIO — GANTI kredensial default
MINIO_ROOT_USER=<user-baru>
MINIO_ROOT_PASSWORD=<password-kuat-baru>
MINIO_BUCKET=applicationletters
MINIO_ENDPOINT=http://minio:9000     # internal antar-container (JANGAN diubah)
MINIO_USE_PATH_STYLE_ENDPOINT=true

# OnlyOffice — internal
ONLYOFFICE_DOCS_SERVER_URL=http://onlyoffice/
```

**Generate APP_KEY baru:**
```bash
# setelah container up, atau di lokal:
docker compose run --rm app php artisan key:generate --show
# salin hasilnya ke APP_KEY di .env.docker
```

> Password DB/MinIO di `.env.docker` HARUS sama dengan yang di `docker-compose.yml`
> (service `db` & `minio`). Ubah keduanya bersamaan.

---

## 4. Ganti Password Default di `docker-compose.yml`

Setup sekarang pakai password dev (`root123`, `sirapel123`, `minioadmin`). **Ganti semua** untuk produksi:

```yaml
db:
  environment:
    MYSQL_ROOT_PASSWORD: <root-password-kuat>
    MYSQL_USER: sirapel
    MYSQL_PASSWORD: <password-kuat>      # samakan dgn DB_PASSWORD di .env.docker

minio:
  environment:
    MINIO_ROOT_USER: <user-baru>          # samakan dgn MINIO_ROOT_USER
    MINIO_ROOT_PASSWORD: <password-kuat>  # samakan dgn MINIO_ROOT_PASSWORD

phpmyadmin:
  environment:
    MYSQL_ROOT_PASSWORD: <root-password-kuat>
```
Juga sesuaikan healthcheck `db` (memakai `-proot123`) agar cocok dengan password root baru.

> Kalau volume DB/MinIO SUDAH ada dari percobaan sebelumnya, password baru tidak berlaku
> ke volume lama (MySQL/MinIO init password hanya sekali). Untuk server baru ini tidak masalah
> (volume masih kosong). Kalau perlu reset: `docker compose down -v` (HATI-HATI: hapus data).

---

## 5. KEAMANAN PORT (paling penting)

Setup dev meng-expose banyak port ke host. Di VPS, **JANGAN buka port internal ke publik**.
Yang benar-benar publik hanya **80/443** (web). Sisanya harus ditutup dari internet.

Port di compose sekarang & tindakannya di produksi:

| Service | Port host (dev) | Tindakan produksi |
|---|---|---|
| nginx (app) | 8000 | Ubah ke 80 (via reverse proxy/HTTPS) — SATU-satunya yang publik |
| MySQL | 3307 | **Tutup dari publik** (hapus mapping `ports` atau bind `127.0.0.1`) |
| phpMyAdmin | 8080 | **Tutup** / akses via SSH tunnel saja |
| MinIO API | 9003 | **Tutup** (aplikasi akses internal `minio:9000`) |
| MinIO Console | 9004 | **Tutup** / SSH tunnel |
| OnlyOffice | 8010 | **Tutup** (app akses internal `onlyoffice:80`) |

**Cara menutup**: untuk service yang hanya dipakai antar-container (MinIO, OnlyOffice),
HAPUS bagian `ports:` — mereka tetap saling terhubung via network `laravel`, tak perlu port host.
Untuk yang kadang perlu diakses admin (phpMyAdmin, DB, MinIO console), bind ke localhost:
```yaml
ports:
  - "127.0.0.1:8080:80"   # hanya bisa diakses dari VPS sendiri -> pakai SSH tunnel
```

**Firewall (ufw):**
```bash
sudo ufw allow 22/tcp        # SSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

**Akses admin via SSH tunnel** (contoh phpMyAdmin yang di-bind 127.0.0.1:8080):
```bash
ssh -L 8080:localhost:8080 user@ip-vps
# lalu buka http://localhost:8080 di browser lokal
```

---

## 6. Domain & HTTPS

Aplikasi butuh diakses via `https://app.domainmu.com`. Dua pendekatan:

### Opsi A — Reverse proxy di host (nginx/Caddy + certbot) — REKOMENDASI
- Jalankan nginx/Caddy DI HOST VPS sebagai reverse proxy → teruskan ke container nginx (port 8000, bind `127.0.0.1:8000:80`).
- Certbot/Let's Encrypt untuk TLS.
- Keuntungan: sertifikat & HTTPS terpusat di host, container tak perlu tahu TLS.

Contoh (nginx host):
```nginx
server {
    server_name app.domainmu.com;
    client_max_body_size 80M;              # samakan dgn PHP/nginx container
    location / { proxy_pass http://127.0.0.1:8000; proxy_set_header Host $host; proxy_set_header X-Forwarded-Proto $scheme; proxy_set_header X-Forwarded-For $remote_addr; }
}
# lalu: sudo certbot --nginx -d app.domainmu.com
```
Ubah mapping nginx container jadi `127.0.0.1:8000:80` supaya tidak langsung publik.

### Opsi B — Caddy sebagai container (auto-HTTPS)
Tambah service Caddy di compose yang otomatis mengurus Let's Encrypt. Lebih ringkas
tapi menambah 1 service.

> Karena APP_URL = https, pastikan Laravel mengenali proxy (X-Forwarded-Proto).
> `TrustProxies` middleware Laravel default sudah menangani; set `APP_URL` https sudah cukup.

### OnlyOffice & HTTPS
OnlyOffice diakses aplikasi secara **internal** (`http://onlyoffice/`), jadi tidak perlu
HTTPS untuk itu. `ALLOW_PRIVATE_IP_ADDRESS=true` tetap diperlukan (MinIO di IP privat).

---

## 7. Build & Jalankan

```bash
cd /opt/application_letters
docker compose up -d --build         # build image (termasuk npm build & composer)
docker compose logs -f app           # pantau entrypoint (tunggu DB, migrasi, cache)
```
Entrypoint otomatis: tunggu DB → `migrate --force` → cache config/route/view.

**Seeding data awal (server baru):**
```bash
docker compose exec app php artisan db:seed --force
```
> ⚠️ `UserSeeder` membuat user default berpassword `admin123`. **GANTI password**
> semua user setelah login pertama, atau sesuaikan seeder sebelum deploy.

---

## 8. Verifikasi

```bash
docker compose ps                                   # semua Up? db Healthy?
curl -I https://app.domainmu.com                     # HTTP 200/302
docker compose exec onlyoffice curl -s localhost/healthcheck   # true
docker compose exec app supervisorctl status         # queue & scheduler RUNNING
```
Uji end-to-end: login → buat pengajuan → generate dokumen (OnlyOffice) → merge LPJ.

---

## 9. Backup (WAJIB untuk produksi)

Data ada di volume Docker: `db_data` (MySQL), `minio_data` (file upload). Backup rutin:

```bash
# MySQL dump
docker compose exec -T db mysqldump -uroot -p<root-pw> application_letters > backup-$(date +%F).sql

# MinIO data (rsync volume atau mc mirror ke storage lain)
docker run --rm -v application_letters_minio_data:/data -v $(pwd):/backup alpine \
  tar czf /backup/minio-$(date +%F).tar.gz -C /data .
```
Jadwalkan via cron. Simpan backup di luar VPS.

---

## 10. Update / Redeploy

```bash
cd /opt/application_letters
git pull
docker compose up -d --build         # rebuild image dgn kode baru
# entrypoint jalankan migrasi otomatis; cache di-refresh
```
> Karena production-like (source di image), setiap perubahan kode = rebuild.
> nginx sudah resolve `app` dinamis (fix 502), jadi tak perlu restart nginx manual.

---

## 11. Perbedaan Kunci: Dev vs Produksi

| Aspek | Dev (sekarang) | Produksi (VPS) |
|---|---|---|
| APP_ENV / APP_DEBUG | local / true | production / **false** |
| APP_URL | http://localhost:8000 | https://domain |
| Password DB/MinIO | default lemah | **kuat & unik** |
| Port terbuka | banyak (8000,8080,9003,9004,8010,3307) | **hanya 80/443** publik |
| HTTPS | tidak | **wajib** (reverse proxy/certbot) |
| APP_KEY | key dev | **baru** |
| Backup | tidak | **terjadwal** |
| User seeder (admin123) | ok | **ganti password** |

---

## 12. Catatan Penting (dari isu yang pernah ditemui)

- **OnlyOffice + MinIO**: `ALLOW_PRIVATE_IP_ADDRESS=true` WAJIB (MinIO di IP privat).
  Jangan mount `local.json` custom ke OnlyOffice — bikin boot stuck. (Lihat menjalankan-docker.md.)
- **502 nginx**: sudah ditangani lewat resolver dinamis di `docker/nginx/default.conf`.
- **Upload limit**: PHP & nginx sudah di-set 80M; sesuaikan reverse proxy host (`client_max_body_size`) agar tidak jadi bottleneck.
- **Ghostscript**: sudah di image (untuk merge PDF). Pastikan cukup RAM saat merge banyak file.
- **Volume `app_public`**: kalau aset (public/build) berubah, mungkin perlu `docker compose down -v`
  untuk refresh volume public — HATI-HATI, `-v` juga menghapus data DB/MinIO. Lebih aman
  hapus hanya volume public: `docker volume rm application_letters_app_public`.
