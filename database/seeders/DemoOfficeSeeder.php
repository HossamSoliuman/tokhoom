<?php

namespace Database\Seeders;

use App\Enums\ClientType;
use App\Enums\DealStage;
use App\Enums\DomainStatus;
use App\Enums\InvitationChannel;
use App\Enums\LeadSource;
use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\LostReason;
use App\Enums\MemberRole;
use App\Enums\Plan;
use App\Enums\ReminderChannel;
use App\Enums\SiteEventType;
use App\Enums\WorkspaceKind;
use App\Models\Activity;
use App\Models\Client;
use App\Models\CommissionTarget;
use App\Models\Deal;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Membership;
use App\Models\Reminder;
use App\Models\SiteEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\FalLicenseExpiring;
use App\Notifications\ListingMatchesRequest;
use App\Notifications\NewInquiry;
use App\Notifications\PaymentFailed;
use App\Notifications\ReminderDue;
use App\Support\Arabic;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The sample office from the design: مكتب الراشد العقاري with its team,
 * listings, clients, deals and thirty days of site analytics.
 */
class DemoOfficeSeeder extends Seeder
{
    private Workspace $office;

    /**
     * @var array<string, User>
     */
    private array $people = [];

    /**
     * @var array<string, Listing>
     */
    private array $listings = [];

    /**
     * @var array<string, Client>
     */
    private array $clients = [];

    public function run(): void
    {
        if (User::where('email', 'k.alrashid@alrashid.sa')->exists()) {
            $this->command?->warn('Demo office already seeded, skipping.');

            return;
        }

        mt_srand(7);

        DB::transaction(function (): void {
            $this->seedTeam();
            $this->seedListings();
            $this->seedClients();
            $this->seedInquiries();
            $this->seedDeals();
            $this->seedReminders();
            $this->seedSiteEvents();
            $this->seedBilling();
            $this->seedJeddahBranch();
            $this->seedNotifications();
        });
    }

    private function seedTeam(): void
    {
        $person = fn (string $key, string $name, string $email, string $mobile, string $gender) => $this->people[$key] = User::create([
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'gender' => $gender,
            'password' => 'password',
        ]);

        $person('khaled', 'خالد الراشد', 'k.alrashid@alrashid.sa', '0501234567', 'male');
        $person('sara', 'سارة القحطاني', 'sara@alrashid.sa', '0502213345', 'female');
        $person('fahad', 'فهد العتيبي', 'fahad@alrashid.sa', '0547781209', 'male');
        $person('noura', 'نورة الشهري', 'noura@alrashid.sa', '0556104471', 'female');

        $this->office = new Workspace([
            'kind' => WorkspaceKind::Office,
            'name' => 'مكتب الراشد العقاري',
            'slug' => 'alrashid',
            'city' => 'الرياض',
            'cr_number' => '1010567890',
            'fal_number' => '1200045678',
            'license_expires_on' => now()->addMonths(14)->toDateString(),
            'brand_color' => '#145A55',
            'about' => 'مكتب عقاري في الرياض منذ 2009، متخصص في بيع وتأجير الفلل والشقق والأراضي السكنية في أحياء شمال الرياض.',
            'whatsapp' => '0501234567',
            'phone' => '0114567890',
            'x_handle' => '@alrashid_re',
            'instagram' => '@alrashid.re',
            'snapchat' => 'alrashid.re',
            'tiktok' => '@alrashid.re',
            'plan' => Plan::Office,
            'seats' => 5,
            'trial_ends_at' => now()->addDays(9)->setTime(23, 0),
            'custom_domain' => 'www.alrashid.sa',
        ]);
        $this->office->forceFill([
            'approval_status' => 'approved',
            'approved_at' => now()->subMonths(4),
            'domain_status' => DomainStatus::Pending,
            'card_brand' => 'مدى',
            'card_last_four' => '4821',
            'card_expiry' => '08/2028',
            'created_at' => now()->subMonths(4),
        ])->save();

        $member = fn (string $key, MemberRole $role, string $fal, int $expiresInDays) => Membership::create([
            'workspace_id' => $this->office->id,
            'user_id' => $this->people[$key]->id,
            'role' => $role,
            'fal_number' => $fal,
            'fal_expires_on' => now()->addDays($expiresInDays)->toDateString(),
        ]);

        $member('khaled', MemberRole::Owner, '1100098761', 186);
        $member('sara', MemberRole::Agent, '1100012345', 39);
        $member('fahad', MemberRole::Agent, '1100023456', 271);
        $member('noura', MemberRole::Agent, '1100034567', 136);

        foreach ($this->people as $user) {
            $user->forceFill(['current_workspace_id' => $this->office->id])->save();
        }

        $this->office->invitations()->create([
            'invited_by' => $this->people['khaled']->id,
            'name' => 'عمر السالم',
            'channel' => InvitationChannel::Mobile,
            'contact' => '0598765432',
            'role' => MemberRole::Agent,
            'sent_at' => now()->subDays(2),
        ]);

        foreach (['sara' => [100000, 3], 'fahad' => [40000, 3], 'noura' => [25000, 2]] as $key => [$commission, $deals]) {
            CommissionTarget::create([
                'workspace_id' => $this->office->id,
                'user_id' => $this->people[$key]->id,
                'month' => now()->startOfMonth()->toDateString(),
                'commission_target' => $commission,
                'deals_target' => $deals,
            ]);
        }
    }

