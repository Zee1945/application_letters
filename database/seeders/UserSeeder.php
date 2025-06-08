<?php

namespace Database\Seeders;

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
                'role' => 'super_admin',
                'department_id' => null,
            ],
            [
                'name' => 'Admin Saintek',
                'email' => 'adminsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'admin',
            ],
            [
                'name' => 'Ibu Lp2m Rektorat',
                'email' => 'lp2m@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 1,
                'role' => 'management',
            ],
            [
                'name' => 'Bapak Kabag Saintek',
                'email' => 'kabagsaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'management',
            ],
            [
                'name' => 'Ibu Finance Saintek',
                'email' => 'financesaintek@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'finance',
            ],
            [
                'name' => 'Ketua Pelaksana Saintek',
                'email' => 'ketupel@gmail.com',
                'password' => Hash::make('admin123'),
                'department_id' => 2,
                'role' => 'user',
            ],
            ];

        foreach ($users as $user) {
            if (!User::where('email', $user['email'])->exists()) {
                $role = $user['role'];
                unset($user['role']);
                $loaded_user = User::create($user);
                $loaded_user->assignRole($role);
            }
        }


    }
}
