<?php

namespace App\Http\Controllers;

use App\Support\Arabic;
use App\Support\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Agent performance for the chosen range as a CSV that opens in Excel.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();
        $reports = new Reports($request->user()->currentMembership(), $from, $to);
        $filename = 'tokhoom-report-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($reports, $from, $to): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['تقرير أداء الوسطاء', Arabic::date($from).' – '.Arabic::date($to)]);
            fputcsv($out, []);
            fputcsv($out, ['الوسيط', 'عقارات مضافة', 'عقارات نشطة', 'المشاهدات', 'العملاء', 'متوسط زمن الرد (دقيقة)', 'متابعات منجزة', 'متابعات مستحقة', 'صفقات ناجحة', 'العمولة (ريال)']);

            foreach ($reports->agents() as $row) {
                fputcsv($out, [
                    $row['name'], $row['added'], $row['active'], $row['views'], $row['clients'],
                    $row['response'] ?? '', $row['followUpsDone'], $row['followUps'], $row['won'], $row['commission'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['العملاء حسب المصدر']);

            foreach ($reports->clientSources() as $row) {
                fputcsv($out, [$row['source']->label(), $row['count']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
