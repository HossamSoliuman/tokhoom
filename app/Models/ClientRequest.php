<?php

namespace App\Models;

use App\Enums\ListingPurpose;
use App\Enums\ListingType;
use App\Support\Arabic;
use Database\Factories\ClientRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a client is looking for: purpose, property types, districts and budget.
 */
#[Fillable(['client_id', 'purpose', 'types', 'districts', 'budget_min', 'budget_max'])]
class ClientRequest extends Model
{
    /** @use HasFactory<ClientRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => ListingPurpose::class,
            'types' => 'array',
            'districts' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return list<ListingType>
     */
    public function listingTypes(): array
    {
        return array_values(array_filter(array_map(
            fn (string $type) => ListingType::tryFrom($type),
            $this->types ?? [],
        )));
    }

    public function typesLabel(): string
    {
        return implode('، ', array_map(fn (ListingType $type) => $type->label(), $this->listingTypes())) ?: '—';
    }

    public function districtsLabel(): string
    {
        return implode('، ', $this->districts ?? []) ?: '—';
    }

    /**
     * "50,000 – 70,000 ريال سنوياً"
     */
    public function budgetLabel(): string
    {
        $suffix = $this->purpose === ListingPurpose::Rent ? ' سنوياً' : '';

        return match (true) {
            $this->budget_min && $this->budget_max => Arabic::number($this->budget_min).' – '.Arabic::number($this->budget_max).' ريال'.$suffix,
            (bool) $this->budget_max => 'حتى '.Arabic::number($this->budget_max).' ريال'.$suffix,
            (bool) $this->budget_min => 'من '.Arabic::number($this->budget_min).' ريال'.$suffix,
            default => '—',
        };
    }

    /**
     * "شقة للإيجار في النرجس أو الياسمين"
     */
    public function summary(): string
    {
        $types = implode(' أو ', array_map(fn (ListingType $type) => $type->label(), $this->listingTypes()));
        $districts = implode(' أو ', $this->districts ?? []);

        return trim(($types ?: 'عقار').' '.$this->purpose->label().($districts ? ' في '.$districts : ''));
    }

    /**
     * A listing fits when purpose, type, district and budget all line up.
     */
    public function matches(Listing $listing): bool
    {
        if ($listing->purpose !== $this->purpose) {
            return false;
        }

        if ($this->types && ! in_array($listing->type->value, $this->types, true)) {
            return false;
        }

        if ($this->districts && ! in_array($listing->district, $this->districts, true)) {
            return false;
        }

        if ($listing->price === null) {
            return false;
        }

        return (! $this->budget_min || $listing->price >= $this->budget_min)
            && (! $this->budget_max || $listing->price <= $this->budget_max);
    }
}
