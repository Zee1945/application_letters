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
        'application',
        'report',
        'user',
        'position',
        'department',
        'dashboard',
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
        'kabag',
        'finance',
        'dekan',
        'monitor',
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

        $executor_application = [
                 'read_dashboard',
                 'read_application','update_application',
                 'read_report','update_report',
        ];
        $user = [
                 'read_dashboard',
                 'create_application','read_application','update_application','delete_application',
                 'create_report','update_report',
                 'read_report','delete_report',
        ];
        $monitor = [
                'read_dashboard',
                 'read_application',
                 'read_report',
                 'read_user',
                 'read_position',
                 'read_department',
        ];

        $admin_permission = [
                             'read_dashboard',
                             'create_application','read_application','update_application','delete_application',
                             'create_report','update_report',
                             'read_report','delete_report',
                             'create_user','read_user','update_user','delete_user',
                             'create_position','read_position','update_position','delete_position',
                             'create_department','read_department','update_department','delete_department',
                            ];
        
        // Membuat role
        foreach ($this->roles as $key => $value) {
            if (!Role::where('name', $value)->exists()) {
                $role = Role::create(['name' => $value]);
                switch ($role->name) {
                    case 'super_admin':
                        $role->givePermissionTo(Permission::all()->pluck('name')->toArray());
                        break;
                    case 'admin':
                        $role->givePermissionTo($admin_permission);
                        break;
                    case 'dekan':
                        $role->givePermissionTo($executor_application);
                        break;
                    case 'kabag':
                        $role->givePermissionTo($executor_application);
                        break;
                    case 'finance':
                        $role->givePermissionTo($executor_application);
                        break;
                    case 'user':
                        $role->givePermissionTo($user);
                        break;
                    case 'monitor':
                        $role->givePermissionTo($monitor);
                        break;
                }
            }



        }
        // Menetapkan role ke pengguna
        // $user = User::where('email','admin@gmail.com'); // Menemukan user pertama
        // $user->assignRole('super_admin'); // Memberikan role admin ke user pertama
    }
}
