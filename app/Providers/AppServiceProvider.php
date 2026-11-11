<?php

namespace App\Providers;

use App\Http\Middleware\EnsureWorkspace;
use App\Models\User;
use App\Support\Arabic;
use App\Support\Phone;
use App\Support\PlatformSettings;
use App\View\Composers\ShellComposer;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use PDOException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $aliases = AliasLoader::getInstance();
        $aliases->alias('Arabic', Arabic::class);
        $aliases->alias('Phone', Phone::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyPlatformSettings();
        $this->defineGates();

        View::composer(['layouts.app', 'layouts::app'], ShellComposer::class);

        Livewire::addPersistentMiddleware([EnsureWorkspace::class]);
    }

    /**
     * Override config with the settings saved in the admin panel. Before the
     * first migration, or without a database, the config files apply as they are.
     */
    private function applyPlatformSettings(): void
    {
        try {
            PlatformSettings::apply();
        } catch (QueryException|PDOException|SQLiteDatabaseDoesNotExistException) {
            return;
        }
    }

    /**
     * Workspace-level permissions, decided by the member's role in the current workspace.
     */
    private function defineGates(): void
    {
        Gate::define('platform-admin', fn (User $user): bool => $user->is_platform_admin);

        Gate::define('manage-team', function (User $user): bool {
            $membership = $user->currentMembership();

            return $membership !== null
                && $membership->workspace->isOffice()
                && $membership->role->managesTeam();
        });

        Gate::define('view-reports', fn (User $user): bool => (bool) $user->currentMembership()?->role->managesTeam());

        Gate::define('manage-site', fn (User $user): bool => (bool) $user->currentMembership()?->role->managesTeam());

        Gate::define('manage-billing', fn (User $user): bool => (bool) $user->currentMembership()?->role->managesBilling());
    }
}
