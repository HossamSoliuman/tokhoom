<?php

use App\Enums\ApprovalStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Livewire\Account;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\Signup;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ApprovalRequested;
use App\Notifications\LoginCode;
use App\Notifications\ResetPasswordLink;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
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

/**
 * Ask for a login code on the mobile tab and return the component and the code that was texted.
 *
 * @return array{0: Testable, 1: string}
 */
function requestLoginCode(User $user): array
{
    $login = Livewire::test(Login::class)
        ->set('method', 'mobile')
        ->set('mobile', '055 123 4567')
        ->call('sendCode')
        ->assertHasNoErrors();

    $code = null;
    Notification::assertSentTo($user, LoginCode::class, function (LoginCode $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    return [$login, $code];
}

test('members sign in with a code texted to their mobile', function () {
    Notification::fake();
    $user = User::factory()->create(['mobile' => '0551234567']);

    [$login, $code] = requestLoginCode($user);
    $login->set('code', $code)->call('verifyCode')->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('a login code works only once', function () {
    Notification::fake();
    $user = User::factory()->create(['mobile' => '0551234567']);
    [$login, $code] = requestLoginCode($user);
    $login->set('code', $code)->call('verifyCode');
    auth()->logout();

    $login->set('code', $code)->call('verifyCode')->assertHasErrors('code');

    $this->assertGuest();
});

test('a login code expires after five minutes', function () {
    Notification::fake();
    $user = User::factory()->create(['mobile' => '0551234567']);
    [$login, $code] = requestLoginCode($user);

    $this->travel(6)->minutes();
    $login->set('code', $code)->call('verifyCode')->assertSee('الرمز غير صحيح أو انتهت صلاحيته.');

    $this->assertGuest();
});

test('wrong login codes are limited to five guesses', function () {
    Notification::fake();
    $user = User::factory()->create(['mobile' => '0551234567']);
    [$login, $code] = requestLoginCode($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $guess) {
        $login->set('code', $wrong)->call('verifyCode')->assertHasErrors('code');
    }
    $login->set('code', $code)->call('verifyCode')->assertSee('محاولات كثيرة');

    $this->assertGuest();
});

test('a mobile without an account gets no code and no hint', function () {
    Notification::fake();
    User::factory()->create(['mobile' => '0550000000']);

    Livewire::test(Login::class)
        ->set('method', 'mobile')
        ->set('mobile', '0551234567')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('codeMobile', '0551234567');

    Notification::assertNothingSent();
});

test('login codes to one mobile are limited to three in ten minutes', function () {
    Notification::fake();
    $user = User::factory()->create(['mobile' => '0551234567']);
    $login = Livewire::test(Login::class)->set('method', 'mobile')->set('mobile', '0551234567');

    foreach (range(1, 4) as $request) {
        $login->call('sendCode');
    }

    $login->assertHasErrors('mobile');
    Notification::assertSentToTimes($user, LoginCode::class, 3);
});

test('mobile sign in is hidden in production until an SMS provider is set', function () {
    config(['services.sms.driver' => 'log']);
    $this->app->detectEnvironment(fn () => 'production');

    Livewire::test(Login::class)
        ->assertDontSee('رقم الجوال')
        ->set('mobile', '0551234567')
        ->call('sendCode')
        ->assertNotFound();
});

test('a forgotten password is reset from the emailed link', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'k.alrashid@alrashid.sa']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'K.Alrashid@alrashid.sa')
        ->call('send')
        ->assertSet('sent', true);

    $token = null;
    Notification::assertSentTo($user, ResetPasswordLink::class, function (ResetPasswordLink $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

    Livewire::withQueryParams(['email' => $user->email])
        ->test(ResetPassword::class, ['token' => $token])
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('an unknown email gets the same answer and no link', function () {
    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@example.com')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    Notification::assertNothingSent();
});

test('a wrong reset token leaves the password unchanged', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams(['email' => $user->email])
        ->test(ResetPassword::class, ['token' => 'not-a-real-token'])
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('save')
        ->assertHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
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
        ->set('kind', 'office')
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

test('signup starts with the site address and account type picked on the landing page', function () {
    Livewire::withQueryParams(['slug' => 'AlNakheel', 'kind' => 'agent'])
        ->test(Signup::class)
        ->assertSet('slug', 'alnakheel')
        ->assertSet('kind', 'agent')
        ->assertSet('step', 1);

    Livewire::withQueryParams(['kind' => 'shop'])
        ->test(Signup::class)
        ->assertSet('kind', 'office');
});

test('an unknown account type picked during signup falls back to an office', function () {
    Livewire::test(Signup::class)
        ->set('kind', 'agent')
        ->set('kind', 'shop')
        ->assertSet('kind', 'office');
});

test('an expired license cannot be used to sign up', function () {
    Livewire::test(Signup::class)
        ->set('step', 3)
        ->set('kind', 'agent')
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

test('members update their own details', function () {
    [$user] = member(MemberRole::Agent);

    $this->actingAs($user)->get(route('account'))->assertOk();

    Livewire::actingAs($user)->test(Account::class)
        ->set('name', 'سارة القحطاني')
        ->set('email', 'Sara@Example.com')
        ->set('mobile', '055 999 8877')
        ->call('saveDetails')
        ->assertHasNoErrors();

    expect($user->fresh())
        ->name->toBe('سارة القحطاني')
        ->email->toBe('sara@example.com')
        ->mobile->toBe('0559998877');
});

test('an email or mobile used by another account is refused', function () {
    [$user] = member();
    User::factory()->create(['email' => 'taken@example.com', 'mobile' => '0551112233']);

    Livewire::actingAs($user)->test(Account::class)
        ->set('email', 'taken@example.com')
        ->set('mobile', '0551112233')
        ->call('saveDetails')
        ->assertHasErrors(['email' => 'unique', 'mobile' => 'unique']);
});

test('changing the password needs the current one', function () {
    [$user] = member();

    Livewire::actingAs($user)->test(Account::class)
        ->set('currentPassword', 'wrong-password')
        ->set('newPassword', 'new-password-123')
        ->set('newPassword_confirmation', 'new-password-123')
        ->call('changePassword')
        ->assertHasErrors(['currentPassword' => 'current_password']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();

    Livewire::actingAs($user)->test(Account::class)
        ->set('currentPassword', 'password')
        ->set('newPassword', 'new-password-123')
        ->set('newPassword_confirmation', 'new-password-123')
        ->call('changePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

/**
 * A member with two-step verification on and one known recovery code.
 */
function twoFactorUser(): User
{
    return tap(User::factory()->create(['email' => 'k.alrashid@alrashid.sa', 'mobile' => '0551234567']), function (User $user) {
        $user->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_recovery_codes' => [Hash::make('abcde-12345')],
            'two_factor_confirmed_at' => now(),
        ])->save();
    });
}

function passwordStep(): Testable
{
    return Livewire::test(Login::class)
        ->set('email', 'k.alrashid@alrashid.sa')
        ->set('password', 'password')
        ->call('login');
}

test('members turn on two-step verification from their account', function () {
    [$user] = member();

    $account = Livewire::actingAs($user)->test(Account::class)->call('startTwoFactor');
    $secret = $user->fresh()->two_factor_secret;

    $account->set('twoFactorCode', Totp::code($secret) === '000000' ? '111111' : '000000')
        ->call('confirmTwoFactor')
        ->assertHasErrors('twoFactorCode');
    expect($user->fresh()->hasTwoFactor())->toBeFalse();

    $account->set('twoFactorCode', Totp::code($secret))
        ->call('confirmTwoFactor')
        ->assertHasNoErrors()
        ->assertCount('recoveryCodes', 8);
    expect($user->fresh()->hasTwoFactor())->toBeTrue();
});

test('with two-step verification the password alone does not sign in', function () {
    $user = twoFactorUser();

    $login = passwordStep()->assertSet('twoFactorUserId', $user->id);
    $this->assertGuest();

    $login->set('twoFactorCode', Totp::code($user->two_factor_secret))
        ->call('verifyTwoFactor')
        ->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

test('a texted code is also followed by the authenticator step', function () {
    Notification::fake();
    $user = twoFactorUser();

    [$login, $code] = requestLoginCode($user);
    $login->set('code', $code)->call('verifyCode')->assertSet('twoFactorUserId', $user->id);

    $this->assertGuest();
});

test('an authenticator code cannot be used twice', function () {
    $user = twoFactorUser();
    $code = Totp::code($user->two_factor_secret);
    passwordStep()->set('twoFactorCode', $code)->call('verifyTwoFactor');
    auth()->logout();

    passwordStep()->set('twoFactorCode', $code)->call('verifyTwoFactor')->assertHasErrors('twoFactorCode');

    $this->assertGuest();
});

test('a recovery code signs in once', function () {
    twoFactorUser();
    passwordStep()->set('twoFactorCode', 'ABCDE-12345')->call('verifyTwoFactor')->assertRedirect(route('home'));
    auth()->logout();

    passwordStep()->set('twoFactorCode', 'abcde-12345')->call('verifyTwoFactor')->assertHasErrors('twoFactorCode');

    $this->assertGuest();
});

test('authenticator guesses are limited to five', function () {
    $user = twoFactorUser();
    $code = Totp::code($user->two_factor_secret);
    $login = passwordStep();

    foreach (range(1, 5) as $guess) {
        $login->set('twoFactorCode', 'wrong-'.$guess)->call('verifyTwoFactor');
    }
    $login->set('twoFactorCode', $code)->call('verifyTwoFactor')->assertSee('محاولات كثيرة');

    $this->assertGuest();
});

test('turning two-step verification off needs the current password', function () {
    [$user] = member();
    $user->forceFill(['two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()])->save();

    Livewire::actingAs($user)->test(Account::class)
        ->set('disablePassword', 'wrong-password')
        ->call('disableTwoFactor')
        ->assertHasErrors('disablePassword');
    expect($user->fresh()->hasTwoFactor())->toBeTrue();

    Livewire::actingAs($user)->test(Account::class)
        ->set('disablePassword', 'password')
        ->call('disableTwoFactor')
        ->assertHasNoErrors();
    expect($user->fresh())
        ->hasTwoFactor()->toBeFalse()
        ->two_factor_secret->toBeNull();
});
