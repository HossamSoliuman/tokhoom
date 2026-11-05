<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchWorkspaceController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->workspaces()->whereKey($workspace->id)->exists(), 403);

        $user->switchWorkspace($workspace);

        return redirect()->route('today')->with('toast', [
            'message' => 'تم التبديل إلى '.$workspace->name,
            'kind' => 'info',
        ]);
    }
}
