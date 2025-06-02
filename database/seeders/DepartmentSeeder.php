<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departemts = [
            [
                'id'=>1,
                'name' => 'Rektorat',
                'code' => 'REKTORAT',
            ],
            [
                'id'=>2,
                'name' => 'Sains dan Teknologi',
                'code' => 'SAINTEK',
            ],
            [
                'id'=>3,
                'name' => 'Sosial dan Humaniora',
                'code' => 'SOSHUM',
            ],
            [
                'id'=>4,
                'name' => 'Lembaga Penelitian dan Pengabdian Masyarakat',
                'code' => 'LP2M',
            ]
        ];
        foreach ($departemts as $department) {
            if (!Department::where('id', $department['id'])->exists()) {
                Department::create($department);
            }
        }
    }
}
