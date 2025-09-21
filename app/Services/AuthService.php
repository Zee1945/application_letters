<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\ServiceProvider;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthService
{
    /**
     * Register services.
     *
     * @return void
     */
    public static function addSession($key, $value)
    {
        Session::put($key, $value);
        Session::regenerate();
    }
    public static function currentAccess()
    {
        if (Auth::check()) {

            // "id" =>
            // "name" => "
            // "email" =>
            // "email_verified_at" => 
            // "created_at" => 
            // "updated_at" => 
            // "department_id" => 
            // "nip" => 
            // "position_id" => 
            // "delete_note" => 
            // "created_by" => 
            // "updated_by" => 
            // "deleted_by" => 
            // "deleted_at" => 
            // "role" => 
            // "department" =>
            return Session::get('user');
        }
    }
    public static function login($user)
    {
        Auth::guard('web')->login($user);
        self::addSession('user', $user);
    }
    public static function logout()
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('login');
    }

    public static function adminHasAccessToApplication($app_department_id = null){

        $current_login = AuthService::currentAccess();
        if ($current_login['role'] == 'admin') {
            $current_department_id = $current_login['department_id']??null;
            $department = Department::with('children')->findOrFail($current_department_id);
            $children = $department->children->pluck('id')->toArray();
            $department_can_accessed = [...$children,$current_department_id];
            return in_array($app_department_id,$department_can_accessed);
        }elseif ($current_login['role'] == 'super_admin') {
            return true;
        }
        return false;
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
