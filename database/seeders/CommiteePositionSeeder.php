<?php

namespace Database\Seeders;

use App\Models\CommiteePosition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommiteePositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activePositions = [
            'Penanggung Jawab',
            'Ketua',
            'Anggota'
        ];
        foreach ($activePositions as $pos) {
            if (!CommiteePosition::where('name', $pos)->exists()) {
                CommiteePosition::create(['name' => $pos]);
            }
        }
    }
}
