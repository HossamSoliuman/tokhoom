<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\DomainStatus;
use App\Enums\MemberRole;
use App\Enums\RejectionReason;
use App\Enums\WorkspaceKind;
use App\Models\AdminAction;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\CollectionFailed;
use App\Support\Arabic;
use App\Support\Subscriptions;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What platform admins see after a few months of running Tokhoom: paid and
 * overdue invoices, a rejected application, a taken down listing, an
 * expiring agent license and a history of admin actions.
 */
class AdminDemoSeeder extends Seeder
{
    private User $admin;

    public function run(): void
    {
        $admin = User::where('email', 'admin@tokhoom.sa')->first();
        $platform = Workspace::whereIn('slug', ['nokhba', 'rukn', 'faisal', 'haya', 'dar-alemar'])->get()->keyBy('slug');

        if (! $admin || $platform->count() < 5) {
            $this->command?->warn('Platform workspaces not found, run PlatformSeeder first.');

            return;
        }

        if (AdminAction::exists()) {
            $this->command?->warn('Admin demo data already seeded, skipping.');

            return;
        }

        $this->admin = $admin;

        DB::transaction(function () use ($platform): void {
            foreach (['nokhba', 'rukn', 'faisal', 'haya'] as $slug) {
                $this->log('workspace.approved', $platform[$slug], at: $platform[$slug]->approved_at ?? $platform[$slug]->created_at->copy()->addHours(5));
            }

            $this->paidMonths($platform['nokhba'], months: 4, manualMonth: 2);
            $this->paidMonths($platform['rukn'], months: 3);
            $this->paidMonths($platform['faisal'], months: 3);

            $this->overdue($platform['rukn'], issuedDaysAgo: 7, attempts: Subscriptions::MAX_AUTOMATIC_ATTEMPTS);
            $this->overdue($platform['faisal'], issuedDaysAgo: 7, attempts: Subscriptions::MAX_AUTOMATIC_ATTEMPTS);
            $this->notify(new CollectionFailed($platform['rukn'], Subscriptions::MAX_AUTOMATIC_ATTEMPTS), now()->subDays(5));

            $this->log('workspace.note', $platform['faisal'], ['body' => 'اتصلنا بالوسيط بعد تعثّر التحصيل ولم يرد. نعيد المحاولة غداً.'], now()->subDays(4));
            $this->log('workspace.suspended', $platform['faisal'], ['reason' => 'تعثّر تحصيل الاشتراك ولم يتجاوب الوسيط'], $platform['faisal']->suspended_at ?? now()->subDays(2));

            $this->log('workspace.days_granted', $platform['haya'], ['days' => 7, 'until' => $platform['haya']->trial_ends_at->toDateString()], now()->subDays(4));
            $this->log('workspace.note', $platform['dar-alemar'], ['body' => 'طلبنا من المكتب صورة الترخيص بعد التجديد قبل الاعتماد.'], now()->subHours(20));

            $this->rejectedApplicant();
            $this->expiringAgentLicense($platform['nokhba']);
            $this->brokenDomain($platform['nokhba']);
            $this->removedListing($platform['rukn']);
        });
    }

    /**
     * One paid invoice for each of the last months, oldest first. The given
     * month back was paid by bank transfer and recorded by hand.
     */
    private function paidMonths(Workspace $workspace, int $months, ?int $manualMonth = null): void
    {
        foreach (range($months, 1) as $monthsAgo) {
            $issued = now()->subMonthsNoOverflow($monthsAgo)->startOfMonth();
            $manual = $monthsAgo === $manualMonth;
            $invoice = $this->invoice($workspace, $issued);

            $invoice->forceFill([
                'paid_at' => $issued->copy()->addDays($manual ? 4 : 0)->addHours(6),
                'tap_charge_id' => $manual ? null : 'chg_demo_'.Str::lower(Str::random(12)),
                'payment_attempts' => $manual ? Subscriptions::MAX_AUTOMATIC_ATTEMPTS : 1,
                'recorded_by' => $manual ? $this->admin->id : null,
                'payment_note' => $manual ? 'تحويل بنكي من مصرف الراجحي، رقم العملية 448120' : null,
            ])->save();

            if ($manual) {
                $this->log('invoice.paid_manually', $workspace, ['number' => $invoice->number, 'note' => $invoice->payment_note], $invoice->paid_at);
            }
        }

        $workspace->forceFill(['paid_until' => now()->startOfMonth()->addMonthNoOverflow()->toDateString()])->save();
    }

