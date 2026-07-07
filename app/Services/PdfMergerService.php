<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;

class PdfMergerService
{
    /**
     * Cache hasil deteksi binary Ghostscript.
     * null = belum dicek, '' = tidak ada, 'gs'/'gswin64c' = nama binary.
     */
    private ?string $gsBinary = null;
    private bool $gsChecked = false;

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

            $outputPath = $this->mergePdfs($sections, $application->id, $tempFiles);
            $tempFiles[] = $outputPath;

            // Pakai record application_files LPJ yang sudah ada.
            // Hasil merge disimpan ke kolom merge_file_id (file_id tetap LPJ asli
            // agar bisa di-re-merge), dengan status_ready khusus = 5.
            $appFile = $application->applicationFiles()
                ->withFileCodeAndParent('laporan_kegiatan')
                ->first();

            if (!$appFile) {
                throw new \RuntimeException("Record application_files 'laporan_kegiatan' tidak ditemukan untuk application {$application->id}");
            }

            $result = FileManagementService::storeFileApplication(
                file_get_contents($outputPath), // konten file, bukan path
                $application,
                'report',                        // trans_type
                'laporan_kegiatan',              // file_code
                $appFile,                        // app_file
                'pdf',                           // mime_type
                'merged_file_id',                 // target_column → simpan ke merge_file_id
                3,                               // status_ready khusus hasil merge
            );

            if (!($result['status'] ?? false)) {
                throw new \RuntimeException($result['message'] ?? 'Gagal menyimpan file hasil merge.');
            }

            $report->update([
                'merge_status'   => 'done',
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
        $lpjPath = $this->getLpjPdfPath($application,$tempFiles);
        if (!$lpjPath) {
            throw new \RuntimeException("File PDF LPJ utama tidak ditemukan untuk application {$application->id}");
        }
        $sections[] = $lpjPath;

        // 2. Form Realisasi (jika ada data realisasi + file SPBY/kuitansi)
        $realisasiSection = $this->buildRealisasiSection($application, $tempFiles);
        array_push($sections, ...$realisasiSection);

        // 3. Dokumentasi Kegiatan (jika ada document-photos)
        // $dokumentasiSection = $this->buildDokumentasiSection($application, $report, $tempFiles);
        // array_push($sections, ...$dokumentasiSection);

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

        // $coverPath = public_path('templates/covers/cover_realisasi.pdf');
        // if (!file_exists($coverPath)) {
        //     Log::warning("Cover realisasi tidak ditemukan, skip section realisasi.");
        //     return [];
        // }

        // Kumpulkan file SPBY/kuitansi, lalu pastikan semuanya PDF
        // (file bisa berupa gambar hasil scan atau Word, bukan hanya PDF).
        $spbyPaths = [];
        foreach ($spbyFiles as $file) {
            $path = $this->downloadTempFromMinio($file, $tempFiles);
            if ($path) {
                $spbyPaths[] = $path;
            }
        }

        if (empty($spbyPaths)) {
            return [];
        }

        return $this->ensurePdf($spbyPaths, $tempFiles);
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

        // $coverPath = public_path('templates/covers/cover_file_pendukung.pdf');
        // if (!file_exists($coverPath)) {
        //     Log::warning("Cover file pendukung tidak ditemukan, skip section ini.");
        //     return [];
        // }

        $section = [];

        // $section = [$coverPath];

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
            // ->orWhereNotNull('material_file_id')
            ->with(['cvFile', 'idcardFile', 'npwpFile'])
            // ->with(['cvFile', 'idcardFile', 'npwpFile','materialFile'])
            ->get();

        if ($participants->isEmpty()) {
            return [];
        }

        // $coverPath = public_path('templates/covers/cover_data_personal.pdf');
        // if (!file_exists($coverPath)) {
        //     Log::warning("Cover data personal tidak ditemukan, skip section ini.");
        //     return [];
        // }

        // Kumpulkan semua file personal, lalu pastikan semuanya PDF
        // (file CV/KTP/NPWP bisa berupa gambar atau Word, bukan hanya PDF).
        // Catatan: 'materialFile' (materi narasumber) belum dipakai untuk saat ini,
        // tapi disiapkan jika kedepannya perlu ikut di-merge.
        $personalRelations = ['cvFile', 'idcardFile', 'npwpFile' /*, 'materialFile' */];

        $personalPaths = [];
        foreach ($participants as $participant) {
            foreach ($personalRelations as $relation) {
                $file = $participant->{$relation};
                if ($file) {
                    $path = $this->downloadTempFromMinio($file, $tempFiles);
                    if ($path) {
                        $personalPaths[] = $path;
                    }
                }
            }
        }

        if (empty($personalPaths)) {
            return [];
        }

        $pdfPaths = $this->ensurePdf($personalPaths, $tempFiles);

        // return array_merge([$coverPath], $pdfPaths);
        return $pdfPaths;
    }

    // --- HELPERS ---

    private function getLpjPdfPath(Application $application, array &$tempFiles): ?string
    {
        $appFile = $application->applicationFiles()
            ->findCode('laporan_kegiatan')
            ->whereNotNull('file_id')
            ->with('file')
            ->first();


        if (!$appFile?->file) {
            return null;
        }


        return $this->downloadTempFromMinio($appFile->file, $tempFiles);
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
            // Jika path sudah mengandung nama file (ada ekstensi/titik), pakai langsung.
            // Jika tidak, gabungkan path dengan filename.
            $filepath_with_name = str_contains($fileModel->path, '.')
                ? $fileModel->path
                : $fileModel->path . '/' . $fileModel->filename;

            $content  = Storage::disk('minio')->get($filepath_with_name);
            $tempPath = $this->tempPath('merge_' . uniqid() . '_' . $fileModel->filename);
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
            $pdfPath = FileManagementService::convertToPdf($path);
            if ($pdfPath) {
                $tempFiles[] = $pdfPath;
                $result[] = $pdfPath;
            }
        }
        return $result;
    }

