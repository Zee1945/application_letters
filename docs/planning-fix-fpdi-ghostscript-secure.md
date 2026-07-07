# Planning: Normalisasi PDF via Ghostscript (Fokus Keamanan)

> Status: **planning, belum ada perubahan kode.**
> Melengkapi `docs/planning-fix-fpdi-ghostscript.md` dengan pertimbangan keamanan
> yang lengkap — karena alur merge **memproses PDF yang di-upload user**.

---

## 1. Konteks Singkat

- **Masalah:** FPDI free (`setasign/fpdi` v2.6.8, tanpa `fpdi-pdf-parser`) gagal
  `setSourceFile()` pada PDF hasil OnlyOffice VPS yang memakai **cross-reference stream (`/XRef`)**.
  - Bukti byte-level: file VPS → `/XRef`=1, `^xref`=0 (gagal). File lokal → `/XRef`=0, `^xref`=1 (sukses).
  - Dua-duanya header PDF 1.7 & produsen OnlyOffice → pembeda = **versi engine OnlyOffice**.
- **Solusi rencana ini:** normalisasi tiap PDF ke PDF 1.4 (xref table klasik) via Ghostscript
  sebelum FPDI membaca — sehingga FPDI free bisa parse.
- **Titik sisip:** `PdfMergerService::ensurePdf()` (sebelum `mergePdfs()`).

---

## 2. Model Ancaman (Threat Model)

Yang membuat gs perlu perhatian keamanan: **sumber PDF yang dinormalisasi tidak semuanya tepercaya.**

| Sumber PDF ke merge | Tepercaya? | Risiko |
|---|---|---|
| Hasil OnlyOffice (TOR, SK, surat) dari template sistem | ✅ relatif | Rendah (bukan file arbitrer attacker) |
| **Lampiran upload user** (SPJ, notulensi, absensi, foto, materi narasumber) | ❌ **tidak** | **Tinggi** — user bisa upload PDF crafting |

Karena `ensurePdf`/merge memproses lampiran upload user, memasukkan gs = **menambah attack
surface terhadap file tak-tepercaya**. Semua mitigasi di bawah ditujukan ke sini.

### Dua kelas ancaman berbeda (jangan dicampur)

1. **Command injection** — dari **cara memanggil** gs (kesalahan kode), bukan dari isi PDF.
2. **Eksploitasi parser Ghostscript** — dari **isi PDF jahat** yang mengeksploitasi bug gs
   (historisnya bisa sampai RCE). Ini yang paling relevan dengan "inject script dalam PDF".

---

## 3. Mitigasi Kelas 1 — Command Injection (WAJIB, mudah & tuntas)

**Aturan mutlak: JANGAN pakai shell string.** Gunakan `Symfony\Component\Process\Process`
dengan **array arguments** (tiap argumen elemen array terpisah).

```php
// ✅ BENAR — array args, tidak ada shell yang mem-parse
new \Symfony\Component\Process\Process([
    $gsBinary,
    '-dSAFER',
    '-sDEVICE=pdfwrite',
    // ...
    '-sOutputFile=' . $outPath,
    $inputPath,          // aman walau berisi ; | $() ' " spasi
]);

// ❌ SALAH — jangan pernah:
// exec("gs ... $inputPath");
// Process::fromShellCommandline("gs ... $inputPath");
// shell_exec(...), system(...), passthru(...)
```

Dengan array-args, argumen dikirim langsung ke `execve()` tanpa shell → nama file berisi
metakarakter shell diperlakukan sebagai **string literal**, bukan perintah. Command injection
tertutup total.

**Tambahan pengerasan input path:**
- Path input **selalu file temp yang kita buat sendiri** (hasil `tempPath()` / download MinIO),
  bukan nama dari user langsung. Jangan pernah menyusun path dari `getClientOriginalName()` mentah.
- Validasi file benar-benar ada & di dalam direktori temp yang diharapkan (`realpath` di bawah
  base temp dir) sebelum diproses — cegah path traversal.

---

## 4. Mitigasi Kelas 2 — Eksploitasi Parser Ghostscript (INTI dari pertanyaan keamanan)

Ghostscript punya sejarah CVE serius (mis. CVE-2018-16509, CVE-2021-3781, CVE-2023-36664)
di mana **PDF/PostScript crafting bisa RCE** bila gs rentan & tak di-sandbox.

### 4.1 `-dSAFER` (WAJIB, jangan andalkan default)
```
-dSAFER
```
Membatasi akses file system & operator berbahaya dari dalam dokumen. Sejak gs 9.28
`-dSAFER` aktif default, TAPI set eksplisit agar tak bergantung versi/konfig.

### 4.2 Ghostscript versi baru (WAJIB)
- Pakai **gs ≥ 10.x** (idealnya terbaru dari repo distro yang dipatch).
- **Jangan pakai gs lama** — banyak RCE lama di sana. (Ironis tapi konsisten: sama seperti
  argumen "jangan pakai OnlyOffice lama", gs pun harus baru.)
