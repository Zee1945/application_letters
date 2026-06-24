<?php

namespace App\Console\Commands;

use App\Models\ApplicationSchedule;
use Illuminate\Console\Command;

class ReplaceDashInScheduleSpeakerText extends Command
{
    /**
     * Contoh:
     *   php artisan schedules:replace-dash
     *   php artisan schedules:replace-dash --dry-run
     *
     * Ganti SEMUA tanda '-' menjadi '###' pada kolom speaker_text & moderator_text
     * di tabel application_schedules. Contoh: 'Budi-UGM;Ani-ITB' -> 'Budi###UGM;Ani###ITB'.
     *
     * Hanya baris aktif (deleted_at NULL) yang diproses — ditangani otomatis oleh SoftDeletes.
     */
    protected $signature = 'schedules:replace-dash
                            {--dry-run : Tampilkan baris yang akan diubah tanpa menyimpan perubahan}';

    protected $description = "Ganti tanda '-' menjadi '###' pada speaker_text & moderator_text di application_schedules";

    public function handle(): int
    {
        $columns = ['speaker_text', 'moderator_text'];
        $dryRun  = (bool) $this->option('dry-run');

        // Ambil baris yang minimal salah satu kolomnya mengandung '-'.
        $schedules = ApplicationSchedule::query()
            ->where(function ($q) use ($columns) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'like', '%-%');
                }
            })
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("Tidak ada baris dengan tanda '-' pada speaker_text/moderator_text. Tidak ada yang diubah.");
            return self::SUCCESS;
        }

        $updatedRows = 0;

        foreach ($schedules as $schedule) {
            $changed = false;

            foreach ($columns as $col) {
                $value = $schedule->{$col};
                if (is_string($value) && str_contains($value, '-')) {
                    $new = str_replace('-', '###', $value);
                    if ($new !== $value) {
                        if ($dryRun) {
                            $this->line(sprintf('  [id %d] %s: "%s" -> "%s"', $schedule->id, $col, $value, $new));
                        } else {
                            $schedule->{$col} = $new;
                        }
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                $updatedRows++;
                if (!$dryRun) {
                    $schedule->save();
                }
            }
        }

        if ($dryRun) {
            $this->info(sprintf('[DRY RUN] %d baris akan diubah.', $updatedRows));
        } else {
            $this->info(sprintf('Selesai. %d baris diperbarui.', $updatedRows));
        }

        return self::SUCCESS;
    }
}
