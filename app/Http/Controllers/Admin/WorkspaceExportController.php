<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberRole;
use App\Enums\WorkspaceKind;
use App\Enums\WorkspaceStatus;
use App\Http\Controllers\Controller;
use App\Livewire\Admin\Workspaces\Index;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceExportController extends Controller
{
    /**
     * The workspaces list, with the same search and filters, as a CSV that opens in Excel.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $status = WorkspaceStatus::tryFrom((string) $request->query('status'));

        $query = Index::filtered((string) $request->query('q'), WorkspaceKind::tryFrom((string) $request->query('kind')))
            ->when($status, fn ($query) => $query->inStatus($status))
            ->with(['memberships' => fn ($query) => $query->where('role', MemberRole::Owner)->with('user')])
            ->withCount(['memberships as members_count' => fn ($query) => $query->whereNull('deactivated_at'), 'listings'])
            ->withExists(['invoices as has_open_invoice' => fn ($query) => $query->whereNull('paid_at')])
            ->orderBy('name');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'الاسم', 'النوع', 'المدينة', 'العنوان', 'المالك', 'الجوال', 'البريد', 'رخصة فال', 'السجل التجاري',
                'انتهاء الترخيص', 'الحالة', 'الخطة', 'المقاعد', 'الأعضاء', 'العقارات', 'الاشتراك الشهري (ريال)', 'تاريخ التسجيل',
            ]);

            $query->chunk(200, function ($workspaces) use ($out): void {
                foreach ($workspaces as $workspace) {
                    /** @var Workspace $workspace */
                    $owner = $workspace->memberships->first()?->user;

                    fputcsv($out, [
                        $workspace->name,
                        $workspace->kind->label($owner?->gender),
                        $workspace->city,
                        $workspace->siteHost(),
                        $owner?->name,
                        $owner?->mobile,
                        $owner?->email,
                        $workspace->fal_number,
                        $workspace->cr_number,
                        $workspace->license_expires_on->toDateString(),
                        $workspace->status()->label(),
                        $workspace->plan->label(),
                        $workspace->paidSeats(),
                        $workspace->members_count,
                        $workspace->listings_count,
                        $workspace->monthlyTotal(),
                        $workspace->created_at->toDateString(),
                    ]);
                }
            });

            fclose($out);
        }, 'tokhoom-workspaces-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
