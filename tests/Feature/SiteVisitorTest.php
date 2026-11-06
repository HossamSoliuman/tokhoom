<?php

use App\Enums\ClientType;
use App\Enums\LeadSource;
use App\Enums\ListingStatus;
use App\Enums\SiteEventType;
use App\Livewire\Site\InquiryForm;
use App\Livewire\Site\ListingBrowser;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\SiteEvent;
use App\Models\Workspace;
use App\Notifications\NewInquiry;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Fill in the inquiry form with a valid message and send it.
 */
function sendInquiry(Listing $listing, string $mobile = '0551234567'): void
{
    Livewire::test(InquiryForm::class, ['listing' => $listing])
        ->set('name', 'سارة القحطاني')
        ->set('mobile', $mobile)
        ->set('message', 'متى يمكن المعاينة؟')
        ->set('consent', true)
        ->call('send');
}

test('an inquiry files the visitor as a client of the listing agent and tells the agent', function () {
    Notification::fake();
    $listing = Listing::factory()->forRent()->create();

    sendInquiry($listing, '055 123 4567');

    $client = Client::sole();
    expect($client)
        ->workspace_id->toBe($listing->workspace_id)
        ->agent_id->toBe($listing->agent_id)
        ->mobile->toBe('0551234567')
        ->type->toBe(ClientType::Tenant)
        ->source->toBe(LeadSource::Site);
    expect(Inquiry::sole())
        ->client_id->toBe($client->id)
        ->listing_id->toBe($listing->id)
        ->message->toBe('متى يمكن المعاينة؟');
    Notification::assertSentTo($listing->agent, NewInquiry::class);
});

test('an inquiry from a known mobile is filed under the existing client', function () {
    Notification::fake();
    $listing = Listing::factory()->create();
    $client = Client::factory()->for($listing->workspace)->create(['mobile' => '0551234567']);

    sendInquiry($listing);

    expect(Client::count())->toBe(1)
        ->and(Inquiry::sole()->client_id)->toBe($client->id);
});

test('an inquiry keeps the source the visitor arrived from', function () {
    Notification::fake();
    $listing = Listing::factory()->create();

    $this->get(siteUrl($listing->workspace, '/listings/'.$listing->short_code.'?src=qr'))->assertOk();
    sendInquiry($listing);

    expect(Inquiry::sole()->source)->toBe(LeadSource::Qr);
});

test('an inquiry needs a name, a Saudi mobile and consent', function () {
    $listing = Listing::factory()->create();

    Livewire::test(InquiryForm::class, ['listing' => $listing])
        ->set('mobile', '0551234')
        ->call('send')
        ->assertHasErrors(['name' => 'required', 'mobile' => 'regex', 'consent' => 'accepted'])
        ->assertSee(['أدخل الاسم', 'أدخل رقم جوال صحيح يبدأ بـ 05', 'يلزم الموافقة لإرسال الاستفسار']);

    expect(Inquiry::count())->toBe(0);
});

test('a visitor can send five inquiries an hour', function () {
    Notification::fake();
    $listing = Listing::factory()->create();

    foreach (range(1, 6) as $attempt) {
        sendInquiry($listing);
    }

    expect(Inquiry::count())->toBe(5);
});

test('contact clicks are counted against the listing', function (SiteEventType $type) {
    $listing = Listing::factory()->create();

    $this->post(siteUrl($listing->workspace, '/events'), ['type' => $type->value, 'listing' => $listing->short_code])
        ->assertNoContent();

    expect(SiteEvent::sole())
        ->type->toBe($type)
        ->listing_id->toBe($listing->id)
        ->source->toBe(LeadSource::Site);
})->with([
    'whatsapp' => SiteEventType::WhatsappClick,
    'call' => SiteEventType::CallClick,
    'share' => SiteEventType::Share,
]);

test('visits and views cannot be posted as click events', function () {
    $workspace = Workspace::factory()->create();

    $this->postJson(siteUrl($workspace, '/events'), ['type' => SiteEventType::SiteVisit->value])
        ->assertJsonValidationErrors('type');

    expect(SiteEvent::count())->toBe(0);
});

test('a click on another office listing is counted without the listing', function () {
    $workspace = Workspace::factory()->create();
    $otherListing = Listing::factory()->create();

    $this->post(siteUrl($workspace, '/events'), ['type' => 'call_click', 'listing' => $otherListing->short_code])
        ->assertNoContent();

    expect(SiteEvent::sole())
        ->workspace_id->toBe($workspace->id)
        ->listing_id->toBeNull();
});

