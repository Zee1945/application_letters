<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanPdfMergeTemp extends Command
{
    /**
     * Contoh:
     *   php artisan pdf-merge:clean-temp
     *   php artisan pdf-merge:clean-temp --hours=6
     */
    protected $signature = 'pdf-merge:clean-temp {--hours=12 : Hapus file yang lebih tua dari N jam}';

    protected $description = 'Bersihkan file temp PDF merge yang tertinggal di storage/app/temp/pdf-merge';

    public function handle(): int
    {
        $dir = Storage::disk('local')->path('temp/pdf-merge');

        if (!is_dir($dir)) {
            $this->info('Direktori temp pdf-merge belum ada, tidak ada yang dibersihkan.');
            return self::SUCCESS;
        }

        $maxAgeSeconds = (int) $this->option('hours') * 3600;
        $threshold     = now()->timestamp - $maxAgeSeconds;

        $deleted = 0;
        $freed   = 0;

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $file) {
            if (!is_file($file)) {
                continue;
            }

            if (filemtime($file) < $threshold) {
                $freed += filesize($file);
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }

        $this->info(sprintf(
            'Selesai. %d file dihapus, ~%s KB dibebaskan (lebih tua dari %d jam).',
            $deleted,
            number_format($freed / 1024, 1),
            (int) $this->option('hours'),
        ));

        return self::SUCCESS;
    }
}
