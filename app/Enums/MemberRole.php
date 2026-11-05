<?php

namespace App\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Agent = 'agent';

    public function label(?string $gender = null, WorkspaceKind $kind = WorkspaceKind::Office): string
    {
        $female = $gender === 'female';

        return match ($this) {
            self::Owner => $kind === WorkspaceKind::Agent
                ? ($female ? 'وسيطة مستقلة' : 'وسيط مستقل')
                : ($female ? 'مالكة المكتب' : 'مالك المكتب'),
            self::Manager => $female ? 'مديرة' : 'مدير',
            self::Agent => $female ? 'وسيطة' : 'وسيط',
        };
    }

    /**
     * Owners and managers see every listing, client and deal in the workspace.
     */
    public function seesWholeWorkspace(): bool
    {
        return $this !== self::Agent;
    }

    public function managesTeam(): bool
    {
        return $this !== self::Agent;
    }

    public function managesBilling(): bool
    {
        return $this === self::Owner;
    }
}
