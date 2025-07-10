<?php

namespace Database\Seeders;

use App\Models\FileType;
use App\Models\ParticipantType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // Jalankan seeder lain di sini
        $this->call([
            RoleAndPermissionSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
            ParticipantTypeSeeder::class,
            CommiteePositionSeeder::class,
            FileTypeSeeder::class,
        ]);

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