    private function seedListings(): void
    {
        $villaDescription = 'فيلا حديثة البناء في حي الملقا على شارع 20 متر، تتكون من دورين وملحق علوي. الدور الأرضي: مجلس رجال بمدخل مستقل، صالة طعام، مطبخ راكب، غرفة خادمة. الدور الأول: صالة عائلية وأربع غرف نوم منها غرفتان ماستر. تشطيب فاخر، تكييف مركزي، مسبح خارجي وحديقة، وموقف سيارتين. قريبة من طريق أنس بن مالك والخدمات والمدارس الأهلية، والصك جاهز للإفراغ.';

        $rows = [
            'villa' => ['sara', ListingPurpose::Sale, ListingType::Villa, ListingStatus::Available, 3200000, 450, 5, 6, 2, 2, 'الملقا', '7200098765', 180, 8, 6, ['مسبح', 'ملحق علوي', 'مجلس رجال', 'تكييف مركزي', 'موقف خاص'], $villaDescription, true, [24.8105, 46.6107]],
            'narjis' => ['fahad', ListingPurpose::Rent, ListingType::Apartment, ListingStatus::Reserved, 65000, 160, 3, 2, 5, 1, 'النرجس', '7200098766', 150, 21, 6, ['مصعد', 'موقف خاص', 'مطبخ راكب'], 'شقة في الدور الثاني بعمارة حديثة، ثلاث غرف وصالة ومطبخ راكب، مصعد وموقف خاص، قريبة من طريق الملك سلمان.', true, [24.8452, 46.6535]],
            'hittin' => ['noura', ListingPurpose::Sale, ListingType::Land, ListingStatus::Available, 2100000, 600, null, null, null, null, 'حطين', '7200098780', -3, 64, 4, [], 'أرض سكنية على شارعين في حي حطين، مستوية وجاهزة للبناء، قريبة من طريق الأمير تركي بن عبدالعزيز الأول.', false, [24.7598, 46.5961]],
            'yasmin' => ['sara', ListingPurpose::Rent, ListingType::Floor, ListingStatus::Available, 48000, 220, 4, 3, 7, 1, 'الياسمين', '7200098771', 200, 16, 5, ['مدخل مستقل', 'موقف خاص'], 'دور أرضي بمدخل مستقل، أربع غرف ومجلس وصالة، مناسب للعائلات، قريب من المدارس والحدائق.', true, [24.8236, 46.6421]],
            'arid' => ['sara', ListingPurpose::Sale, ListingType::Apartment, ListingStatus::Draft, 1150000, 180, 4, 3, 1, 1, 'العارض', null, null, null, 4, ['مصعد'], '', false, [24.8665, 46.6129]],
            'qairawan' => ['fahad', ListingPurpose::Rent, ListingType::Villa, ListingStatus::Rented, 180000, 400, 6, 5, 4, 2, 'القيروان', '7200098750', 120, 35, 6, ['مسبح', 'حديقة', 'غرفة خادمة'], 'فيلا للإيجار السنوي بحديقة ومسبح، ست غرف وملحق، في شارع هادئ بحي القيروان.', true, [24.8376, 46.5793]],
            'yasmin-apartment' => ['noura', ListingPurpose::Rent, ListingType::Apartment, ListingStatus::Available, 58000, 150, 3, 2, 3, 1, 'الياسمين', '7200098772', 220, 6, 7, ['مصعد', 'تكييف مركزي'], 'شقة ثلاث غرف بتكييف مركزي ومصعد، قريبة من طريق أبي بكر الصديق.', false, [24.8261, 46.6512]],
            'sahafa' => ['sara', ListingPurpose::Sale, ListingType::Villa, ListingStatus::Sold, 2400000, 375, 5, 5, 6, 2, 'الصحافة', '7200098701', 60, 48, 9, ['مجلس رجال', 'ملحق علوي'], 'فيلا دوبلكس في الصحافة بحالة ممتازة.', true, [24.8058, 46.6383]],
            'rabie' => ['sara', ListingPurpose::Sale, ListingType::Apartment, ListingStatus::Sold, 1060000, 170, 3, 3, 2, 1, 'الربيع', '7200098702', 75, 40, 8, ['مصعد', 'موقف خاص'], 'شقة تمليك في الربيع، تشطيب حديث وموقع مميز.', false, [24.7957, 46.6578]],
        ];

        $captions = ['facade', 'majlis', 'family living', 'kitchen', 'master bedroom', 'bathroom', 'roof', 'garden', 'street view'];

        foreach ($rows as $key => [$agent, $purpose, $type, $status, $price, $area, $beds, $baths, $age, $floors, $district, $license, $licenseDays, $publishedDaysAgo, $photos, $features, $description, $tour, $location]) {
            $publishedAt = $publishedDaysAgo !== null ? now()->subDays($publishedDaysAgo)->setTime(10, 15) : null;

            $listing = new Listing([
                'workspace_id' => $this->office->id,
                'agent_id' => $this->people[$agent]->id,
                'purpose' => $purpose,
                'type' => $type,
                'status' => $status,
                'price' => $price,
                'area' => $area,
                'bedrooms' => $beds,
                'bathrooms' => $baths,
                'age' => $age,
                'floors' => $floors,
                'city' => 'الرياض',
                'district' => $district,
                'latitude' => $location[0],
                'longitude' => $location[1],
                'features' => $features,
                'description' => $description ?: null,
                'ad_license_number' => $license,
                'ad_license_expires_on' => $licenseDays !== null ? now()->addDays($licenseDays)->toDateString() : null,
                'brokerage_contract_number' => $license ? '44100'.mt_rand(10000, 99999) : null,
                'tour_url' => $tour ? 'https://tour.example.com/'.$key : null,
                'published_at' => $publishedAt,
                'price_changed_at' => $key === 'villa' ? now()->subDays(6) : $publishedAt,
            ]);
            $listing->created_at = $publishedAt ?? now()->subDays(2);
            $listing->save();

            foreach (range(0, $photos - 1) as $position) {
                $listing->photos()->create([
                    'path' => 'sample:'.($position === 0 ? $type->value.' '.$captions[0] : $captions[$position % count($captions)]),
                    'position' => $position,
                ]);
            }

            if ($publishedAt) {
                $this->activity($listing, 'listing.published', ['by' => $this->people[$agent]->name], $publishedAt, $this->people[$agent]);
            }

            $this->listings[$key] = $listing;
        }

        $this->activity($this->listings['villa'], 'listing.status_changed', ['status' => 'available'], now()->subDays(7));
        $this->activity($this->listings['villa'], 'listing.price_changed', [], now()->subDays(6));
        $this->activity($this->listings['villa'], 'listing.views_milestone', ['views' => 1000], now()->subDays(2));
        $this->activity($this->listings['narjis'], 'listing.status_changed', ['status' => 'reserved'], now()->subDays(3));
        $this->activity($this->listings['hittin'], 'listing.ad_license_expired', [], now()->subDays(3));
        $this->activity($this->listings['qairawan'], 'listing.status_changed', ['status' => 'rented'], now()->subDays(9));
    }

