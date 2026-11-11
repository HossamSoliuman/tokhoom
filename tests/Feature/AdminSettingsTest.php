<?php

use App\Enums\Plan;
use App\Livewire\Admin\Settings;
use App\Models\AdminAction;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\MailSettingsCheck;
use App\Support\PlatformSettings;
use App\Support\SiteAddress;
use App\Support\Subscriptions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create();
});

test('only platform admins open the settings', function () {
    [$owner] = member();

    $this->actingAs($owner)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.settings', ['section' => 'mail']))->assertOk()->assertSee('خادم SMTP');
});

test('new plan prices apply to the next invoice and stay saved', function () {
    $workspace = Workspace::factory()->create(['plan' => Plan::Office]);

    Livewire::actingAs($this->admin)->test(Settings::class)
        ->set('form.office_price', '399')
        ->set('form.office_seats', '6')
        ->call('save')
        ->assertHasNoErrors();

    expect(Subscriptions::dueInvoice($workspace)->amount)->toEqual(399);

    config(['tokhoom.plans.office.price' => 349, 'tokhoom.plans.office.seats' => 5]);
    PlatformSettings::apply();

    expect(Plan::Office)
        ->monthlyPrice()->toBe(399)
        ->includedSeats()->toBe(6);
    expect(AdminAction::sole()->text())->toBe('تعديل الخطط والأسعار: سعر خطة المكتب، مقاعد خطة المكتب');
});

test('saving without changes stores nothing', function () {
    Livewire::actingAs($this->admin)->test(Settings::class)
        ->call('save')
        ->assertDispatched('toast', message: 'لا تغييرات لحفظها', kind: 'info');

    expect(Setting::count())->toBe(0)
        ->and(AdminAction::count())->toBe(0);
});

test('a malformed VAT number is refused', function () {
    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'invoicing'])
        ->set('form.seller_vat_number', '1234567890')
        ->call('save')
        ->assertHasErrors(['form.seller_vat_number' => 'الرقم الضريبي 15 رقماً يبدأ وينتهي بالرقم 3.']);

    expect(Setting::count())->toBe(0);
});

test('support numbers are saved in the local mobile form', function () {
    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'contact'])
        ->set('form.support_whatsapp', '+966 50 123 4567')
        ->call('save')
        ->assertHasNoErrors();

    expect(config('tokhoom.support.whatsapp'))->toBe('0501234567');
});

test('reserved site addresses are saved one per line', function () {
    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'signups'])
        ->set('form.reserved_slugs', "www\nAdmin\nriyadh-offers")
        ->call('save')
        ->assertHasNoErrors();

    expect(config('tokhoom.reserved_slugs'))->toBe(['www', 'admin', 'riyadh-offers'])
        ->and(SiteAddress::state('riyadh-offers'))->toBe('reserved')
        ->and(SiteAddress::state('dashboard'))->toBe('available');
});

test('a reserved address with other characters is refused', function () {
    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'signups'])
        ->set('form.reserved_slugs', "www\nمكتب")
        ->call('save')
        ->assertHasErrors(['form.reserved_slugs.1' => 'اكتب العناوين بأحرف إنجليزية صغيرة وأرقام وشرطة فقط.']);
});

test('the payment key is stored encrypted and kept when left blank', function () {
    $settings = Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'payments'])
        ->set('form.tap_secret_key', 'sk_live_abc123456789')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('form.tap_secret_key', '')
        ->assertSee('sk_live_••••6789');

    expect(Setting::find('tap_secret_key')->value)->not->toContain('sk_live_abc123456789')
        ->and(config('services.tap.secret_key'))->toBe('sk_live_abc123456789');

    $settings->set('form.tap_merchant_id', '599424')->call('save');

    expect(config('services.tap.secret_key'))->toBe('sk_live_abc123456789')
        ->and(config('services.tap.merchant_id'))->toBe('599424');
});

test('a mail server is required to send through SMTP', function () {
    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'mail'])
        ->set('form.mail_mailer', 'smtp')
        ->set('form.mail_host', '')
        ->call('save')
        ->assertHasErrors(['form.mail_host' => 'أدخل الخادم.']);
});

test('a test email goes to the signed-in admin', function () {
    Notification::fake();

    Livewire::actingAs($this->admin)->test(Settings::class, ['section' => 'mail'])
        ->call('sendTestEmail');

    Notification::assertSentTo($this->admin, MailSettingsCheck::class);
});
