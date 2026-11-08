<?php

use App\Models\User;

test('guests see the landing page with every plan', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('موقع عقاري باسم مكتبك')
        ->assertSee(route('signup'))
        ->assertSee('349')
        ->assertSee('799');
});

test('the sample site link shows only when one is set', function () {
    $this->get('/')->assertDontSee('زر موقعاً نموذجياً');

    config(['tokhoom.demo_site_url' => 'https://alrashid.example.test']);

    $this->get('/')->assertSee('https://alrashid.example.test');
});

test('signed-in people skip the landing page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect();
});
