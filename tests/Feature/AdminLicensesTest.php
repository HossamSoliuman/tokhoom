<?php

use App\Enums\MemberRole;
use App\Livewire\Admin\Licenses;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create();
});

test('the licenses page lists licenses running out in the chosen window', function () {
    Workspace::factory()->create(['name' => 'مكتب منتهٍ', 'license_expires_on' => today()->subDays(3)]);
    Workspace::factory()->create(['name' => 'مكتب قريب', 'license_expires_on' => today()->addDays(20)]);
    Workspace::factory()->create(['name' => 'مكتب بعيد', 'license_expires_on' => today()->addDays(80)]);
    $office = Workspace::factory()->create(['name' => 'مكتب الفريق']);
    Membership::factory()->for($office)->for(User::factory()->create(['name' => 'وسيط رخصته قريبة']))
        ->create(['role' => MemberRole::Agent, 'fal_number' => '1100011111', 'fal_expires_on' => today()->addDays(10)]);

    $this->actingAs($this->admin)->get(route('admin.licenses'))
        ->assertOk()
        ->assertSee(['مكتب منتهٍ', 'مكتب قريب', 'وسيط رخصته قريبة'])
        ->assertDontSee('مكتب بعيد');

    Livewire::actingAs($this->admin)->test(Licenses::class)
        ->set('window', 'expired')
        ->assertSee('مكتب منتهٍ')
        ->assertDontSee(['مكتب قريب', 'وسيط رخصته قريبة'])
        ->set('window', '90')
        ->assertSee('مكتب بعيد');
});

test('a renewal needs a future date and updates the license', function () {
    $workspace = Workspace::factory()->create(['license_expires_on' => today()->addDays(5)]);

    Livewire::actingAs($this->admin)->test(Licenses::class)
        ->call('openRenewal', $workspace->id)
        ->set('expiresOn', today()->subDay()->toDateString())
        ->call('renew')
        ->assertHasErrors(['expiresOn' => 'after'])
        ->set('expiresOn', today()->addYear()->toDateString())
        ->call('renew')
        ->assertHasNoErrors();

    expect($workspace->fresh()->license_expires_on->toDateString())->toBe(today()->addYear()->toDateString())
        ->and($workspace->adminActions()->where('type', 'workspace.license_updated')->exists())->toBeTrue();
});
