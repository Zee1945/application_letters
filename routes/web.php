<?php

use App\Services\AuthService;
use Illuminate\Support\Facades\Route;

// Route::view('/', 'layouts.main');

// Route::view('dashboard', 'layouts.main')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');


Route::middleware(['auth', 'verified']) // Menambahkan middleware untuk rute ini
    ->group(function () {
        Route::view('dashboard', 'dashboard')// Route ini akan menjadi /admin/dashboard
        // Route::view('dashboard', 'dashboard')->middleware('role:super_admin') // Route ini akan menjadi /admin/dashboard
            ->name('dashboard');

        Route::get('logout', function () {
            return AuthService::logout();
        })->name('logout');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
