<?php

use App\Livewire\ClaimSiteAddress;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

test('guests see the landing page with every plan', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('موقع عقاري باسم مكتبك')
        ->assertSee(route('signup'))
        ->assertSee('349')
        ->assertSee('799');
});

test('the sample site link shows only when one is set', function () {
    config(['tokhoom.demo_site_url' => null]);

    $this->get('/')->assertDontSee('زر موقعاً نموذجياً');

    config(['tokhoom.demo_site_url' => 'https://alrashid.example.test']);

    $this->get('/')->assertSee('https://alrashid.example.test');
});

test('a free site address and account type picked on the landing page are carried into signup', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->set('kind', 'agent')
        ->set('slug', 'Al Nakheel')
        ->assertSet('slug', 'alnakheel')
        ->assertSee('العنوان متاح')
        ->assertDispatched('site-address', slug: 'alnakheel', kind: 'agent', state: 'available')
        ->call('start')
        ->assertRedirect(route('signup', ['slug' => 'alnakheel', 'kind' => 'agent']));
});

test('a pasted site link keeps only the address', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->set('slug', 'https://AlRashid.tokhoom.sa/')
        ->assertSet('slug', 'alrashid');
});

test('typing Arabic letters asks for latin ones', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->set('slug', 'الراشد')
        ->assertSet('slug', '')
        ->assertSee('اكتب العنوان بأحرف إنجليزية')
        ->call('start')
        ->assertHasErrors('slug')
        ->assertNoRedirect();
});

test('a reserved site address is not offered', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->set('slug', 'admin')
        ->assertSee('هذا العنوان غير متاح')
        ->assertDontSee('هذا العنوان مستخدم')
        ->call('start')
        ->assertHasErrors('slug')
        ->assertNoRedirect();
});

test('the final form starts with what the visitor picked in the hero', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->set('kind', 'agent')
        ->set('slug', 'alnakheel');

    Livewire::test(ClaimSiteAddress::class, ['withLabel' => false])
        ->assertSet('slug', 'alnakheel')
        ->assertSet('kind', 'agent')
        ->assertSee('العنوان متاح');
});

test('a taken or invalid site address keeps the visitor on the landing page', function (string $slug) {
    Workspace::factory()->create(['slug' => 'alrashid']);

    Livewire::test(ClaimSiteAddress::class)
        ->set('slug', $slug)
        ->call('start')
        ->assertHasErrors('slug')
        ->assertNoRedirect();
})->with(['taken' => 'alrashid', 'too short' => 'ab']);

test('a taken site address offers free alternatives', function () {
    Workspace::factory()->create(['slug' => 'alrashid']);

    Livewire::test(ClaimSiteAddress::class)
        ->set('slug', 'alrashid')
        ->assertSee('هذا العنوان مستخدم')
        ->call('useSuggestion', 'alrashid-re')
        ->assertSet('slug', 'alrashid-re')
        ->assertSee('العنوان متاح');
});

test('starting without a site address opens signup', function () {
    Livewire::test(ClaimSiteAddress::class)
        ->call('start')
        ->assertRedirect(route('signup', ['kind' => 'office']));
});

test('signed-in people skip the landing page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect();
});
