<?php

namespace App\Livewire\Team;

use App\Actions\DeactivateMember;
use App\Enums\DealStage;
use App\Enums\InvitationChannel;
use App\Enums\ListingStatus;
use App\Enums\MemberRole;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Invitation;
use App\Notifications\InvitationSent;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('الفريق')]
class Index extends Component
{
    use HasDialogs, InteractsWithWorkspace, ShowsToasts;

    public string $channel = 'mobile';

    public string $contact = '';

    public string $role = 'agent';

    #[Locked]
    public ?int $leavingId = null;

    public ?int $recipientId = null;

    public function boot(): void
    {
        Gate::authorize('manage-team');
    }

    public function openInvite(): void
    {
        $this->reset('channel', 'contact', 'role');
        $this->openDialog('invite');
    }

    public function sendInvite(): void
    {
        $workspace = $this->workspace();
        $email = $this->channel === InvitationChannel::Email->value;
        $this->contact = $email ? Str::lower(trim($this->contact)) : Phone::normalize($this->contact);

        $this->validate([
            'channel' => ['required', Rule::enum(InvitationChannel::class)],
            'contact' => $email ? ['required', 'email', 'max:190'] : ['required', 'regex:/^05\d{8}$/'],
            'role' => ['required', Rule::in([MemberRole::Agent->value, MemberRole::Manager->value])],
        ], ['contact.regex' => 'أدخل رقم جوال سعودي يبدأ بـ 05.'], ['contact' => $email ? 'البريد الإلكتروني' : 'رقم الجوال']);

        if (! $workspace->hasFreeSeat()) {
            $this->addError('contact', 'استخدمت كل المقاعد في خطتك. أضف مقاعد من صفحة الاشتراك.');

            return;
        }

        $alreadyMember = $workspace->members()
            ->where($email ? 'email' : 'mobile', $this->contact)
            ->exists();

        if ($alreadyMember || $workspace->invitations()->pending()->where('contact', $this->contact)->exists()) {
            $this->addError('contact', 'هذا العضو في الفريق أو لديه دعوة معلّقة.');

            return;
        }

        $invitation = $workspace->invitations()->create([
            'invited_by' => $this->user()->id,
            'channel' => $this->channel,
            'contact' => $this->contact,
            'role' => $this->role,
            'sent_at' => now(),
        ]);

        $this->closeDialog();
        $this->deliver($invitation);
    }

    public function resend(int $id): void
    {
        $invitation = $this->workspace()->invitations()->pending()->findOrFail($id);
        $invitation->update(['sent_at' => now()]);

        $this->deliver($invitation);
    }

    public function cancelInvite(int $id): void
    {
        $this->workspace()->invitations()->pending()->findOrFail($id)->delete();

        $this->toast('تم إلغاء الدعوة', 'info');
    }

    /**
     * Email invitations go out by mail; mobile ones open WhatsApp with the link.
     */
    private function deliver(Invitation $invitation): void
    {
        if ($invitation->channel === InvitationChannel::Email) {
            Notification::route('mail', $invitation->contact)->notify(new InvitationSent($invitation));
            $this->toast('تم إرسال الدعوة');

            return;
        }

        $message = 'دعوة للانضمام إلى '.$invitation->workspace->name.' على تخوم: '.route('invitations.accept', $invitation->token);
        $this->js('window.open('.json_encode(Phone::whatsappUrl($invitation->contact, $message)).', "_blank")');
        $this->toast('افتح واتساب لإرسال رابط الدعوة', 'info');
    }

    public function openDeactivate(int $membershipId): void
    {
        $leaving = $this->workspace()->memberships()->active()->whereKeyNot($this->membership()->id)
            ->where('role', '!=', MemberRole::Owner)
            ->findOrFail($membershipId);

        $this->leavingId = $leaving->id;
        $this->recipientId = $this->membership()->id;
        $this->openDialog('deactivate');
    }

    public function deactivate(DeactivateMember $deactivateMember): void
    {
        $memberships = $this->workspace()->memberships()->active();
        $leaving = (clone $memberships)->findOrFail($this->leavingId);

        $this->validate(['recipientId' => ['required', Rule::in((clone $memberships)->whereKeyNot($leaving->id)->pluck('id'))]], [], ['recipientId' => 'المستلم']);

        $deactivateMember->handle($leaving, (clone $memberships)->findOrFail($this->recipientId));

        $this->closeDialog();
        $this->toast('تم إيقاف '.$leaving->user->name.' ونقل بياناته');
    }

    public function render(): View
    {
        $workspace = $this->workspace();

        $members = $workspace->memberships()->active()->with('user')
            ->orderByRaw("case role when 'owner' then 0 when 'manager' then 1 else 2 end")
            ->orderBy('id')
            ->get();

        $activeCounts = $workspace->listings()
            ->whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])
            ->selectRaw('agent_id, count(*) as total')
            ->groupBy('agent_id')
            ->pluck('total', 'agent_id');

        $leaving = $this->dialog === 'deactivate' && $this->leavingId
            ? $workspace->memberships()->with('user')->find($this->leavingId)
            : null;

        return view('livewire.team.index', [
            'members' => $members,
            'activeCounts' => $activeCounts,
            'invitations' => $workspace->invitations()->pending()->latest()->get(),
            'leaving' => $leaving,
            'leavingCounts' => $leaving ? [
                'عقارات' => $workspace->listings()->where('agent_id', $leaving->user_id)->count(),
                'عملاء' => $workspace->clients()->where('agent_id', $leaving->user_id)->count(),
                'صفقات' => $workspace->deals()->where('agent_id', $leaving->user_id)->whereNotIn('stage', [DealStage::Won, DealStage::Lost])->count(),
            ] : [],
            'recipients' => $leaving ? $members->where('id', '!=', $leaving->id) : collect(),
            'seatsLeft' => $workspace->paidSeats() - $workspace->usedSeats(),
        ]);
    }
}
