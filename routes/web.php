<?php

use App\Http\Controllers\Trans\ApplicationController;
use App\Livewire\FormLists\Applications\ApplicationCreate;
use App\Livewire\FormLists\Applications\ApplicationCreateDraft;
use App\Livewire\FormLists\Applications\ApplicationList;
use App\Livewire\FormLists\Reports\ReportCreate;
use App\Livewire\FormLists\Reports\ReportList;
use App\Services\AuthService;
use Illuminate\Support\Facades\Route;

Route::view('/','dashboard')->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth', 'verified']) // Menambahkan middleware untuk rute ini
    ->group(function () {
        // Route::view('dashboard', 'dashboard')->name('dashboard');
        // Route::view('dashboard', 'dashboard')->name('dashboard');

    // Transaction
        // Route::resource('applications', ApplicationController::class)->except(['index']);
        // Transaction

        Route::group(['prefix' => 'app'], function () {
            Route::get('/', ApplicationList::class)
                ->name('applications.index');
            Route::get('create', ApplicationCreate::class)
                ->name('applications.create');
            Route::get('create/{application_id}/draft', ApplicationCreateDraft::class)
                ->name('applications.create.draft');

        });

        Route::group(['prefix' => 'report'], function () {
            Route::get('/', ReportList::class)
                ->name('report.index');
            Route::get('create/{application_id}', ReportCreate::class)
                ->name('reports.create');
            // Route::get('create/{application_id}/draft', ApplicationCreateDraft::class)
            //     ->name('applications.create.draft');

        });



            Route::get('logout', function () {
            return AuthService::logout();
        })->name('logout');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
