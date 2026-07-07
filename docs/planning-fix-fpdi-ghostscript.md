# Planning: Normalisasi PDF via Ghostscript sebelum Merge (FPDI)

> Status: **planning, belum ada perubahan kode.**
> Konteks: FPDI free (`setasign/fpdi` v2.6.8) gagal `setSourceFile()` pada PDF hasil
> OnlyOffice VPS karena **object stream / xref stream** (lihat
> `docs/analisa-fpdi-onlyoffice-pdf.md`). Opsi B (OnlyOffice PDF/A) belum terbukti
> jalan. Rencana ini memakai Ghostscript untuk menulis ulang PDF ke bentuk yang
> bisa dibaca FPDI free, tepat sebelum merge.

---

## 1. Ide Inti

Sebelum FPDI membaca PDF, **normalisasi** tiap file ke PDF 1.4 (tanpa object stream)
memakai Ghostscript. FPDI free lalu bisa `setSourceFile()` tanpa error.

```
download MinIO -> temp lokal -> [NORMALISASI gs -> temp baru] -> FPDI merge
```

Titik sisip: `PdfMergerService::ensurePdf()` (setiap file yang sudah PDF), atau tepat
sebelum `mergePdfs()`. Rekomendasi: di `ensurePdf()` agar semua cabang (PDF langsung,
hasil konversi OnlyOffice, hasil imageToPdf) ikut ternormalisasi seragam.

Perintah Ghostscript:
```bash
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/prepress \
   -dNOPAUSE -dBATCH -dQUIET -sOutputFile=<out.pdf> <in.pdf>
```
- `-dCompatibilityLevel=1.4` → paksa PDF 1.4 → tidak ada object stream → FPDI-friendly.
- `-dPDFSETTINGS=/prepress` → jaga kualitas & (idealnya) embed font → minim risiko layout.
- `-dNOPAUSE -dBATCH -dQUIET` → non-interaktif, senyap.

---

## 2. Prasyarat

- **Ghostscript ter-install di setiap environment yang menjalankan merge** (VPS + mesin
  yang menjalankan queue worker). Cek: `gs --version`. (Ini konsekuensi memilih gs:
  menambah dependensi binary OS.)
- Nama binary: `gs` (Linux/VPS). Di Windows lokal biasanya `gswin64c`. Implementasi harus
  mendeteksi keduanya.
- Gunakan `Symfony\Component\Process\Process` (bawaan Laravel), **bukan** `exec()` mentah,
  demi keamanan argumen & timeout.

---

## 3. Rencana Perubahan Kode

### 3.1 Helper deteksi ketersediaan gs (cache hasil)
```php
// PdfMergerService
private ?string $gsBinary = null;      // cache: null=belum dicek, ''=tidak ada, 'gs'/'gswin64c'=ada
private bool $gsChecked = false;

private function ghostscriptBinary(): ?string
{
    if ($this->gsChecked) {
        return $this->gsBinary ?: null;
    }
    $this->gsChecked = true;

    foreach (['gs', 'gswin64c'] as $bin) {
        try {
            $p = new \Symfony\Component\Process\Process([$bin, '--version']);
            $p->setTimeout(10);
            $p->run();
            if ($p->isSuccessful()) {
                $this->gsBinary = $bin;
                return $bin;
            }
        } catch (\Throwable $e) {
            // lanjut ke kandidat berikutnya
        }
    }
    $this->gsBinary = '';
    Log::warning('Ghostscript tidak ditemukan; normalisasi PDF dilewati (FPDI bisa gagal pada PDF object-stream).');
    return null;
}
```

### 3.2 Helper normalisasi satu file (fallback aman)
```php
/**
 * Tulis ulang PDF ke 1.4 (tanpa object stream) via Ghostscript agar FPDI free bisa baca.
 * Return path hasil normalisasi; bila gs tak ada / gagal, return $path asli (fallback).
 */
private function normalizePdf(string $path, array &$tempFiles): string
{
    $gs = $this->ghostscriptBinary();
    if (!$gs) {
        return $path; // tanpa gs: perilaku seperti sekarang
    }

    $outPath = $this->tempPath('gsnorm_' . uniqid() . '.pdf');

    try {
        $process = new \Symfony\Component\Process\Process([
            $gs,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/prepress',
            '-dNOPAUSE', '-dBATCH', '-dQUIET',
            '-sOutputFile=' . $outPath,
            $path,
        ]);
        $process->setTimeout(120);
        $process->run();

        // Sukses = exit 0 DAN file output valid (ada & > 0 byte).
        if ($process->isSuccessful() && is_file($outPath) && filesize($outPath) > 0) {
            $tempFiles[] = $outPath;
            return $outPath;
        }

        Log::warning('Normalisasi gs gagal, pakai file asli.', [
            'path' => $path,
            'exit' => $process->getExitCode(),
            'err'  => $process->getErrorOutput(),
        ]);
    } catch (\Throwable $e) {
        Log::warning('Exception saat normalisasi gs: ' . $e->getMessage(), ['path' => $path]);
    }

    // Fallback: kembalikan file asli (biar alur lama tetap jalan; FPDI mungkin skip).
    if (is_file($outPath)) {
        @unlink($outPath);
    }
    return $path;
}
```

