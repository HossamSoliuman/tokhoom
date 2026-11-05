<?php

namespace Database\Seeders;

use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\WorkspaceKind;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ApprovalRequested;
use App\Notifications\WorkspaceLicenseExpiring;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The platform admin, the license approval queue and other subscriber workspaces.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@tokhoom.sa')->exists()) {
            $this->command?->warn('Platform data already seeded, skipping.');

            return;
        }

        DB::transaction(function (): void {
            $admin = User::create([
                'name' => 'هند العمري',
                'email' => 'admin@tokhoom.sa',
                'mobile' => '0500000001',
                'gender' => 'female',
                'password' => 'password',
            ]);
            $admin->forceFill(['is_platform_admin' => true])->save();

            $pending = [
                ['مكتب الأفق العقاري', WorkspaceKind::Office, 'alofuq', 'جدة', '1200078812', '4030221187', 89, 2, ['عادل الحربي', 'male']],
                ['ليان الغامدي', WorkspaceKind::Agent, 'layan', 'الدمام', '1100054329', null, 225, 1, ['ليان الغامدي', 'female']],
                ['مكتب دار الإعمار', WorkspaceKind::Office, 'dar-alemar', 'الرياض', '1200081904', '1010889241', 34, 1, ['منصور العمري', 'male']],
                ['محمد الشهراني', WorkspaceKind::Agent, 'mshahrani', 'أبها', '1100061177', null, 83, 0, ['محمد الشهراني', 'male']],
            ];

            foreach ($pending as [$name, $kind, $slug, $city, $fal, $cr, $expiresInDays, $submittedDaysAgo, [$ownerName, $gender]]) {
                $workspace = $this->workspace($name, $kind, $slug, $city, $fal, $cr, $expiresInDays, $ownerName, $gender, 'pending', $submittedDaysAgo);

                $this->notify($admin, new ApprovalRequested($workspace), $workspace->created_at, read: $submittedDaysAgo > 0);
            }

            $active = [
                ['مكتب النخبة للعقار', WorkspaceKind::Office, 'nokhba', 'الرياض', Plan::Plus, 7, 42, null, ['تركي النخبة', 'male']],
                ['فيصل المالكي', WorkspaceKind::Agent, 'faisal', 'الرياض', Plan::Agent, 1, 9, 'suspended', ['فيصل المالكي', 'male']],
                ['مكتب ركن الديار', WorkspaceKind::Office, 'rukn', 'الخبر', Plan::Office, 3, 17, null, ['سلمان الديار', 'male']],
                ['هيا الدوسري', WorkspaceKind::Agent, 'haya', 'الرياض', Plan::Agent, 1, 2, 'trial', ['هيا الدوسري', 'female']],
            ];

            foreach ($active as [$name, $kind, $slug, $city, $plan, $members, $listings, $state, [$ownerName, $gender]]) {
                $workspace = $this->workspace($name, $kind, $slug, $city, '1200'.mt_rand(100000, 999999), $kind === WorkspaceKind::Office ? '1010'.mt_rand(100000, 999999) : null, 300, $ownerName, $gender, 'approved', 120);

                $workspace->forceFill([
                    'plan' => $plan,
                    'seats' => max($members, $plan->includedSeats()),
                    'trial_ends_at' => $state === 'trial' ? now()->addDays(11) : now()->subMonths(3),
                    'suspended_at' => $state === 'suspended' ? now()->subDays(2) : null,
                ])->save();

                foreach (range(2, $members) as $index) {
                    if ($members < 2) {
                        break;
                    }

                    Membership::create([
                        'workspace_id' => $workspace->id,
                        'user_id' => User::create([
                            'name' => 'عضو '.$index.' — '.$workspace->name,
                            'email' => $slug.$index.'@example.com',
                            'password' => 'password',
                        ])->id,
                        'role' => MemberRole::Agent,
                    ]);
                }

                $ownerId = $workspace->memberships()->where('role', MemberRole::Owner)->value('user_id');

                Listing::factory()->count($listings)->create([
                    'workspace_id' => $workspace->id,
                    'agent_id' => $ownerId,
                    'city' => $city,
                    'purpose' => ListingPurpose::Sale,
                    'type' => ListingType::Apartment,
                    'status' => ListingStatus::Available,
                ]);
            }

            $expiring = Workspace::where('slug', 'dar-alemar')->first();
            $this->notify($admin, new WorkspaceLicenseExpiring($expiring), now()->subDay(), read: false);
        });
    }

    private function workspace(string $name, WorkspaceKind $kind, string $slug, string $city, string $fal, ?string $cr, int $expiresInDays, string $ownerName, string $gender, string $status, int $createdDaysAgo): Workspace
    {
        $owner = User::create([
            'name' => $ownerName,
            'email' => $slug.'@example.com',
            'mobile' => '05'.mt_rand(10000000, 99999999),
            'gender' => $gender,
            'password' => 'password',
        ]);

        $plan = $kind === WorkspaceKind::Office ? Plan::Office : Plan::Agent;

        $workspace = new Workspace([
            'kind' => $kind,
            'name' => $name,
            'slug' => $slug,
            'city' => $city,
            'cr_number' => $cr,
            'fal_number' => $fal,
            'license_expires_on' => now()->addDays($expiresInDays)->toDateString(),
            'plan' => $plan,
            'seats' => $plan->includedSeats(),
            'trial_ends_at' => now()->addDays(14),
            'whatsapp' => $owner->mobile,
        ]);
        $workspace->forceFill([
            'approval_status' => $status,
            'approved_at' => $status === 'approved' ? now()->subDays($createdDaysAgo) : null,
            'created_at' => now()->subDays($createdDaysAgo)->subMinutes($createdDaysAgo === 0 ? 20 : 0),
        ])->save();

        Membership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => MemberRole::Owner,
            'fal_number' => $kind === WorkspaceKind::Agent ? $fal : null,
            'fal_expires_on' => $kind === WorkspaceKind::Agent ? $workspace->license_expires_on : null,
        ]);

        $owner->forceFill(['current_workspace_id' => $workspace->id])->save();

        return $workspace;
    }

    private function notify(User $user, ApprovalRequested|WorkspaceLicenseExpiring $notification, CarbonInterface $at, bool $read): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => $notification->toArray($user),
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
