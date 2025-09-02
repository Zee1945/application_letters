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
                'department_id' => null,
            ],
            [
                'name' => 'Ibu Anggota Lp2m Rektorat',
                'email' => 'lp2m@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Staff umum',
                'department_id' => 1,
            ],
            [
                'name' => 'Bapak Kabag Saintek',
                'email' => 'kabagsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Kabag Umum',
                'department_id' => 2,
            ],
            [
                'name' => 'Bapak Dekan Saintek',
                'email' => 'dekansaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'position' => 'Dekan',
                'department_id' => 2,
            ],
            [
                'name' => 'Ibu Bendahara Saintek',
                'email' => 'bendaharasaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'position' => 'Kabag Keuangan',

            ],
            [
                'name' => 'Ketua Pelaksana Saintek',
                'email' => 'ketupelsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'position' => 'Dosen'
            ],
            [
                'name' => 'Mahasiswa Saintek',
                'email' => 'ketupel@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'position' => 'Mahasiswa'
            ],
            [
                'name' => 'Ketua Pelaksana TI',
                'email' => 'ketupelti@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 5,
                'position' => 'Dosen'
            ],
            [
                'name' => 'Admin Zul',
                'email' => 'adminzul@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => null,
                'position' => 'Admin Super'
            ],
            [
                'name' => 'Admin Departemen F. Saintek ',
                'email' => 'adminsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'position' => 'Admin Departemen'
            ],
            [
                'name' => 'Admin Departemen J. TIF ',
                'email' => 'adminti@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 5,
                'position' => 'Admin Departemen'
            ],
            ];

        foreach ($users as $user) {
            if (!User::where('email', $user['email'])->exists()) {
                $pos = Position::where('name',$user['position'])->first();
                unset($user['position']);
                unset($user['role']);
                $user['position_id'] = $pos->id;
                $loaded_user = User::create($user);
                // $loaded_user->assignRole($role);
            }
        }


    }
}