    /**
     * Deteksi binary Ghostscript yang tersedia (hasil di-cache per instance).
     * 'gs' untuk Linux/VPS, 'gswin64c' untuk Windows lokal.
     */
    private function ghostscriptBinary(): ?string
    {
        if ($this->gsChecked) {
            return $this->gsBinary ?: null;
        }
        $this->gsChecked = true;

        foreach (['gs', 'gswin64c'] as $bin) {
            try {
                $probe = new Process([$bin, '--version']);
                $probe->setTimeout(10);
                $probe->run();
                if ($probe->isSuccessful()) {
                    $this->gsBinary = $bin;
                    return $bin;
                }
            } catch (\Throwable $e) {
                // coba kandidat berikutnya
            }
        }

        $this->gsBinary = '';
        Log::warning('Ghostscript tidak ditemukan; normalisasi PDF dilewati (FPDI bisa gagal pada PDF cross-reference stream).');
        return null;
    }

    /**
     * Tulis ulang PDF ke versi 1.4 (xref table klasik, tanpa cross-reference/object
     * stream) via Ghostscript, agar FPDI free bisa membacanya saat merge.
     *
     * Keamanan (file bisa berasal dari upload user):
     *  - Dipanggil via Symfony Process dengan ARRAY args (bukan shell) -> tidak ada
     *    shell yang mem-parse -> command injection tertutup.
     *  - '-dSAFER' mengaktifkan sandbox Ghostscript (batasi akses FS & operator
     *    berbahaya dari dalam dokumen) -> mitigasi eksploit PDF crafting.
     *  - Device dibatasi 'pdfwrite' saja, non-interaktif, dengan timeout.
     *  - Input di-realpath & dipastikan file valid sebelum diproses.
     *
     * Fallback aman: bila gs tak ada / gagal, kembalikan file asli (perilaku lama).
     */
    private function normalizePdf(string $path, array &$tempFiles): string
    {
        $gs = $this->ghostscriptBinary();
        if (!$gs) {
            return $path; // tanpa gs: perilaku seperti sebelumnya
        }

        // Input harus file nyata milik sistem (file temp hasil download MinIO).
        $real = realpath($path);
        if ($real === false || !is_file($real)) {
            return $path;
        }

        $outPath = $this->tempPath('gsnorm_' . uniqid() . '.pdf');

        try {
            $process = new Process([
                $gs,
                '-dSAFER',                    // sandbox: mitigasi PDF jahat (WAJIB)
                '-dNOPAUSE',
                '-dBATCH',
                '-dQUIET',
                '-sDEVICE=pdfwrite',          // hanya tulis PDF
                '-dCompatibilityLevel=1.4',   // -> xref table klasik, FPDI-friendly
                '-sOutputFile=' . $outPath,
                $real,
            ]);
            $process->setTimeout(120);        // cegah PDF "bom" menggantung worker
            $process->run();

            if ($process->isSuccessful() && is_file($outPath) && filesize($outPath) > 0) {
                $tempFiles[] = $outPath;
                return $outPath;
            }

            Log::warning('Normalisasi Ghostscript gagal, pakai file asli.', [
                'path' => $real,
                'exit' => $process->getExitCode(),
                'err'  => mb_substr($process->getErrorOutput(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Exception normalisasi Ghostscript: ' . $e->getMessage(), ['path' => $real]);
        }

        // Bersihkan output gagal, fallback ke file asli.
        if (is_file($outPath)) {
            @unlink($outPath);
        }
        return $path;
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
            $outputPath = $this->tempPath('img_pdf_' . uniqid() . '.pdf');
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
    private function mergePdfs(array $pdfPaths, int $applicationId, array &$tempFiles = []): string
    {
        $fpdi = new Fpdi();

        foreach ($pdfPaths as $pdfPath) {
            if (!file_exists($pdfPath)) {
                Log::warning("File tidak ditemukan saat merge: {$pdfPath}, skip.");
                continue;
            }

            // Normalisasi ke PDF 1.4 (xref table klasik) via Ghostscript agar FPDI
            // free bisa membaca. Dilakukan terpusat di sini supaya SEMUA file yang
            // masuk FPDI ter-cover — termasuk LPJ utama yang tidak lewat ensurePdf.
            // Fallback aman: bila gs tak ada/gagal, kembalikan path asli.
            $pdfPath = $this->normalizePdf($pdfPath, $tempFiles);

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

        $outputPath = $this->tempPath("lpj_merged_{$applicationId}_" . time() . '.pdf');
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

    /**
     * Bangun path absolut untuk file temp di dalam storage aplikasi
     * (storage/app/temp/pdf-merge). Direktori dibuat otomatis bila belum ada.
     */
    private function tempPath(string $filename): string
    {
        $dir = Storage::disk('local')->path('temp/pdf-merge');

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $filename;
    }
}