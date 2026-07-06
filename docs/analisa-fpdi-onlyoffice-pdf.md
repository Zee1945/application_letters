# Analisa: FPDI Gagal Merge PDF Hasil OnlyOffice

> Status: **analisa saja, belum ada perubahan kode.**
> Fokus: solusi **manipulasi PDF via PHP murni**, tanpa menjalankan command CLI (gs/qpdf) di VPS.

---

## 1. Ringkasan Masalah

Saat menggabungkan (merge) beberapa PDF laporan jadi satu file LPJ, muncul warning:

```
Gagal import PDF .../Laporan Kegiatan-....pdf: This PDF document probably uses a
compression technique which is not supported by the free parser shipped with FPDI.
```

Akibatnya **halaman itu di-skip** — LPJ gabungan jadi tidak lengkap (silent, hanya warning di log).

Lokasi kode: `app/Services/PdfMergerService.php`
- `mergePdfs()` baris ~390: `$fpdi->setSourceFile($pdfPath)` → di sinilah exception dilempar & di-`catch`.

---

## 2. Akar Penyebab (yang sudah dipastikan)

### 2.1 Rantai file
```
OnlyOffice (DOCX→PDF)  →  byte PDF disimpan ke MinIO  →  di-get dari MinIO ke temp lokal  →  FPDI baca file temp
```
- MinIO **tidak** mengubah isi file — ia object storage, hanya menyimpan & mengembalikan byte yang sama.
- Jadi FPDI membaca byte **persis** seperti yang dihasilkan OnlyOffice. Masalahnya ada **di dalam byte PDF**, bukan di transport.

### 2.2 Kenapa FPDI gagal — bukan soal "versi PDF"
- Header PDF sama-sama **1.7** di lokal & server, tapi tetap beda hasil. Angka versi **bukan** penentu.
- Penentu sebenarnya = **struktur cross-reference**:
  - **Object stream (`/ObjStm`)** dan/atau **cross-reference stream (`/Type /XRef`)** → dipakai sejak PDF 1.5+.
  - **FPDI versi free (`setasign/fpdi` v2.6.8, terpasang di lokal & VPS)** hanya punya parser bawaan yang **tidak mendukung** object stream / xref stream.
  - Parser yang mendukung itu = **`setasign/fpdi-pdf-parser` (berbayar)** — TIDAK terpasang (dikonfirmasi dari `composer.json`/`composer.lock`).

### 2.3 Kenapa "di lokal jalan, di server gagal"
- `composer.json` VPS == lokal → versi FPDI identik → **bukan** beda dependensi.
- Yang berbeda = **instance OnlyOffice**:
  - Lokal: `ONLYOFFICE_DOCS_SERVER_URL=http://127.0.0.1:8005/` → hasilkan PDF struktur xref klasik → FPDI free OK.
  - Server: OnlyOffice produksi (versi/konfig beda) → hasilkan PDF dengan object stream → FPDI free GAGAL.
- Kesimpulan: byte PDF yang masuk ke MinIO **berbeda struktur** antar-environment, walau angka versi sama.

### 2.4 Cara memverifikasi (opsional, untuk memastikan 100%)
Buka file PDF yang gagal dengan text editor / grep, cari:
- `/ObjStm` ada → object stream → penyebab error.
- `/Type /XRef` ada → xref stream → penyebab error.
- `xref` (baris sendiri) ada & dua di atas tidak ada → xref klasik → FPDI OK.

---

## 3. Peta Solusi (gambaran umum)

| Kategori | Contoh | PHP murni? | Menyelesaikan akar? |
|---|---|---|---|
| Parser berbayar | `setasign/fpdi-pdf-parser` | ✅ (composer) | ✅ | 
| Normalisasi CLI | Ghostscript / qpdf | ❌ (butuh binary VPS) | ✅ (paling andal) |
| **Normalisasi PHP murni** | **rebuild via library PHP** | ✅ | ⚠️ tergantung teknik |
| Hulu (converter) | minta OnlyOffice output beda | ✅ (ubah request) | ⚠️ tergantung OnlyOffice |

Karena kamu ingin **PHP murni tanpa CLI**, fokus ke baris ke-3 & ke-4. Berikut analisanya.

---

## 4. Opsi PHP Murni (tanpa CLI di VPS)

### Opsi A — Ganti engine merge ke library yang paham object stream (REKOMENDASI utama untuk PHP-murni)

**Ide:** FPDI free tidak paham object stream, tapi ada library PHP lain yang parser-nya lebih lengkap.

Kandidat:
- **`smalot/pdfparser`** — hanya *membaca* teks/metadata, **tidak bisa menyusun ulang / merge halaman utuh**. ❌ tidak cocok untuk merge.
- **PDF parser murni-PHP yang mendukung object stream untuk keperluan import halaman** — pilihan realistis sangat terbatas di ekosistem gratis. Sebagian besar library merge PHP populer (`webklex/laravel-pdfmerger`, `setasign/fpdi`) berujung ke FPDI free yang sama.

**Kesimpulan:** di ranah gratis + PHP murni, hampir semua jalan merge bermuara ke FPDI. Jadi "ganti library" jarang benar-benar lepas dari keterbatasan yang sama. ⚠️ Kurang menjanjikan.

---

### Opsi B — Normalisasi PDF di hulu: minta OnlyOffice hasilkan struktur yang FPDI-friendly (REKOMENDASI paling praktis & PHP murni)

**Ide:** Masalahnya lahir di OnlyOffice. Kalau OnlyOffice bisa diminta menghasilkan PDF tanpa object stream (mis. **PDF/A-1** yang basisnya PDF 1.4), FPDI free langsung bisa baca — **tanpa manipulasi apa pun setelahnya**.

