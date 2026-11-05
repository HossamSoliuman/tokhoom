<x-app.page title="التقارير">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="ti-download" :href="route('reports.export', ['from' => $from, 'to' => $to])">تصدير</x-ui.button>
    </x-slot:actions>

    <div class="relative flex flex-wrap items-center gap-2" x-data="{ calendar: false }" x-on:keydown.escape="calendar = false">
        @foreach (['7' => 'آخر 7 أيام', '30' => 'آخر 30 يوماً', 'month' => 'هذا الشهر'] as $value => $label)
            <x-ui.chip :active="$range === (string) $value" class="h-10" wire:click="preset('{{ $value }}')">{{ $label }}</x-ui.chip>
        @endforeach
        <button type="button" x-on:click="calendar = ! calendar"
                :class="calendar ? 'border-primary' : 'border-line-strong'"
                class="flex h-10 items-center gap-2 rounded-field border bg-white px-3.5 text-14 font-semibold text-ink">
            <i class="ti ti-calendar text-18"></i>{{ $this->rangeLabel() }}
        </button>

        <div x-cloak x-show="calendar" x-on:click.outside="calendar = false" wire:key="calendar-{{ $from }}-{{ $to }}"
             x-data="rangeCalendar({ from: @js($from), to: @js($to), today: @js(today()->toDateString()) })"
             class="absolute right-0 top-12 z-[14] flex w-[328px] max-w-[calc(100vw-32px)] flex-col gap-3 rounded-card border border-line bg-white p-4 shadow-pop">
            <div class="flex items-center justify-between">
                <button type="button" x-on:click="previousMonth()" class="flex size-9 items-center justify-center rounded-lg text-ink hover:bg-canvas" aria-label="الشهر السابق"><i class="ti ti-chevron-right"></i></button>
                <span class="text-15 font-bold" x-text="title"></span>
                <button type="button" x-on:click="nextMonth()" :disabled="! canGoForward" class="flex size-9 items-center justify-center rounded-lg text-ink hover:bg-canvas disabled:text-faint" aria-label="الشهر التالي"><i class="ti ti-chevron-left"></i></button>
            </div>
            <div class="grid grid-cols-7 gap-0.5 text-center">
                @foreach (Arabic::WEEKDAYS as $weekday)
                    <span class="flex h-7 items-center justify-center text-12 text-subtle">{{ $weekday }}</span>
                @endforeach
                <template x-for="cell in cells" :key="cell.key">
                    <button type="button" x-on:click="pick(cell)" :disabled="cell.blank || cell.future"
                            :class="{
                                'invisible': cell.blank,
                                'bg-primary text-white': cell.edge && cell.inRange,
                                'bg-primary-soft': cell.inRange && ! cell.edge,
                                'text-faint': cell.future,
                                'font-bold': cell.today,
                            }"
                            class="h-[38px] rounded-lg p-0 text-14" x-text="cell.day"></button>
                </template>
            </div>
            <div class="flex items-center justify-between border-t border-divider pt-3">
                <span class="text-13 text-body" x-text="label"></span>
                <x-ui.button size="xs" class="rounded-lg px-3.5 text-14" x-on:click="apply(); calendar = false">تطبيق</x-ui.button>
            </div>
        </div>
    </div>

    @if (! $data)
        <x-ui.error-state />
    @else
        <div class="flex flex-wrap gap-4">
            <x-ui.card class="min-w-0 flex-[2_1_460px]">
                <x-slot:heading>
                    <h2 class="m-0 text-16 font-bold">زيارات الموقع</h2>
                    <span class="text-20 font-bold">{{ Arabic::number($data['visitTotal']) }}</span>
                </x-slot:heading>
                <x-bar-chart :values="$data['visits']" unit=" زيارة" />
                <div class="flex justify-between text-12 text-subtle">
                    @foreach ($axis as $day)
                        <span>{{ Arabic::date(\Illuminate\Support\Carbon::parse($day), false) }}</span>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="العملاء حسب المصدر" class="min-w-0 flex-[1_1_300px] gap-4">
                @foreach ($data['sources'] as $row)
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between text-14">
                            <span>{{ $row['source']->label() }}</span>
                            <span class="font-semibold">{{ $row['count'] }} <span class="font-normal text-subtle">· {{ $row['percent'] }}%</span></span>
                        </div>
                        <x-ui.meter :percent="$row['percent']" :color="$row['source']->colorClass()" />
                    </div>
                @endforeach
            </x-ui.card>
        </div>

        <div class="flex flex-wrap items-start gap-4">
            <x-ui.card title="مسار العملاء" class="min-w-0 flex-[1_1_380px] gap-3">
                <x-funnel :steps="$data['funnel']" rate-suffix="من السابق" />
            </x-ui.card>

            <x-ui.card :title="'أهداف '.$targetMonth" class="min-w-0 flex-[1_1_380px] gap-4">
                @forelse ($data['targets'] as $target)
                    <div class="flex flex-col gap-2">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-14 font-semibold">{{ $target['name'] }}</span>
                            <span class="text-13 text-body">{{ Arabic::number($target['earned']) }} / {{ Arabic::number($target['target']) }} ريال</span>
                        </div>
                        <x-ui.meter :percent="max(2, $target['percent'])" :color="$target['color']" />
                        <div class="flex justify-between text-12 text-muted">
                            <span>{{ Arabic::percent($target['percent']) }} من هدف العمولة</span>
                            <span>{{ $target['won'] }} من {{ $target['dealsTarget'] }} صفقات</span>
                        </div>
                    </div>
                @empty
                    <p class="m-0 text-14 text-muted">لا توجد أهداف لهذا الشهر.</p>
                @endforelse
            </x-ui.card>
        </div>

        <x-ui.card title="أداء الوسطاء" flush class="overflow-hidden">
            <div class="overflow-x-auto">
                <div class="min-w-[1000px]">
                    <div class="grid h-[52px] grid-cols-[1.6fr_repeat(8,1fr)] items-center bg-head px-4 text-13 font-semibold leading-[18px] text-muted">
                        <span>الوسيط</span><span>عقارات مضافة</span><span>عقارات نشطة</span><span>المشاهدات</span><span>العملاء</span><span>متوسط زمن الرد</span><span>متابعات منجزة</span><span>صفقات ناجحة</span><span>العمولة</span>
                    </div>
                    @forelse ($data['agents'] as $row)
                        <div @class([
                            'grid h-[52px] grid-cols-[1.6fr_repeat(8,1fr)] items-center border-t border-divider px-4 text-14',
                            'bg-canvas font-bold' => $row['total'],
                        ])>
                            <span>{{ $row['name'] }}</span>
                            <span>{{ $row['added'] }}</span>
                            <span>{{ $row['active'] }}</span>
                            <span>{{ Arabic::number($row['views']) }}</span>
                            <span>{{ $row['clients'] }}</span>
                            <span @class([
                                'text-danger' => ! $row['total'] && $row['response'] !== null && $row['response'] > 30,
                                'text-warning' => ! $row['total'] && $row['response'] !== null && $row['response'] > 15 && $row['response'] <= 30,
                            ])>{{ $row['response'] !== null ? Arabic::minutesShort($row['response']) : '—' }}</span>
                            <span>{{ $row['followUps'] ? $row['followUpsDone'].' / '.$row['followUps'] : '—' }}</span>
                            <span>{{ $row['won'] }}</span>
                            <span>{{ $row['commission'] ? Arabic::money($row['commission']) : '—' }}</span>
                        </div>
                    @empty
                        <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا يوجد نشاط في هذه الفترة.</p>
                    @endforelse
                </div>
            </div>
        </x-ui.card>
    @endif
</x-app.page>
