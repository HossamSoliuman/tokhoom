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
     * Seeded sample photos have no file; the UI shows a placeholder for them.
     */
    public function url(): ?string
    {
        if (str_starts_with($this->path, 'sample:')) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }

    /**
     * Caption for placeholder photos, e.g. "villa facade".
     */
    public function placeholderLabel(): string
    {
        return str_starts_with($this->path, 'sample:') ? substr($this->path, 7) : basename($this->path);
    }
}
