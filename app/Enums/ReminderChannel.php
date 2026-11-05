<?php

namespace App\Enums;

enum ReminderChannel: string
{
    case Call = 'call';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'اتصال',
            self::Whatsapp => 'واتساب',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call => 'ti-phone',
            self::Whatsapp => 'ti-brand-whatsapp',
        };
    }
}
