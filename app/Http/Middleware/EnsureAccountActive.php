<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out someone whose account a platform admin blocked, on their next request.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->flash('toast', ['message' => 'أُوقف حسابك. تواصل مع دعم تخوم.', 'kind' => 'err']);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
