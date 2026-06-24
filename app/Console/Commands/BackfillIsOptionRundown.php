<?php

namespace App\Console\Commands;

use App\Models\ApplicationParticipant;
use Illuminate\Console\Command;

class BackfillIsOptionRundown extends Command
{
    /**
     * Contoh:
     *   php artisan participants:backfill-is-option-rundown
     *   php artisan participants:backfill-is-option-rundown --dry-run
     *
     * Set is_option_rundown = 1 untuk peserta bertipe Narasumber (2) & Moderator (4)
     * yang masih aktif (deleted_at NULL). SoftDeletes membuat query default sudah
     * otomatis mengecualikan baris yang sudah dihapus.
     */
    protected $signature = 'participants:backfill-is-option-rundown
                            {--dry-run : Tampilkan jumlah baris yang akan diupdate tanpa benar-benar mengubah data}';

    protected $description = 'Set is_option_rundown = 1 untuk peserta Narasumber (2) & Moderator (4) yang aktif';

    public function handle(): int
    {
        // Hanya tipe Narasumber (2) dan Moderator (4). deleted_at NULL ditangani SoftDeletes.
        $query = ApplicationParticipant::query()
            ->whereIn('participant_type_id', [2, 4]);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Tidak ada peserta Narasumber/Moderator aktif. Tidak ada yang diupdate.');
            return self::SUCCESS;
        }

        // Hitung yang nilainya belum 1, agar laporan akurat (baris yang sudah 1 tidak terhitung berubah).
        $pending = (clone $query)
            ->where(function ($q) {
                $q->where('is_option_rundown', '!=', 1)
                  ->orWhereNull('is_option_rundown');
            })
            ->count();

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                '[DRY RUN] %d peserta Narasumber/Moderator aktif; %d di antaranya akan diset is_option_rundown = 1.',
                $total,
                $pending,
            ));
            return self::SUCCESS;
        }

        $updated = $query->update(['is_option_rundown' => 1]);

        $this->info(sprintf(
            'Selesai. %d baris diupdate (dari %d peserta Narasumber/Moderator aktif).',
            $updated,
            $total,
        ));

        return self::SUCCESS;
    }
}
