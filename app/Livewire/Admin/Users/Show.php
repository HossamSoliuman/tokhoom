<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * One person's account for platform admins: their workspaces, sign-in
 * security and admin access.
 */
class Show extends Component
{
    use HasDialogs, ShowsToasts;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function confirmAdmin(): void
    {
        abort_if($this->user->is(auth()->user()), 403);

        $this->openDialog('admin');
    }

    /**
     * Grant or take away platform admin access. Admins cannot remove their own.
     */
    public function toggleAdmin(): void
    {
        abort_if($this->user->is(auth()->user()), 403);

        $granting = ! $this->user->is_platform_admin;

        $this->user->forceFill(['is_platform_admin' => $granting])->save();
        AdminAction::record($granting ? 'user.admin_granted' : 'user.admin_revoked', user: $this->user);

        $this->closeDialog();
        $this->toast($granting ? 'أصبح '.$this->user->name.' مشرفاً على المنصة' : 'سُحبت صلاحية إدارة المنصة من '.$this->user->name);
    }

    public function openDetails(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->mobile = (string) $this->user->mobile;
        $this->openDialog('details');
    }

    /**
     * Correct someone's name, email or mobile, such as when they lost access
     * to the email they signed up with.
     */
    public function saveDetails(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->mobile = trim($this->mobile) !== '' ? Phone::normalize($this->mobile) : '';

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
            'mobile' => ['nullable', 'regex:/^05\d{8}$/', Rule::unique('users', 'mobile')->ignore($this->user)],
        ], [
            'email.unique' => 'هذا البريد مستخدم في حساب آخر',
            'mobile.regex' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'mobile.unique' => 'هذا الرقم مستخدم في حساب آخر',
        ], ['name' => 'الاسم']);

        $this->user->fill(['name' => trim($this->name), 'email' => $this->email, 'mobile' => $this->mobile ?: null]);
        $changed = array_keys($this->user->getDirty());
        $this->user->save();

        if ($changed !== []) {
            AdminAction::record('user.details_updated', user: $this->user, properties: ['fields' => $changed]);
        }

        $this->closeDialog();
        $this->toast('تم حفظ بيانات '.$this->user->name);
    }

    public function confirmBlock(): void
    {
        abort_if($this->user->is(auth()->user()), 403);

        $this->openDialog('block');
    }

    /**
     * Stop the account from signing in, or let it back in. A blocked person is
     * signed out on their next request; their workspaces keep their data.
     * Admins cannot block themselves.
     */
    public function toggleBlock(): void
    {
        abort_if($this->user->is(auth()->user()), 403);

        $blocking = ! $this->user->isBlocked();

        $this->user->forceFill(['blocked_at' => $blocking ? now() : null])->save();
        AdminAction::record($blocking ? 'user.blocked' : 'user.unblocked', user: $this->user);

        $this->closeDialog();
        $this->toast($blocking ? 'أُوقف حساب '.$this->user->name : 'أُعيد تفعيل حساب '.$this->user->name);
    }

    public function confirmTwoFactorReset(): void
    {
        abort_unless($this->user->hasTwoFactor(), 404);

        $this->openDialog('two-factor');
    }

    /**
     * Turn off two-step verification for someone who lost their authenticator
     * app and recovery codes, so they can sign in with their password again.
     */
    public function resetTwoFactor(): void
    {
        abort_unless($this->user->hasTwoFactor(), 404);

        $this->user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AdminAction::record('user.two_factor_reset', user: $this->user);

        $this->closeDialog();
        $this->toast('تم إلغاء التحقق بخطوتين لـ'.$this->user->name);
    }

    public function sendPasswordReset(): void
    {
        $status = Password::sendResetLink(['email' => $this->user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->toast('أُرسل رابط قبل قليل. حاول مرة أخرى بعد دقائق', 'info');

            return;
        }

        AdminAction::record('user.password_reset_sent', user: $this->user);

        $this->toast('أُرسل رابط إعادة تعيين كلمة المرور إلى '.$this->user->email);
    }

    /**
     * When the person last used the dashboard, from their database sessions.
     */
    private function lastSeen(): ?Carbon
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        $timestamp = DB::table(config('session.table', 'sessions'))->where('user_id', $this->user->id)->max('last_activity');

        return $timestamp ? Carbon::createFromTimestamp($timestamp, config('app.timezone')) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.users.show', [
            'memberships' => $this->user->memberships()->with('workspace')->orderByRaw('deactivated_at is not null')->orderBy('id')->get(),
            'history' => $this->user->adminActions()->with('admin')->latest('created_at')->latest('id')->limit(30)->get(),
            'lastSeen' => $this->lastSeen(),
            'isSelf' => $this->user->is(auth()->user()),
        ])->title($this->user->name);
    }
}
