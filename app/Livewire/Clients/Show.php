<?php

namespace App\Livewire\Clients;

use App\Enums\ListingPurpose;
use App\Enums\ListingType;
use App\Enums\ReminderChannel;
use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Listing;
use App\Models\Note;
use App\Support\Locations;
use App\Support\Matcher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    use HasDialogs, InteractsWithWorkspace, ShowsToasts;

    #[Locked]
    public int $clientId;

    public string $noteBody = '';

    public bool $noteShared = false;

    public string $requestPurpose = 'rent';

    /**
     * @var list<string>
     */
    public array $requestTypes = [];

    /**
     * @var list<string>
     */
    public array $requestDistricts = [];

    public string $budgetMin = '';

    public string $budgetMax = '';

    public string $reminderTitle = '';

    public string $reminderDueAt = '';

    public string $reminderChannel = 'call';

    public function mount(Client $client): void
    {
        Gate::authorize('view', $client);

        $this->clientId = $client->id;
    }

    private function client(): Client
    {
        return Client::visibleTo($this->membership())->findOrFail($this->clientId);
    }

    public function openRequest(): void
    {
        $this->reset('requestPurpose', 'requestTypes', 'requestDistricts', 'budgetMin', 'budgetMax');
        $this->requestTypes = [ListingType::Apartment->value];
        $this->openDialog('request');
    }

    public function openReminder(): void
    {
        $this->reset('reminderTitle', 'reminderChannel');
        $this->reminderDueAt = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->openDialog('reminder');
    }

    public function toggleRequestType(string $type): void
    {
        $this->requestTypes = $this->toggle($this->requestTypes, ListingType::from($type)->value);
    }

    public function toggleRequestDistrict(string $district): void
    {
        $this->requestDistricts = $this->toggle($this->requestDistricts, $district);
    }

    public function saveRequest(): void
    {
        $this->budgetMin = (string) preg_replace('/\D/', '', $this->budgetMin);
        $this->budgetMax = (string) preg_replace('/\D/', '', $this->budgetMax);

        $this->validate([
            'requestPurpose' => ['required', Rule::enum(ListingPurpose::class)],
            'requestTypes' => ['required', 'array', 'min:1'],
            'requestTypes.*' => [Rule::enum(ListingType::class)],
            'requestDistricts' => ['array'],
            'requestDistricts.*' => ['string', 'max:60'],
            'budgetMin' => ['nullable', 'integer'],
            'budgetMax' => ['nullable', 'integer', $this->budgetMin !== '' ? 'gte:budgetMin' : 'nullable'],
        ], [
            'requestTypes.required' => 'اختر نوع عقار واحداً على الأقل.',
            'budgetMax.gte' => 'الحد الأعلى للميزانية أقل من الحد الأدنى.',
        ]);

        $client = $this->client();
        $request = $client->requests()->create([
            'purpose' => $this->requestPurpose,
            'types' => $this->requestTypes,
            'districts' => $this->requestDistricts,
            'budget_min' => $this->budgetMin !== '' ? (int) $this->budgetMin : null,
            'budget_max' => $this->budgetMax !== '' ? (int) $this->budgetMax : null,
        ]);

        Activity::record($client, 'client.request_added', ['summary' => $request->summary()]);

        $this->closeDialog();
        $this->toast('تمت إضافة الطلب. سنرسل لك التطابقات الجديدة');
    }

    public function saveReminder(): void
    {
        $this->validate([
            'reminderTitle' => ['required', 'string', 'max:150'],
            'reminderDueAt' => ['required', 'date'],
            'reminderChannel' => ['required', Rule::enum(ReminderChannel::class)],
        ], [], ['reminderTitle' => 'التذكير', 'reminderDueAt' => 'الموعد']);

        $client = $this->client();
        $client->reminders()->create([
            'workspace_id' => $client->workspace_id,
            'user_id' => $client->agent_id === $this->user()->id ? $this->user()->id : $client->agent_id,
            'title' => trim($this->reminderTitle),
            'channel' => $this->reminderChannel,
            'due_at' => Carbon::parse($this->reminderDueAt),
        ]);

        $this->closeDialog();
        $this->toast('تمت إضافة التذكير', 'info');
    }

    public function toggleReminder(int $id): void
    {
        $reminder = $this->client()->reminders()->findOrFail($id);
        $reminder->update(['completed_at' => $reminder->isDone() ? null : now()]);
    }

    public function addNote(): void
    {
        $this->validate(['noteBody' => ['required', 'string', 'max:2000']], [], ['noteBody' => 'الملاحظة']);

        $this->client()->notes()->create([
            'user_id' => $this->user()->id,
            'body' => trim($this->noteBody),
            'is_shared' => $this->noteShared,
        ]);

        $this->reset('noteBody');
        $this->toast('تم حفظ الملاحظة');
    }

    public function markSent(int $listingId): void
    {
        $client = $this->client();
        $listing = Listing::where('workspace_id', $client->workspace_id)->findOrFail($listingId);

        Activity::record($client, 'client.listing_sent', ['listing' => $listing->title, 'listing_id' => $listing->id]);

        $this->toast('تم إرسال العقار إلى '.$client->name.' عبر واتساب');
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function toggle(array $values, string $value): array
    {
        return in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];
    }

    public function render(Matcher $matcher): View
    {
        $client = $this->client()->load(['agent', 'requests']);

        return view('livewire.clients.show', [
            'client' => $client,
            'matches' => $matcher->forClient($client)->take(4),
            'reminders' => $client->reminders()->orderByRaw('completed_at is not null')->orderBy('due_at')->limit(6)->get(),
            'notes' => $client->notes()->readableBy($this->user())->with('author')->latest()->latest('id')->get(),
            'timeline' => $client->activities()->latest('occurred_at')->limit(10)->get(),
            'districtOptions' => array_values(array_unique([...Locations::districts($this->workspace()->city), ...$this->requestDistricts])),
        ])->title($client->name);
    }
}