- Cek: `gs --version`.

### 4.3 Batasi device & fitur
- Hanya `-sDEVICE=pdfwrite` (kita cuma butuh tulis PDF). Jangan izinkan device lain.
- `-dNOPAUSE -dBATCH -dQUIET` — non-interaktif, tak menunggu input.
- Pertimbangkan `-dNODISPLAY` tidak relevan (kita pakai pdfwrite), tapi hindari device
  yang mengeksekusi PostScript untuk output layar.

### 4.4 Batas sumber daya & waktu
- `Process::setTimeout(120)` — cegah PDF "bom" (loop tak henti / konsumsi CPU).
- Pertimbangkan batas memori di level OS (ulimit/cgroup) untuk worker.
- Batasi ukuran file input sebelum diproses (sudah ada `$upload_rules` max filesize;
  pastikan berlaku juga untuk file yang masuk merge).

### 4.5 Least privilege (pengerasan environment)
- Worker queue yang menjalankan gs **berjalan sebagai user non-root**.
- User itu **tanpa akses tulis** ke path sensitif; hanya ke direktori temp merge.
- Idealnya gs dijalankan dalam **container/sandbox terpisah** (mis. worker container khusus),
  sehingga kompromi gs tidak langsung mengenai host/app.
- Bila memungkinkan: seccomp/AppArmor profile untuk proses gs.

### 4.6 Validasi output
- Setelah gs jalan: pastikan **exit code 0** DAN file output **ada & > 0 byte**.
- Bila gagal → **fallback ke file asli** (jangan hapus data), log detail (`exit`, `stderr`).

---

## 5. Rencana Kode (dengan pengerasan keamanan)

### 5.1 Deteksi binary gs (cache)
```php
private ?string $gsBinary = null;
private bool $gsChecked = false;

private function ghostscriptBinary(): ?string
{
    if ($this->gsChecked) return $this->gsBinary ?: null;
    $this->gsChecked = true;

    foreach (['gs', 'gswin64c'] as $bin) {            // gs=Linux/VPS, gswin64c=Windows lokal
        try {
            $p = new \Symfony\Component\Process\Process([$bin, '--version']);
            $p->setTimeout(10);
            $p->run();
            if ($p->isSuccessful()) { $this->gsBinary = $bin; return $bin; }
        } catch (\Throwable $e) { /* coba kandidat berikutnya */ }
    }
    $this->gsBinary = '';
    Log::warning('Ghostscript tidak ditemukan; normalisasi PDF dilewati.');
    return null;
}
```

### 5.2 Normalisasi satu file (aman + fallback)
```php
private function normalizePdf(string $path, array &$tempFiles): string
{
    $gs = $this->ghostscriptBinary();
    if (!$gs) return $path;                            // tanpa gs: perilaku lama

    // Pastikan input adalah file temp milik kita (anti path traversal).
    $real = realpath($path);
    if ($real === false || !is_file($real)) return $path;

    $outPath = $this->tempPath('gsnorm_' . uniqid() . '.pdf');

    try {
        $process = new \Symfony\Component\Process\Process([
            $gs,
            '-dSAFER',                                 // sandbox (WAJIB)
            '-dNOPAUSE', '-dBATCH', '-dQUIET',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',                // -> xref table klasik, FPDI-friendly
            '-sOutputFile=' . $outPath,
            $real,
        ]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful() && is_file($outPath) && filesize($outPath) > 0) {
            $tempFiles[] = $outPath;
            return $outPath;
        }

        Log::warning('Normalisasi gs gagal, pakai file asli.', [
            'exit' => $process->getExitCode(),
            'err'  => mb_substr($process->getErrorOutput(), 0, 500),
        ]);
    } catch (\Throwable $e) {
        Log::warning('Exception normalisasi gs: ' . $e->getMessage());
    }

    if (is_file($outPath)) @unlink($outPath);          // bersihkan output gagal
    return $path;                                      // fallback aman
}
```

### 5.3 Panggil di ensurePdf()
```php
if ($mime === 'application/pdf') {
    $result[] = $this->normalizePdf($path, $tempFiles);           // PDF (mis. dari MinIO)
    continue;
}
// ... image tetap via imageToPdf (hasil mPDF sudah FPDI-friendly)
// DOCX -> OnlyOffice -> normalisasi:
$pdfPath = FileManagementService::convertToPdf($path);
if ($pdfPath) {
    $tempFiles[] = $pdfPath;
    $result[] = $this->normalizePdf($pdfPath, $tempFiles);
}
```
> Verifikasi dulu: `convertToPdf` return **path** atau **byte**? Bila byte, tulis ke temp
> dulu sebelum `normalizePdf`.

