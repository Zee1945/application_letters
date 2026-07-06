# Planning: Perbaikan FPDI Gagal Merge — Opsi B (OnlyOffice PDF/A) vs Ghostscript

> Status: **planning, belum ada perubahan kode.**
> Konteks masalah lengkap: lihat `docs/analisa-fpdi-onlyoffice-pdf.md`.
> Ringkas: FPDI free (`setasign/fpdi` v2.6.8, tanpa pdf-parser berbayar) tidak bisa
> membaca PDF hasil OnlyOffice VPS yang memakai **object stream / xref stream**,
> sehingga halaman di-skip saat merge LPJ (`PdfMergerService::mergePdfs`).

---

## 0. Fakta Environment (dari investigasi)

- OnlyOffice VPS: image **`onlyoffice/documentserver:latest`** (container `festive_benz`),
  base Ubuntu 24.04 + PG16 → indikasi **v8.x** (versi persis belum dikonfirmasi via `/info/info.json`).
  - Implikasi: tag `latest` = versi bisa berubah saat pull ulang → perilaku output PDF bisa berubah.
- OnlyOffice lokal: `http://127.0.0.1:8005/` → hasil PDF FPDI-friendly (xref klasik).
- Titik konversi DOCX→PDF: `FileManagementService::convertToPdf()` → `onlyOfficeConversion($from,'pdf',$url)`
  → build `$config` dengan `'outputtype' => 'pdf'` (sekitar baris 463).
- Titik merge: `PdfMergerService`
  - `downloadTempFromMinio()` (~baris 309) → file PDF ditulis ke temp lokal.
  - `ensurePdf()` (~baris 324) → pastikan semua input PDF (konversi non-PDF di sini).
  - `mergePdfs()` (~baris 390) → `$fpdi->setSourceFile($tempPath)` ← titik gagal.

---

## 1. Ringkasan Dua Opsi

| Aspek | **Opsi B — OnlyOffice output PDF/A** | **Ghostscript — normalisasi sebelum merge** |
|---|---|---|
| Prinsip | Cegah di **hulu**: minta converter hasilkan PDF FPDI-friendly | Perbaiki di **tengah**: tulis ulang PDF sebelum FPDI |
| Lokasi ubah | `FileManagementService::onlyOfficeConversion` (payload) | `PdfMergerService::ensurePdf` (tambah langkah `gs`) |
| Butuh binary VPS | ❌ Tidak | ✅ Ya (`ghostscript`) |
| Butuh library baru | ❌ Tidak | ❌ Tidak (pakai `Process`/`exec`) |
| Bergantung versi OnlyOffice | ✅ Ya (skema PDF/A beda antar versi) | ❌ Tidak |
| Menangani PDF non-OnlyOffice | ⚠️ Tidak (hanya jalur konversi) | ✅ Ya (semua PDF sebelum merge) |
| Keandalan terhadap PDF "bandel" | ⚠️ Sedang | ✅ Tinggi |
| PHP murni (sesuai preferensi) | ✅ Ya | ❌ Tidak (panggil CLI) |

---

## 2. Opsi B — OnlyOffice Menghasilkan PDF/A

### 2.1 Cara kerja
PDF/A (mis. PDF/A-1) berbasis PDF 1.4 → **tidak memakai object stream** → FPDI free bisa baca.
Alih-alih memanipulasi hasil, kita minta OnlyOffice langsung menghasilkannya.

### 2.2 Perubahan kode (rencana)
Di `onlyOfficeConversion()` saat `$to === 'pdf'`, sesuaikan payload sesuai versi:

**Skema lama (< v7.3):**
```php
$config['outputtype'] = 'pdfa';
```

**Skema baru (>= v7.3, kemungkinan VPS ini):**
```php
$config['outputtype'] = 'pdf';
$config['pdf'] = [ /* opsi PDF/A sesuai dokumentasi versi terkait */ ];
```
> Format objek `pdf`/PDF-A berbeda antar versi → **wajib dites ke instance VPS dulu**.

### 2.3 Prasyarat / verifikasi sebelum eksekusi
1. Pastikan versi OnlyOffice VPS: `GET http://{IP_VPS}:8080/info/info.json`
   atau `docker exec festive_benz sh -c 'cat /var/www/onlyoffice/documentserver/server/Common/package.json | grep version'`.
2. Uji konversi PDF/A di luar produksi (tinker/route sementara):
   - kirim `onlyOfficeConversion($ext, <skema pdfa>, $url)`;
   - simpan hasil; cek `grep -a -c "/ObjStm"` = 0;
   - uji `(new Fpdi())->setSourceFile($tmp)` tidak lempar exception.
3. Cek visual hasil (font embed, layout, tabel) — PDF/A punya batasan font.

### 2.4 Kelebihan
- **PHP murni**, tidak perlu tool OS, tidak perlu library baru.
- Perbaikan paling "hulu": PDF benar sejak lahir; MinIO menyimpan yang sudah benar.
- Perubahan kode kecil & terlokalisasi di satu fungsi.

### 2.5 Kekurangan / risiko
- **Tergantung dukungan versi OnlyOffice** — kalau versi tak mendukung PDF/A dengan struktur yang tepat, tidak menyelesaikan.
- Tag image `latest` → perilaku bisa berubah saat update (fragile). Sebaiknya pin versi.
- Hanya menyelesaikan PDF **jalur konversi OnlyOffice**. Kalau ada PDF sumber lain (upload user, template) yang juga object-stream, tidak tertangani.
- PDF/A membatasi beberapa fitur (transparansi, font non-embed) → perlu cek regresi visual.

