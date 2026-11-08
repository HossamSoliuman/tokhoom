<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Livewire\Admin\Invoices;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportController extends Controller
{
    /**
     * The invoices list, with the same search and filters, as a CSV for accounting.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $query = Invoices::filtered((string) $request->query('q'), (string) $request->query('status', 'all'), (string) $request->query('month'))
            ->with('workspace')
            ->orderBy('issued_on')
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'رقم الفاتورة', 'مساحة العمل', 'الوصف', 'تاريخ الإصدار', 'المبلغ شامل الضريبة (ريال)', 'المبلغ قبل الضريبة', 'الضريبة',
                'الحالة', 'تاريخ الدفع', 'طريقة الدفع', 'ملاحظة الدفع', 'رقم عملية Tap',
            ]);

            $query->chunk(500, function ($invoices) use ($out): void {
                foreach ($invoices as $invoice) {
                    fputcsv($out, [
                        $invoice->number,
                        $invoice->workspace->name,
                        $invoice->description,
                        $invoice->issued_on->toDateString(),
                        $invoice->amount,
                        number_format($invoice->amountBeforeVat(), 2, '.', ''),
                        number_format($invoice->vatAmount(), 2, '.', ''),
                        $invoice->paid_at ? 'مدفوعة' : ($invoice->hasFailedCollection() ? 'تعثّر التحصيل' : 'مستحقة'),
                        $invoice->paid_at?->toDateString(),
                        $invoice->paymentMethod(),
                        $invoice->payment_note,
                        $invoice->tap_charge_id,
                    ]);
                }
            });

            fclose($out);
        }, 'tokhoom-invoices-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
