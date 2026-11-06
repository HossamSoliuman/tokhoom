<?php

namespace App\Models;

use Database\Factories\ListingPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['listing_id', 'path', 'position'])]
class ListingPhoto extends Model
{
    /** @use HasFactory<ListingPhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * How many bundled demo photos exist per subject in public/images/samples.
     *
     * @var array<string, int>
     */
    private const SAMPLE_COUNTS = [
        'villa' => 12, 'floor' => 6, 'apartment' => 3, 'land' => 3, 'aerial' => 2, 'commercial' => 4,
        'interior' => 4, 'parking' => 2, 'street' => 3, 'majlis' => 5, 'living' => 15, 'kitchen' => 5,
        'bedroom' => 10, 'bathroom' => 5, 'roof' => 2, 'garden' => 3, 'entrance' => 3, 'balcony' => 2,
    ];

    /**
     * Seeded caption to sample subject.
     *
     * @var array<string, string>
     */
    private const SAMPLE_SUBJECTS = [
        'facade' => 'villa', 'building' => 'apartment', 'plot' => 'land', 'corner' => 'land', 'aerial view' => 'aerial',
        'frontage' => 'commercial', 'interior' => 'interior', 'parking' => 'parking', 'street view' => 'street',
        'majlis' => 'majlis', 'family living' => 'living', 'living room' => 'living', 'kitchen' => 'kitchen',
        'master bedroom' => 'bedroom', 'bedroom' => 'bedroom', 'bathroom' => 'bathroom', 'roof' => 'roof',
        'garden' => 'garden', 'entrance' => 'entrance', 'balcony' => 'balcony',
    ];

    /**
     * Seeded sample photos resolve to a bundled demo image picked by caption and listing.
     */
    public function url(): ?string
    {
        if (str_starts_with($this->path, 'sample:')) {
            return $this->sampleUrl(substr($this->path, 7));
        }

        return Storage::disk('public')->url($this->path);
    }

    private function sampleUrl(string $caption): ?string
    {
        [$prefix] = explode(' ', $caption, 2);
        $subject = isset(self::SAMPLE_COUNTS[$prefix]) && $prefix !== $caption
            ? $prefix
            : (self::SAMPLE_SUBJECTS[$caption] ?? null);

        if ($subject === null) {
            return null;
        }

        $variant = ($this->listing_id * 3 + $this->position) % self::SAMPLE_COUNTS[$subject] + 1;

        return asset("images/samples/{$subject}-{$variant}.jpg");
    }

    /**
     * Caption for placeholder photos, e.g. "villa facade".
     */
    public function placeholderLabel(): string
    {
        return str_starts_with($this->path, 'sample:') ? substr($this->path, 7) : basename($this->path);
    }
}