    private function seedClients(): void
    {
        $rows = [
            'abdullah' => ['عبدالله الدوسري', '0551234567', ClientType::Tenant, LeadSource::Site, 'fahad', 4],
            'munira' => ['منيرة السبيعي', '0508762210', ClientType::Buyer, LeadSource::Qr, 'sara', 1],
            'turki' => ['تركي الحربي', '0543391802', ClientType::Owner, LeadSource::Direct, 'noura', 12],
            'reem' => ['ريم العنزي', '0567024418', ClientType::Investor, LeadSource::SharedLink, 'sara', 9],
            'majed' => ['ماجد الشمري', '0531186620', ClientType::Buyer, LeadSource::Site, 'fahad', 0],
            'sultan' => ['سلطان القرني', '0559934410', ClientType::Tenant, LeadSource::SharedLink, 'sara', 0],
            'nouf' => ['نوف المطيري', '0541122334', ClientType::Tenant, LeadSource::Site, 'fahad', 24],
            'bandar' => ['بندر الزهراني', '0532233445', ClientType::Tenant, LeadSource::Site, 'fahad', 20],
            'abdulaziz' => ['عبدالعزيز المطيري', '0553344556', ClientType::Buyer, LeadSource::Qr, 'sara', 2],
            'huda' => ['هدى الحربي', '0564455667', ClientType::Buyer, LeadSource::SharedLink, 'sara', 6],
        ];

        foreach ($rows as $key => [$name, $mobile, $type, $source, $agent, $daysAgo]) {
            $client = new Client([
                'workspace_id' => $this->office->id,
                'agent_id' => $this->people[$agent]->id,
                'name' => $name,
                'mobile' => $mobile,
                'type' => $type,
                'source' => $source,
            ]);
            $client->created_at = now()->subDays($daysAgo)->subHours(mt_rand(1, 5));
            $client->save();

            $this->clients[$key] = $client;
        }

        $requests = [
            'abdullah' => [ListingPurpose::Rent, [ListingType::Apartment], ['النرجس', 'الياسمين'], 50000, 70000],
            'munira' => [ListingPurpose::Sale, [ListingType::Villa], ['الملقا', 'حطين'], 3000000, 3500000],
            'reem' => [ListingPurpose::Sale, [ListingType::Land], ['حطين', 'الملقا'], 1800000, 2300000],
            'sultan' => [ListingPurpose::Rent, [ListingType::Floor, ListingType::Apartment], ['الياسمين'], 40000, 55000],
        ];

        foreach ($requests as $key => [$purpose, $types, $districts, $min, $max]) {
            $this->clients[$key]->requests()->create([
                'purpose' => $purpose,
                'types' => array_map(fn (ListingType $type) => $type->value, $types),
                'districts' => $districts,
                'budget_min' => $min,
                'budget_max' => $max,
            ]);
        }

        $abdullah = $this->clients['abdullah'];
        $abdullah->notes()->forceCreate(['user_id' => $this->people['fahad']->id, 'body' => 'يفضّل الدور الثاني أو أعلى، ولديه سيارتان.', 'is_shared' => true, 'created_at' => now()->subDays(2)]);
        $abdullah->notes()->forceCreate(['user_id' => $this->people['khaled']->id, 'body' => 'العميل حساس للسعر، لا يتجاوز 70 ألف حتى لو كان العقار مناسباً.', 'is_shared' => false, 'created_at' => now()->subDays(4)]);

        $this->activity($abdullah, 'client.inquiry', ['source' => 'الموقع', 'listing' => 'شقة النرجس'], now()->subDays(4)->setTime(20, 10));
        $this->activity($abdullah, 'client.request_added', ['summary' => 'شقة للإيجار في النرجس أو الياسمين'], now()->subDays(4)->setTime(21, 0), $this->people['fahad']);
        $this->activity($abdullah, 'client.listing_sent', ['listing' => $this->listings['narjis']->title, 'listing_id' => $this->listings['narjis']->id], now()->subDays(3)->setTime(11, 20), $this->people['fahad']);
        $this->activity($abdullah, 'client.viewing', ['listing' => 'شقة النرجس', 'agent' => 'فهد العتيبي'], now()->subDay()->setTime(19, 30), $this->people['fahad']);
    }

