<?php

namespace Database\Seeders;

use App\Services\MasterManagementService;
use Illuminate\Database\Seeder;

class DummyDraftDetailSeeder extends Seeder
{
    /**
     * Application id target. Diisi oleh command `dummy:draft-detail` sebelum
     * seeder dijalankan (karena seeder Laravel tidak menerima argumen).
     */
    public static ?int $applicationId = null;

    /**
     * Hasil terakhir dari MasterManagementService::seedDummyDraftDetail,
     * dibaca kembali oleh command untuk menampilkan ringkasan.
     *
     * @var array<string,mixed>|null
     */
    public static ?array $result = null;

    public function run(): void
    {
        if (empty(self::$applicationId)) {
            self::$result = ['status' => false, 'message' => 'application id belum diset. Jalankan via command dummy:draft-detail --app_id=...'];
            $this->command?->error(self::$result['message']);
            return;
        }

        self::$result = MasterManagementService::seedDummyDraftDetail(self::$applicationId);

        if (self::$result['status']) {
            $this->command?->info(self::$result['message']);
        } else {
            $this->command?->error(self::$result['message']);
        }
    }
}
