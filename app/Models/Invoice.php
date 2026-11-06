<?php

namespace App\Models;

use App\Support\ZatcaQr;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['workspace_id', 'number', 'description', 'amount', 'issued_on', 'paid_at'])]
class Invoice extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'issued_on' => 'date',
            'paid_at' => 'datetime',
            'payment_attempts' => 'integer',
            'last_attempted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Whether Tokhoom is VAT registered, which makes this a tax invoice.
     */
    public function isTaxInvoice(): bool
    {
        return filled(config('tokhoom.seller.vat_number'));
    }

    /**
     * The VAT included in the amount, since plan prices include VAT.
     */
    public function vatAmount(): float
    {
        $rate = config('tokhoom.vat_rate');

        return round($this->amount * $rate / (100 + $rate), 2);
    }

    public function amountBeforeVat(): float
    {
        return round($this->amount - $this->vatAmount(), 2);
    }

    /**
     * The issue date at the time the invoice was created.
     */
    public function issuedAt(): Carbon
    {
        return $this->issued_on->copy()->setTimeFrom($this->created_at ?? now());
    }

    /**
     * The Base64 TLV string encoded in the invoice's ZATCA QR code.
     */
    public function zatcaPayload(): string
    {
        return ZatcaQr::payload(
            config('tokhoom.seller.name'),
            config('tokhoom.seller.vat_number'),
            $this->issuedAt(),
            $this->amount,
            $this->vatAmount(),
        );
    }
}