    private function seedInquiries(): void
    {
        $inquiry = function (string $client, ?string $listing, LeadSource $source, CarbonInterface $at, ?int $responseMinutes, ?string $message = null): Inquiry {
            $owner = $this->clients[$client];

            return Inquiry::create([
                'workspace_id' => $this->office->id,
                'listing_id' => $listing ? $this->listings[$listing]->id : null,
                'client_id' => $owner->id,
                'agent_id' => $listing ? $this->listings[$listing]->agent_id : $owner->agent_id,
                'name' => $owner->name,
                'mobile' => $owner->mobile,
                'message' => $message,
                'source' => $source,
                'responded_at' => $responseMinutes !== null ? $at->copy()->addMinutes($responseMinutes) : null,
                'created_at' => $at,
            ]);
        };

        $inquiry('munira', 'villa', LeadSource::Site, now()->subMinutes(5), null, 'هل السعر قابل للتفاوض؟');
        $inquiry('majed', 'villa', LeadSource::Qr, now()->subMinutes(42), null);
        $inquiry('sultan', 'yasmin', LeadSource::SharedLink, now()->subMinutes(21), null);
        $inquiry('abdulaziz', 'villa', LeadSource::Qr, now()->subDays(2)->setTime(18, 40), 9);
        $inquiry('huda', 'villa', LeadSource::SharedLink, now()->subDays(6)->setTime(13, 5), 22);
        $inquiry('abdullah', 'narjis', LeadSource::Site, now()->subDays(4)->setTime(20, 10), 12);
        $inquiry('reem', 'hittin', LeadSource::SharedLink, now()->subDays(9)->setTime(10, 30), 31);
        $inquiry('nouf', 'qairawan', LeadSource::Site, now()->subDays(24)->setTime(17, 0), 6);
        $inquiry('bandar', 'narjis', LeadSource::Site, now()->subDays(20)->setTime(21, 15), 14);

        $names = ['فيصل القحطاني', 'نوال العتيبي', 'سعود الدوسري', 'لمى الشهري', 'خالد المالكي', 'رهف الحربي', 'ياسر الغامدي', 'شهد السبيعي', 'مشعل العنزي', 'جود الزهراني', 'عبدالرحمن القرني', 'أمل المطيري', 'بدر الشمري', 'دانة العمري'];
        $listingKeys = ['villa', 'narjis', 'yasmin', 'yasmin-apartment', 'qairawan', 'hittin'];
        $sources = [LeadSource::Site, LeadSource::Site, LeadSource::SharedLink, LeadSource::Qr, LeadSource::Direct];
        $responseMinutes = [4, 7, 9, 11, 12, 13, 14, 15, 18, 26, 35, 48, 8, 10];

        foreach ($names as $index => $name) {
            $listing = $this->listings[$listingKeys[$index % count($listingKeys)]];
            $daysAgo = $index < 6 ? $index % 6 : 7 + ($index * 2) % 21;
            $source = $sources[$index % count($sources)];

            $client = new Client([
                'workspace_id' => $this->office->id,
                'agent_id' => $listing->agent_id,
                'name' => $name,
                'mobile' => '05'.(30000000 + $index * 1234567 % 69999999),
                'type' => $listing->purpose === ListingPurpose::Rent ? ClientType::Tenant : ClientType::Buyer,
                'source' => $source,
            ]);
            $client->created_at = now()->subDays($daysAgo)->setTime(9 + $index % 12, 20);
            $client->save();
            $this->clients['lead-'.$index] = $client;

            $inquiry('lead-'.$index, array_search($listing, $this->listings, true), $source, $client->created_at, $responseMinutes[$index]);
        }
    }

