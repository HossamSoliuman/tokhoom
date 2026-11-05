<?php

use App\Enums\DealStage;
use App\Enums\MemberRole;
use App\Livewire\Reports\Index;
use App\Models\Client;
use App\Models\CommissionTarget;
use App\Models\Deal;
use App\Models\Listing;
use Livewire\Livewire;

test('agents cannot open reports', function () {
    [$agent] = member(MemberRole::Agent);

    $this->actingAs($agent)->get(route('reports'))->assertForbidden();
    $this->actingAs($agent)->get(route('reports.export', ['from' => today()->subDays(7)->toDateString(), 'to' => today()->toDateString()]))->assertForbidden();
});

test('the report shows each agent and their commission against the monthly target', function () {
    [$owner, $workspace] = member();
    [$agent] = member(MemberRole::Agent, $workspace);
    $agent->update(['name' => 'سارة القحطاني']);
    $client = Client::factory()->for($workspace)->create(['agent_id' => $agent->id]);
    Listing::factory()->for($workspace)->create(['agent_id' => $agent->id]);
    Deal::factory()->for($workspace)->inStage(DealStage::Won)->create(['client_id' => $client->id, 'agent_id' => $agent->id, 'commission' => 60000, 'closed_at' => now()]);
    CommissionTarget::create(['workspace_id' => $workspace->id, 'user_id' => $agent->id, 'month' => now()->startOfMonth(), 'commission_target' => 100000, 'deals_target' => 3]);

    Livewire::actingAs($owner)->test(Index::class)
        ->call('preset', 'month')
        ->assertSee('سارة القحطاني')
        ->assertSee('60,000 / 100,000 ريال')
        ->assertSee('60,000 ريال');
});

test('a custom range is applied and future days are cut off', function () {
    [$owner] = member();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('applyRange', today()->toDateString(), today()->subDays(10)->toDateString())
        ->assertSet('range', 'custom')
        ->assertSet('from', today()->subDays(10)->toDateString())
        ->assertSet('to', today()->toDateString());
});

test('the export downloads a CSV of agent performance', function () {
    [$owner] = member();

    $response = $this->actingAs($owner)->get(route('reports.export', [
        'from' => today()->subDays(29)->toDateString(),
        'to' => today()->toDateString(),
    ]));

    $response->assertOk()->assertDownload();
    expect($response->streamedContent())->toContain('أداء الوسطاء');
});
