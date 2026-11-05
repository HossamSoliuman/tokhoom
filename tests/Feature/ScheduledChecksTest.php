<?php

use App\Enums\MemberRole;
use App\Models\Client;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use App\Notifications\AdLicenseExpired;
use App\Notifications\FalLicenseExpiring;
use App\Notifications\ReminderDue;
use Illuminate\Support\Facades\Notification;

test('due reminders notify their agent once', function () {
    Notification::fake();
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);
    $due = Reminder::factory()->for($workspace)->for($client)->create(['user_id' => $user->id, 'due_at' => now()->addMinutes(5)]);
    Reminder::factory()->for($workspace)->for($client)->create(['user_id' => $user->id, 'due_at' => now()->addDay()]);

    $this->artisan('tokhoom:send-due-reminders')->assertSuccessful();
    $this->artisan('tokhoom:send-due-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, ReminderDue::class, 1);
    expect($due->fresh()->notified_at)->not->toBeNull();
});

test('expired ad licenses are recorded and reported once', function () {
    Notification::fake();
    [$owner, $workspace] = member();
    [$agent] = member(MemberRole::Agent, $workspace);
    $listing = Listing::factory()->for($workspace)->adLicenseExpired()->create(['agent_id' => $agent->id]);

    $this->artisan('tokhoom:check-licenses')->assertSuccessful();
    $this->artisan('tokhoom:check-licenses')->assertSuccessful();

    expect($listing->isLive())->toBeFalse()
        ->and($listing->activities()->where('type', 'listing.ad_license_expired')->count())->toBe(1);
    Notification::assertSentToTimes($agent, AdLicenseExpired::class, 1);
    Notification::assertSentToTimes($owner, AdLicenseExpired::class, 1);
});

test('FAL licenses expiring in thirty days are flagged to the owner', function () {
    Notification::fake();
    [$owner, $workspace] = member();
    [$agent] = member(MemberRole::Agent, $workspace);
    Membership::where('user_id', $agent->id)->update(['fal_expires_on' => today()->addDays(30)]);

    $this->artisan('tokhoom:check-licenses')->assertSuccessful();

    Notification::assertSentTo($owner, FalLicenseExpiring::class);
    Notification::assertSentTo($agent, FalLicenseExpiring::class);
});