    private function seedDeals(): void
    {
        $deal = function (string $client, ?string $listing, string $agent, DealStage $stage, int $value, int $daysAgo, ?DealStage $furthest = null, array $extra = []): Deal {
            $deal = new Deal([
                'workspace_id' => $this->office->id,
                'client_id' => $this->clients[$client]->id,
                'listing_id' => $listing ? $this->listings[$listing]->id : null,
                'agent_id' => $this->people[$agent]->id,
                'stage' => $stage,
                'furthest_stage' => $furthest ?? $stage,
                'value' => $value,
                ...$extra,
            ]);
            $deal->created_at = now()->subDays($daysAgo);
            $deal->save();

            return $deal;
        };

        $deal('majed', 'arid', 'sara', DealStage::New, 1150000, 0);
        $deal('reem', 'hittin', 'noura', DealStage::Contacted, 2100000, 8);
        $deal('sultan', 'yasmin', 'sara', DealStage::Contacted, 48000, 0);
        $deal('abdullah', 'narjis', 'fahad', DealStage::Viewing, 65000, 4);
        $deal('munira', 'villa', 'sara', DealStage::Negotiation, 3200000, 1);
        $deal('nouf', 'qairawan', 'fahad', DealStage::Won, 180000, 23, null, ['final_price' => 180000, 'commission' => 4500, 'closed_at' => now()->subDays(9)]);
        $deal('bandar', 'narjis', 'fahad', DealStage::Lost, 62000, 19, DealStage::Viewing, ['lost_reason' => LostReason::OtherListing, 'closed_at' => now()->subDays(5)]);
        $deal('huda', 'sahafa', 'sara', DealStage::Won, 2400000, 26, null, ['final_price' => 2400000, 'commission' => 60000, 'closed_at' => now()->startOfMonth()->addDay()]);
        $deal('abdulaziz', 'rabie', 'sara', DealStage::Won, 1060000, 28, null, ['final_price' => 1060000, 'commission' => 26500, 'closed_at' => now()->startOfMonth()->addDays(2)]);

        $stages = [DealStage::Contacted, DealStage::Contacted, DealStage::Viewing, DealStage::Contacted, DealStage::Lost, DealStage::Viewing, DealStage::Contacted, DealStage::Negotiation, DealStage::Lost, DealStage::Contacted];

        foreach ($stages as $index => $stage) {
            $client = $this->clients['lead-'.($index + 2)];
            $listing = Listing::find(Inquiry::where('client_id', $client->id)->value('listing_id'));
            $agent = array_search($listing->agent_id, array_map(fn (User $user) => $user->id, $this->people), true);

            $deal('lead-'.($index + 2), $this->listingKey($listing->id), $agent, $stage, (int) $listing->price, (int) $client->created_at->diffInDays(now(), true),
                $stage === DealStage::Lost ? DealStage::Contacted : null,
                $stage === DealStage::Lost ? ['lost_reason' => LostReason::NoInterest, 'closed_at' => now()->subDays(2)] : []);
        }
    }

