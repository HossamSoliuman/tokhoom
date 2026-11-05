<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public site pages find their workspace from the host: a subdomain of the
 * site domain or an active custom domain. Unknown hosts, and sites not yet
 * approved or suspended, get a 404.
 */
class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = Workspace::forSiteHost($request->getHost());

        abort_if($workspace === null || ! $workspace->isApproved() || $workspace->isSuspended(), 404);

        $request->attributes->set('site', $workspace);

        return $next($request);
    }
}