test('a visit and a listing view are counted once per session', function () {
    $listing = Listing::factory()->create();
    $url = siteUrl($listing->workspace, '/listings/'.$listing->short_code);

    $this->get(siteUrl($listing->workspace))->assertOk();
    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    expect(SiteEvent::where('type', SiteEventType::SiteVisit)->count())->toBe(1)
        ->and(SiteEvent::where('type', SiteEventType::ListingView)->count())->toBe(1);
});

test('a view from a short link is not counted again on the listing page', function () {
    $listing = Listing::factory()->create();

    $this->get(siteUrl($listing->workspace, '/listings/'.$listing->short_code.'?src=shared_link'))->assertOk();

    expect(SiteEvent::where('type', SiteEventType::ListingView)->count())->toBe(0)
        ->and(SiteEvent::sole()->source)->toBe(LeadSource::SharedLink);
});

test('a sold listing shows other live listings with a gone status', function () {
    $sold = Listing::factory()->create(['status' => ListingStatus::Sold, 'district' => 'الملقا']);
    $other = Listing::factory()->for($sold->workspace)->create(['district' => 'حطين']);

    $this->get(siteUrl($sold->workspace, '/listings/'.$sold->short_code))
        ->assertStatus(410)
        ->assertSee('هذا العقار لم يعد متاحاً')
        ->assertSee($other->shortTitle());
});

test('a draft listing is not found on the site', function () {
    $draft = Listing::factory()->draft()->create();

    $this->get(siteUrl($draft->workspace, '/listings/'.$draft->short_code))->assertNotFound();
});

test('an agent page shows only that agent listings', function () {
    $agent = Membership::factory()->create();
    $colleague = Membership::factory()->for($agent->workspace)->create();
    $own = Listing::factory()->for($agent->workspace)->for($agent->user, 'agent')->create(['district' => 'الملقا']);
    $colleagues = Listing::factory()->for($agent->workspace)->for($colleague->user, 'agent')->create(['district' => 'حطين']);

    $this->get(siteUrl($agent->workspace, '/agents/'.$agent->user_id))
        ->assertOk()
        ->assertSee($agent->user->name)
        ->assertSee($own->shortTitle())
        ->assertDontSee($colleagues->shortTitle());
});

test('agent pages are not found for former members or independent agents', function (Closure $makeMembership) {
    $membership = $makeMembership();

    $this->get(siteUrl($membership->workspace, '/agents/'.$membership->user_id))->assertNotFound();
})->with([
    'deactivated member' => fn () => Membership::factory()->deactivated()->create(),
    'independent agent' => fn () => Membership::factory()->owner()->for(Workspace::factory()->independentAgent())->create(),
]);

test('the listing filters narrow the grid', function (string $filter, string $value, array $matching, array $other) {
    $workspace = Workspace::factory()->create();
    $match = Listing::factory()->for($workspace)->create($matching);
    Listing::factory()->for($workspace)->create($other);

    Livewire::test(ListingBrowser::class, ['workspace' => $workspace])
        ->set($filter, $value)
        ->assertViewHas('listings', fn ($listings) => $listings->modelKeys() === [$match->id]);
})->with([
    'purpose' => ['purpose', 'rent', ['purpose' => 'rent'], ['purpose' => 'sale']],
    'type' => ['type', 'villa', ['type' => 'villa'], ['type' => 'apartment']],
    'district' => ['district', 'الملقا', ['district' => 'الملقا'], ['district' => 'حطين']],
    'bedrooms' => ['beds', '4', ['bedrooms' => 5], ['bedrooms' => 3]],
    'lowest price' => ['min', '1,000,000', ['price' => 1_500_000], ['price' => 900_000]],
    'highest price' => ['max', '1000000', ['price' => 900_000], ['price' => 1_500_000]],
]);

test('the listings grid shows only live listings of its site', function () {
    $workspace = Workspace::factory()->create();
    $live = Listing::factory()->for($workspace)->create(['status' => ListingStatus::Reserved]);
    Listing::factory()->for($workspace)->create(['status' => ListingStatus::Rented]);
    Listing::factory()->create();

    Livewire::test(ListingBrowser::class, ['workspace' => $workspace])
        ->assertViewHas('listings', fn ($listings) => $listings->modelKeys() === [$live->id]);
});

test('social accounts link to their profiles whether saved as handles or pasted links', function () {
    $workspace = Workspace::factory()->create([
        'x_handle' => '@alrashid_re',
        'instagram' => 'https://www.instagram.com/alrashid/?igsh=abc123',
        'tiktok' => 'https://www.tiktok.com/@alrashid.sa',
    ]);

    $this->get(siteUrl($workspace))
        ->assertOk()
        ->assertSee('href="https://x.com/alrashid_re"', false)
        ->assertSee('href="https://instagram.com/alrashid"', false)
        ->assertSee('href="https://tiktok.com/@alrashid.sa"', false);
});
