<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

     public $resources = [
        'application-letter',
        'approval',
     ];

     public $permissions = [
        'create',
        'read',
        'update',
        'delete'
     ];

     public $roles = [
        'super_admin',
        'admin',
        'management',
        'finance',
        'user',
     ];
    public function run(): void
    {
        // Membuat permissions
        foreach ($this->resources as $key => $value) {
            foreach ($this->permissions as $perm) {
                $permissionName = $perm . '_' . $value;
                if (!Permission::where('name', $permissionName)->exists()) {
                    // $temp[] = $permissionName;
                    Permission::create(['name' => $permissionName]);
                }
            }
        }

        // Membuat role
        foreach ($this->roles as $key => $value) {
            if (!Role::where('name', $value)->exists()) {
                $role = Role::create(['name' => $value]);
                switch ($role->name) {
                    case 'super_admin':
                        $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
                        break;
                    case 'admin':
                        $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
                        break;
                    case 'management':
                        $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
                        break;
                    case 'finance':
                        $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
                        break;
                    case 'user':
                        $role->givePermissionTo(['read_application-letter', 'create_application-letter']);
                        break;
                }
            }



        }
        // Menetapkan role ke pengguna
        $user = User::find(2); // Menemukan user pertama
        $user->assignRole('super_admin'); // Memberikan role admin ke user pertama
    }
}
