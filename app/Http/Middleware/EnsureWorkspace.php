<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard pages need an active membership in a workspace that is not suspended.
 */
class EnsureWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->currentMembership()) {
            $user->switchWorkspace();
            $user->load('memberships');
        }

        $membership = $user->currentMembership();

        if (! $membership) {
            return $user->is_platform_admin
                ? redirect()->route('admin.approvals')
                : redirect()->route('workspaces.create');
        }

        if ($membership->workspace->isSuspended()) {
            return response()->view('suspended', ['workspace' => $membership->workspace], 403);
        }

        return $next($request);
    }
}
