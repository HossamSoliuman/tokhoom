<?php

use App\Livewire\Admin\Users\Index;
use App\Livewire\Admin\Users\Show;
use App\Models\User;
use App\Notifications\ResetPasswordLink;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create(['name' => 'هند العمري']);
});

test('the users list searches and filters accounts', function () {
    [$owner, $workspace] = member();
    $owner->update(['name' => 'خالد الراشد', 'mobile' => '0559876543']);
    User::factory()->create(['name' => 'سارة القحطاني']);

    $this->actingAs($this->admin)->get(route('admin.users'))->assertOk()->assertSee($workspace->name);

    Livewire::actingAs($this->admin)->test(Index::class)
        ->set('search', '0559876543')
        ->assertSee('خالد الراشد')
        ->assertDontSee('سارة القحطاني')
        ->set('search', '')
        ->set('filter', 'admins')
        ->assertSee('هند العمري')
        ->assertDontSee(['خالد الراشد', 'سارة القحطاني'])
        ->set('filter', 'no-workspace')
        ->assertSee('سارة القحطاني')
        ->assertDontSee('خالد الراشد');
});

test('the user page lists their workspaces', function () {
    [$owner, $workspace] = member();

    $this->actingAs($this->admin)->get(route('admin.users.show', $owner))
        ->assertOk()
        ->assertSee([$owner->email, $workspace->name]);
});

test('admin access can be granted and taken away', function () {
    $user = User::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('confirmAdmin')
        ->call('toggleAdmin')
        ->assertSee('منح صلاحية إدارة المنصة');

    expect($user->fresh()->is_platform_admin)->toBeTrue();

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user->fresh()])
        ->call('toggleAdmin');

    expect($user->fresh()->is_platform_admin)->toBeFalse();
});

test('admins cannot remove their own admin access', function () {
    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $this->admin])
        ->call('toggleAdmin')
        ->assertForbidden();

    expect($this->admin->fresh()->is_platform_admin)->toBeTrue();
});

test('two-step verification can be turned off for a locked out member', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_recovery_codes' => ['hash'],
        'two_factor_confirmed_at' => now(),
    ])->save();

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('confirmTwoFactorReset')
        ->call('resetTwoFactor');

    expect($user->fresh())
        ->hasTwoFactor()->toBeFalse()
        ->two_factor_secret->toBeNull();
});

test('a password reset link can be sent to a member', function () {
    Notification::fake();
    $user = User::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('sendPasswordReset')
        ->assertSee('إرسال رابط إعادة تعيين كلمة المرور');

    Notification::assertSentTo($user, ResetPasswordLink::class);
});