    /**
     * An unpaid invoice after every automatic try on the saved card failed.
     */
    private function overdue(Workspace $workspace, int $issuedDaysAgo, int $attempts): void
    {
        $issued = now()->subDays($issuedDaysAgo)->startOfDay();

        $this->invoice($workspace, $issued)->forceFill([
            'payment_attempts' => $attempts,
            'last_attempted_at' => $issued->copy()->addDays($attempts - 1)->setTime(6, 0),
        ])->save();

        $workspace->forceFill([
            'paid_until' => $issued->toDateString(),
            'card_brand' => 'mada',
            'card_last_four' => '4417',
            'card_expiry' => '09/2027',
        ])->save();
    }

    private function invoice(Workspace $workspace, CarbonInterface $issued): Invoice
    {
        $invoice = $workspace->invoices()->create([
            'number' => (string) Str::uuid(),
            'description' => 'اشتراك '.$workspace->plan->label().' — '.Arabic::month($issued->month).' '.$issued->year,
            'amount' => $workspace->monthlyTotal(),
            'issued_on' => $issued->toDateString(),
        ]);

        $invoice->forceFill([
            'number' => 'TKH-'.$issued->format('Ym').'-'.str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
            'created_at' => $issued,
            'updated_at' => $issued,
        ])->save();

        return $invoice;
    }

    private function rejectedApplicant(): void
    {
        $owner = User::create([
            'name' => 'بندر القرني',
            'email' => 'alwaha@example.com',
            'mobile' => '0554411220',
            'gender' => 'male',
            'password' => 'password',
        ]);

        $workspace = new Workspace([
            'kind' => WorkspaceKind::Office,
            'name' => 'مكتب الواحة العقاري',
            'slug' => 'alwaha',
            'city' => 'الرياض',
            'cr_number' => '1010774410',
            'fal_number' => '1200881902',
            'license_expires_on' => now()->addDays(20)->toDateString(),
            'trial_ends_at' => now()->addDays(8),
            'whatsapp' => $owner->mobile,
        ]);
        $workspace->forceFill([
            'approval_status' => ApprovalStatus::Rejected,
            'rejected_at' => now()->subDays(3),
            'rejection_reason' => RejectionReason::Mismatch,
            'rejection_message' => 'اسم المنشأة في الطلب لا يطابق الاسم المسجّل في رخصة فال.',
            'created_at' => now()->subDays(6),
        ])->save();

        Membership::create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => MemberRole::Owner]);
        $owner->forceFill(['current_workspace_id' => $workspace->id])->save();

        $this->log('workspace.rejected', $workspace, ['reason' => RejectionReason::Mismatch->value], now()->subDays(3));
    }

    private function expiringAgentLicense(Workspace $workspace): void
    {
        $workspace->memberships()->where('role', MemberRole::Agent)->orderBy('id')->first()?->update([
            'fal_number' => '1100552310',
            'fal_expires_on' => now()->addDays(18)->toDateString(),
        ]);
    }

    private function brokenDomain(Workspace $workspace): void
    {
        $workspace->forceFill([
            'custom_domain' => 'www.alnokhba-demo.sa',
            'domain_status' => DomainStatus::Error,
            'domain_checked_at' => now()->subHours(9),
        ])->save();
    }

    private function removedListing(Workspace $workspace): void
    {
        $listing = $workspace->listings()->orderBy('id')->first();

        if (! $listing) {
            return;
        }

        $reason = 'بلاغ من زائر بأن العقار مؤجّر ولا يزال معروضاً';
        $listing->forceFill(['removed_at' => now()->subDays(1), 'removal_reason' => $reason])->save();

        $this->log('listing.removed', $workspace, ['code' => $listing->short_code, 'reason' => $reason], now()->subDays(1));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(string $type, Workspace $workspace, array $properties = [], ?CarbonInterface $at = null): void
    {
        AdminAction::create([
            'admin_id' => $this->admin->id,
            'workspace_id' => $workspace->id,
            'type' => $type,
            'properties' => $properties,
            'created_at' => $at ?? now(),
        ]);
    }

    private function notify(CollectionFailed $notification, Carbon $at): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'notifiable_type' => $this->admin->getMorphClass(),
            'notifiable_id' => $this->admin->id,
            'data' => $notification->toArray($this->admin),
            'read_at' => null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
