<?php

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\RejectionReason;
use App\Livewire\Admin\Approvals;
use App\Livewire\Admin\Workspaces\Index as WorkspaceList;
use App\Livewire\Admin\Workspaces\Show as WorkspacePage;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceApproved;
use App\Notifications\WorkspaceRejected;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('only platform admins reach the admin pages', function () {
    [$owner] = member();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($owner)->get(route('admin.approvals'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.approvals'))->assertOk();
    $this->actingAs($admin)->get(route('admin.workspaces'))->assertOk();
});

test('an admin without a workspace lands on the overview', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)->get('/')->assertRedirect(route('admin.overview'));
    $this->actingAs($admin)->get(route('today'))->assertRedirect(route('admin.overview'));
});

test('approving a license notifies the owner', function () {
    Notification::fake();
    $admin = User::factory()->platformAdmin()->create();
    $workspace = Workspace::factory()->pending()->create();
    [$owner] = member(MemberRole::Owner, $workspace);

    Livewire::actingAs($admin)->test(Approvals::class)
        ->assertSee($workspace->name)
        ->call('approve', $workspace->id);

    expect($workspace->fresh()->approval_status)->toBe(ApprovalStatus::Approved);
    Notification::assertSentTo($owner, WorkspaceApproved::class);
});

test('rejecting needs a reason and records it', function () {
    Notification::fake();
    $admin = User::factory()->platformAdmin()->create();
    $workspace = Workspace::factory()->pending()->create();
    [$owner] = member(MemberRole::Owner, $workspace);

    Livewire::actingAs($admin)->test(Approvals::class)
        ->call('openReject', $workspace->id)
        ->call('reject')
        ->assertHasErrors('reason')
        ->set('reason', RejectionReason::Expiring->value)
        ->set('message', 'الترخيص ينتهي قريباً')
        ->call('reject')
        ->assertHasNoErrors();

    $workspace->refresh();
    expect($workspace->approval_status)->toBe(ApprovalStatus::Rejected)
        ->and($workspace->rejection_reason)->toBe(RejectionReason::Expiring);
    Notification::assertSentTo($owner, WorkspaceRejected::class);
});

test('workspaces can be suspended with a reason and reactivated', function () {
    $admin = User::factory()->platformAdmin()->create();
    $workspace = Workspace::factory()->create();

    Livewire::actingAs($admin)->test(WorkspacePage::class, ['workspace' => $workspace])
        ->call('openSuspend')
        ->assertSet('dialog', 'suspend')
        ->set('suspendReason', 'شكوى من الهيئة')
        ->call('suspend')
        ->assertSee('السبب: شكوى من الهيئة');

    expect($workspace->fresh()->isSuspended())->toBeTrue();

    Livewire::actingAs($admin)->test(WorkspacePage::class, ['workspace' => $workspace->fresh()])
        ->call('openSuspend')
        ->assertSet('dialog', 'reactivate')
        ->call('reactivate');

    expect($workspace->fresh()->isSuspended())->toBeFalse();
});

test('admins see which workspaces owe a payment', function () {
    $admin = User::factory()->platformAdmin()->create();
    $owing = Workspace::factory()->create(['name' => 'مكتب المتأخر', 'trial_ends_at' => now()->subMonth()]);
    $owing->invoices()->create(['number' => 'TKH-1', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);
    Workspace::factory()->create(['name' => 'مكتب المنتظم', 'trial_ends_at' => now()->subMonth()]);

    Livewire::actingAs($admin)->test(WorkspaceList::class)
        ->assertSeeInOrder(['مكتب المتأخر', 'دفعة مستحقة', 'مكتب المنتظم', 'نشط']);
});
