<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Super Admin',
                'role' => 'super_admin',
                'department_id' => null,
            ],
            [
                'name' => 'Bapak Admin Saintek',
                'email' => 'adminsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Staff Admin Aplikasi',
                'role' => 'admin',
                'department_id' => 2,
            ],
            [
                'name' => 'Ibu Anggota Lp2m Rektorat',
                'email' => 'lp2m@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Staff umum',
                'role' => 'user',
                'department_id' => 1,
            ],
            [
                'name' => 'Bapak Kabag Saintek',
                'email' => 'kabagsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Kabag Umum',
                'role' => 'kabag',
                'department_id' => 2,
            ],
            [
                'name' => 'Bapak Dekan Saintek',
                'email' => 'dekansaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Dekan',
                'role' => 'dekan',
                'department_id' => 2,
            ],
            [
                'name' => 'Ibu Kabag Keuangan Saintek',
                'email' => 'keuangansaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'finance',
                'position' => 'Kabag Keuangan',

            ],
            [
                'name' => 'Ketua Pelaksana Saintek',
                'email' => 'ketupelsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'user',
                'position' => 'Dosen'
            ],
            [
                'name' => 'Mahasiswa Saintek',
                'email' => 'ketupel@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'user',
                'position' => 'Mahasiswa'
            ],
            [
                'name' => 'Ketua Pelaksana TI',
                'email' => 'ketupelti@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 5,
                'role' => 'user',
                'position' => 'Dosen'
            ],
            ];

        foreach ($users as $user) {
            if (!User::where('email', $user['email'])->exists()) {
                $pos = Position::where('name',$user['position'])->first();
                $role = $user['role'];
                unset($user['position']);
                unset($user['role']);
                $user['position_id'] = $pos->id;
                $loaded_user = User::create($user);
                // $loaded_user->assignRole($role);
            }
        }


    }
}
