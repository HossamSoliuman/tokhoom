<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function render(): View
    {
        return view('livewire.account');
    }
}
