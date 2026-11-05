<?php

namespace App\Livewire;

use App\Enums\ListingStatus;
use App\Enums\SiteEventType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use App\Support\Arabic;
use App\Support\Matcher;
use App\Support\Metrics;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Lazy]
#[Title('اليوم')]
class Today extends Component
{
    use InteractsWithWorkspace, ShowsToasts;

    public function placeholder(): View
    {
        return view('livewire.today-placeholder');
    }

    public function toggleReminder(int $id): void
    {
        $reminder = Reminder::visibleTo($this->membership())->findOrFail($id);

        $reminder->update(['completed_at' => $reminder->isDone() ? null : now()]);

        if ($reminder->isDone()) {
            $this->toast('تم إنجاز المتابعة');
        }
    }

    public function markResponded(int $id): void
    {
        Inquiry::visibleTo($this->membership())->whereKey($id)->whereNull('responded_at')->update(['responded_at' => now()]);
    }

    public function markSent(int $clientId, int $listingId): void
    {
        $client = Client::visibleTo($this->membership())->findOrFail($clientId);
        $listing = Listing::where('workspace_id', $client->workspace_id)->findOrFail($listingId);

        Activity::record($client, 'client.listing_sent', ['listing' => $listing->title, 'listing_id' => $listing->id]);

        $this->toast('تم إرسال العقار إلى '.$client->name.' عبر واتساب');
    }

    /**
     * @return list<array{text: string, cta: ?string, url: ?string}>
     */
    private function licenseWarnings(Membership $membership): array
    {
        $warnings = [];
        $workspace = $membership->workspace;

        if ($workspace->license_expires_on->lte(now()->addDays(60))) {
            $warnings[] = [
                'text' => ($workspace->isOffice() ? 'ترخيص فال للمنشأة' : 'رخصة فال').' ينتهي في '.Arabic::date($workspace->license_expires_on),
                'cta' => null,
                'url' => null,
            ];
        }

        $members = $workspace->memberships()->active()->with('user')
            ->when(! $membership->seesWholeWorkspace(), fn (Builder $query) => $query->whereKey($membership->id))
            ->whereNotNull('fal_expires_on')
            ->whereDate('fal_expires_on', '<=', now()->addDays(60))
            ->get();

        foreach ($members as $member) {
            $warnings[] = [
                'text' => 'رخصة فال ل'.$member->user->name.' تنتهي في '.Arabic::date($member->fal_expires_on),
                'cta' => $membership->role->managesTeam() && $workspace->isOffice() ? 'الفريق' : null,
                'url' => route('team'),
            ];
        }

        $expired = Listing::visibleTo($membership)
            ->whereIn('status', [ListingStatus::Available, ListingStatus::Reserved])
            ->whereDate('ad_license_expires_on', '<', today())
            ->limit(3)
            ->get();

        foreach ($expired as $listing) {
            $warnings[] = [
                'text' => 'انتهى ترخيص إعلان «'.$listing->title.'». الإعلان مخفي من الموقع حتى التجديد',
                'cta' => 'تحديث',
                'url' => route('listings.edit', $listing),
            ];
        }

        return $warnings;
    }

    /**
     * @return list<array{icon: string, label: string, value: string, change: string, up: bool}>
     */
    private function kpis(Metrics $metrics): array
    {
        $from = now()->subDays(7);
        $previousFrom = now()->subDays(14);

        $kpi = function (string $icon, string $label, int $current, int $previous, bool $asDifference = false): array {
            return [
                'icon' => $icon,
                'label' => $label,
                'value' => Arabic::number($current),
                'change' => $asDifference ? Arabic::difference($current, $previous) : Arabic::change($current, $previous),
                'up' => $current >= $previous,
            ];
        };

        $count = fn (SiteEventType $type, $start, $end) => $metrics->eventCount($type, $start, $end);

        return [
            $kpi('ti-world', 'زيارات الموقع', $count(SiteEventType::SiteVisit, $from, now()), $count(SiteEventType::SiteVisit, $previousFrom, $from)),
            $kpi('ti-eye', 'مشاهدات العقارات', $count(SiteEventType::ListingView, $from, now()), $count(SiteEventType::ListingView, $previousFrom, $from)),
            $kpi('ti-brand-whatsapp', 'نقرات واتساب', $count(SiteEventType::WhatsappClick, $from, now()), $count(SiteEventType::WhatsappClick, $previousFrom, $from)),
            $kpi('ti-user-plus', 'عملاء جدد', $metrics->newClients($from, now()), $metrics->newClients($previousFrom, $from), true),
        ];
    }

