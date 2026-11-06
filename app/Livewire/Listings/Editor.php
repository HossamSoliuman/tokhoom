<?php

namespace App\Livewire\Listings;

use App\Actions\ChangeListingStatus;
use App\Enums\ListingPurpose;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Activity;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Support\Locations;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Add or edit a listing on one screen. Publishing needs the ad license number.
 */
class Editor extends Component
{
    use InteractsWithWorkspace, ShowsToasts, WithFileUploads;

    #[Locked]
    public ?int $listingId = null;

    /**
     * Photos in display order. Saved photos carry an id, new ones a key into $pending.
     *
     * @var list<array{key: string, id: ?int}>
     */
    public array $photos = [];

    /**
     * @var array<string, TemporaryUploadedFile>
     */
    public array $pending = [];

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    public string $purpose = 'sale';

    public string $type = 'villa';

    public string $price = '';

    public string $area = '';

    public string $bedrooms = '';

    public string $bathrooms = '';

    public string $age = '';

    public string $floors = '';

    public string $city = 'الرياض';

    public string $district = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    /**
     * @var list<string>
     */
    public array $features = [];

    public string $description = '';

    public string $adLicenseNumber = '';

    public string $adLicenseExpiresOn = '';

    public string $brokerageContractNumber = '';

    public string $tourUrl = '';

    public string $videoUrl = '';

