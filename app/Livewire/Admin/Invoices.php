<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasDialogs;
use App\Livewire\Concerns\RecordsManualPayments;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Invoice;
use App\Support\Arabic;
use App\Support\Subscriptions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Subscription invoices across every workspace: what was collected, what is
 * still owed and where card renewals failed.
 */
#[Title('الفواتير')]
class Invoices extends Component
{
    use HasDialogs, RecordsManualPayments, ShowsToasts;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $month = '';

    public int $limit = 30;

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function updated(): void
    {
        $this->limit = 30;
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'month');
    }

    public function showMore(): void
    {
        $this->limit += 30;
    }

    /**
     * Invoices matching the search, status and issue month.
     *
     * @return Builder<Invoice>
     */
    public static function filtered(string $search, string $status, string $month): Builder
    {
        $search = trim($search);
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay() : null;

        return Invoice::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('number', 'like', '%'.$search.'%')
                ->orWhereHas('workspace', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))))
            ->when($month, fn (Builder $query) => $query->whereBetween('issued_on', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()]))
            ->when($status === 'open', fn (Builder $query) => $query->whereNull('paid_at'))
            ->when($status === 'failed', fn (Builder $query) => $query->whereNull('paid_at')->where('payment_attempts', '>=', Subscriptions::maxAutomaticAttempts()))
            ->when($status === 'paid', fn (Builder $query) => $query->whereNotNull('paid_at'));
    }

    /**
     * @return list<array{label: string, value: string, note: string, tone: string}>
     */
    private function summary(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonthNoOverflow();
        $collected = (int) Invoice::where('paid_at', '>=', $thisMonth)->sum('amount');
        $collectedBefore = (int) Invoice::whereBetween('paid_at', [$lastMonth, $thisMonth->copy()->subSecond()])->sum('amount');
        $open = Invoice::whereNull('paid_at');

        return [
            [
                'label' => 'المحصّل هذا الشهر',
                'value' => Arabic::money($collected),
                'note' => 'الشهر الماضي '.Arabic::money($collectedBefore),
                'tone' => 'neutral',
            ],
            [
                'label' => 'بانتظار الدفع',
                'value' => Arabic::money((int) (clone $open)->sum('amount')),
                'note' => Arabic::count((clone $open)->count(), 'فاتورة واحدة', 'فاتورتان', 'فواتير', 'فاتورة'),
                'tone' => 'neutral',
            ],
            [
                'label' => 'تعثّر التحصيل',
                'value' => Arabic::number((clone $open)->where('payment_attempts', '>=', Subscriptions::maxAutomaticAttempts())->count()),
                'note' => 'بعد '.Arabic::count(Subscriptions::maxAutomaticAttempts(), 'محاولة واحدة', 'محاولتين', 'محاولات', 'محاولة').' على البطاقة',
                'tone' => 'danger',
            ],
            [
                'label' => 'صدرت هذا الشهر',
                'value' => Arabic::number(Invoice::where('issued_on', '>=', $thisMonth->toDateString())->count()),
                'note' => Arabic::money((int) Invoice::where('issued_on', '>=', $thisMonth->toDateString())->sum('amount')),
                'tone' => 'neutral',
            ],
        ];
    }

    public function render(): View
    {
        $query = self::filtered($this->search, $this->status, $this->month)
            ->with('workspace')
            ->latest('issued_on')
            ->latest('id');

        return view('livewire.admin.invoices', [
            'invoices' => (clone $query)->limit($this->limit)->get(),
            'total' => $query->count(),
            'summary' => $this->summary(),
            'months' => collect(range(0, 11))->map(fn (int $back) => now()->startOfMonth()->subMonthsNoOverflow($back))
                ->mapWithKeys(fn (Carbon $date) => [$date->format('Y-m') => Arabic::month($date->month).' '.$date->year]),
            'payingInvoice' => $this->payingInvoice(),
        ]);
    }
}
