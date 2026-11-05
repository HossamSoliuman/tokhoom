<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\Arabic;
use App\Support\Reports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Title('التقارير')]
class Index extends Component
{
    use InteractsWithWorkspace;

    #[Url]
    public string $range = '30';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function boot(): void
    {
        Gate::authorize('view-reports');
    }

    public function mount(): void
    {
        $this->preset($this->range === 'custom' && $this->from && $this->to ? 'custom' : $this->range);
    }

    public function preset(string $range): void
    {
        $this->range = in_array($range, ['7', '30', 'month', 'custom'], true) ? $range : '30';

        [$from, $to] = match ($this->range) {
            '7' => [today()->subDays(6), today()],
            'month' => [today()->startOfMonth(), today()],
            'custom' => [$this->parse($this->from) ?? today()->subDays(29), $this->parse($this->to) ?? today()],
            default => [today()->subDays(29), today()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->min(today())->toDateString();
    }

    public function applyRange(string $from, string $to): void
    {
        $start = $this->parse($from);
        $end = $this->parse($to);

        if (! $start || ! $end) {
            return;
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $this->range = 'custom';
        $this->from = $start->toDateString();
        $this->to = $end->min(today())->toDateString();
    }

    private function parse(string $date): ?Carbon
    {
        try {
            return $date !== '' ? Carbon::parse($date)->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function rangeLabel(): string
    {
        $from = Carbon::parse($this->from);
        $to = Carbon::parse($this->to);

        if ($from->isSameDay($to)) {
            return Arabic::date($to);
        }

        return Arabic::date($from, ! $from->isSameYear($to)).' – '.Arabic::date($to);
    }

    public function render(): View
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $reports = new Reports($this->membership(), $from, $to);

        try {
            $data = [
                'visits' => $visits = $reports->visits(),
                'visitTotal' => array_sum($visits),
                'sources' => $reports->clientSources(),
                'funnel' => $reports->funnel(),
                'targets' => $reports->targets(),
                'agents' => $reports->agents(),
            ];
        } catch (Throwable $exception) {
            report($exception);
            $data = null;
        }

        $days = array_keys($data['visits'] ?? []);

        return view('livewire.reports.index', [
            'data' => $data,
            'axis' => $days ? array_values(array_unique([$days[0], $days[intdiv(count($days) - 1, 2)], $days[count($days) - 1]])) : [],
            'targetMonth' => Arabic::month($to->month),
        ]);
    }
}
