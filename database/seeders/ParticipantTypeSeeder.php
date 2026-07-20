<?php

namespace Database\Seeders;

use App\Models\ParticipantType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ParticipantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeParticipantTypes = [
            'Panitia',
            'Narasumber',
            'Peserta',
            'Moderator',
            'Anggota',
        ];
        foreach ($activeParticipantTypes as $type) {
            // updateOrCreate: idempoten sekaligus mengisi slug pada data lama
            // yang mungkin dibuat sebelum kolom slug ada.
            ParticipantType::updateOrCreate(
                ['name' => $type],
                ['slug' => Str::slug($type)]
            );
        }
    }
}
