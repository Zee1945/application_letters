<?php

namespace App\Services;

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


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
