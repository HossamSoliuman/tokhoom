<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Choose a new password from the emailed link.
 */
#[Layout('layouts::auth')]
#[Title('كلمة مرور جديدة')]
class ResetPassword extends Component
{
    use ShowsToasts;

    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email');
    }

    public function save(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'أدخل بريدك الإلكتروني',
            'password.required' => 'أدخل كلمة مرور من 8 أحرف على الأقل',
            'password.min' => 'أدخل كلمة مرور من 8 أحرف على الأقل',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
        ]);

        $status = Password::reset(
            ['email' => Str::lower($this->email), 'token' => $this->token, 'password' => $this->password],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'رابط إعادة التعيين غير صالح أو انتهت صلاحيته. اطلب رابطاً جديداً.');

            return;
        }

        $this->flashToast('تم تغيير كلمة المرور. سجّل الدخول بكلمة المرور الجديدة.');
        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