**Cara kerja (PHP murni, hanya ubah request konversi):**
Di `FileManagementService::convertToPdf` / builder `$config` (sekitar baris 463):
```php
$config = [
    "async"      => false,
    'filetype'   => $from,
    'outputtype' => $to,        // 'pdf'
    'url'        => $fileUrl,
    'key'        => $key,
    // TAMBAHAN yang perlu diuji ke instance OnlyOffice:
    // 'pdf' => [ 'form' => false ],          // beberapa versi mendukung sub-opsi pdf
    // atau minta PDF/A: 'outputtype' => 'pdfa'
];
```

**Catatan penting:**
- Dukungan opsi ini **tergantung versi OnlyOffice Document Server**. Tidak semua versi menerima `pdfa` / sub-opsi `pdf`.
- Perlu **dites langsung ke instance OnlyOffice VPS** — kirim request dengan `outputtype: 'pdfa'` lalu cek apakah hasilnya (a) sukses & (b) tidak lagi pakai object stream.
- Kalau OnlyOffice VPS mendukung PDF/A → **ini solusi paling bersih**: tidak menambah library, tidak menambah tool CLI, cuma menyesuaikan payload konversi yang sudah dikirim via PHP (`Http::post`).

**Risiko:** PDF/A punya batasan (font harus embed, dsb). Untuk dokumen laporan internal biasanya aman. Perlu verifikasi visual hasil.

---

### Opsi C — Round-trip konversi via OnlyOffice (PDF → PDF)

**Ide:** kirim ulang PDF hasil ke OnlyOffice untuk dikonversi ulang, berharap struktur berubah jadi xref klasik.

**Kelemahan:** tidak ada jaminan struktur output berubah; boros (2× konversi); rapuh terhadap versi OnlyOffice. ⚠️ Tidak direkomendasikan kecuali Opsi B (pdfa) tidak tersedia.

---

### Opsi D — Rebuild PDF via library PHP (parse → tulis ulang)

**Ide:** baca PDF dengan parser PHP, tulis ulang jadi PDF 1.4.

**Kelemahan:** library gratis yang bisa **round-trip PDF utuh dengan layout terjaga** praktis tidak ada di PHP. Rawan merusak layout, tabel, font. ❌ Tidak realistis.

---

## 5. Kalau Boleh Sedikit Kompromi (bukan PHP murni, tapi tetap tanpa "command manual")

Untuk kelengkapan — dua ini **bukan** PHP murni, dicatat sebagai pembanding:

- **`setasign/fpdi-pdf-parser` (berbayar):** paling mulus dari sisi kode (drop-in, FPDI langsung bisa baca object stream). Hanya butuh `composer` + lisensi. Tidak perlu tool OS.
- **Ghostscript / qpdf via `exec()` PHP:** tetap "lewat PHP" (dipanggil dari kode PHP pakai `Process`/`exec`), tapi **butuh binary ter-install di VPS**. Paling andal untuk segala macam PDF bandel. Titik sisip: `PdfMergerService::ensurePdf()` sebelum `mergePdfs()`.

---

## 6. Rekomendasi Akhir (dengan batasan "PHP murni, tanpa CLI")

**Urutan yang disarankan untuk dicoba:**

1. **Opsi B — minta OnlyOffice output PDF/A (`outputtype: 'pdfa'`)**.
   Paling sesuai keinginan (PHP murni, cuma ubah payload konversi yang sudah ada).
   **Langkah verifikasi dulu:** tes request konversi ke OnlyOffice VPS dengan `pdfa`, ambil hasilnya, cek apakah `/ObjStm` sudah hilang & FPDI bisa baca. Kalau ya → selesai, paling murah.

2. Kalau OnlyOffice VPS **tidak** mendukung PDF/A dengan struktur FPDI-friendly:
   → pertimbangkan **`fpdi-pdf-parser` (berbayar)** — paling mulus di sisi kode meski tak gratis;
   → atau **Ghostscript via `exec()`** kalau boleh install binary di VPS (paling andal & gratis).

**Yang sebaiknya dihindari:** Opsi A/C/D — di ekosistem PHP gratis, tidak ada yang benar-benar menyelesaikan object stream tanpa mengorbankan keandalan layout.

---

## 7. Catatan Tambahan (di luar pilihan solusi)

- **Data lama berisiko tidak lengkap:** karena error ini hanya warning + skip, LPJ yang sudah ter-generate mungkin kehilangan halaman diam-diam. Perlu audit file LPJ hasil merge terakhir.
- **Silent skip sebaiknya lebih terlihat:** apa pun solusinya, `PdfMergerService::mergePdfs()` yang saat ini hanya `Log::warning` saat gagal import sebaiknya minimal menandai LPJ sebagai "tidak lengkap" agar tidak lolos tanpa disadari.

---

## 8. Langkah Verifikasi yang Perlu Dilakukan Sebelum Memilih

1. Ambil file PDF "Laporan Kegiatan" yang **gagal** (dari MinIO produksi).
2. Cek struktur: `grep -a -c "/ObjStm"` → pastikan > 0 (konfirmasi akar masalah).
3. Tes konversi OnlyOffice VPS dengan `outputtype: 'pdfa'` → cek hasilnya:
   - Sukses konversi?
   - `/ObjStm` hilang?
   - FPDI bisa `setSourceFile()` tanpa exception?
4. Kalau ketiganya ya → jalankan Opsi B. Kalau tidak → naik ke fpdi-pdf-parser / Ghostscript.
