<?php

use App\Enums\ListingStatus;
use App\Enums\MemberRole;
use App\Livewire\Listings\Editor;
use App\Livewire\Listings\Index;
use App\Livewire\Listings\ShareDialog;
use App\Livewire\Listings\Show;
use App\Models\Client;
use App\Models\ClientRequest;
use App\Models\Listing;
use App\Notifications\ListingMatchesRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the listings page shows the workspace listings', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id]);

    $this->actingAs($user)->get(route('listings.index'))
        ->assertOk()
        ->assertSee($listing->title);
});

test('agents only see their own listings', function () {
    [$agent, $workspace] = member(MemberRole::Agent);
    [$colleague] = member(MemberRole::Agent, $workspace);
    $own = Listing::factory()->for($workspace)->create(['agent_id' => $agent->id, 'district' => 'الملقا']);
    $other = Listing::factory()->for($workspace)->create(['agent_id' => $colleague->id, 'district' => 'حطين']);

    Livewire::actingAs($agent)->test(Index::class)
        ->assertSee($own->title)
        ->assertDontSee($other->title);

    $this->actingAs($agent)->get(route('listings.show', $other))->assertForbidden();
});

test('listings from another workspace are not reachable', function () {
    [$user] = member();
    $foreign = Listing::factory()->create();

    $this->actingAs($user)->get(route('listings.show', $foreign))->assertForbidden();
    $this->actingAs($user)->get(route('listings.edit', $foreign))->assertForbidden();
});

test('the status can be changed from the card', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id]);

    Livewire::actingAs($user)->test(Index::class)
        ->call('setStatus', $listing->id, ListingStatus::Sold->value)
        ->assertDispatched('toast');

    expect($listing->fresh()->status)->toBe(ListingStatus::Sold);
});

test('a listing without an ad license cannot go live', function () {
    [$user, $workspace] = member();
    $draft = Listing::factory()->for($workspace)->draft()->create(['agent_id' => $user->id]);

    Livewire::actingAs($user)->test(Index::class)
        ->call('setStatus', $draft->id, ListingStatus::Available->value);

    expect($draft->fresh()->status)->toBe(ListingStatus::Draft);
});

test('status filters narrow the list', function () {
    [$user, $workspace] = member();
    $available = Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'district' => 'الملقا']);
    $sold = Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'district' => 'حطين', 'status' => ListingStatus::Sold]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('status', 'sold')
        ->assertSee($sold->title)
        ->assertDontSee($available->title);
});

test('publishing requires the ad license number', function () {
    [$user] = member();

    Livewire::actingAs($user)->test(Editor::class)
        ->set('price', '3,200,000')
        ->set('district', 'الملقا')
        ->call('publish')
        ->assertHasErrors(['adLicenseNumber' => 'required']);

    expect(Listing::count())->toBe(0);
});

test('tour and video links must be web addresses', function (string $link) {
    [$user] = member();

    Livewire::actingAs($user)->test(Editor::class)
        ->set('district', 'النرجس')
        ->set('tourUrl', $link)
        ->set('videoUrl', $link)
        ->call('saveDraft')
        ->assertHasErrors(['tourUrl' => 'url', 'videoUrl' => 'url']);

    expect(Listing::count())->toBe(0);
})->with([
    'data link' => 'data://text/html;base64,PHNjcmlwdD4=',
    'file link' => 'file://server/share/tour.html',
]);

test('a listing can be saved as a draft without a license', function () {
    [$user] = member();

    Livewire::actingAs($user)->test(Editor::class)
        ->set('purpose', 'rent')
        ->set('type', 'apartment')
        ->set('district', 'النرجس')
        ->call('saveDraft')
        ->assertRedirect(route('listings.index'));

    $listing = Listing::sole();
    expect($listing->status)->toBe(ListingStatus::Draft)
        ->and($listing->title)->toBe('شقة للإيجار — حي النرجس');
});

test('publishing stores photos in order and notifies agents of matching clients', function () {
    Storage::fake('public');
    Notification::fake();
    [$user, $workspace] = member();
    $client = Client::factory()->for($workspace)->create(['agent_id' => $user->id]);
    ClientRequest::factory()->for($client)->create(['purpose' => 'sale', 'types' => ['villa'], 'districts' => ['الملقا'], 'budget_min' => 3000000, 'budget_max' => 3500000]);

    Livewire::actingAs($user)->test(Editor::class)
        ->set('uploads', [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('majlis.jpg')])
        ->set('price', '3,200,000')
        ->set('area', '450')
        ->set('district', 'الملقا')
        ->set('adLicenseNumber', '7200098765')
        ->call('publish')
        ->assertHasNoErrors();

    $listing = Listing::sole();
    expect($listing->status)->toBe(ListingStatus::Available)
        ->and($listing->price)->toBe(3200000)
        ->and($listing->published_at)->not->toBeNull()
        ->and($listing->photos)->toHaveCount(2);

    Storage::disk('public')->assertExists($listing->photos->first()->path);
    Notification::assertSentTo($user, ListingMatchesRequest::class);
});

test('changing the price of a published listing is recorded', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id, 'price' => 1000000, 'district' => 'الملقا']);

    Livewire::actingAs($user)->test(Editor::class, ['listing' => $listing])
        ->set('price', '950,000')
        ->call('publish')
        ->assertHasNoErrors();

    expect($listing->fresh()->price)->toBe(950000)
        ->and($listing->activities()->where('type', 'listing.price_changed')->exists())->toBeTrue();
});

test('the detail page shows performance and quality', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id]);

    Livewire::actingAs($user)->test(Show::class, ['listing' => $listing])
        ->assertSee('جودة الإعلان')
        ->assertSee('مصادر الزيارات')
        ->set('metric', 'inquiries')
        ->assertOk();
});

test('the share dialog opens for a visible listing and the QR downloads', function () {
    [$user, $workspace] = member();
    $listing = Listing::factory()->for($workspace)->create(['agent_id' => $user->id]);

    Livewire::actingAs($user)->test(ShareDialog::class)
        ->dispatch('open-share', listingId: $listing->id)
        ->assertSee($listing->shortLink());

    $this->actingAs($user)->get(route('listings.qr', [$listing, 'svg']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('seeded sample photos resolve to a bundled demo image that exists', function () {
    $listing = Listing::factory()->create();

    foreach (['villa facade', 'land plot', 'master bedroom', 'street view', 'commercial frontage'] as $position => $caption) {
        $url = $listing->photos()->create(['path' => 'sample:'.$caption, 'position' => $position])->url();

        expect($url)->toContain('/images/samples/')
            ->and(public_path(parse_url($url, PHP_URL_PATH)))->toBeFile();
    }

    expect($listing->photos()->create(['path' => 'sample:unknown', 'position' => 9])->url())->toBeNull();
});
