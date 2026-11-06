<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Ask for a password reset link by email. The answer is the same whether or
 * not the email has an account.
 */
#[Layout('layouts::auth')]
#[Title('نسيت كلمة المرور')]
class ForgotPassword extends Component
{
    public string $email = '';

    #[Locked]
    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => ['required', 'email']], [
            'email.required' => 'أدخل بريدك الإلكتروني',
            'email.email' => 'أدخل بريداً إلكترونياً صحيحاً',
        ]);

        $key = 'password-reset:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'طلبات كثيرة. حاول مرة أخرى بعد '.RateLimiter::availableIn($key).' ثانية.');

            return;
        }

        RateLimiter::hit($key, 600);
        Password::sendResetLink(['email' => Str::lower($this->email)]);

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