    private function seedReminders(): void
    {
        $reminder = fn (string $client, string $agent, string $title, ReminderChannel $channel, CarbonInterface $due, bool $done = false) => Reminder::create([
            'workspace_id' => $this->office->id,
            'client_id' => $this->clients[$client]->id,
            'user_id' => $this->people[$agent]->id,
            'title' => $title,
            'channel' => $channel,
            'due_at' => $due,
            'completed_at' => $done ? $due : null,
        ]);

        $reminder('abdullah', 'fahad', 'اتصال متابعة بعد المعاينة', ReminderChannel::Call, today()->setTime(16, 0));
        $reminder('munira', 'sara', 'إرسال عرض سعر فيلا الملقا', ReminderChannel::Whatsapp, today()->setTime(11, 0));
        $reminder('turki', 'noura', 'تجديد عقد الوساطة', ReminderChannel::Call, today()->setTime(13, 30));
        $reminder('abdullah', 'fahad', 'إرسال صور شقة الياسمين', ReminderChannel::Whatsapp, today()->addDay()->setTime(10, 0));
        $reminder('turki', 'noura', 'زيارة العقار مع المالك', ReminderChannel::Call, today()->addDays(7)->setTime(17, 0));
        $reminder('majed', 'fahad', 'متابعة التمويل العقاري', ReminderChannel::Call, today()->addDays(4)->setTime(12, 0));
        $reminder('reem', 'sara', 'إرسال مخطط الأرض', ReminderChannel::Whatsapp, today()->subDays(3)->setTime(10, 0), true);
        $reminder('huda', 'sara', 'متابعة إجراءات الإفراغ', ReminderChannel::Call, today()->subDays(8)->setTime(12, 0), true);
        $reminder('nouf', 'fahad', 'تذكير بموعد توقيع العقد', ReminderChannel::Whatsapp, today()->subDays(10)->setTime(15, 0), true);
    }

