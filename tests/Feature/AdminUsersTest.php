<?php

use App\Livewire\Admin\Users\Index;
use App\Livewire\Admin\Users\Show;
use App\Livewire\Auth\Login;
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

test('a blocked account is signed out and cannot sign in again', function () {
    [$owner] = member();
    $owner->update(['email' => 'k.alrashid@alrashid.sa']);

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $owner])
        ->call('confirmBlock')
        ->call('toggleBlock')
        ->assertSee('إيقاف الحساب عن تسجيل الدخول');

    $this->actingAs($owner->fresh())->get(route('today'))->assertRedirect(route('login'));
    $this->assertGuest();

    Livewire::test(Login::class)
        ->set('email', 'k.alrashid@alrashid.sa')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => 'هذا الحساب موقوف. تواصل مع دعم تخوم.']);

    $this->assertGuest();
});

test('an unblocked account signs in again', function () {
    $user = User::factory()->create(['email' => 'k.alrashid@alrashid.sa', 'blocked_at' => now()]);

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('toggleBlock');

    Livewire::test(Login::class)
        ->set('email', 'k.alrashid@alrashid.sa')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

test('admins cannot block themselves', function () {
    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $this->admin])
        ->call('toggleBlock')
        ->assertForbidden();

    expect($this->admin->fresh()->isBlocked())->toBeFalse();
});

test('an account email and mobile can be corrected', function () {
    $user = User::factory()->create(['email' => 'old@alrashid.sa']);

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('openDetails')
        ->set('email', 'New@Alrashid.sa')
        ->set('mobile', '٠٥٥٩٨٧٦٥٤٣')
        ->call('saveDetails')
        ->assertHasNoErrors()
        ->assertSee('تعديل بيانات الحساب: البريد الإلكتروني، الجوال');

    expect($user->fresh())
        ->email->toBe('new@alrashid.sa')
        ->mobile->toBe('0559876543');
});

test('an email used by another account is refused', function () {
    User::factory()->create(['email' => 'taken@alrashid.sa']);
    $user = User::factory()->create();

    Livewire::actingAs($this->admin)->test(Show::class, ['user' => $user])
        ->call('openDetails')
        ->set('email', 'taken@alrashid.sa')
        ->call('saveDetails')
        ->assertHasErrors(['email' => 'هذا البريد مستخدم في حساب آخر']);
});
