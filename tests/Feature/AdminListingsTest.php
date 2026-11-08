<?php

use App\Enums\ListingStatus;
use App\Enums\MemberRole;
use App\Livewire\Admin\Listings;
use App\Livewire\Listings\Index as ListingsIndex;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingRemoved;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->platformAdmin()->create();
});

test('admins find a listing by its ad license number or a pasted link', function () {
    $listing = Listing::factory()->create(['ad_license_number' => '7200012345']);
    $other = Listing::factory()->create(['ad_license_number' => '7200099999']);

    $this->actingAs($this->admin)->get(route('admin.listings'))->assertOk();

    Livewire::actingAs($this->admin)->test(Listings::class)
        ->set('search', '7200012345')
        ->assertSee($listing->workspace->name)
        ->assertDontSee($other->workspace->name)
        ->set('search', $other->shortUrl())
        ->assertSee($other->workspace->name)
        ->assertDontSee($listing->workspace->name);
});

test('taking down a listing needs a reason, hides it and tells the office', function () {
    Notification::fake();
    [$owner, $workspace] = member();
    [$agent] = member(MemberRole::Agent, $workspace);
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $agent->id]);

    Livewire::actingAs($this->admin)->test(Listings::class)
        ->call('openRemove', $listing->id)
        ->call('remove')
        ->assertHasErrors(['reason' => 'required'])
        ->set('reason', 'بلاغ عن ترخيص غير صحيح')
        ->call('remove')
        ->assertHasNoErrors();

    expect($listing->fresh())->isRemoved()->toBeTrue()->isLive()->toBeFalse()
        ->and(Listing::live()->whereKey($listing->id)->exists())->toBeFalse();
    Notification::assertSentTo([$owner, $agent], ListingRemoved::class);

    $this->get(siteUrl($workspace, '/listings/'.$listing->short_code))->assertStatus(410);
});

test('the office cannot put a taken down listing back on its site', function () {
    [$owner, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $owner->id, 'status' => ListingStatus::Hidden]);
    $listing->forceFill(['removed_at' => now(), 'removal_reason' => 'بلاغ'])->save();

    Livewire::actingAs($owner)->test(ListingsIndex::class)
        ->call('setStatus', $listing->id, ListingStatus::Available->value)
        ->assertDispatched('toast', message: 'أوقفت إدارة المنصة عرض هذا العقار. تواصل مع الدعم لإعادته.', kind: 'err');

    expect($listing->fresh()->status)->toBe(ListingStatus::Hidden);

    $this->actingAs($owner)->get(route('listings.show', $listing))->assertSee('أوقفت إدارة المنصة عرض هذا العقار على موقعك: بلاغ');
});

test('a taken down listing can be restored', function () {
    $listing = Listing::factory()->create();
    $listing->forceFill(['removed_at' => now(), 'removal_reason' => 'بلاغ'])->save();

    Livewire::actingAs($this->admin)->test(Listings::class)
        ->call('restore', $listing->id);

    expect($listing->fresh())->isRemoved()->toBeFalse()->isLive()->toBeTrue();
});
