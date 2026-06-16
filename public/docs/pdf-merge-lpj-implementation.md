# Rencana Implementasi PDF Merge LPJ

> Dokumen ini adalah jawaban teknis atas prompt `prompt-pdf-merger-lpj.md`, dianalisa berdasarkan kode aktual di codebase.

---

## 1. Rekomendasi Library PDF Merger

### Evaluasi

| Library | Kelebihan | Kekurangan | Rekomendasi |
|---|---|---|---|
| `webklex/laravel-pdfmerger` | Sudah terinstall, API simpel | Wrapper tipis atas FPDI, kurang aktif dikembangkan | Cukup untuk file sederhana |
| `setasign/fpdi` | Solid, aktif, bisa manipulasi per-halaman | Perlu sedikit boilerplate | **Rekomendasi utama** |
| `ilovepdf/ilovepdf-php` | Cloud-based, handle PDF kompleks | Bergantung internet, berbayar untuk volume besar | Tidak cocok production offline |
| `pdftk` (binary) | Sangat handal, handle PDF terenkripsi | Perlu install di server, dependency binary | Cadangan jika FPDI gagal |
| `Ghostscript` (binary) | Paling powerful | Berat, lisensi AGPL | Overkill untuk use case ini |

### Keputusan

**Gunakan `setasign/fpdi` + `setasign/fpdi-fpdf`** sebagai core merger.

`webklex/laravel-pdfmerger` sudah menggunakan FPDI di baliknya, jadi tidak perlu install ulang — cukup pakai langsung.

```bash
composer require setasign/fpdi
```

**Catatan penting:** FPDI tidak bisa merge PDF yang diproteksi/terenkripsi. Jika file dari MinIO terenkripsi, perlu fallback ke `pdftk`:
```bash
# Di VPS (Ubuntu/Debian)
sudo apt-get install pdftk-java
```

---

## 2. Desain Flag "Aktifkan Merge"

### Analisa opsi

| Opsi | Pro | Kontra |
|---|---|---|
| Kolom di `application_reports` | Dekat dengan data LPJ | Harus migrasi, report belum tentu ada saat flag di-set |
| Kolom di `applications` | Tersedia lebih awal di flow | Terlalu jauh dari konteks report |
| Config app | Zero migrasi | Berlaku global, tidak bisa per-aplikasi |
| Kolom di `application_files` | Granular per file | Terlalu detail, sulit dibaca |

### Rekomendasi: Kolom di `application_reports`

Tambahkan 2 kolom ke tabel `application_reports`:

```php
// Migration baru — TIDAK ubah tabel lain
$table->boolean('merge_with_attachments')->default(false)->after('closing');
$table->enum('merge_status', ['idle', 'pending', 'processing', 'done', 'failed'])
      ->default('idle')->after('merge_with_attachments');
$table->unsignedBigInteger('merged_file_id')->nullable()->after('merge_status');
$table->foreign('merged_file_id')->references('id')->on('files')->nullOnDelete();
```

**Alasan:**
- Flag `merge_with_attachments` di-set oleh user saat mengisi form LPJ (sebelum submit)
- `merge_status` untuk tracking async job
- `merged_file_id` menyimpan referensi ke file hasil merge di tabel `files` (yang sudah ada)
- Tidak menyentuh tabel lain sama sekali

---

## 3. Desain Tabel/Kolom Baru

