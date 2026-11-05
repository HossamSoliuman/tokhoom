<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Monthly commission and deal targets for one agent.
 */
#[Fillable(['workspace_id', 'user_id', 'month', 'commission_target', 'deals_target'])]
class CommissionTarget extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
