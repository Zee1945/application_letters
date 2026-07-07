# Panduan Instalasi Ghostscript di VPS (Laravel di Host)

> Konteks: Laravel berjalan **langsung di host VPS** (bukan container). Ghostscript
> dipakai untuk menormalisasi PDF hasil OnlyOffice (cross-reference stream) ke PDF 1.4
> agar FPDI free bisa merge. Implementasi kode: `app/Services/PdfMergerService.php`
> (`normalizePdf()` + `ghostscriptBinary()`).

---

## 1. Cek dulu apakah sudah terpasang

```bash
gs --version
```
- Kalau keluar angka (mis. `10.02.1`) → sudah ada, lompat ke bagian **4 (verifikasi)**.
- Kalau `command not found` → lanjut instalasi (bagian 2). *(Kasus kamu: tidak ditemukan.)*

Cek OS/distro dulu supaya perintahnya tepat:
```bash
cat /etc/os-release | grep -E '^(NAME|VERSION)='
```

---

## 2. Instalasi

### Debian / Ubuntu (paling umum)
```bash
sudo apt update
sudo apt install -y ghostscript
```

### RHEL / CentOS / Rocky / Alma
```bash
sudo dnf install -y ghostscript
# atau: sudo yum install -y ghostscript
```

### Alpine (jarang untuk host, biasanya container)
```bash
sudo apk add ghostscript
```

---

## 3. Pastikan versi cukup baru (KEAMANAN — penting)

Karena Ghostscript memproses PDF yang bisa berasal dari **upload user**, versi lama
berisiko (CVE RCE historis). Target: **gs ≥ 10.x**.

```bash
gs --version
```

- **≥ 10.x** → aman, lanjut.
- **9.x** → masih diterima bila itu versi terbaru yang dipatch dari repo distro, TAPI
  usahakan yang paling baru. Jalankan update paket:
  ```bash
  sudo apt update && sudo apt upgrade ghostscript   # Debian/Ubuntu
  ```
- **< 9.28** → **hindari** (`-dSAFER` belum default & banyak CVE). Update dulu.

> Catatan: `-dSAFER` sudah di-set eksplisit di kode, jadi sandbox aktif tak bergantung default.
> Tetap utamakan versi baru untuk menutup CVE parser.

---

## 4. Verifikasi Ghostscript bekerja

```bash
# Uji versi
gs --version

# Uji konversi/normalisasi 1 file (ganti in.pdf dengan PDF apa saja)
gs -dSAFER -dNOPAUSE -dBATCH -dQUIET -sDEVICE=pdfwrite \
   -dCompatibilityLevel=1.4 -sOutputFile=out.pdf in.pdf

# Bukti struktur berubah jadi FPDI-friendly:
grep -a -c "/XRef" out.pdf     # harus 0  (tidak ada xref stream)
grep -a -c "^xref" out.pdf     # harus >0 (ada xref table klasik)
```

Kalau `out.pdf` terbentuk & pola di atas sesuai → gs siap.

---

## 5. Pastikan gs terlihat oleh proses PHP/queue worker

Aplikasi memanggil `gs` lewat `Symfony\Component\Process`. Prosesnya harus menemukan
binary `gs` di `PATH`.

### 5.1 PATH web server (PHP-FPM) & worker
`apt install` menaruh gs di `/usr/bin/gs` — biasanya sudah di `PATH` semua proses.
Verifikasi lokasi:
```bash
which gs        # contoh: /usr/bin/gs
```

### 5.2 Worker queue (KRITIS)
Merge LPJ jalan di **queue worker**. Pastikan **proses worker** bisa akses `gs`:
```bash
# jika pakai systemd untuk queue worker, cek user & PATH service-nya
sudo systemctl status laravel-queue   # (sesuaikan nama service kamu)
```
- Worker harus dijalankan oleh user yang PATH-nya memuat `/usr/bin`.
- Setelah install gs, **restart worker** agar environment ter-refresh:
  ```bash
  php artisan queue:restart
  # atau restart service systemd worker:
  # sudo systemctl restart laravel-queue
  ```

> Kode punya fallback: bila `gs` tak ditemukan, normalisasi dilewati & di-log
> ("Ghostscript tidak ditemukan..."). Jadi kalau setelah deploy masih gagal merge,
> cek log ini dulu — kemungkinan worker tak melihat gs / belum di-restart.

---

## 6. Pengerasan keamanan tingkat OS (disarankan)

Kode sudah menerapkan `-dSAFER`, array-args, timeout. Tambahan di sisi server:

1. **Worker queue jalan sebagai user non-root.**
   Jangan jalankan `queue:work` sebagai root. Pakai user aplikasi (mis. `www-data`
   atau user deploy khusus) dengan akses tulis hanya ke `storage/`.
   ```bash
   # contoh unit systemd
   # User=www-data
   # Group=www-data
   ```

2. **Batasi resource (opsional, VPS kecil).**
   Bila khawatir PDF "bom", tambahkan limit di systemd unit worker:
   ```
   MemoryMax=512M
   CPUQuota=80%
   ```

3. **Update rutin.**
   Masukkan `ghostscript` ke siklus `apt upgrade` rutin agar CVE tertutup.

---

## 7. Ringkasan Perintah (Ubuntu, host VPS)

```bash
# 1. install
sudo apt update && sudo apt install -y ghostscript

# 2. cek versi (target >= 10.x)
gs --version

# 3. lokasi binary
which gs

# 4. uji normalisasi
gs -dSAFER -dNOPAUSE -dBATCH -dQUIET -sDEVICE=pdfwrite \
   -dCompatibilityLevel=1.4 -sOutputFile=/tmp/out.pdf /path/ke/file.pdf
grep -a -c "^xref" /tmp/out.pdf     # harus > 0

# 5. restart worker agar melihat gs
php artisan queue:restart
```

---

## 8. Troubleshooting

| Gejala | Kemungkinan sebab | Solusi |
|---|---|---|
| Log "Ghostscript tidak ditemukan" padahal sudah install | Worker belum di-restart / PATH worker beda | `php artisan queue:restart`; cek `which gs` sbg user worker |
| `gs: command not found` di shell | Belum terpasang / PATH shell | install ulang; `hash -r`; cek `/usr/bin/gs` |
| Merge tetap skip halaman | gs jalan tapi output masih xref stream | pastikan `-dCompatibilityLevel=1.4` (sudah di kode); cek versi gs |
| Worker error permission | worker root/akses storage kurang | jalankan worker sbg user aplikasi, beri akses `storage/` |
| Proses gs lama/hang | PDF besar/rusak | timeout 120s sudah ada di kode; cek log `err` |

---

## 9. Setelah Instalasi — Verifikasi End-to-End

1. Restart queue worker (`php artisan queue:restart`).
2. Trigger merge LPJ untuk 1 aplikasi yang sebelumnya gagal.
3. Cek log: **tidak ada** lagi warning `"Gagal import PDF ... compression technique"`.
4. Buka LPJ hasil merge: pastikan halaman "Laporan Kegiatan" (& lainnya) **ada & lengkap**.
5. (Disarankan) audit LPJ lama yang mungkin sudah kehilangan halaman → regenerate.
