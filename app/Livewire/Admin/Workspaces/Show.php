<?php

namespace App\Livewire\Admin\Workspaces;

use App\Actions\ApproveWorkspace;
use App\Actions\RejectWorkspace;
use App\Enums\ApprovalStatus;
use App\Enums\DomainStatus;
use App\Enums\Plan;
use App\Enums\RejectionReason;
use App\Enums\SiteEventType;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\RecordsManualPayments;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Support\Arabic;
use App\Support\DomainVerifier;
use App\Support\Phone;
use App\Support\SiteAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * One workspace for platform admins: its license, owner, subscription,
 * team, invoices and the history of what admins did to it.
 */
class Show extends Component
{
    use HasDialogs, RecordsManualPayments, ShowsToasts;

    public Workspace $workspace;

    public string $reason = '';

    public string $message = '';

    public string $suspendReason = '';

    public string $name = '';

    public string $city = '';

    public string $falNumber = '';

    public string $crNumber = '';

    public string $licenseExpiresOn = '';

    public int $days = 14;

    public string $plan = 'office';

    public int $seats = 5;

    public string $note = '';

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function approve(ApproveWorkspace $approveWorkspace): void
    {
        abort_unless(in_array($this->workspace->approval_status, [ApprovalStatus::Pending, ApprovalStatus::Rejected], true), 403);

        $approveWorkspace->handle($this->workspace);

        $this->toast('تم اعتماد '.$this->workspace->name);
    }

    public function openReject(): void
    {
        abort_unless($this->workspace->approval_status === ApprovalStatus::Pending, 403);

        $this->reset('reason', 'message');
        $this->openDialog('reject');
    }

