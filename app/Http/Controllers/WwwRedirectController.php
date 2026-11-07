<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The wildcard DNS record also sends www.{app_domain} here. Move visitors to
 * the bare dashboard domain, keeping the path and query.
 */
class WwwRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->away($request->getScheme().'://'.config('tokhoom.app_domain').$request->getRequestUri(), 301);
    }
}
