<?php

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Signup;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ApprovalRequested;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('members sign in with email and password', function () {
    $user = User::factory()->create(['email' => 'k.alrashid@alrashid.sa']);

    Livewire::test(Login::class)
        ->set('email', 'K.Alrashid@alrashid.sa')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('a wrong password is rejected', function () {
    User::factory()->create(['email' => 'k.alrashid@alrashid.sa']);

    Livewire::test(Login::class)
        ->set('email', 'k.alrashid@alrashid.sa')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('an office signs up step by step and waits for approval', function () {
    Notification::fake();
    $admin = User::factory()->platformAdmin()->create();
    Workspace::factory()->create(['slug' => 'alrashid']);

    $component = Livewire::test(Signup::class)
        ->set('name', 'خالد الراشد')
        ->set('email', 'k.alrashid@alrashid.sa')
        ->set('mobile', '0501234567')
        ->set('password', 'password1')
        ->call('next')
        ->assertSet('step', 2)
        ->call('pickKind', 'office')
        ->call('next')
        ->assertSet('step', 3)
        ->set('tradeName', 'مكتب الراشد العقاري')
        ->set('crNumber', '1010567890')
        ->set('falNumber', '1200045678')
        ->set('licenseExpiresOn', now()->addYear()->toDateString())
        ->call('next')
        ->assertSet('step', 4)
        ->set('slug', 'AlRashid!')
        ->assertSet('slug', 'alrashid')
        ->assertSee('هذا العنوان مستخدم')
        ->call('useSuggestion', 'alrashid-re')
        ->assertSee('العنوان متاح')
        ->call('next')
        ->assertSet('step', 5);

    $user = User::where('email', 'k.alrashid@alrashid.sa')->sole();
    $workspace = $user->currentWorkspace;

    expect($workspace->slug)->toBe('alrashid-re')
        ->and($workspace->approval_status)->toBe(ApprovalStatus::Pending)
        ->and($workspace->plan)->toBe(Plan::Office)
        ->and($user->currentMembership()->role)->toBe(MemberRole::Owner);
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($admin, ApprovalRequested::class);
    $component->assertSee('طلبك قيد المراجعة');
});

test('an expired license cannot be used to sign up', function () {
    Livewire::test(Signup::class)
        ->set('step', 3)
        ->call('pickKind', 'agent')
        ->set('name', 'سارة القحطاني')
        ->set('falNumber', '1100012345')
        ->set('licenseExpiresOn', now()->subDay()->toDateString())
        ->call('next')
        ->assertHasErrors('licenseExpiresOn')
        ->assertSet('step', 3);
});

test('signed-in users can create another workspace starting at the account type', function () {
    [$user] = member();

    Livewire::actingAs($user)->test(Signup::class)
        ->assertSet('step', 2);
});

test('people without a workspace are sent to create one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('today'))->assertRedirect(route('workspaces.create'));
});

test('members of a suspended workspace cannot use the dashboard', function () {
    $workspace = Workspace::factory()->suspended()->create();
    [$user] = member(MemberRole::Owner, $workspace);

    $this->actingAs($user)->get(route('listings.index'))->assertForbidden()->assertSee('مساحة العمل موقوفة');
});

test('members can switch only to their own workspaces', function () {
    [$user] = member();
    $other = Workspace::factory()->create();
    [, $mine] = member(MemberRole::Manager);
    $mine->memberships()->create(['user_id' => $user->id, 'role' => MemberRole::Manager]);

    $this->actingAs($user)->post(route('workspaces.switch', $other))->assertForbidden();
    $this->actingAs($user)->post(route('workspaces.switch', $mine))->assertRedirect(route('today'));

    expect($user->fresh()->current_workspace_id)->toBe($mine->id);
});

test('the more page lists only what the member may manage', function () {
    [$agent] = member(MemberRole::Agent);

    $this->actingAs($agent)->get(route('more'))
        ->assertOk()
        ->assertSee('تسجيل الخروج')
        ->assertDontSee('الاشتراك والنطاق');
});