    public function reject(RejectWorkspace $rejectWorkspace): void
    {
        abort_unless($this->workspace->approval_status === ApprovalStatus::Pending, 403);

        $this->validate([
            'reason' => ['required', Rule::enum(RejectionReason::class)],
            'message' => ['nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'اختر سبب الرفض.']);

        $rejectWorkspace->handle($this->workspace, RejectionReason::from($this->reason), $this->message);

        $this->closeDialog();
        $this->toast('تم رفض الطلب وإبلاغ المتقدم', 'info');
    }

    public function openSuspend(): void
    {
        $this->reset('suspendReason');
        $this->openDialog($this->workspace->isSuspended() ? 'reactivate' : 'suspend');
    }

    public function suspend(): void
    {
        abort_if($this->workspace->isSuspended(), 403);

        $this->validate(['suspendReason' => ['nullable', 'string', 'max:500']]);

        $this->workspace->forceFill(['suspended_at' => now()])->save();
        AdminAction::record('workspace.suspended', $this->workspace, properties: ['reason' => trim($this->suspendReason) ?: null]);

        $this->closeDialog();
        $this->toast('تم إيقاف '.$this->workspace->name);
    }

    public function reactivate(): void
    {
        abort_unless($this->workspace->isSuspended(), 403);

        $this->workspace->forceFill(['suspended_at' => null])->save();
        AdminAction::record('workspace.reactivated', $this->workspace);

        $this->closeDialog();
        $this->toast('تمت إعادة تفعيل '.$this->workspace->name);
    }

    public function openLicense(): void
    {
        $this->name = $this->workspace->name;
        $this->city = $this->workspace->city;
        $this->falNumber = $this->workspace->fal_number;
        $this->crNumber = (string) $this->workspace->cr_number;
        $this->licenseExpiresOn = $this->workspace->license_expires_on->toDateString();
        $this->openDialog('license');
    }

    /**
     * Correct the license details, or record a renewed license's new expiry.
     */
    public function saveLicense(): void
    {
        $this->falNumber = Phone::digits($this->falNumber);
        $this->crNumber = Phone::digits($this->crNumber);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', Rule::in(SiteAddress::cities())],
            'falNumber' => ['required', 'digits:10'],
            'crNumber' => [$this->workspace->isOffice() ? 'required' : 'nullable', 'digits:10'],
            'licenseExpiresOn' => ['required', 'date'],
        ], [], [
            'name' => 'الاسم',
            'city' => 'المدينة',
            'falNumber' => 'رقم رخصة فال',
            'crNumber' => 'رقم السجل التجاري',
            'licenseExpiresOn' => 'تاريخ انتهاء الترخيص',
        ]);

        $this->workspace->update([
            'name' => trim($this->name),
            'city' => $this->city,
            'fal_number' => $this->falNumber,
            'cr_number' => $this->workspace->isOffice() ? $this->crNumber : null,
            'license_expires_on' => $this->licenseExpiresOn,
        ]);

        if (! $this->workspace->isOffice()) {
            $this->workspace->memberships()->where('user_id', $this->workspace->owner()?->id)->update([
                'fal_number' => $this->falNumber,
                'fal_expires_on' => $this->licenseExpiresOn,
            ]);
        }

        AdminAction::record('workspace.license_updated', $this->workspace, properties: ['expires_on' => $this->licenseExpiresOn]);

        $this->closeDialog();
        $this->toast('تم تحديث بيانات الترخيص');
    }

    public function openDays(): void
    {
        $this->days = 14;
        $this->openDialog('days');
    }

    /**
     * Push the next charge back by a number of free days: on the paid month
     * when the workspace has paid before, otherwise on the trial.
     */
    public function grantDays(): void
    {
        $this->validate(['days' => ['required', 'integer', 'min:1', 'max:365']], [], ['days' => 'عدد الأيام']);

        $workspace = $this->workspace;

        if ($workspace->paid_until) {
            $workspace->forceFill(['paid_until' => $workspace->paid_until->copy()->max(Carbon::today())->addDays($this->days)->toDateString()])->save();
        } else {
            $workspace->forceFill(['trial_ends_at' => ($workspace->trial_ends_at?->copy()->max(now()) ?? now())->addDays($this->days)])->save();
        }

        AdminAction::record('workspace.days_granted', $workspace, properties: [
            'days' => $this->days,
            'until' => $workspace->nextChargeDate()->toDateString(),
        ]);

        $this->closeDialog();
        $this->toast('تمت إضافة '.Arabic::days($this->days).' · الخصم القادم '.Arabic::date($workspace->nextChargeDate()));
    }

    public function openPlan(): void
    {
        $this->plan = $this->workspace->plan->value;
        $this->seats = $this->workspace->paidSeats();
        $this->openDialog('plan');
    }

    public function updatedPlan(): void
    {
        $plan = Plan::tryFrom($this->plan);

        if ($plan) {
            $this->seats = max($this->seats, $plan->includedSeats(), $this->workspace->usedSeats());
        }
    }

    public function savePlan(): void
    {
        $this->validate([
            'plan' => ['required', Rule::enum(Plan::class)],
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $workspace = $this->workspace;
        $plan = Plan::from($this->plan);

        if ($plan === Plan::Agent && $workspace->usedSeats() > 1) {
            $this->addError('plan', 'خطة الوسيط لعضو واحد، ولدى مساحة العمل '.$workspace->usedSeats().' أعضاء ودعوات.');

            return;
        }

        if ($plan->listingLimit() !== null && $workspace->listings()->count() > $plan->listingLimit()) {
            $this->addError('plan', 'عدد عقارات مساحة العمل أكبر من حد هذه الخطة.');

            return;
        }

        $seats = max($this->seats, $plan->includedSeats(), $workspace->usedSeats());
        $workspace->update(['plan' => $plan, 'seats' => $plan === Plan::Agent ? 1 : $seats]);

        AdminAction::record('workspace.plan_changed', $workspace, properties: ['plan' => $plan->value, 'seats' => $workspace->paidSeats()]);

        $this->closeDialog();
        $this->toast('تم تغيير الخطة إلى '.$plan->label());
    }

    public function checkDomain(DomainVerifier $verifier): void
    {
        abort_unless(filled($this->workspace->custom_domain), 404);

        $active = $verifier->pointsToUs($this->workspace->custom_domain);

        $this->workspace->forceFill([
            'domain_status' => $active ? DomainStatus::Active : DomainStatus::Error,
            'domain_checked_at' => now(),
        ])->save();

        $active
            ? $this->toast('النطاق يشير إلى تخوم ومفعّل')
            : $this->toast('لم نعثر على سجل CNAME الصحيح', 'err');
    }

    public function openRemoveDomain(): void
    {
        abort_unless(filled($this->workspace->custom_domain), 404);

        $this->openDialog('domain');
    }

    public function removeDomain(): void
    {
        $domain = $this->workspace->custom_domain;
        abort_unless(filled($domain), 404);

        $this->workspace->forceFill(['custom_domain' => null, 'domain_status' => null, 'domain_checked_at' => null])->save();
        AdminAction::record('workspace.domain_removed', $this->workspace, properties: ['domain' => $domain]);

        $this->closeDialog();
        $this->toast('تم إلغاء ربط النطاق');
    }

    /**
     * @return Builder<Invoice>
     */
    protected function payableInvoices(): Builder
    {
        return Invoice::where('workspace_id', $this->workspace->id);
    }

    public function addNote(): void
    {
        $this->validate(['note' => ['required', 'string', 'max:2000']], ['note.required' => 'اكتب الملاحظة أولاً.']);

        AdminAction::record('workspace.note', $this->workspace, properties: ['body' => trim($this->note)]);

        $this->reset('note');
        $this->toast('تمت إضافة الملاحظة');
    }

    public function render(): View
    {
        $workspace = $this->workspace;
        $since = now()->subDays(30);
        $owner = $workspace->owner();

        return view('livewire.admin.workspaces.show', [
            'status' => $workspace->status(),
            'owner' => $owner,
            'members' => $workspace->memberships()->with('user')->orderByRaw('deactivated_at is not null')->orderBy('id')->get()
                ->each->setRelation('workspace', $workspace),
            'usedSeats' => $workspace->usedSeats(),
            'invoices' => $workspace->invoices()->latest('issued_on')->latest('id')->limit(24)->get(),
            'stats' => [
                ['icon' => 'ti-home-check', 'label' => 'عقارات معروضة', 'value' => $workspace->listings()->live()->count()],
                ['icon' => 'ti-home', 'label' => 'كل العقارات', 'value' => $workspace->listings()->count()],
                ['icon' => 'ti-users', 'label' => 'العملاء', 'value' => $workspace->clients()->count()],
                ['icon' => 'ti-message-circle', 'label' => 'استفسارات · 30 يوماً', 'value' => $workspace->inquiries()->where('created_at', '>=', $since)->count()],
                ['icon' => 'ti-world', 'label' => 'زيارات · 30 يوماً', 'value' => $workspace->siteEvents()->where('type', SiteEventType::SiteVisit)->where('occurred_at', '>=', $since)->count()],
            ],
            'history' => $workspace->adminActions()->with('admin')->latest('created_at')->latest('id')->limit(50)->get(),
            'lastSuspension' => $workspace->isSuspended()
                ? $workspace->adminActions()->where('type', 'workspace.suspended')->latest('created_at')->latest('id')->first()
                : null,
            'licenseSoon' => $workspace->license_expires_on->lte(today()->addDays(60)),
            'canOpenSite' => $workspace->isApproved() && ! $workspace->isSuspended(),
            'cities' => SiteAddress::cities(),
            'payingInvoice' => $this->payingInvoice(),
        ])->title($workspace->name);
    }
}