### 3.3 Panggil di ensurePdf()
Ubah cabang "sudah PDF" (dan idealnya hasil konversi OnlyOffice juga) agar dinormalisasi:
```php
private function ensurePdf(array $paths, array &$tempFiles): array
{
    $result = [];
    foreach ($paths as $path) {
        $mime = mime_content_type($path);

        if ($mime === 'application/pdf') {
            $result[] = $this->normalizePdf($path, $tempFiles);   // <— NORMALISASI
            continue;
        }

        if (str_starts_with($mime, 'image/')) {
            $pdfPath = $this->imageToPdf($path, $tempFiles);
            if ($pdfPath) {
                $result[] = $pdfPath;   // hasil mPDF sudah FPDI-friendly, tak wajib gs
            }
            continue;
        }

        // DOCX/DOC -> OnlyOffice -> PDF (INI yang object-stream) -> normalisasi
        $pdfPath = FileManagementService::convertToPdf($path);
        if ($pdfPath) {
            $tempFiles[] = $pdfPath;
            $result[] = $this->normalizePdf($pdfPath, $tempFiles); // <— NORMALISASI
        }
    }
    return $result;
}
```

> Catatan: `convertToPdf` mengembalikan **konten** atau **path**? Perlu dicek — bila ia
> mengembalikan byte, harus ditulis ke temp dulu sebelum `normalizePdf`. (Di `ensurePdf`
> saat ini hasilnya diperlakukan sebagai path & dimasukkan ke `$tempFiles`, jadi asumsinya path.)

### 3.4 (Opsional tapi disarankan) Buat skip halaman terlihat
Di `mergePdfs()`, saat `catch` gagal import, tandai LPJ tidak lengkap (mis. set flag /
`merge_status = 'partial'` pada report), bukan hanya `Log::warning`. Supaya kegagalan
tidak lolos diam-diam.

---

## 4. Perilaku & Keamanan

- **Idempoten & aman**: bila gs tak ada atau gagal → fallback ke file asli → alur lama
  tetap jalan (paling buruk: sama seperti sekarang, halaman object-stream tetap di-skip).
- **Tidak menaikkan risiko data loss**: normalisasi menulis ke file temp baru; file asli
  tak disentuh.
- **Timeout** di-set (120s) agar proses gs tidak menggantung worker.
- **Process array-args** (bukan string) → tidak ada risiko shell-injection dari nama file.
- Temp hasil gs dimasukkan ke `$tempFiles` → ikut dibersihkan `cleanupTempFiles()`.

---

## 5. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| gs tak terpasang di VPS/worker | Normalisasi dilewati, masalah tetap | Deteksi + log jelas; dokumentasikan `apt install ghostscript` |
| gs mengubah tampilan (font/warna) | Layout berubah | `-dPDFSETTINGS=/prepress` (embed font); **verifikasi visual** sebelum produksi |
| Overhead proses per file | Merge sedikit lebih lambat | Timeout wajar; hanya saat merge (bukan hot path) |
| `convertToPdf` return byte, bukan path | `normalizePdf` gagal baca | Verifikasi tipe return; tulis ke temp dulu bila byte |
| Worker beda mesin dari web | gs ada di web tapi tak di worker | Pastikan gs di environment **yang menjalankan queue** |

---

## 6. Verifikasi (sebelum & sesudah)

1. **Sebelum**: ambil 1 PDF yang gagal → `grep -a -c "/ObjStm" file.pdf` (harus > 0).
2. Jalankan gs manual sekali:
   `gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o out.pdf file.pdf`
   → `grep -a -c "/ObjStm" out.pdf` (harus 0) → `grep -a -c "^xref" out.pdf` (harus > 0).
3. Uji FPDI: `(new \setasign\Fpdi\Fpdi())->setSourceFile('out.pdf')` tidak lempar exception.
4. **Visual**: buka out.pdf, bandingkan dengan asli (font, tabel, tanda tangan, QR).
5. **End-to-end**: jalankan `mergeLpjWithAttachments` untuk 1 aplikasi bermasalah →
   pastikan tidak ada warning "Gagal import PDF" & halaman lengkap.

---

## 7. Langkah Implementasi (urutan)

1. Konfirmasi gs tersedia di VPS + mesin worker (`gs --version`). Bila belum, install.
2. Cek return type `FileManagementService::convertToPdf` (path vs byte) → sesuaikan 3.3.
3. Tambah `ghostscriptBinary()` + `normalizePdf()` di `PdfMergerService`.
4. Panggil `normalizePdf()` di `ensurePdf()` (cabang PDF & hasil OnlyOffice).
5. (Opsional) tandai LPJ "partial" saat masih ada halaman gagal di `mergePdfs()`.
6. Verifikasi sesuai bagian 6.
7. Audit LPJ lama yang mungkin sudah kehilangan halaman (regenerate bila perlu).

---

## 8. Kelebihan Pendekatan gs (ringkas)

- **Paling andal** untuk segala PDF bandel, apa pun sumbernya (bukan cuma OnlyOffice).
- **Kebal versi OnlyOffice** (termasuk image `latest` yang bisa berubah).
- Tidak perlu library PHP baru, tidak perlu lisensi berbayar.

## 9. Kekurangan (ringkas)

- **Butuh binary OS di VPS + worker** (menambah dependensi environment).
- Perlu verifikasi visual sekali untuk memastikan layout tidak bergeser.
- Sedikit overhead waktu saat merge.
