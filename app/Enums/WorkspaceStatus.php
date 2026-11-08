<?php

namespace App\Enums;

/**
 * Where a workspace stands for platform admins: its license review, then
 * suspension, trial and payment.
 */
enum WorkspaceStatus: string
{
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Trial = 'trial';
    case Overdue = 'overdue';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار الاعتماد',
            self::Rejected => 'مرفوض',
            self::Suspended => 'موقوف',
            self::Trial => 'فترة تجريبية',
            self::Overdue => 'دفعة مستحقة',
            self::Active => 'نشط',
        };
    }

    public function pillClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-sand-soft text-sand-ink',
            self::Rejected => 'bg-closed text-closed-ink',
            self::Suspended => 'bg-danger-soft text-danger',
            self::Trial => 'bg-primary-soft text-primary-strong',
            self::Overdue => 'bg-warning-soft text-warning',
            self::Active => 'bg-success-soft text-success',
        };
    }
}
