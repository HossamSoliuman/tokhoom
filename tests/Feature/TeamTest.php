<?php

use App\Enums\DealStage;
use App\Enums\MemberRole;
use App\Livewire\Invitations\Accept;
use App\Livewire\Team\Index;
use App\Models\Client;
use App\Models\Deal;
use App\Models\Invitation;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\InvitationSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('owners and managers can open the team page', function (MemberRole $role, int $status) {
    [$user] = member($role);

    $this->actingAs($user)->get(route('team'))->assertStatus($status);
})->with([
    'owner' => [MemberRole::Owner, 200],
    'manager' => [MemberRole::Manager, 200],
    'agent' => [MemberRole::Agent, 403],
]);

test('independent agents have no team page', function () {
    $workspace = Workspace::factory()->independentAgent()->create();
    [$user] = member(MemberRole::Owner, $workspace);

    $this->actingAs($user)->get(route('team'))->assertForbidden();
});

test('an email invitation is sent', function () {
    Notification::fake();
    [$owner] = member();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('openInvite')
        ->set('channel', 'email')
        ->set('contact', 'Omar@Example.com')
        ->set('role', 'manager')
        ->call('sendInvite')
        ->assertHasNoErrors();

    $invitation = Invitation::sole();
    expect($invitation->contact)->toBe('omar@example.com')
        ->and($invitation->role)->toBe(MemberRole::Manager);

    Notification::assertSentTo(new AnonymousNotifiable, InvitationSent::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'omar@example.com');
});

test('invitations need a free seat', function () {
    $workspace = Workspace::factory()->create(['seats' => 1, 'plan' => 'agent']);
    [$owner] = member(MemberRole::Owner, $workspace);

    Livewire::actingAs($owner)->test(Index::class)
        ->call('openInvite')
        ->set('contact', '0598765432')
        ->call('sendInvite')
        ->assertHasErrors('contact');

    expect(Invitation::count())->toBe(0);
});

test('deactivating a member hands their work to a colleague', function () {
    [$owner, $workspace] = member();
    [$leaving] = member(MemberRole::Agent, $workspace);
    [$colleague] = member(MemberRole::Agent, $workspace);
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $leaving->id]);
    $client = Client::factory()->for($workspace)->create(['agent_id' => $leaving->id]);
    $openDeal = Deal::factory()->for($workspace)->create(['client_id' => $client->id, 'agent_id' => $leaving->id]);
    $wonDeal = Deal::factory()->for($workspace)->inStage(DealStage::Won)->create(['client_id' => $client->id, 'agent_id' => $leaving->id]);

    $leavingMembership = Membership::where('user_id', $leaving->id)->first();
    $colleagueMembership = Membership::where('user_id', $colleague->id)->first();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('openDeactivate', $leavingMembership->id)
        ->set('recipientId', $colleagueMembership->id)
        ->call('deactivate')
        ->assertHasNoErrors();

    expect($leavingMembership->fresh()->deactivated_at)->not->toBeNull()
        ->and($listing->fresh()->agent_id)->toBe($colleague->id)
        ->and($client->fresh()->agent_id)->toBe($colleague->id)
        ->and($openDeal->fresh()->agent_id)->toBe($colleague->id)
        ->and($wonDeal->fresh()->agent_id)->toBe($leaving->id);

    $this->actingAs($leaving->fresh())->get(route('today'))->assertRedirect(route('workspaces.create'));
});

test('the owner cannot be deactivated', function () {
    [$owner, $workspace] = member();
    [$manager] = member(MemberRole::Manager, $workspace);

    Livewire::actingAs($manager)->test(Index::class)
        ->call('openDeactivate', Membership::where('user_id', $owner->id)->value('id'))
        ->assertNotFound();
});

test('a new person can accept an invitation and join the office', function () {
    $workspace = Workspace::factory()->create();
    $invitation = Invitation::factory()->for($workspace)->create(['contact' => 'omar@example.com']);

    Livewire::test(Accept::class, ['token' => $invitation->token])
        ->assertSet('email', 'omar@example.com')
        ->set('name', 'عمر السالم')
        ->set('mobile', '0598765432')
        ->set('password', 'secret-pass')
        ->call('accept')
        ->assertRedirect(route('today'));

    $user = User::where('email', 'omar@example.com')->sole();
    expect($user->current_workspace_id)->toBe($workspace->id)
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});
