<?php

namespace App\Http\Controllers;

use App\Enums\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show guests the landing page and send signed-in people to the right starting page.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return view('landing', ['plans' => Plan::cases()]);
        }

        if ($user->is_platform_admin && $user->memberships()->whereNull('deactivated_at')->doesntExist()) {
            return redirect()->route('admin.approvals');
        }

        return redirect()->route('today');
    }
}
