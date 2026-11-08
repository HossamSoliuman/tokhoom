<?php

use App\Enums\ApprovalStatus;
use App\Enums\DomainStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\RejectionReason;
use App\Livewire\Admin\Workspaces\Index;
use App\Livewire\Admin\Workspaces\Show;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceApproved;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create(['name' => 'هند العمري']);
});

test('the list filters workspaces by status', function () {
    Workspace::factory()->create(['name' => 'مكتب نشط', 'trial_ends_at' => now()->subDay()]);
    Workspace::factory()->create(['name' => 'مكتب تجريبي']);
    Workspace::factory()->suspended()->create(['name' => 'مكتب موقوف']);
    Workspace::factory()->pending()->create(['name' => 'مكتب جديد']);

    Livewire::actingAs($this->admin)->test(Index::class)
        ->assertSee(['مكتب نشط', 'مكتب تجريبي', 'مكتب موقوف', 'مكتب جديد'])
        ->set('status', 'suspended')
        ->assertSee('مكتب موقوف')
        ->assertDontSee(['مكتب نشط', 'مكتب تجريبي', 'مكتب جديد'])
        ->set('status', 'trial')
        ->assertSee('مكتب تجريبي')
        ->assertDontSee(['مكتب نشط', 'مكتب موقوف']);
});

test('the list finds a workspace by its owner mobile or license number', function () {
    $workspace = Workspace::factory()->create(['name' => 'مكتب البحث', 'fal_number' => '1200555123']);
    $owner = User::factory()->create(['mobile' => '0551234567']);
    Membership::factory()->for($workspace)->for($owner)->create(['role' => MemberRole::Owner]);
    Workspace::factory()->create(['name' => 'مكتب آخر']);

    Livewire::actingAs($this->admin)->test(Index::class)
        ->set('search', '055 123 4567')
        ->assertSee('مكتب البحث')
        ->assertDontSee('مكتب آخر')
        ->set('search', '١٢٠٠٥٥٥')
        ->assertSee('مكتب البحث')
        ->assertDontSee('مكتب آخر');
});

test('the export lists the filtered workspaces', function () {
    Workspace::factory()->suspended()->create(['name' => 'مكتب موقوف']);
    Workspace::factory()->create(['name' => 'مكتب نشط']);

    $csv = $this->actingAs($this->admin)->get(route('admin.workspaces.export', ['status' => 'suspended']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('مكتب موقوف')->not->toContain('مكتب نشط');
});

test('members cannot open the admin workspace and user pages', function () {
    [$owner, $workspace] = member();

    $this->actingAs($owner)->get(route('admin.workspaces.show', $workspace))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.users.show', $owner))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.workspaces.export'))->assertForbidden();
});

test('the workspace page shows its owner, team and invoices', function () {
    [$owner, $workspace] = member();
    $workspace->invoices()->create(['number' => 'TKH-202611-00001', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);

    $this->actingAs($this->admin)->get(route('admin.workspaces.show', $workspace))
        ->assertOk()
        ->assertSee([$workspace->name, $owner->name, 'TKH-202611-00001']);
});

test('a rejected workspace can be approved once corrected', function () {
    Notification::fake();
    $workspace = Workspace::factory()->create();
    $workspace->forceFill([
        'approval_status' => ApprovalStatus::Rejected,
        'rejected_at' => now(),
        'rejection_reason' => RejectionReason::InvalidCr,
    ])->save();
    [$owner] = member(MemberRole::Owner, $workspace);

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->assertSee(RejectionReason::InvalidCr->label())
        ->call('approve')
        ->assertSee('اعتماد الترخيص');

    $workspace->refresh();
    expect($workspace->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($workspace->rejection_reason)->toBeNull();
    Notification::assertSentTo($owner, WorkspaceApproved::class);
});

test('an approved workspace cannot be approved or rejected again', function () {
    $workspace = Workspace::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('approve')
        ->assertForbidden();

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('openReject')
        ->assertForbidden();
});

test('license details are validated and saved', function () {
    $workspace = Workspace::factory()->independentAgent()->create();
    [$owner] = member(MemberRole::Owner, $workspace);

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('openLicense')
        ->set('falNumber', '12345')
        ->call('saveLicense')
        ->assertHasErrors(['falNumber' => 'digits'])
        ->set('falNumber', '١١٠٠٠٩٩٨٨٧')
        ->set('licenseExpiresOn', '2028-03-01')
        ->call('saveLicense')
        ->assertHasNoErrors();

    expect($workspace->fresh())
        ->fal_number->toBe('1100099887')
        ->license_expires_on->toDateString()->toBe('2028-03-01')
        ->and($owner->memberships()->first()->fal_number)->toBe('1100099887');
});

test('free days extend the paid month, or the trial before the first payment', function () {
    $this->travelTo('2026-11-08 10:00');
    $paying = Workspace::factory()->create(['trial_ends_at' => now()->subMonths(2)]);
    $paying->forceFill(['paid_until' => '2026-11-20'])->save();
    $trial = Workspace::factory()->create(['trial_ends_at' => now()->addDays(3)]);

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $paying])
        ->set('days', 30)
        ->call('grantDays');

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $trial])
        ->set('days', 7)
        ->call('grantDays');

    expect($paying->fresh()->paid_until->toDateString())->toBe('2026-12-20')
        ->and($trial->fresh()->trial_ends_at->toDateString())->toBe('2026-11-18');
});

test('an office with a team cannot move to the agent plan', function () {
    $workspace = Workspace::factory()->create();
    member(MemberRole::Owner, $workspace);
    member(MemberRole::Agent, $workspace);

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('openPlan')
        ->set('plan', Plan::Agent->value)
        ->call('savePlan')
        ->assertHasErrors('plan')
        ->set('plan', Plan::Plus->value)
        ->call('savePlan')
        ->assertHasNoErrors();

    expect($workspace->fresh())->plan->toBe(Plan::Plus)->seats->toBe(15);
});

test('a custom domain can be unlinked', function () {
    $workspace = Workspace::factory()->create(['custom_domain' => 'www.example.sa']);
    $workspace->forceFill(['domain_status' => DomainStatus::Active])->save();

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('openRemoveDomain')
        ->call('removeDomain')
        ->assertSee('إلغاء ربط النطاق www.example.sa');

    expect($workspace->fresh())->custom_domain->toBeNull()->domain_status->toBeNull();
});

test('admins leave notes in the workspace history', function () {
    $workspace = Workspace::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->call('addNote')
        ->assertHasErrors('note')
        ->set('note', 'اتصلنا بالمالك بخصوص التجديد')
        ->call('addNote')
        ->assertSee(['اتصلنا بالمالك بخصوص التجديد', 'هند العمري']);
});

test('notes are escaped in the history', function () {
    $workspace = Workspace::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['workspace' => $workspace])
        ->set('note', '<script>alert(1)</script>')
        ->call('addNote')
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
