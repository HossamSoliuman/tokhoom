<?php

namespace App\Enums;

enum WorkspaceKind: string
{
    case Office = 'office';
    case Agent = 'agent';

    public function label(?string $gender = null): string
    {
        return match ($this) {
            self::Office => 'مكتب',
            self::Agent => $gender === 'female' ? 'وسيطة مستقلة' : 'وسيط مستقل',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Office => 'ti-building',
            self::Agent => 'ti-user',
        };
    }
}