    /**
     * @return list<array{icon: string, tone: string, text: string, count: int, url: string}>
     */
    private function listingHealth(Membership $membership): array
    {
        $listings = Listing::visibleTo($membership)->withCount('photos')->get();
        $live = $listings->filter(fn (Listing $listing) => $listing->status->isPublic());

        $rows = [
            ['ti-file-alert', 'danger', 'ترخيص إعلان منتهٍ', $live->filter->isAdLicenseExpired()->count(), 'expired'],
            ['ti-photo-off', 'warning', 'عقارات بأقل من '.Listing::MINIMUM_PHOTOS.' صور', $listings->filter(fn (Listing $listing) => $listing->isPublished() && $listing->photos_count < Listing::MINIMUM_PHOTOS)->count(), 'photos'],
            ['ti-clock-pause', 'warning', 'لم يُحدَّث السعر منذ '.Listing::STALE_PRICE_DAYS.' يوماً', $live->filter(fn (Listing $listing) => ($listing->daysSincePriceChange() ?? 0) >= Listing::STALE_PRICE_DAYS)->count(), 'stale'],
            ['ti-file-pencil', 'neutral', 'مسودات غير منشورة', $listings->where('status', ListingStatus::Draft)->count(), 'drafts'],
        ];

        return array_map(fn (array $row) => [
            'icon' => $row[0],
            'tone' => $row[1],
            'text' => $row[2],
            'count' => $row[3],
            'url' => route('listings.index', ['issue' => $row[4]]),
        ], $rows);
    }

    /**
     * @return Collection<int, Reminder>
     */
    private function todaysReminders(Membership $membership): Collection
    {
        return Reminder::visibleTo($membership)
            ->with('client')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $open) => $open->whereNull('completed_at')->where('due_at', '<=', now()->endOfDay()))
                ->orWhereBetween('completed_at', [now()->startOfDay(), now()->endOfDay()]))
            ->orderBy('due_at')
            ->get();
    }

    public function render(Matcher $matcher): View
    {
        $membership = $this->membership();
        $metrics = new Metrics($membership);
        $reminders = $this->todaysReminders($membership);

        $awaiting = Inquiry::visibleTo($membership)->awaitingReply()
            ->with(['listing', 'agent'])
            ->where('created_at', '>=', now()->subDays(7))
            ->oldest()
            ->limit(5)
            ->get();

        $inquiries = Inquiry::visibleTo($membership)
            ->with('listing')
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(3)
            ->get();

        $isEmpty = ! Listing::visibleTo($membership)->exists()
            && $reminders->isEmpty()
            && $inquiries->isEmpty();

        return view('livewire.today', [
            'workspaceName' => $membership->workspace->name,
            'isEmpty' => $isEmpty,
            'warnings' => $this->licenseWarnings($membership),
            'kpis' => $this->kpis($metrics),
            'awaiting' => $awaiting,
            'response' => $metrics->responseTimes(now()->subDays(30), now()),
            'funnel' => $metrics->funnel(now()->subDays(30), now()),
            'health' => $this->listingHealth($membership),
            'reminders' => $reminders,
            'remindersLeft' => $reminders->whereNull('completed_at')->count(),
            'inquiries' => $inquiries,
            'newInquiries' => Inquiry::visibleTo($membership)->where('created_at', '>=', now()->subDay())->count(),
            'matches' => $matcher->forMember($membership, 3),
        ]);
    }
}
