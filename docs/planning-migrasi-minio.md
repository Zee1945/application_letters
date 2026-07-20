# Planning: Migrasi File MinIO (storage.mydomain.com → MinIO Docker Lokal)

> Status: **planning, belum ada kode.**
> Tujuan: menyalin file dari MinIO produksi (`storage.mydomain.com`) ke MinIO
> lokal (Docker), **hanya** untuk file yang masih aktif (`files.deleted_at IS NULL`),
> dengan penyesuaian path sesuai aturan ekstensi.

---

## 1. Ringkasan Kebutuhan

- **Sumber**: MinIO produksi di `storage.mydomain.com` (bucket + kredensial produksi).
- **Tujuan**: MinIO lokal Docker (`minio:9000` internal / `localhost:9003` dari host),
  bucket `applicationletters`.
- **Yang disalin**: baris di tabel `files` dengan `deleted_at IS NULL` saja
  (file yatim/terhapus tidak ikut).
- **Aturan path per file** (INI KUNCI):
  - Ambil `path` dari kolom `files.path`.
  - **Jika ujung `path` sudah mengandung ekstensi** (mis. `.pdf`, `.docx`) →
    pakai `path` **apa adanya** (jangan diubah).
  - **Jika tidak ada ekstensi** (path = folder saja) →
    object key = `path` + `/` + `files.filename`.

### 1.1 Aturan ini SUDAH ada di kode (acuan)

`PdfMergerService::downloadTempFromMinio()` memakai logika identik:
```php
$filepath_with_name = str_contains($fileModel->path, '.')
    ? $fileModel->path                                 // ada '.' -> pakai apa adanya
    : $fileModel->path . '/' . $fileModel->filename;   // tidak -> tambah /filename
```
Migrasi harus memakai **aturan yang sama** agar object key hasil migrasi cocok dengan
cara aplikasi membaca file nanti.

> ⚠️ Catatan akurasi: `str_contains($path, '.')` mengecek ADA titik **di mana saja**,
> bukan khusus di ujung. Untuk keputusan "ada ekstensi di ujung", lebih tepat pakai
> `pathinfo($path, PATHINFO_EXTENSION) !== ''`. Planning ini memakai pengecekan ujung
> agar sesuai kalimat requirement ("ujung nama path ada extension"). Perlu keputusan:
> ikuti kode lama (`str_contains`) atau lebih ketat (`pathinfo`)? Lihat bagian 7.

---

## 2. Struktur Data (tabel `files`)

Kolom relevan:
- `path` — folder ATAU path lengkap (tergantung file, lihat aturan di atas).
- `filename` — nama file (dengan ekstensi).
- `storage_type` — umumnya `'minio'`.
- `deleted_at` — NULL = aktif (filter utama).

Config disk (`config/filesystems.php` → `minio`): driver `s3`, `use_path_style_endpoint`.

---

## 3. Strategi (opsi implementasi)

### Opsi A — Artisan Command khusus (REKOMENDASI)
Buat command mis. `files:migrate-minio` yang:
1. Query `Files` dengan `whereNull('deleted_at')` (chunkById untuk data besar).
2. Untuk tiap file, hitung **object key** dengan aturan ekstensi di atas.
3. Baca objek dari **disk source** (MinIO produksi) → tulis ke **disk target** (MinIO lokal),
   dengan **key yang sama** (mirror path).
4. Log ringkas: total, sukses, gagal, skip (source tak ada).

Kelebihan: terkontrol penuh, bisa dry-run, resume, filtering DB persis sesuai kebutuhan.

### Opsi B — `mc mirror` (MinIO Client) — untuk copy massal buta
`mc mirror source/bucket target/bucket` menyalin SEMUA object. **Tidak cocok** karena:
- Tidak bisa filter "hanya yang `deleted_at IS NULL`".
- Tidak menerapkan aturan path per-baris DB.
Bisa jadi pelengkap kalau ingin copy semua tanpa filter, tapi bukan solusi utama.

**Kesimpulan: Opsi A.**

---

## 4. Konfigurasi Dua Disk (source & target)

Migrasi butuh 2 koneksi MinIO sekaligus. Tambahkan disk `minio_source` di
`config/filesystems.php` (target = disk `minio` yang sudah ada, arahkan ke lokal Docker).

```php
// config/filesystems.php
'minio_source' => [
    'driver' => 's3',
    'key'    => env('MINIO_SRC_KEY'),
    'secret' => env('MINIO_SRC_SECRET'),
    'region' => env('MINIO_SRC_REGION', 'us-east-1'),
    'bucket' => env('MINIO_SRC_BUCKET'),
    'endpoint' => env('MINIO_SRC_ENDPOINT'),           // https://storage.mydomain.com
    'use_path_style_endpoint' => env('MINIO_SRC_USE_PATH_STYLE', false),
    'throw' => true,
],
```

`.env` (diisi kredensial produksi):
```
MINIO_SRC_ENDPOINT=https://storage.mydomain.com
MINIO_SRC_KEY=...
MINIO_SRC_SECRET=...
MINIO_SRC_BUCKET=<bucket-produksi>
MINIO_SRC_USE_PATH_STYLE=false
```

> `minio` (target) = MinIO lokal Docker. Saat command dijalankan DI DALAM container app,
> `MINIO_ENDPOINT=http://minio:9000`. Kalau dijalankan dari host, arahkan ke `http://localhost:9003`.

