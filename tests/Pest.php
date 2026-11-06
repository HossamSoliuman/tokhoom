<?php

use App\Enums\MemberRole;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A user who belongs to an approved office with the given role, already
 * pointed at that office as their current workspace.
 *
 * @return array{0: User, 1: Workspace}
 */
function member(MemberRole $role = MemberRole::Owner, ?Workspace $workspace = null): array
{
    $workspace ??= Workspace::factory()->create();
    $user = User::factory()->create();

    Membership::factory()->for($workspace)->for($user)->create(['role' => $role]);
    $user->forceFill(['current_workspace_id' => $workspace->id])->save();

    return [$user->fresh(), $workspace];
}

function hostUrl(string $host, string $path = '/'): string
{
    return 'http://'.$host.$path;
}

/**
 * A page on a workspace's public site.
 */
function siteUrl(Workspace $workspace, string $path = '/'): string
{
    return hostUrl(siteSubdomain($workspace->slug), $path);
}

function siteSubdomain(string $slug): string
{
    return $slug.'.'.config('tokhoom.site_domain');
}
