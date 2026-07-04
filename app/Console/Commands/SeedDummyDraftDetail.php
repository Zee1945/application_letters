<?php

namespace App\Console\Commands;

use App\Models\Application;
use Database\Seeders\DummyDraftDetailSeeder;
use Illuminate\Console\Command;

class SeedDummyDraftDetail extends Command
{
    /**
     * Contoh:
     *   php artisan dummy:draft-detail --app_id=12
     *
     * Mengisi data dummy detail draft (application_details, application_schedules,
     * application_participants, application_draft_cost_budgets,
     * application_letter_numbers) untuk satu application.
     *
     * Bisnis logic ada di MasterManagementService::seedDummyDraftDetail,
     * dieksekusi lewat DummyDraftDetailSeeder (mekanisme seeder Laravel).
     */
    protected $signature = 'dummy:draft-detail
                            {--app_id= : ID application yang akan diisi data dummy}';

    protected $description = 'Isi data dummy detail draft untuk sebuah application (5 tabel) via seeder.';

    public function handle(): int
    {
        $appId = $this->option('app_id');

        if (empty($appId) || !ctype_digit((string) $appId)) {
            $this->error('Opsi --app_id wajib diisi dengan angka. Contoh: php artisan dummy:draft-detail --app_id=12');
            return self::INVALID;
        }

        $appId = (int) $appId;

        // Validasi keberadaan application (tanpa global scope agar tidak bergantung auth).
        $app = Application::withoutGlobalScopes()->find($appId);
        if (!$app) {
            $this->error("Application id {$appId} tidak ditemukan.");
            return self::FAILURE;
        }

        $this->warn("Data dummy lama milik application id {$appId} (jika ada) akan DIHAPUS lalu diisi ulang.");
        if (!$this->confirm('Lanjutkan?', true)) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        // Titipkan app id ke seeder, lalu jalankan lewat mekanisme seeder.
        DummyDraftDetailSeeder::$applicationId = $appId;
        DummyDraftDetailSeeder::$result = null;

        $this->call('db:seed', [
            '--class' => DummyDraftDetailSeeder::class,
            '--force' => true,
        ]);

        $result = DummyDraftDetailSeeder::$result;

        if (!$result || !($result['status'] ?? false)) {
            $this->error($result['message'] ?? 'Gagal mengisi data dummy.');
            return self::FAILURE;
        }

        if (!empty($result['counts'])) {
            $this->newLine();
            $this->table(
                ['Tabel', 'Baris dibuat'],
                collect($result['counts'])->map(fn ($count, $table) => [$table, $count])->values()->all()
            );
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