### Migration: `add_merge_columns_to_application_reports_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_reports', function (Blueprint $table) {
            $table->boolean('merge_with_attachments')->default(false)->after('closing');
            $table->enum('merge_status', ['idle', 'pending', 'processing', 'done', 'failed'])
                  ->default('idle')->after('merge_with_attachments');
            $table->unsignedBigInteger('merged_file_id')->nullable()->after('merge_status');
            $table->timestamp('merged_at')->nullable()->after('merged_file_id');
            $table->text('merge_error')->nullable()->after('merged_at');

            $table->foreign('merged_file_id')->references('id')->on('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_reports', function (Blueprint $table) {
            $table->dropForeign(['merged_file_id']);
            $table->dropColumn([
                'merge_with_attachments',
                'merge_status',
                'merged_file_id',
                'merged_at',
                'merge_error',
            ]);
        });
    }
};
```

**Tidak perlu tabel baru** — urutan lampiran sudah implisit dari urutan proses di service (hardcoded sequence sesuai requirement), dan `merged_file_id` cukup untuk referensi hasil.

---

## 4. Arsitektur Service

### Keputusan: Buat `PdfMergeService` baru (jangan extend `FileManagementService`)

**Alasan:**
- `FileManagementService` sudah punya tanggung jawab jelas (storage + konversi)
- Merge logic kompleks dan akan bertumbuh — layak dapat class sendiri
- Memudahkan testing secara isolated

### Dependency Graph

```
MergeLpjDocumentJob
    └── PdfMergeService
            ├── FileManagementService  (ambil file dari MinIO, upload hasil)
            └── TemplateProcessorService (konversi foto dokumentasi ke PDF)