### 5.4 (Disarankan) Jadikan skip halaman terlihat
Di `mergePdfs()` saat `catch` gagal import → tandai report `merge_status = 'partial'`
(bukan hanya `Log::warning`), agar LPJ tidak lengkap tidak lolos diam-diam.

---

## 6. Checklist Keamanan (ringkas — semua harus ✅ sebelum produksi)

- [ ] Panggil gs via `Process` **array-args** (bukan shell string / exec).
- [ ] `-dSAFER` di-set eksplisit.
- [ ] gs versi **≥ 10.x** di VPS **dan** mesin worker queue.
- [ ] Device dibatasi `pdfwrite` saja.
- [ ] `setTimeout` + batas ukuran file input.
- [ ] Worker jalan sebagai **user non-root**, akses terbatas ke dir temp.
- [ ] (Ideal) gs terisolasi di container/sandbox worker.
- [ ] Input path = file temp milik sistem, di-`realpath` & dicek di dalam base dir.
- [ ] Validasi output (exit 0 + file > 0 byte) + fallback aman ke file asli.
- [ ] Temp hasil gs masuk `$tempFiles` → dibersihkan `cleanupTempFiles()`.

---

## 7. Perbandingan Attack Surface vs Alternatif

| Solusi | Attack surface pada PDF **upload user** | Catatan |
|---|---|---|
| **Ghostscript** | ⚠️ Menengah — proses OS mem-parse PDF user; **butuh** `-dSAFER` + gs baru + least-priv | Paling andal & gratis; risiko dikelola dengan pengerasan di atas |
| **fpdi-pdf-parser (berbayar)** | ✅ Lebih kecil — parsing di dalam runtime PHP, tanpa proses OS eksternal | Berbayar; risiko parser tetap ada tapi umumnya tak sampai RCE OS |
| **Downgrade OnlyOffice** | ✅ Tak menambah surface merge | Punya masalah keamanan sendiri (versi lama) + rapuh |

**Kesimpulan keamanan:** gs **aman dipakai** untuk file upload user **asalkan** seluruh
checklist bagian 6 dipenuhi (terutama `-dSAFER` + gs versi baru + least privilege). Tanpa itu,
gs pada file tak-tepercaya = risiko RCE nyata. Bila tim ingin attack surface seminimal mungkin
tanpa mengelola pengerasan OS, `fpdi-pdf-parser` lebih ringan dari sisi keamanan (dengan biaya lisensi).

---

## 8. Risiko Non-Keamanan (tetap perlu dicatat)

| Risiko | Mitigasi |
|---|---|
| gs tak terpasang di VPS/worker | Deteksi + fallback + dokumentasi instalasi |
| gs mengubah layout (font/warna) | Verifikasi visual sebelum produksi; `-dPDFSETTINGS=/prepress` bila perlu embed font |
| `convertToPdf` return byte, bukan path | Verifikasi tipe return; tulis ke temp dulu |
| Worker beda mesin dari web | Pastikan gs ada di environment **yang menjalankan queue** |
| Overhead proses per file | Timeout wajar; hanya saat merge (bukan hot path) |

---

## 9. Langkah Verifikasi (before/after)

1. **Before:** `grep -a -c "/XRef" file.pdf` (VPS = 1) & `grep -a -c "^xref" file.pdf` (= 0).
2. Jalankan gs manual sekali dengan `-dSAFER`:
   `gs -dSAFER -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dBATCH -dQUIET -sOutputFile=out.pdf file.pdf`
   → `grep -a -c "/XRef" out.pdf` (harus 0) & `grep -a -c "^xref" out.pdf` (harus > 0).
3. FPDI: `(new \setasign\Fpdi\Fpdi())->setSourceFile('out.pdf')` tak lempar exception.
4. **Visual:** bandingkan out.pdf dengan asli (font, tabel, tanda tangan, QR).
5. **End-to-end:** `mergeLpjWithAttachments` untuk 1 aplikasi bermasalah → tak ada warning
   "Gagal import PDF", halaman lengkap.
6. **Uji keamanan (opsional):** proses PDF uji yang diketahui memicu operator PostScript
   berbahaya → pastikan `-dSAFER` memblokir (tidak ada file tak terduga tertulis / perintah jalan).

---

## 10. Urutan Implementasi

1. Pastikan gs ≥ 10.x di VPS **dan** mesin worker; worker jalan non-root.
2. Cek return type `FileManagementService::convertToPdf` (path vs byte).
3. Tambah `ghostscriptBinary()` + `normalizePdf()` (dengan `-dSAFER`, array-args, realpath, timeout).
4. Panggil `normalizePdf()` di `ensurePdf()` (cabang PDF & hasil OnlyOffice).
5. (Disarankan) tandai LPJ `partial` saat masih ada halaman gagal.
6. Jalankan verifikasi bagian 9 + checklist keamanan bagian 6.
7. Audit LPJ lama yang mungkin sudah kehilangan halaman; regenerate bila perlu.