---

## 5. Logika Inti Command (pseudocode)

```php
$deriveKey = function ($file) {
    $path = rtrim((string) $file->path, '/');
    // "ada ekstensi di ujung path?" -> pakai apa adanya, else tambah /filename
    $hasExt = pathinfo($path, PATHINFO_EXTENSION) !== '';   // (lihat bagian 7 utk opsi str_contains)
    return $hasExt ? $file->path : $path . '/' . $file->filename;
};

Files::whereNull('deleted_at')
    ->orderBy('id')
    ->chunkById(200, function ($files) use ($deriveKey) {
        foreach ($files as $file) {
            $key = $deriveKey($file);

            // Lewati bila source tak punya object ini.
            if (! Storage::disk('minio_source')->exists($key)) {
                // log: skip (source missing)
                continue;
            }

            // Lewati bila target sudah punya (idempoten / resume).
            if (Storage::disk('minio')->exists($key)) {
                // log: skip (already exists)
                continue;
            }

            // Stream copy source -> target dengan key sama.
            $stream = Storage::disk('minio_source')->readStream($key);
            Storage::disk('minio')->writeStream($key, $stream);
            if (is_resource($stream)) { fclose($stream); }
            // log: copied
        }
    });
```

Fitur command yang disarankan:
- `--dry-run` : hanya hitung & tampilkan key yang AKAN disalin, tanpa menulis.
- `--chunk=N` : ukuran batch (default 200).
- `--overwrite` : timpa yang sudah ada di target (default: skip).
- Statistik akhir: `scanned, copied, skipped_exists, skipped_missing_source, failed`.

---

## 6. Alur Menjalankan

1. **Prasyarat**: DB `files` sudah terisi (hasil import SQL). MinIO lokal Docker jalan.
2. Isi `.env` dengan kredensial `MINIO_SRC_*` (produksi) — read-only lebih aman.
3. **Dry-run dulu**:
   ```
   docker compose exec app php artisan files:migrate-minio --dry-run
   ```
   Periksa: jumlah file, contoh key yang dihasilkan (cek aturan ekstensi benar).
4. Jalankan sungguhan:
   ```
   docker compose exec app php artisan files:migrate-minio
   ```
5. Verifikasi (bagian 8).

---

## 7. Keputusan yang Perlu Ditentukan (sebelum implementasi)

| # | Keputusan | Opsi |
|---|---|---|
| 1 | Deteksi ekstensi | (a) `str_contains($path,'.')` — sama seperti kode lama, tapi bisa false-positive bila ada titik di folder; (b) `pathinfo(...PATHINFO_EXTENSION)` — cek ujung, lebih tepat sesuai kalimatmu |
| 2 | File sudah ada di target | skip (default) / overwrite |
| 3 | Source object tak ada | skip + log (default) / hitung sebagai gagal |
| 4 | Jalankan dari mana | dalam container app (`minio:9000`) / host (`localhost:9003`) |
| 5 | Bucket source vs target | sama nama atau beda? (key di-mirror apa adanya) |

Rekomendasi default: **1(b) pathinfo**, **2 skip**, **3 skip+log**, **4 dalam container**.

---

## 8. Verifikasi Setelah Migrasi

1. **Hitung**: jumlah `files` aktif vs jumlah object tercopy (dari statistik command).
2. **Sampling**: ambil beberapa `files` (yang path-nya ada ekstensi & yang tidak),
   cek object-nya benar ada di MinIO lokal dengan key yang tepat:
   ```
   docker compose exec minio sh -c "ls -la /data/applicationletters/<path>"
   ```
   atau via MinIO Console (http://localhost:9004).
3. **Uji aplikasi**: buka detail pengajuan / download file / merge LPJ →
   file harus terbaca (karena key hasil migrasi cocok dengan cara app membaca).
4. **File yang skip**: tinjau log "missing source" — apakah wajar (file lama benar hilang)
   atau ada aturan path yang salah.

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Aturan ekstensi salah → key tak cocok saat app baca | Samakan PERSIS dengan logika app (bagian 1.1); dry-run + sampling |
| File besar / banyak → lama & boros memori | `readStream/writeStream` (bukan `get`); chunkById; jalankan di background |
| Kredensial source bocor | Pakai akun read-only; jangan commit `.env`; hapus `MINIO_SRC_*` setelah selesai |
| Source endpoint pakai TLS/self-signed | Pastikan cert valid, atau set opsi verify sesuai kebutuhan |
| Migrasi terputus di tengah | Command idempoten (skip yang sudah ada) → aman dijalankan ulang |
| Titik di nama FOLDER memicu false "punya ekstensi" (opsi str_contains) | Pilih keputusan #1 = pathinfo |

---

## 10. Langkah Implementasi (ringkas)

1. Tambah disk `minio_source` di `config/filesystems.php` + env `MINIO_SRC_*`.
2. Buat command `App\Console\Commands\MigrateMinioFiles` (`files:migrate-minio`).
3. Terapkan query `whereNull('deleted_at')` + `chunkById` + aturan key (bagian 5).
4. Tambah opsi `--dry-run`, `--overwrite`, `--chunk`.
5. Dry-run → tinjau → jalankan → verifikasi (bagian 8).
6. Setelah selesai: cabut kredensial source dari `.env`.