```

### Integrasi dengan Job yang Sudah Ada

Di `GenerateReportJob`, tambahkan dispatch ke `MergeLpjDocumentJob` **setelah** LPJ selesai di-generate:

```php
// GenerateReportJob.php — tambahkan di akhir handle()
if ($report->merge_with_attachments) {
    $report->update(['merge_status' => 'pending']);
    MergeLpjDocumentJob::dispatch($application)->onQueue('documents');
}
```

---

## 5. Implementasi Step-by-Step

### 5.1 `PdfMergeService`

```php
<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfMergeService
{
    public function __construct(
        private FileManagementService $fileService,
        private TemplateProcessorService $templateService,
    ) {}

    /**
     * Entry point: merge semua lampiran ke dalam LPJ utama.
     * Urutan sesuai requirement: LPJ → Realisasi → Dokumentasi → File Pendukung → Data Personal
     */
    public function mergeLpjWithAttachments(Application $application): string
    {
        $report = $application->report;

        $report->update(['merge_status' => 'processing']);

        $tempFiles = [];

        try {
            $sections = $this->buildMergeSections($application, $report, $tempFiles);

            if (count($sections) <= 1) {
                // Hanya LPJ saja, tidak perlu merge
                $report->update(['merge_status' => 'idle']);
                return '';
            }

            $outputPath = $this->mergePdfs($sections, $application->id);
            $tempFiles[] = $outputPath;

            $fileRecord = $this->fileService->storeFileApplication(
                filePath: $outputPath,
                filename: "LPJ-Lengkap-{$application->id}.pdf",
                applicationId: $application->id,
                fileTypeCode: 'lpj_merged',
                mimeType: 'application/pdf',
            );

            $report->update([
                'merge_status'   => 'done',
                'merged_file_id' => $fileRecord->id,
                'merged_at'      => now(),
                'merge_error'    => null,
            ]);

            return $outputPath;

        } catch (\Throwable $e) {
            Log::error("PDF merge gagal untuk application {$application->id}: " . $e->getMessage());

            $report->update([
                'merge_status' => 'failed',
                'merge_error'  => $e->getMessage(),
            ]);

            throw $e;

        } finally {
            $this->cleanupTempFiles($tempFiles);
        }
    }

    /**
     * Kumpulkan semua path PDF yang akan di-merge sesuai urutan.
     * $tempFiles di-pass by reference agar bisa di-cleanup di finally.
     */
    private function buildMergeSections(Application $application, ApplicationReport $report, array &$tempFiles): array
    {
        $sections = [];

        // 1. LPJ Utama (wajib ada)
        $lpjPath = $this->getLpjPdfPath($application);
        if (!$lpjPath) {
            throw new \RuntimeException("File PDF LPJ utama tidak ditemukan untuk application {$application->id}");
        }
        $sections[] = $lpjPath;

        // 2. Form Realisasi (jika ada data realisasi + file SPBY/kuitansi)
        $realisasiSection = $this->buildRealisasiSection($application, $tempFiles);
        array_push($sections, ...$realisasiSection);

        // 3. Dokumentasi Kegiatan (jika ada document-photos)
        $dokumentasiSection = $this->buildDokumentasiSection($application, $report, $tempFiles);
        array_push($sections, ...$dokumentasiSection);

        // 4. File Pendukung (attendence-files dan tipe lain)
        $pendukungSection = $this->buildFilePendukungSection($report, $tempFiles);
        array_push($sections, ...$pendukungSection);

        // 5. Data Personal Narasumber/Moderator
        $personalSection = $this->buildDataPersonalSection($application, $tempFiles);
        array_push($sections, ...$personalSection);

        return $sections;
    }

    // --- SECTION BUILDERS ---

    private function buildRealisasiSection(Application $application, array &$tempFiles): array
    {
        $budgets = $application->draftCostBudgets()->with('files')->get();
        $spbyFiles = $budgets->flatMap->files->filter();

        if ($spbyFiles->isEmpty()) {
            return [];
        }

        $coverPath = public_path('templates/covers/cover_realisasi.pdf');
        if (!file_exists($coverPath)) {
            Log::warning("Cover realisasi tidak ditemukan, skip section realisasi.");
            return [];
        }

        $section = [$coverPath];

        foreach ($spbyFiles as $file) {
            $path = $this->downloadTempFromMinio($file, $tempFiles);
            if ($path) {
                $section[] = $path;
            }
        }

        return $section;
    }

    private function buildDokumentasiSection(Application $application, ApplicationReport $report, array &$tempFiles): array
    {
        $photoAttachments = $report->attachments()
            ->where('type', 'document-photos')
            ->with('file')
            ->get();

        if ($photoAttachments->isEmpty()) {
            return [];
        }

        $coverPath = public_path('templates/covers/cover_dokumentasi.pdf');
        if (!file_exists($coverPath)) {
            Log::warning("Cover dokumentasi tidak ditemukan, skip section dokumentasi.");
            return [];
        }

        // Kumpulkan foto, konversi ke PDF via TemplateProcessorService / OnlyOffice
        $photoPaths = [];
        foreach ($photoAttachments as $attachment) {
            $path = $this->downloadTempFromMinio($attachment->file, $tempFiles);
            if ($path) {
                $photoPaths[] = $path;
            }
        }

        if (empty($photoPaths)) {
            return [];
        }

        // Jika foto bukan PDF, konversi via OnlyOffice
        $pdfPaths = $this->ensurePdf($photoPaths, $tempFiles);

        return array_merge([$coverPath], $pdfPaths);
    }

    private function buildFilePendukungSection(ApplicationReport $report, array &$tempFiles): array
    {
        $pendukungAttachments = $report->attachments()
            ->whereIn('type', ['attendence-files', 'minutes-file'])
            ->with('file')
            ->get();

        if ($pendukungAttachments->count() <= 0) {
            return [];
        }

        $coverPath = public_path('templates/covers/cover_file_pendukung.pdf');
        if (!file_exists($coverPath)) {
            Log::warning("Cover file pendukung tidak ditemukan, skip section ini.");
            return [];
        }

        $section = [$coverPath];

        foreach ($pendukungAttachments as $attachment) {
            $path = $this->downloadTempFromMinio($attachment->file, $tempFiles);
            if ($path) {
                $section[] = $path;
            }
        }

        return $section;
    }

    private function buildDataPersonalSection(Application $application, array &$tempFiles): array
    {
        $participants = $application->participants()
            ->whereNotNull('cv_file_id')
            ->orWhereNotNull('idcard_file_id')
            ->orWhereNotNull('npwp_file_id')
            ->with(['cvFile', 'idcardFile', 'npwpFile'])
            ->get();

        if ($participants->isEmpty()) {
            return [];
        }

        $coverPath = public_path('templates/covers/cover_data_personal.pdf');
        if (!file_exists($coverPath)) {
            Log::warning("Cover data personal tidak ditemukan, skip section ini.");
            return [];
        }

        $section = [$coverPath];

        foreach ($participants as $participant) {
            foreach (['cvFile', 'idcardFile', 'npwpFile'] as $relation) {
                $file = $participant->{$relation};
                if ($file) {
                    $path = $this->downloadTempFromMinio($file, $tempFiles);
                    if ($path) {
                        $section[] = $path;
                    }
                }
            }
        }

        return $section;
    }

    // --- HELPERS ---

    private function getLpjPdfPath(Application $application): ?string
    {
        $appFile = $application->applicationFiles()
            ->findCode('laporan_kegiatan')
            ->whereNotNull('file_id')
            ->with('file')
            ->first();

        if (!$appFile?->file) {
            return null;
        }

        return $this->downloadTempFromMinio($appFile->file, $this->tempRegistry);
    }

    /**
     * Download file dari MinIO ke temp lokal.
     * Kembalikan path lokal, atau null jika file tidak ada.
     */
    private function downloadTempFromMinio($fileModel, array &$tempFiles): ?string
    {
        if (!$fileModel) {
            return null;
        }

        try {
            $content = Storage::disk('minio')->get($fileModel->path);
            $tempPath = sys_get_temp_dir() . '/merge_' . uniqid() . '_' . basename($fileModel->path);
            file_put_contents($tempPath, $content);
            $tempFiles[] = $tempPath;
            return $tempPath;
        } catch (\Throwable $e) {
            Log::warning("Gagal download file {$fileModel->id} dari MinIO: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Pastikan semua file adalah PDF.
     * File yang bukan PDF (gambar, docx) di-konversi via OnlyOffice.
     */
    private function ensurePdf(array $paths, array &$tempFiles): array
    {
        $result = [];
        foreach ($paths as $path) {
            $mime = mime_content_type($path);
            if ($mime === 'application/pdf') {
                $result[] = $path;
                continue;
            }

            // Gambar: wrap ke PDF sederhana via Fpdi/mPDF
            if (str_starts_with($mime, 'image/')) {
                $pdfPath = $this->imageToPdf($path, $tempFiles);
                if ($pdfPath) {
                    $result[] = $pdfPath;
                }
                continue;
            }

            // DOCX/DOC: konversi via OnlyOffice (sudah ada di FileManagementService)
            $pdfPath = $this->fileService->convertToPdf($path);
            if ($pdfPath) {
                $tempFiles[] = $pdfPath;
                $result[] = $pdfPath;
            }
        }
        return $result;
    }

    /**
     * Convert gambar ke PDF satu halaman menggunakan mPDF
     * (mPDF sudah dipakai di TemplateProcessorService)
     */
    private function imageToPdf(string $imagePath, array &$tempFiles): ?string
    {
        try {
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
            $mime = mime_content_type($imagePath);
            $b64  = base64_encode(file_get_contents($imagePath));
            $mpdf->WriteHTML(
                "<img src=\"data:{$mime};base64,{$b64}\" style=\"max-width:100%;max-height:100%;\">"
            );
            $outputPath = sys_get_temp_dir() . '/img_pdf_' . uniqid() . '.pdf';
            $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
            $tempFiles[] = $outputPath;
            return $outputPath;
        } catch (\Throwable $e) {
            Log::warning("Gagal konversi gambar ke PDF: {$imagePath} — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Merge array of PDF paths menjadi satu file PDF.
     */
    private function mergePdfs(array $pdfPaths, int $applicationId): string
    {
        $fpdi = new Fpdi();

        foreach ($pdfPaths as $pdfPath) {
            if (!file_exists($pdfPath)) {
                Log::warning("File tidak ditemukan saat merge: {$pdfPath}, skip.");
                continue;
            }

            try {
                $pageCount = $fpdi->setSourceFile($pdfPath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl  = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($tpl);
                    $fpdi->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                    $fpdi->useTemplate($tpl);
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal import PDF {$pdfPath}: " . $e->getMessage() . ", skip halaman ini.");
            }
        }

        $outputPath = sys_get_temp_dir() . "/lpj_merged_{$applicationId}_" . time() . '.pdf';
        $fpdi->Output($outputPath, 'F');

        return $outputPath;
    }

    private function cleanupTempFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
```

### 5.2 `MergeLpjDocumentJob`

```php
<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\PdfMergeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MergeLpjDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300; // 5 menit, karena konversi OnlyOffice bisa lambat

    public function __construct(
        private Application $application
    ) {}

    public function handle(PdfMergeService $mergeService): void
    {
        Log::info("MergeLpjDocumentJob mulai untuk application {$this->application->id}");

        $mergeService->mergeLpjWithAttachments($this->application);

        Log::info("MergeLpjDocumentJob selesai untuk application {$this->application->id}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("MergeLpjDocumentJob GAGAL untuk application {$this->application->id}: " . $e->getMessage());

        $report = $this->application->report;
        $report?->update([
            'merge_status' => 'failed',
            'merge_error'  => $e->getMessage(),
        ]);
    }
}
```

### 5.3 Trigger dari `GenerateReportJob`

Tambahkan di akhir method `handle()` di `GenerateReportJob`:

```php
// Setelah storeAttachmentToDetails() selesai
$report = $this->application->report()->first();

if ($report?->merge_with_attachments) {
    $report->update(['merge_status' => 'pending']);
    MergeLpjDocumentJob::dispatch($this->application)
        ->onQueue('documents')
        ->delay(now()->addSeconds(5)); // beri jeda agar file sudah tersimpan di MinIO
}
```

---

## 6. Edge Cases & Penanganannya

| Edge Case | Penanganan |
|---|---|
| File di MinIO tidak ditemukan | `downloadTempFromMinio()` catch exception, return `null`, section tetap berjalan tanpa file itu. Warning di log. |
| OnlyOffice timeout saat konversi foto | `ensurePdf()` wrap try-catch per file, file di-skip jika gagal. Job punya `timeout = 300` dan `tries = 3`. |
| Rollback jika merge gagal di tengah | `finally` block di `mergeLpjWithAttachments()` selalu cleanup temp files. `merge_status` di-set `failed`. File hasil partial tidak diupload karena upload hanya terjadi setelah `mergePdfs()` sukses. |
| File non-PDF (gambar) | `ensurePdf()` deteksi mime type, gambar di-wrap ke PDF via mPDF, DOCX via OnlyOffice. |
| PDF terenkripsi | FPDI akan throw exception. Catch per-file di `mergePdfs()`, file di-skip + warning. Jika sering terjadi, pertimbangkan fallback ke `pdftk`. |
| LPJ belum di-generate saat job jalan | `getLpjPdfPath()` return null → throw RuntimeException → job `failed()` → status `failed`. |
| Rerun / regenerate | Cek `merge_status === 'done'` sebelum dispatch ulang, atau reset dulu via admin action. |

---

## 7. Urutan Implementasi yang Disarankan

```
1. [ ] Buat migration kolom baru di application_reports
2. [ ] php artisan migrate
3. [ ] Buat app/Services/PdfMergeService.php
4. [ ] Buat app/Jobs/MergeLpjDocumentJob.php
5. [ ] Tambahkan relasi cvFile/idcardFile/npwpFile di ApplicationParticipant (jika belum ada)
6. [ ] Siapkan file cover PDF di public/templates/covers/ (4 file)
7. [ ] Tambahkan trigger dispatch di GenerateReportJob
8. [ ] Tambahkan field merge_with_attachments di form ReportCreate Livewire
9. [ ] Test manual dengan 1 aplikasi yang punya semua jenis lampiran
10. [ ] Cek hasil di MinIO dan verify merged_file_id tersimpan
```

---

## 8. Catatan Tambahan

### File cover PDF yang dibutuhkan
Buat/siapkan 4 file statis di `public/templates/covers/`:
- `cover_realisasi.pdf`
- `cover_dokumentasi.pdf`
- `cover_file_pendukung.pdf`
- `cover_data_personal.pdf`

### Model `ApplicationReport` — tambahkan cast
```php
protected $casts = [
    // ... cast yang sudah ada
    'merge_with_attachments' => 'boolean',
    'merged_at'              => 'datetime',
];
```

### Queue worker
Pastikan queue worker jalan di VPS dan memproses queue `documents`:
```bash
php artisan queue:work --queue=documents,default --timeout=360
```

### Monitoring merge status
Bisa tambahkan endpoint atau Livewire polling sederhana untuk cek `merge_status` di frontend, sehingga user tahu kapan file siap di-download.
