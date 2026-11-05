<?php

use App\Enums\DealStage;
use App\Enums\ListingStatus;
use App\Enums\LostReason;
use App\Enums\MemberRole;
use App\Livewire\Deals\Board;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Listing;
use Livewire\Livewire;

function dealFor($user, $workspace, array $attributes = []): Deal
{
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);

    return Deal::factory()->for($workspace)->create([
        'client_id' => $client->id,
        'agent_id' => $user->id,
        ...$attributes,
    ]);
}

test('the board groups deals by stage', function () {
    [$user, $workspace] = member();
    $deal = dealFor($user, $workspace);

    $this->actingAs($user)->get(route('deals.index'))
        ->assertOk()
        ->assertSee($deal->client->name);
});

test('moving a deal forward remembers the furthest stage', function () {
    [$user, $workspace] = member();
    $deal = dealFor($user, $workspace);

    Livewire::actingAs($user)->test(Board::class)
        ->call('moveTo', $deal->id, DealStage::Negotiation->value)
        ->call('moveTo', $deal->id, DealStage::Contacted->value);

    $deal->refresh();
    expect($deal->stage)->toBe(DealStage::Contacted)
        ->and($deal->furthest_stage)->toBe(DealStage::Negotiation);
});

test('winning a deal records the commission and marks the listing sold', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'price' => 3200000]);
    $deal = dealFor($user, $workspace, ['listing_id' => $listing->id, 'value' => 3200000, 'stage' => DealStage::Negotiation, 'furthest_stage' => DealStage::Negotiation]);

    Livewire::actingAs($user)->test(Board::class)
        ->call('moveTo', $deal->id, DealStage::Won->value)
        ->assertSet('dialog', 'win')
        ->assertSet('commission', '80,000')
        ->set('finalPrice', '3,150,000')
        ->set('commission', '78,750')
        ->call('confirmWin')
        ->assertSet('dialog', null);

    $deal->refresh();
    expect($deal->stage)->toBe(DealStage::Won)
        ->and($deal->final_price)->toBe(3150000)
        ->and($deal->commission)->toBe(78750)
        ->and($deal->closed_at)->not->toBeNull()
        ->and($listing->fresh()->status)->toBe(ListingStatus::Sold);
});

test('losing a deal needs a reason', function () {
    [$user, $workspace] = member();
    $deal = dealFor($user, $workspace);

    Livewire::actingAs($user)->test(Board::class)
        ->call('moveTo', $deal->id, DealStage::Lost->value)
        ->call('confirmLoss')
        ->assertHasErrors('lostReason')
        ->set('lostReason', LostReason::Price->value)
        ->call('confirmLoss')
        ->assertHasNoErrors();

    expect($deal->fresh()->stage)->toBe(DealStage::Lost)
        ->and($deal->fresh()->lost_reason)->toBe(LostReason::Price);
});

test('agents cannot move deals of other agents', function () {
    [$agent, $workspace] = member(MemberRole::Agent);
    [$colleague] = member(MemberRole::Agent, $workspace);
    $deal = dealFor($colleague, $workspace);

    Livewire::actingAs($agent)->test(Board::class)
        ->call('moveTo', $deal->id, DealStage::Viewing->value)
        ->assertNotFound();
});

test('a new deal can be created for a client', function () {
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'price' => 1150000]);

    Livewire::actingAs($user)->test(Board::class)
        ->call('openNewDeal')
        ->set('clientId', $client->id)
        ->set('listingId', $listing->id)
        ->assertSet('value', '1,150,000')
        ->call('createDeal')
        ->assertHasNoErrors();

    expect(Deal::sole()->value)->toBe(1150000);
});
