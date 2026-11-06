<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Support\Phone;
use App\Support\QrCode;
use App\Support\Totp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The signed-in member's own details and password.
 */
#[Title('حسابي')]
class Account extends Component
{
    use InteractsWithWorkspace;
    use ShowsToasts;

    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public string $twoFactorCode = '';

    public string $disablePassword = '';

    /**
     * Recovery codes in plain text, shown once right after setup.
     *
     * @var list<string>
     */
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $user = $this->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->mobile = (string) $user->mobile;
    }

    public function saveDetails(): void
    {
        $user = $this->user();
        $this->email = Str::lower(trim($this->email));
        $this->mobile = Phone::normalize($this->mobile);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'mobile' => ['required', 'regex:/^05\d{8}$/', Rule::unique('users', 'mobile')->ignore($user)],
        ], [
            'name.required' => 'أدخل الاسم',
            'email.required' => 'أدخل بريداً إلكترونياً صحيحاً',
            'email.email' => 'أدخل بريداً إلكترونياً صحيحاً',
            'email.unique' => 'هذا البريد مستخدم في حساب آخر',
            'mobile.required' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'mobile.regex' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'mobile.unique' => 'هذا الرقم مستخدم في حساب آخر',
        ]);

        $user->update(['name' => trim($this->name), 'email' => $this->email, 'mobile' => $this->mobile]);

        $this->toast('تم حفظ بياناتك');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'currentPassword.required' => 'أدخل كلمة المرور الحالية',
            'currentPassword.current_password' => 'كلمة المرور الحالية غير صحيحة',
            'newPassword.required' => 'أدخل كلمة مرور من 8 أحرف على الأقل',
            'newPassword.min' => 'أدخل كلمة مرور من 8 أحرف على الأقل',
            'newPassword.confirmed' => 'كلمتا المرور غير متطابقتين',
        ]);

        $this->user()->update(['password' => $this->newPassword]);

        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        $this->toast('تم تغيير كلمة المرور');
    }

    /**
     * Create a secret for the member to scan. It counts only once confirmed.
     */
    public function startTwoFactor(): void
    {
        $this->user()->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => null])->save();
        $this->reset('twoFactorCode');
    }

    public function confirmTwoFactor(): void
    {
        $user = $this->user();
        abort_if($user->two_factor_secret === null || $user->hasTwoFactor(), 404);

        $this->resetErrorBag('twoFactorCode');
        $this->twoFactorCode = Phone::digits($this->twoFactorCode);

        if (! Totp::verify($user->two_factor_secret, $this->twoFactorCode)) {
            $this->addError('twoFactorCode', 'الرمز غير صحيح. تأكد من الوقت في جوالك وأعد المحاولة.');

            return;
        }

        $this->recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();

        $user->forceFill([
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $this->recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->reset('twoFactorCode');
        $this->toast('تم تفعيل التحقق بخطوتين');
    }

    public function cancelTwoFactor(): void
    {
        $user = $this->user();

        if (! $user->hasTwoFactor()) {
            $user->forceFill(['two_factor_secret' => null])->save();
        }
    }

    public function disableTwoFactor(): void
    {
        $this->validate(['disablePassword' => ['required', 'current_password']], [
            'disablePassword.required' => 'أدخل كلمة المرور الحالية',
            'disablePassword.current_password' => 'كلمة المرور الحالية غير صحيحة',
        ]);

        $this->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->reset('disablePassword', 'recoveryCodes');
        $this->toast('تم إيقاف التحقق بخطوتين');
    }

    public function render(): View
    {
        $user = $this->user();
        $pending = $user->two_factor_secret !== null && ! $user->hasTwoFactor();

        return view('livewire.account', [
            'twoFactorOn' => $user->hasTwoFactor(),
            'recoveryLeft' => count($user->two_factor_recovery_codes ?? []),
            'pendingSecret' => $pending ? $user->two_factor_secret : null,
            'pendingQr' => $pending ? QrCode::svg(Totp::uri($user->two_factor_secret, $user->email)) : null,
        ]);
    }
}
