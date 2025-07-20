<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\AuthService;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();
        $user = auth()->user()->except('password', 'remember_token');
        $position = auth()->user()->position;
        // $role = auth()->user()->position()->getRoleNames()->first() ?? 'user';
        $role = $position->getRoleNames()->first() ?? 'user';
        $department = auth()->user()->department()->first()->name ?? '';
        Session::put('user', [...auth()->user()->except('password', 'remember_token'),'role'=> $role,'department'=>$department]);
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>



{{-- filepath: c:\DATA DISK\Projects\application generator\application_letters\resources\views\livewire\pages\auth\login.blade.php --}}
<div class="container mt-5" style="max-width: 400px;">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" type="email" name="email" class="form-control @error('form.email') is-invalid @enderror" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('form.email')" class="invalid-feedback" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input wire:model="form.password" id="password" type="password" name="password" class="form-control @error('form.password') is-invalid @enderror" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('form.password')" class="invalid-feedback" />
        </div>

        <!-- Remember Me -->
        <div class="mb-3 form-check">
            <input wire:model="form.remember" id="remember" type="checkbox" class="form-check-input" name="remember">
            <label for="remember" class="form-check-label">{{ __('Remember me') }}</label>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-100">
            {{ __('Log in') }}
        </button>
    </form>
</div>
