<?php

namespace App\Livewire\Invitations;

use App\Enums\InvitationChannel;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Join a workspace from an invitation link, creating an account when needed.
 */
#[Layout('layouts::auth')]
#[Title('قبول الدعوة')]
class Accept extends Component
{
    #[Locked]
    public string $token;

    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public string $password = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $invitation = $this->invitation();

        if ($invitation) {
            $this->name = (string) $invitation->name;
            $this->email = $invitation->channel === InvitationChannel::Email ? $invitation->contact : '';
            $this->mobile = $invitation->channel === InvitationChannel::Mobile ? $invitation->contact : '';
        }
    }

    private function invitation(): ?Invitation
    {
        return Invitation::where('token', $this->token)->pending()->with('workspace')->first();
    }

    public function accept(): void
    {
        $invitation = $this->invitation();
        abort_unless($invitation !== null, 404);

        $user = Auth::user() ?? $this->register();

        DB::transaction(function () use ($invitation, $user): void {
            $membership = $invitation->workspace->memberships()->firstOrNew(['user_id' => $user->id]);
            $membership->fill(['role' => $invitation->role, 'deactivated_at' => null])->save();

            $invitation->update(['accepted_at' => now()]);
            $user->switchWorkspace($invitation->workspace);
        });

        session()->flash('toast', ['message' => 'انضممت إلى '.$invitation->workspace->name, 'kind' => 'ok']);

        $this->redirectRoute('today', navigate: true);
    }

    private function register(): User
    {
        $this->email = Str::lower(trim($this->email));
        $this->mobile = Phone::normalize($this->mobile);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'password' => ['required', 'string', 'min:8'],
        ], ['mobile.regex' => 'أدخل رقم جوال سعودي يبدأ بـ 05.', 'email.unique' => 'لديك حساب بهذا البريد. سجّل الدخول ثم افتح رابط الدعوة.']);

        $user = User::create([
            'name' => trim($this->name),
            'email' => $this->email,
            'mobile' => $this->mobile,
            'password' => $this->password,
        ]);

        Auth::login($user, remember: true);
        session()->regenerate();

        return $user;
    }

    public function render(): View
    {
        return view('livewire.invitations.accept', ['invitation' => $this->invitation()]);
    }
}
