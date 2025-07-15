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
                'limit_submission' => 2,

            ],
            [
                'id'=>2,
                'name' => 'Fakultas Sains dan Teknologi',
                'code' => 'SAINTEK',
                'limit_submission' => 2,

            ],
            [
                'id'=>3,
                'name' => 'Fakultas Sosial dan Humaniora',
                'code' => 'SOSHUM',
                'limit_submission' => 2,
            ],
            [
                'id'=>4,
                'name' => 'Fakultas Lembaga Penelitian dan Pengabdian Masyarakat',
                'code' => 'LP2M',
                'limit_submission' => 2,
            ]
        ];
        foreach ($departemts as $department) {
            if (!Department::where('id', $department['id'])->exists()) {
                Department::create($department);
            }
        }
    }
}
