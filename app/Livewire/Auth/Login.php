<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Sign in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function submit(): void
    {
        $this->validate();

        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds}s.",
            ]);
        }

        if (! Auth::attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        request()->session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