    /**
     * Thirty-one days of visits, views and contact clicks with a gentle upward trend.
     */
    private function seedSiteEvents(): void
    {
        $listingTotals = [
            'villa' => [1284, 86, 31, 22],
            'narjis' => [642, 48, 19, 12],
            'hittin' => [410, 17, 8, 5],
            'yasmin' => [538, 29, 11, 9],
            'qairawan' => [921, 61, 22, 15],
            'yasmin-apartment' => [296, 20, 9, 6],
        ];
        $sourceWeights = [[LeadSource::Site, 46], [LeadSource::SharedLink, 27], [LeadSource::Qr, 17], [LeadSource::Direct, 10]];
        $visits = [62, 48, 71, 55, 80, 94, 66, 58, 73, 88, 61, 52, 77, 91, 84, 69, 57, 63, 79, 102, 95, 71, 66, 83, 98, 110, 87, 74, 92, 118, 96];

        $rows = [];
        $push = function (?int $listingId, SiteEventType $type, CarbonInterface $at, ?LeadSource $source) use (&$rows): void {
            $rows[] = [
                'workspace_id' => $this->office->id,
                'listing_id' => $listingId,
                'type' => $type->value,
                'source' => $source?->value,
                'occurred_at' => $at->format('Y-m-d H:i:s'),
            ];
        };

        foreach ($visits as $index => $count) {
            $day = today()->subDays(30 - $index);

            foreach (range(1, $count * 3) as $visit) {
                $push(null, SiteEventType::SiteVisit, $this->momentOn($day), $this->weighted($sourceWeights));
            }
        }

        foreach ($listingTotals as $key => [$views, $whatsapp, $calls, $shares]) {
            $listing = $this->listings[$key];
            $start = Carbon::parse($listing->published_at)->max(today()->subDays(30));

            foreach ([[SiteEventType::ListingView, $views], [SiteEventType::WhatsappClick, $whatsapp], [SiteEventType::CallClick, $calls], [SiteEventType::Share, $shares]] as [$type, $total]) {
                foreach (range(1, $total) as $event) {
                    $push($listing->id, $type, $this->trendingMoment($start), $this->weighted($sourceWeights));
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            SiteEvent::insert($chunk);
        }
    }

    private function seedBilling(): void
    {
        foreach ([1, 2, 3] as $monthsAgo) {
            $issued = now()->subMonthsNoOverflow($monthsAgo)->startOfMonth();

            $this->office->invoices()->create([
                'number' => 'TKH-'.$issued->format('Ym').'-0142',
                'description' => 'اشتراك خطة المكتب — '.Arabic::month($issued->month).' '.$issued->year,
                'amount' => 349,
                'issued_on' => $issued->toDateString(),
                'paid_at' => $issued->copy()->addHours(3),
            ]);
        }
    }

    private function seedJeddahBranch(): void
    {
        $branch = new Workspace([
            'kind' => WorkspaceKind::Office,
            'name' => 'مكتب الراشد — جدة',
            'slug' => 'alrashid-jeddah',
            'city' => 'جدة',
            'cr_number' => '4030567890',
            'fal_number' => '1200045699',
            'license_expires_on' => now()->addYear()->toDateString(),
            'plan' => Plan::Office,
            'seats' => 5,
            'trial_ends_at' => now()->subMonth(),
            'about' => 'فرع مكتب الراشد العقاري في جدة.',
            'whatsapp' => '0501234567',
        ]);
        $branch->forceFill(['approval_status' => 'approved', 'approved_at' => now()->subMonths(2)])->save();

        Membership::create([
            'workspace_id' => $branch->id,
            'user_id' => $this->people['khaled']->id,
            'role' => MemberRole::Manager,
        ]);

        Listing::create([
            'workspace_id' => $branch->id,
            'agent_id' => $this->people['khaled']->id,
            'purpose' => ListingPurpose::Sale,
            'type' => ListingType::Apartment,
            'status' => ListingStatus::Available,
            'price' => 890000,
            'area' => 165,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'city' => 'جدة',
            'district' => 'الشاطئ',
            'features' => ['مصعد'],
            'ad_license_number' => '7200091234',
            'ad_license_expires_on' => now()->addMonths(5)->toDateString(),
            'published_at' => now()->subDays(12),
            'price_changed_at' => now()->subDays(12),
        ]);
    }

    private function seedNotifications(): void
    {
        $khaled = $this->people['khaled'];

        $abdullahReminder = Reminder::where('client_id', $this->clients['abdullah']->id)->orderBy('due_at')->first();
        $muniraInquiry = Inquiry::where('client_id', $this->clients['munira']->id)->first();
        $saraMembership = Membership::where('user_id', $this->people['sara']->id)->first();

        $this->notify($khaled, new PaymentFailed, now()->subDays(3), read: true);
        $this->notify($khaled, new FalLicenseExpiring($saraMembership), now()->subDay(), read: true);
        $this->notify($khaled, new ListingMatchesRequest($this->listings['yasmin-apartment'], $this->clients['abdullah']), now()->subHour());
        $this->notify($khaled, new ReminderDue($abdullahReminder), now()->subMinutes(30));
        $this->notify($khaled, new NewInquiry($muniraInquiry), now()->subMinutes(5));

        $this->notify($this->people['sara'], new NewInquiry($muniraInquiry), now()->subMinutes(5));
        $this->notify($this->people['sara'], new FalLicenseExpiring($saraMembership), now()->subDay());
    }

    /**
     * Store a database notification with a chosen timestamp.
     */
    private function notify(User $user, Notification $notification, CarbonInterface $at, bool $read = false): void
    {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => $notification::class,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => $notification->toArray($user),
            'read_at' => $read ? $at : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function activity(Listing|Client $subject, string $type, array $properties, CarbonInterface $at, ?User $user = null): void
    {
        Activity::create([
            'workspace_id' => $subject->workspace_id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'user_id' => $user?->id,
            'type' => $type,
            'properties' => $properties,
            'occurred_at' => $at,
        ]);
    }

    private function listingKey(int $id): string
    {
        foreach ($this->listings as $key => $listing) {
            if ($listing->id === $id) {
                return $key;
            }
        }

        throw new \RuntimeException("Unknown listing {$id}");
    }

    private function momentOn(CarbonInterface $day): Carbon
    {
        $moment = Carbon::parse($day)->setTime(mt_rand(8, 23), mt_rand(0, 59), mt_rand(0, 59));

        return $moment->isFuture() ? now()->subMinutes(mt_rand(1, 120)) : $moment;
    }

    /**
     * A moment between the start and now, weighted towards recent days.
     */
    private function trendingMoment(CarbonInterface $start): Carbon
    {
        $span = max(1, (int) $start->diffInMinutes(now(), true));
        $offset = (int) ($span * sqrt(mt_rand(0, 10000) / 10000));

        return Carbon::parse($start)->addMinutes($offset);
    }

    /**
     * @param  list<array{0: LeadSource, 1: int}>  $weights
     */
    private function weighted(array $weights): LeadSource
    {
        $roll = mt_rand(1, 100);

        foreach ($weights as [$source, $weight]) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $source;
            }
        }

        return $weights[0][0];
    }
}