    public function mount(?Listing $listing = null): void
    {
        if (! $listing?->exists) {
            $this->city = $this->workspace()->city;

            return;
        }

        Gate::authorize('update', $listing);

        $this->listingId = $listing->id;
        $this->photos = $listing->photos->map(fn (ListingPhoto $photo) => ['key' => 'p'.$photo->id, 'id' => $photo->id])->all();
        $this->purpose = $listing->purpose->value;
        $this->type = $listing->type->value;
        $this->price = $listing->price ? number_format($listing->price) : '';
        $this->area = (string) $listing->area;
        $this->bedrooms = (string) $listing->bedrooms;
        $this->bathrooms = (string) $listing->bathrooms;
        $this->age = (string) $listing->age;
        $this->floors = (string) $listing->floors;
        $this->city = (string) $listing->city;
        $this->district = (string) $listing->district;
        $this->latitude = $listing->latitude;
        $this->longitude = $listing->longitude;
        $this->features = $listing->features ?? [];
        $this->description = (string) $listing->description;
        $this->adLicenseNumber = (string) $listing->ad_license_number;
        $this->adLicenseExpiresOn = (string) $listing->ad_license_expires_on?->toDateString();
        $this->brokerageContractNumber = (string) $listing->brokerage_contract_number;
        $this->tourUrl = (string) $listing->tour_url;
        $this->videoUrl = (string) $listing->video_url;
    }

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => ['array', 'max:'.Listing::MAX_PHOTOS],
            'uploads.*' => ['image', 'max:10240'],
        ]);

        foreach ($this->uploads as $upload) {
            if (count($this->photos) >= Listing::MAX_PHOTOS) {
                $this->toast('الحد الأقصى '.Listing::MAX_PHOTOS.' صورة للعقار', 'err');
                break;
            }

            $key = 'u'.Str::random(8);
            $this->pending[$key] = $upload;
            $this->photos[] = ['key' => $key, 'id' => null];
        }

        $this->uploads = [];
    }

    public function updatedAdLicenseNumber(string $value): void
    {
        $this->adLicenseNumber = substr(preg_replace('/\D/', '', $value) ?? '', 0, 10);
    }

    public function updatedCity(): void
    {
        if (! in_array($this->district, Locations::districts($this->city), true)) {
            $this->district = '';
        }
    }

    public function removePhoto(string $key): void
    {
        $this->photos = array_values(array_filter($this->photos, fn (array $photo) => $photo['key'] !== $key));
        unset($this->pending[$key]);
    }

    public function reorderPhoto(string $key, int $position): void
    {
        $index = array_search($key, array_column($this->photos, 'key'), true);

        if ($index === false) {
            return;
        }

        $photo = array_splice($this->photos, $index, 1)[0];
        array_splice($this->photos, max(0, min($position, count($this->photos))), 0, [$photo]);
    }

    public function toggleFeature(string $feature): void
    {
        $this->features = in_array($feature, $this->features, true)
            ? array_values(array_diff($this->features, [$feature]))
            : [...$this->features, $feature];
    }

    public function saveDraft(ChangeListingStatus $changeStatus): void
    {
        $this->save(publish: false, changeStatus: $changeStatus);
    }

    public function publish(ChangeListingStatus $changeStatus): void
    {
        $this->save(publish: true, changeStatus: $changeStatus);
    }

    private function save(bool $publish, ChangeListingStatus $changeStatus): void
    {
        $listing = $this->listing() ?? new Listing([
            'workspace_id' => $this->workspace()->id,
            'agent_id' => $this->user()->id,
            'status' => ListingStatus::Draft,
        ]);

        if (! $listing->exists && $this->reachedListingLimit()) {
            $this->toast('وصلت إلى حد '.$this->workspace()->plan->listingLimit().' عقاراً في خطتك. غيّر الخطة لإضافة المزيد.', 'err');

            return;
        }

        $this->normalizeNumbers();
        $this->validate($this->rules($publish), [
            'adLicenseNumber.required' => 'أدخل رقم ترخيص الإعلان لتفعيل النشر.',
            'adLicenseNumber.digits' => 'رقم ترخيص الإعلان 10 أرقام.',
        ]);

        $priceChanged = $listing->exists && (int) $listing->price !== $this->digits($this->price);

        DB::transaction(function () use ($listing, $priceChanged): void {
            $listing->fill([
                'purpose' => $this->purpose,
                'type' => $this->type,
                'price' => $this->digits($this->price) ?: null,
                'area' => $this->digits($this->area) ?: null,
                'bedrooms' => $this->nullableInt($this->bedrooms),
                'bathrooms' => $this->nullableInt($this->bathrooms),
                'age' => $this->nullableInt($this->age),
                'floors' => $this->nullableInt($this->floors),
                'city' => $this->city ?: null,
                'district' => $this->district ?: null,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'features' => array_values(array_intersect(Locations::FEATURES, $this->features)),
                'description' => trim($this->description) ?: null,
                'ad_license_number' => $this->adLicenseNumber ?: null,
                'ad_license_expires_on' => $this->adLicenseExpiresOn ?: null,
                'brokerage_contract_number' => trim($this->brokerageContractNumber) ?: null,
                'tour_url' => trim($this->tourUrl) ?: null,
                'video_url' => trim($this->videoUrl) ?: null,
            ]);

            if ($priceChanged) {
                $listing->price_changed_at = now();
            }

            $listing->save();

            if ($priceChanged && $listing->isPublished()) {
                Activity::record($listing, 'listing.price_changed');
            }

            $this->syncPhotos($listing);
        });

        $wasDraft = $listing->status === ListingStatus::Draft;

        if ($publish && $wasDraft) {
            $changeStatus->handle($listing, ListingStatus::Available);
        }

        $this->flashToast(match (true) {
            $publish && $wasDraft => 'تم نشر العقار على موقع المكتب',
            $publish => 'تم حفظ التعديلات',
            default => 'تم حفظ العقار كمسودة',
        }, $publish ? 'ok' : 'info');

        $this->redirectRoute($publish && ! $wasDraft ? 'listings.show' : 'listings.index', $publish && ! $wasDraft ? $listing : [], navigate: true);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(bool $publish): array
    {
        $required = $publish ? 'required' : 'nullable';

        return [
            'purpose' => ['required', Rule::enum(ListingPurpose::class)],
            'type' => ['required', Rule::enum(ListingType::class)],
            'price' => [$required, 'regex:/^[\d,]+$/'],
            'area' => ['nullable', 'integer', 'max:1000000'],
            'bedrooms' => ['nullable', 'integer', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'max:50'],
            'age' => ['nullable', 'integer', 'max:100'],
            'floors' => ['nullable', 'integer', 'max:100'],
            'city' => [$required, Rule::in(Locations::cities())],
            'district' => [$required, 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:3000'],
            'adLicenseNumber' => [$required, 'digits:10'],
            'adLicenseExpiresOn' => ['nullable', 'date'],
            'brokerageContractNumber' => ['nullable', 'string', 'max:30'],
            'tourUrl' => ['nullable', 'url:http,https', 'max:255'],
            'videoUrl' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    private function syncPhotos(Listing $listing): void
    {
        $keptIds = array_filter(array_column($this->photos, 'id'));

        $listing->photos()->whereNotIn('id', $keptIds)->get()->each(function (ListingPhoto $photo): void {
            if (! str_starts_with($photo->path, 'sample:')) {
                Storage::disk('public')->delete($photo->path);
            }

            $photo->delete();
        });

        foreach ($this->photos as $position => $photo) {
            if ($photo['id']) {
                $listing->photos()->whereKey($photo['id'])->update(['position' => $position]);

                continue;
            }

            if ($upload = $this->pending[$photo['key']] ?? null) {
                $listing->photos()->create([
                    'path' => $upload->store('listings/'.$listing->id, 'public'),
                    'position' => $position,
                ]);
            }
        }
    }

    private function reachedListingLimit(): bool
    {
        $limit = $this->workspace()->plan->listingLimit();

        return $limit !== null && $this->workspace()->listings()->count() >= $limit;
    }

    private function normalizeNumbers(): void
    {
        foreach (['area', 'bedrooms', 'bathrooms', 'age', 'floors'] as $field) {
            $this->{$field} = (string) (preg_replace('/\D/', '', $this->{$field}) ?? '');
        }
    }

    private function digits(string $value): int
    {
        return (int) preg_replace('/\D/', '', $value);
    }

    private function nullableInt(string $value): ?int
    {
        return $value === '' ? null : (int) $value;
    }

    private function listing(): ?Listing
    {
        return $this->listingId ? Listing::visibleTo($this->membership())->findOrFail($this->listingId) : null;
    }

    public function canPublish(): bool
    {
        return strlen($this->adLicenseNumber) === 10;
    }

    public function render(): View
    {
        $listing = $this->listing();
        $saved = $listing ? $listing->photos->keyBy('id') : collect();

        return view('livewire.listings.editor', [
            'listing' => $listing,
            'savedPhotos' => $saved,
            'districts' => Locations::districts($this->city),
        ])->title($listing ? 'تعديل العقار' : 'إضافة عقار');
    }
}
