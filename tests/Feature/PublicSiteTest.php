<?php

use App\Enums\DomainStatus;
use App\Models\Listing;
use App\Models\Workspace;

test('a workspace subdomain shows its live listings only', function () {
    $workspace = Workspace::factory()->create(['slug' => 'alrashid']);
    $live = Listing::factory()->for($workspace)->create(['district' => 'الملقا']);
    $draft = Listing::factory()->for($workspace)->draft()->create(['district' => 'النرجس']);
    $expired = Listing::factory()->for($workspace)->adLicenseExpired()->create(['district' => 'حطين']);
    $otherOffice = Listing::factory()->create(['district' => 'العارض']);

    $this->get(hostUrl(siteSubdomain('alrashid')))
        ->assertOk()
        ->assertSee($workspace->name)
        ->assertSee($live->shortTitle())
        ->assertDontSee($draft->shortTitle())
        ->assertDontSee($expired->shortTitle())
        ->assertDontSee($otherOffice->shortTitle());
});

test('a listing opens on its office site but not on another', function () {
    $listing = Listing::factory()->create();
    Workspace::factory()->create(['slug' => 'other']);

    $this->get(hostUrl(siteSubdomain($listing->workspace->slug), '/listings/'.$listing->short_code))
        ->assertOk()
        ->assertSee($listing->ad_license_number);

    $this->get(hostUrl(siteSubdomain('other'), '/listings/'.$listing->short_code))->assertNotFound();
});

test('unknown, pending and suspended sites are not found', function (Closure $makeWorkspace) {
    $makeWorkspace();

    $this->get(hostUrl(siteSubdomain('hidden')))->assertNotFound();
})->with([
    'unknown' => fn () => null,
    'pending approval' => fn () => Workspace::factory()->pending()->create(['slug' => 'hidden']),
    'suspended' => fn () => Workspace::factory()->suspended()->create(['slug' => 'hidden']),
]);

test('a custom domain serves the site only once it is active', function (DomainStatus $status, int $expected) {
    Workspace::factory()->create(['custom_domain' => 'www.alrashid.sa', 'domain_status' => $status]);

    $this->get(hostUrl('www.alrashid.sa'))->assertStatus($expected);
})->with([
    'active' => [DomainStatus::Active, 200],
    'pending' => [DomainStatus::Pending, 404],
]);

test('dashboard pages are not served on a site host', function () {
    Workspace::factory()->create(['slug' => 'alrashid']);

    $this->get(hostUrl(siteSubdomain('alrashid'), '/login'))->assertNotFound();
    $this->get(hostUrl(config('tokhoom.app_domain'), '/login'))->assertOk();
});

test('short links redirect to the listing on its office site', function () {
    $listing = Listing::factory()->create();

    $this->get(hostUrl(config('tokhoom.short_domain'), '/'.$listing->short_code))
        ->assertRedirect($listing->publicUrl().'?src=shared_link');
});

test('the local hosts file gets one entry per workspace subdomain', function () {
    Workspace::factory()->create(['slug' => 'alrashid']);
    Workspace::factory()->create(['slug' => 'nokhba']);
    $path = tempnam(sys_get_temp_dir(), 'hosts');
    file_put_contents($path, "127.0.0.1 localhost\n");

    $this->artisan('tokhoom:local-hosts', ['--path' => $path])->assertSuccessful();
    $this->artisan('tokhoom:local-hosts', ['--path' => $path])->assertSuccessful();

    $contents = file_get_contents($path);
    unlink($path);

    expect($contents)->toContain('127.0.0.1 localhost')
        ->toContain('127.0.0.1 '.siteSubdomain('alrashid'))
        ->toContain('127.0.0.1 '.siteSubdomain('nokhba'))
        ->toContain('127.0.0.1 '.config('tokhoom.short_domain'))
        ->and(substr_count($contents, '# tokhoom sites start'))->toBe(1);
});
