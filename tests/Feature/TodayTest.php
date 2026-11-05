<?php

use App\Enums\MemberRole;
use App\Livewire\Today;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Reminder;
use Livewire\Livewire;

test('the today page loads for a member', function () {
    [$user] = member();

    $this->actingAs($user)->get(route('today'))->assertOk()->assertSee('اليوم');
});

test('a workspace with nothing yet shows the empty state', function () {
    [$user] = member();

    Livewire::actingAs($user)->withoutLazyLoading()->test(Today::class)
        ->assertSee('لا شيء لليوم');
});

test('reminders due today can be completed', function () {
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);
    $reminder = Reminder::factory()->for($workspace)->for($client)->create(['user_id' => $user->id, 'due_at' => now()->addHour()]);

    Livewire::actingAs($user)->withoutLazyLoading()->test(Today::class)
        ->assertSee($reminder->title)
        ->call('toggleReminder', $reminder->id)
        ->assertDispatched('toast');

    expect($reminder->fresh()->completed_at)->not->toBeNull();
});

test('agents cannot complete reminders of other agents', function () {
    [$agent, $workspace] = member(MemberRole::Agent);
    [$colleague] = member(MemberRole::Agent, $workspace);
    $client = Client::factory()->for($workspace)->create(['agent_id' => $colleague->id]);
    $reminder = Reminder::factory()->for($workspace)->for($client)->create(['user_id' => $colleague->id]);

    Livewire::actingAs($agent)->withoutLazyLoading()->test(Today::class)
        ->call('toggleReminder', $reminder->id)
        ->assertNotFound();

    expect($reminder->fresh()->completed_at)->toBeNull();
});

test('replying to an inquiry records the response time', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id]);
    $inquiry = Inquiry::factory()->for($workspace)->create(['listing_id' => $listing->id, 'agent_id' => $user->id, 'created_at' => now()->subMinutes(20)]);

    Livewire::actingAs($user)->withoutLazyLoading()->test(Today::class)
        ->assertSee($inquiry->name)
        ->call('markResponded', $inquiry->id);

    expect($inquiry->fresh()->responded_at)->not->toBeNull();
});
