<?php

use App\Enums\MemberRole;
use App\Livewire\Clients\Index;
use App\Livewire\Clients\Show;
use App\Models\Client;
use App\Models\ClientRequest;
use App\Models\Listing;
use App\Models\Note;
use Livewire\Livewire;

test('the clients page lists clients and filters by type', function () {
    [$user, $workspace] = member();
    $buyer = Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'منيرة السبيعي', 'type' => 'buyer']);
    $tenant = Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'عبدالله الدوسري', 'type' => 'tenant']);

    $this->actingAs($user)->get(route('clients.index'))->assertOk()->assertSee($buyer->name);

    Livewire::actingAs($user)->test(Index::class)
        ->set('type', 'tenant')
        ->assertSee($tenant->name)
        ->assertDontSee($buyer->name);
});

test('clients can be found by mobile number', function () {
    [$user, $workspace] = member();
    Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'عبدالله الدوسري', 'mobile' => '0551234567']);
    Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'ريم العنزي', 'mobile' => '0567024418']);

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', '055 123')
        ->assertSee('عبدالله الدوسري')
        ->assertDontSee('ريم العنزي');
});

test('adding a client opens their file', function () {
    [$user] = member();

    $component = Livewire::actingAs($user)->test(Index::class)
        ->call('openAddClient')
        ->set('name', 'سلطان القرني')
        ->set('mobile', '+966 55 993 4410')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::sole();
    $component->assertRedirect(route('clients.show', $client));
    expect($client->mobile)->toBe('0559934410')
        ->and($client->agent_id)->toBe($user->id);
});

test('a duplicate mobile number is flagged and not saved', function () {
    [$user, $workspace] = member();
    Client::factory()->for($workspace)->create(['agent_id' => $user->id, 'name' => 'عبدالله الدوسري', 'mobile' => '0551234567']);

    Livewire::actingAs($user)->test(Index::class)
        ->call('openAddClient')
        ->set('name', 'عميل جديد')
        ->set('mobile', '0551234567')
        ->assertSee('هذا الرقم مسجّل للعميل عبدالله الدوسري')
        ->call('save');

    expect(Client::count())->toBe(1);
});

test('agents cannot open clients of other agents', function () {
    [$agent, $workspace] = member(MemberRole::Agent);
    [$colleague] = member(MemberRole::Agent, $workspace);
    $client = Client::factory()->for($workspace)->create(['agent_id' => $colleague->id]);

    $this->actingAs($agent)->get(route('clients.show', $client))->assertForbidden();
});

test('a request shows matching available listings', function () {
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);
    $match = Listing::factory()->for($workspace)->forRent()->create(['agent_id' => $user->id, 'type' => 'apartment', 'district' => 'الياسمين', 'price' => 58000]);
    $tooExpensive = Listing::factory()->for($workspace)->forRent()->create(['agent_id' => $user->id, 'type' => 'apartment', 'district' => 'النرجس', 'price' => 90000]);

    Livewire::actingAs($user)->test(Show::class, ['client' => $client])
        ->call('openRequest')
        ->set('requestPurpose', 'rent')
        ->set('requestDistricts', ['النرجس', 'الياسمين'])
        ->set('budgetMin', '50,000')
        ->set('budgetMax', '70,000')
        ->call('saveRequest')
        ->assertHasNoErrors()
        ->assertSee($match->title)
        ->assertDontSee($tooExpensive->title);

    $request = ClientRequest::sole();
    expect($request->types)->toBe(['apartment'])
        ->and($request->budget_max)->toBe(70000);
});

test('private notes are only visible to their author', function () {
    [$owner, $workspace] = member();
    [$manager] = member(MemberRole::Manager, $workspace);
    $client = Client::factory()->for($workspace)->create(['agent_id' => $owner->id]);

    Livewire::actingAs($owner)->test(Show::class, ['client' => $client])
        ->set('noteBody', 'العميل حساس للسعر')
        ->call('addNote')
        ->assertSee('العميل حساس للسعر');

    expect(Note::sole()->is_shared)->toBeFalse();

    Livewire::actingAs($manager)->test(Show::class, ['client' => $client])
        ->assertDontSee('العميل حساس للسعر');
});

test('reminders can be added to a client', function () {
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);

    Livewire::actingAs($user)->test(Show::class, ['client' => $client])
        ->call('openReminder')
        ->set('reminderTitle', 'إرسال صور شقة الياسمين')
        ->call('saveReminder')
        ->assertHasNoErrors()
        ->assertSee('إرسال صور شقة الياسمين');

    expect($client->reminders()->sole()->user_id)->toBe($user->id);
});