### 2.6 Estimasi effort
- Kode: kecil (1 fungsi).
- Verifikasi/tes: sedang (harus tes ke OnlyOffice VPS + cek visual).

---

## 3. Opsi Ghostscript — Normalisasi PDF Sebelum Merge

### 3.1 Cara kerja
Setelah PDF ada di temp lokal (hasil download MinIO / konversi), jalankan Ghostscript
untuk menulis ulang ke PDF 1.4 (tanpa object stream), lalu FPDI merge hasil normalisasi.

```bash
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dBATCH -dQUIET \
   -o /tmp/normalized.pdf /tmp/input.pdf
```

### 3.2 Perubahan kode (rencana)
Tambah helper `normalizePdf(string $path): string` di `PdfMergerService`, dipanggil di
`ensurePdf()` untuk tiap file ber-mime `application/pdf` **sebelum** masuk `mergePdfs()`:
```php
// pseudocode
if ($this->ghostscriptAvailable()) {
    $normalized = $this->normalizePdf($path, $tempFiles); // gs via Symfony Process
    $result[] = $normalized ?: $path; // fallback ke asli bila gagal
} else {
    $result[] = $path; // tanpa gs: perilaku seperti sekarang + log
}
```
- Pakai `Symfony\Component\Process\Process` (bawaan Laravel) — bukan `exec()` mentah.
- **Deteksi ketersediaan `gs`** dulu; kalau tidak ada → skip + log jelas (tidak error).

### 3.3 Prasyarat / verifikasi
1. Ghostscript ter-install di VPS (`which gs`). Kalau tidak, perlu `apt install ghostscript`
   → **ini keluar dari batasan "tanpa command Ubuntu"** yang kamu minta.
2. Uji: normalisasi 1 file yang gagal → `grep -a -c "/ObjStm"` = 0 → FPDI bisa baca.
3. Cek visual hasil normalisasi (gs umumnya menjaga layout dengan baik).

### 3.4 Kelebihan
- **Paling andal** — menangani hampir semua PDF bandel, apa pun sumbernya.
- **Tidak bergantung versi/pengaturan OnlyOffice** — kebal terhadap update `latest`.
- Menormalisasi **semua** input merge (termasuk PDF non-OnlyOffice).

### 3.5 Kekurangan / risiko
- **Butuh binary di VPS** → melanggar preferensimu (PHP murni, tanpa command Ubuntu).
- Menambah ketergantungan environment: deploy baru harus pastikan `gs` ada.
- Overhead per-file (proses eksternal) saat merge — kecil, tapi ada.
- Perlu penanganan keamanan/timeout pada pemanggilan Process.

### 3.6 Estimasi effort
- Kode: sedang (helper + deteksi + fallback).
- Ops: perlu jaminan `gs` terpasang di semua environment.

---

## 4. Rekomendasi

**Sesuai batasanmu (PHP murni, tanpa command Ubuntu di VPS): utamakan Opsi B**, dengan syarat
lolos verifikasi 2.3 (OnlyOffice VPS mendukung PDF/A yang FPDI-friendly).

- Jika verifikasi Opsi B **berhasil** → pakai Opsi B. Paling murah, PHP murni, tidak menambah ops.
- Jika Opsi B **gagal** (versi OnlyOffice tak mendukung PDF/A yang benar) → pilihan realistis:
  - **Ghostscript** (paling andal, tapi butuh binary VPS), atau
  - **`fpdi-pdf-parser` berbayar** (PHP murni, drop-in, tapi berbayar).

### Catatan lintas-opsi (lakukan apa pun solusinya)
- **Pin versi image OnlyOffice** (jangan `latest`) setelah ketemu versi yang bekerja — mencegah regresi diam-diam.
- **Audit LPJ lama** — karena skip halaman selama ini hanya warning, LPJ tergenerate mungkin tidak lengkap.
- **Jadikan skip terlihat** — `mergePdfs()` sebaiknya menandai LPJ "tidak lengkap" saat ada halaman gagal, bukan sekadar `Log::warning`.

---

## 5. Matriks Keputusan Cepat

| Kondisi | Aksi |
|---|---|
| OnlyOffice VPS mendukung PDF/A FPDI-friendly | **Opsi B** |
| Tidak mendukung, **boleh** install `gs` di VPS | **Ghostscript** |
| Tidak mendukung, **tak boleh** install apa pun di VPS | **`fpdi-pdf-parser` berbayar** |
| Butuh solusi kebal-sumber (PDF dari mana saja) | **Ghostscript** |

---

## 6. Langkah Selanjutnya (urutan disarankan)

1. Konfirmasi versi OnlyOffice VPS (`/info/info.json` atau `docker exec ... package.json`).
2. Tes konversi PDF/A ke OnlyOffice VPS di luar produksi (verifikasi 2.3).
3. Jika lolos → implement Opsi B di `onlyOfficeConversion`; jika tidak → evaluasi Ghostscript / parser berbayar.
4. Terapkan catatan lintas-opsi (pin versi, audit LPJ lama, skip terlihat).
