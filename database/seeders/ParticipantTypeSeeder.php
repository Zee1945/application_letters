<?php

namespace Database\Seeders;

use App\Models\ParticipantType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        ];
        foreach ($activeParticipantTypes as $type) {
            if (!ParticipantType::where('name', $type)->exists()) {
                ParticipantType::create(['name' => $type]);
            }
        }
    }
}
