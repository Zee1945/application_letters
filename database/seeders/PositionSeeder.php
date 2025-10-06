<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activePositions = [
            ['name'=>'Super Admin','role'=> 'super_admin'],
            ['name'=>'Dekan','role'=>'dekan'],
            ['name' => 'Kabag Umum','role'=>'kabag'],
            ['name' => 'Kabag Keuangan','role'=>'finance'],
            ['name' =>'Mahasiswa','role'=>'user'],
            ['name' => 'Dosen', 'role' => 'user'],
            ['name' => 'Staff umum', 'role' => 'user'],
            ['name' => 'Admin Departemen', 'role' => 'admin'],
        ];
        foreach ($activePositions as $pos) {
            if (!Position::where('name', $pos['name'])->exists()) {
                $role = $pos['role'];
                unset($pos['role']);
                $loaded_pos = Position::create(['name' => $pos['name']]);
                $loaded_pos->assignRole($role);
            }
        }
    }
}
