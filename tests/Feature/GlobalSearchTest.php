<?php

use App\Enums\MemberRole;
use App\Livewire\GlobalSearch;
use App\Models\Client;
use App\Models\Listing;
use Livewire\Livewire;

test('the top bar search finds clients by mobile and listings by district', function () {
    [$user, $workspace] = member();
    Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'عبدالله الدوسري', 'mobile' => '0551234567']);
    Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'district' => 'الملقا']);

    Livewire::actingAs($user)->test(GlobalSearch::class)
        ->set('query', '055 123')
        ->assertSee('عبدالله الدوسري')
        ->set('query', 'الملقا')
        ->assertSee('حي الملقا')
        ->assertDontSee('عبدالله الدوسري');
});

test('agents only find their own clients in the top bar search', function () {
    [$agent, $workspace] = member(MemberRole::Agent);
    [$colleague] = member(MemberRole::Agent, $workspace);
    Client::factory()->for($workspace)->create(['agent_id' => $colleague->id, 'name' => 'ريم العنزي']);

    Livewire::actingAs($agent)->test(GlobalSearch::class)
        ->set('query', 'ريم')
        ->assertDontSee('ريم العنزي')
        ->assertSee('لا نتائج ضمن صلاحياتك');
});
