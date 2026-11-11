<?php

use App\Enums\DomainStatus;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Livewire\Billing\Index as Billing;
use App\Livewire\ShareSite;
use App\Livewire\Site\Settings;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Support\DomainVerifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('managers can edit the site but agents cannot', function () {
    [$manager, $workspace] = member(MemberRole::Manager);
    [$agent] = member(MemberRole::Agent, $workspace);

    $this->actingAs($manager)->get(route('site'))->assertOk()->assertSee('لون الهوية');
    $this->actingAs($agent)->get(route('site'))->assertForbidden();
});

test('site identity and contacts are saved', function () {
    Storage::fake('public');
    [$owner, $workspace] = member();

    Livewire::actingAs($owner)->test(Settings::class)
        ->set('brandColor', '#1f3a5f')
        ->set('about', 'مكتب عقاري في الرياض')
        ->set('whatsapp', '+966501234567')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $workspace->refresh();
    expect($workspace->brand_color)->toBe('#1F3A5F')
        ->and($workspace->about)->toBe('مكتب عقاري في الرياض')
        ->and($workspace->whatsapp)->toBe('0501234567');
    Storage::disk('public')->assertExists($workspace->logo_path);
});

test('an invalid brand colour is rejected', function () {
    [$owner] = member();

    Livewire::actingAs($owner)->test(Settings::class)
        ->set('brandColor', 'teal')
        ->call('save')
        ->assertHasErrors('brandColor');
});

test('only the owner manages billing', function () {
    [$owner, $workspace] = member();
    [$manager] = member(MemberRole::Manager, $workspace);

    $this->actingAs($owner)->get(route('billing'))->assertOk()->assertSee('الخطة الحالية');
    $this->actingAs($manager)->get(route('billing'))->assertForbidden();
});

test('changing plan and adding seats updates the monthly total', function () {
    [$owner, $workspace] = member();

    Livewire::actingAs($owner)->test(Billing::class)
        ->call('openPlan')
        ->set('plan', Plan::Plus->value)
        ->call('changePlan')
        ->assertHasNoErrors();

    expect($workspace->fresh()->plan)->toBe(Plan::Plus)
        ->and($workspace->fresh()->paidSeats())->toBe(15);

    Livewire::actingAs($owner)->test(Billing::class)
        ->call('openSeats')
        ->call('stepSeats', 2)
        ->call('saveSeats');

    expect($workspace->fresh()->seats)->toBe(17)
        ->and($workspace->fresh()->monthlyTotal())->toBe(799 + 2 * 49);
});

test('an office with several members cannot move to the single agent plan', function () {
    [$owner, $workspace] = member();
    member(MemberRole::Agent, $workspace);

    Livewire::actingAs($owner)->test(Billing::class)
        ->call('openPlan')
        ->set('plan', Plan::Agent->value)
        ->call('changePlan')
        ->assertHasErrors('plan');

    expect($workspace->fresh()->plan)->toBe(Plan::Office);
});

test('a custom domain moves on to its certificate once its CNAME points at us', function (bool $pointsToUs, DomainStatus $status) {
    [$owner, $workspace] = member();
    $this->mock(DomainVerifier::class)->shouldReceive('pointsToUs')->with('www.alrashid.sa')->andReturn($pointsToUs);

    Livewire::actingAs($owner)->test(Billing::class)
        ->set('domain', 'https://WWW.alrashid.sa/')
        ->call('verifyDomain');

    expect($workspace->fresh()->custom_domain)->toBe('www.alrashid.sa')
        ->and($workspace->fresh()->domain_status)->toBe($status);
})->with([
    'pointing' => [true, DomainStatus::Securing],
    'missing record' => [false, DomainStatus::Error],
]);

test('a domain already used by another workspace is refused', function () {
    Workspace::factory()->create(['custom_domain' => 'www.taken.sa']);
    [$owner] = member();

    Livewire::actingAs($owner)->test(Billing::class)
        ->set('domain', 'www.taken.sa')
        ->call('verifyDomain')
        ->assertHasErrors('domain');
});

test('invoices open only for their own workspace', function () {
    [$owner, $workspace] = member();
    $invoice = Invoice::create(['workspace_id' => $workspace->id, 'number' => 'TKH-1', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);
    $foreign = Invoice::create(['workspace_id' => Workspace::factory()->create()->id, 'number' => 'TKH-2', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);

    $this->actingAs($owner)->get(route('billing.invoice', $invoice))->assertOk()->assertSee('TKH-1');
    $this->actingAs($owner)->get(route('billing.invoice', $foreign))->assertNotFound();
});

test('a VAT registered invoice shows the VAT and carries the ZATCA QR fields', function () {
    config(['tokhoom.seller.name' => 'تخوم', 'tokhoom.seller.vat_number' => '300000000000003']);
    [$owner, $workspace] = member();
    $invoice = Invoice::create(['workspace_id' => $workspace->id, 'number' => 'TKH-1', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => '2026-10-01']);

    $this->actingAs($owner)->get(route('billing.invoice', $invoice))
        ->assertOk()
        ->assertSee('فاتورة ضريبية مبسطة')
        ->assertSee('300000000000003')
        ->assertSeeInOrder(['303.48 ريال', '45.52 ريال', '349.00 ريال']);

    $tlv = base64_decode($invoice->zatcaPayload());
    $fields = [];
    for ($offset = 0; $offset < strlen($tlv); $offset += 2 + ord($tlv[$offset + 1])) {
        $fields[ord($tlv[$offset])] = substr($tlv, $offset + 2, ord($tlv[$offset + 1]));
    }

    expect($fields)->toHaveCount(5)
        ->and($fields[1])->toBe('تخوم')
        ->and($fields[2])->toBe('300000000000003')
        ->and($fields[3])->toStartWith('2026-')
        ->and($fields[4])->toBe('349.00')
        ->and($fields[5])->toBe('45.52');
});

test('without a VAT number the invoice is a plain invoice', function () {
    config(['tokhoom.seller.vat_number' => null]);
    [$owner, $workspace] = member();
    $invoice = Invoice::create(['workspace_id' => $workspace->id, 'number' => 'TKH-1', 'description' => 'اشتراك', 'amount' => 349, 'issued_on' => today()]);

    $this->actingAs($owner)->get(route('billing.invoice', $invoice))
        ->assertOk()
        ->assertDontSee('فاتورة ضريبية مبسطة')
        ->assertDontSee('ضريبة القيمة المضافة');
});

test('members share their own public page', function (MemberRole $role, Closure $makeWorkspace, string $path) {
    [$user, $workspace] = member($role, $makeWorkspace());

    Livewire::actingAs($user)
        ->test(ShareSite::class)
        ->call('openDialog')
        ->assertViewHas('url', $workspace->siteUrl(str_replace('{id}', (string) $user->id, $path)));
})->with([
    'office agent' => [MemberRole::Agent, fn () => Workspace::factory()->create(), '/agents/{id}'],
    'office manager' => [MemberRole::Manager, fn () => Workspace::factory()->create(), ''],
    'independent agent' => [MemberRole::Owner, fn () => Workspace::factory()->independentAgent()->create(), ''],
]);

test('the site QR code downloads for signed-in members only', function () {
    [$agent] = member(MemberRole::Agent);

    $this->get(route('share.qr', 'png'))->assertRedirect(route('login'));
    $this->actingAs($agent)->get(route('share.qr', 'png'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertDownload();
});
