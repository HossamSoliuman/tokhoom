<?php

namespace App\Livewire\Billing;

use App\Enums\DomainStatus;
use App\Enums\Plan;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Workspace;
use App\Support\DomainVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('الاشتراك والنطاق')]
class Index extends Component
{
    use HasDialogs, InteractsWithWorkspace, ShowsToasts;

    public string $plan = 'office';

    public int $seats = 5;

    public string $domain = '';

    public function boot(): void
    {
        Gate::authorize('manage-billing');
    }

    public function mount(): void
    {
        $this->domain = (string) $this->workspace()->custom_domain;
    }

    public function openPlan(): void
    {
        $this->plan = $this->workspace()->plan->value;
        $this->openDialog('plan');
    }

    public function changePlan(): void
    {
        $this->validate(['plan' => ['required', Rule::enum(Plan::class)]]);

        $workspace = $this->workspace();
        $plan = Plan::from($this->plan);

        if ($plan === Plan::Agent && $workspace->usedSeats() > 1) {
            $this->addError('plan', 'خطة الوسيط لعضو واحد. أوقف أعضاء الفريق أولاً.');

            return;
        }

        if ($plan->listingLimit() !== null && $workspace->listings()->count() > $plan->listingLimit()) {
            $this->addError('plan', 'عدد عقاراتك أكبر من حد هذه الخطة.');

            return;
        }

        $workspace->update(['plan' => $plan, 'seats' => max($workspace->usedSeats(), $plan->includedSeats())]);

        $this->closeDialog();
        $this->toast('تم تغيير الخطة إلى '.$plan->label());
    }

    public function openSeats(): void
    {
        $this->seats = $this->workspace()->paidSeats();
        $this->openDialog('seats');
    }

    public function stepSeats(int $step): void
    {
        $this->seats = max($this->minimumSeats($this->workspace()), min(100, $this->seats + $step));
    }

    public function saveSeats(): void
    {
        $workspace = $this->workspace();
        $this->seats = max($this->minimumSeats($workspace), min(100, $this->seats));

        $workspace->update(['seats' => $this->seats]);

        $this->closeDialog();
        $this->toast('أصبح لديك '.$this->seats.' مقاعد');
    }

    public function changeCard(): void
    {
        $this->toast('سيتوفر تحديث البطاقة بعد ربط بوابة الدفع', 'info');
    }

    public function verifyDomain(DomainVerifier $verifier): void
    {
        $this->domain = DomainVerifier::clean($this->domain);

        if (! DomainVerifier::isValid($this->domain)) {
            $this->addError('domain', 'أدخل نطاقاً صحيحاً مثل www.alrashid.sa');

            return;
        }

        $taken = Workspace::where('custom_domain', $this->domain)->whereKeyNot($this->workspace()->id)->exists();

        if ($taken) {
            $this->addError('domain', 'هذا النطاق مربوط بمساحة عمل أخرى.');

            return;
        }

        $active = $verifier->pointsToUs($this->domain);

        $workspace = $this->workspace();
        $workspace->custom_domain = $this->domain;
        $workspace->forceFill([
            'domain_status' => $active ? DomainStatus::Active : DomainStatus::Error,
            'domain_checked_at' => now(),
        ])->save();

        $active
            ? $this->toast('تم تفعيل النطاق بنجاح')
            : $this->toast('لم يكتمل إعداد النطاق بعد', 'err');
    }

    private function minimumSeats(Workspace $workspace): int
    {
        return max($workspace->plan->includedSeats(), $workspace->usedSeats());
    }

    public function render(): View
    {
        $workspace = $this->workspace();
        $seats = $this->dialog === 'seats' ? $this->seats : $workspace->paidSeats();
        $extra = max(0, $seats - $workspace->plan->includedSeats());
        $previewDomain = DomainVerifier::clean($this->domain) ?: 'www.example.sa';

        return view('livewire.billing.index', [
            'workspace' => $workspace,
            'usedSeats' => $workspace->usedSeats(),
            'extraSeats' => $extra,
            'extraCost' => $extra * Plan::EXTRA_SEAT_PRICE,
            'minimumSeats' => $this->minimumSeats($workspace),
            'invoices' => $workspace->invoices()->latest('issued_on')->limit(12)->get(),
            'recordName' => DomainVerifier::recordName($previewDomain),
            'domainStatus' => $workspace->custom_domain ? ($workspace->domain_status ?? DomainStatus::Pending) : null,
        ]);
    }
}
