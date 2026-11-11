<?php

namespace App\Models;

use App\Enums\Plan;
use App\Enums\RejectionReason;
use App\Support\Arabic;
use App\Support\PlatformSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something a platform admin did to a workspace or an account, or a note
 * they left on it. Shown on the admin pages as a history.
 */
#[Fillable(['admin_id', 'workspace_id', 'user_id', 'type', 'properties', 'created_at'])]
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Record an action by the signed-in admin.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $type, ?Workspace $workspace = null, ?User $user = null, array $properties = []): self
    {
        return self::create([
            'admin_id' => auth()->id(),
            'workspace_id' => $workspace?->id,
            'user_id' => $user?->id,
            'type' => $type,
            'properties' => $properties,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isNote(): bool
    {
        return $this->type === 'workspace.note';
    }

    public function text(): string
    {
        $p = $this->properties ?? [];
        $reason = filled($p['reason'] ?? null) ? ': '.$p['reason'] : '';

        return match ($this->type) {
            'workspace.approved' => 'اعتماد الترخيص',
            'workspace.rejected' => 'رفض الطلب: '.(RejectionReason::tryFrom($p['reason'] ?? '')?->label() ?? ''),
            'workspace.suspended' => 'إيقاف مساحة العمل'.$reason,
            'workspace.reactivated' => 'إعادة تفعيل مساحة العمل',
            'workspace.license_updated' => 'تحديث بيانات الترخيص · ينتهي '.Arabic::date(isset($p['expires_on']) ? Carbon::parse($p['expires_on']) : null),
            'workspace.days_granted' => 'إضافة '.Arabic::days((int) ($p['days'] ?? 0)).' مجاناً · الخصم القادم '.Arabic::date(isset($p['until']) ? Carbon::parse($p['until']) : null),
            'workspace.plan_changed' => 'تغيير الخطة إلى '.(Plan::tryFrom($p['plan'] ?? '')?->label() ?? '').' · '.Arabic::countWithNumber((int) ($p['seats'] ?? 0), 'مقعد', 'مقاعد', 'مقعداً'),
            'workspace.domain_removed' => 'إلغاء ربط النطاق '.($p['domain'] ?? ''),
            'workspace.note' => (string) ($p['body'] ?? ''),
            'invoice.paid_manually' => 'تسجيل دفع الفاتورة '.($p['number'] ?? '').' يدوياً'.(filled($p['note'] ?? null) ? ': '.$p['note'] : ''),
            'listing.removed' => 'إيقاف عرض العقار '.($p['code'] ?? '').$reason,
            'listing.restored' => 'إعادة عرض العقار '.($p['code'] ?? ''),
            'user.admin_granted' => 'منح صلاحية إدارة المنصة',
            'user.admin_revoked' => 'سحب صلاحية إدارة المنصة',
            'user.two_factor_reset' => 'إلغاء التحقق بخطوتين',
            'user.password_reset_sent' => 'إرسال رابط إعادة تعيين كلمة المرور',
            'settings.updated' => 'تعديل '.(PlatformSettings::SECTIONS[$p['section'] ?? '']['label'] ?? 'الإعدادات').': '
                .implode('، ', array_map(PlatformSettings::label(...), $p['fields'] ?? [])),
            default => $this->type,
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'workspace.approved' => 'ti-certificate',
            'workspace.rejected' => 'ti-circle-x',
            'workspace.suspended' => 'ti-lock',
            'workspace.reactivated' => 'ti-lock-open',
            'workspace.license_updated' => 'ti-id-badge-2',
            'workspace.days_granted' => 'ti-gift',
            'workspace.plan_changed' => 'ti-arrows-exchange',
            'workspace.domain_removed' => 'ti-world-off',
            'workspace.note' => 'ti-note',
            'invoice.paid_manually' => 'ti-receipt',
            'listing.removed' => 'ti-eye-off',
            'listing.restored' => 'ti-eye',
            'user.admin_granted', 'user.admin_revoked' => 'ti-shield',
            'user.two_factor_reset' => 'ti-shield-off',
            'user.password_reset_sent' => 'ti-key',
            'settings.updated' => 'ti-settings',
            default => 'ti-point',
        };
    }
}
