<?php

namespace App\Console\Commands;

use App\Services\FileManagementService;
use Illuminate\Console\Command;

class PurgeOrphanFiles extends Command
{
    /**
     * Contoh:
     *   php artisan files:purge-orphans                 (dry-run: hanya audit, tidak menghapus)
     *   php artisan files:purge-orphans --force         (benar-benar menghapus)
     *   php artisan files:purge-orphans --force --chunk=1000
     */
    protected $signature = 'files:purge-orphans
                            {--force : Jalankan penghapusan sungguhan (tanpa ini: dry-run)}
                            {--chunk=500 : Jumlah record files yang di-scan per batch}';

    protected $description = 'Hapus file yatim (record tabel files yang tidak lagi direferensikan) beserta file fisiknya di MinIO. Default dry-run.';

    public function handle(): int
    {
        $isForce   = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun    = !$isForce;

        if ($dryRun) {
            $this->info('Mode DRY-RUN: hanya menghitung orphan, tidak ada yang dihapus.');
            $this->line('Jalankan ulang dengan --force untuk benar-benar menghapus.');
        } else {
            $this->warn('Mode FORCE: file fisik & record akan DIHAPUS PERMANEN (hard delete).');
            if (!$this->confirm('Lanjutkan penghapusan?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->line('Memindai tabel files per batch ' . $chunkSize . ' record...');

        $stats = FileManagementService::purgeOrphanFiles($dryRun, $chunkSize);

        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Record di-scan', $stats['scanned']],
                ['Orphan ditemukan', $stats['orphans']],
                ['Record dihapus (DB)', $stats['deleted']],
                ['File fisik dihapus (MinIO)', $stats['physical_deleted']],
                ['Gagal', $stats['failed']],
            ]
        );

        if ($dryRun && $stats['orphans'] > 0) {
            $this->warn(sprintf('Ditemukan %d orphan. Jalankan dengan --force untuk menghapusnya.', $stats['orphans']));
        } else {
            $this->info('Selesai.');
        }

        return self::SUCCESS;
    }
}
