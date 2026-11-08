<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

/**
 * One person's account for platform admins: their workspaces, sign-in
 * security and admin access.
 */
class Show extends Component
{
    use HasDialogs, ShowsToasts;

    public User $user;

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
