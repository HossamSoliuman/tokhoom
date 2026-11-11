<x-app.page title="نظرة عامة">
    @if ($counts['pending'] > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-notice-line bg-notice px-3.5 py-3">
            <i class="ti ti-certificate shrink-0 text-20 text-warning"></i>
            <span class="flex-1 text-14 leading-[22px] text-notice-ink">
                {{ Arabic::count($counts['pending'], 'طلب اعتماد واحد', 'طلبا اعتماد', 'طلبات اعتماد', 'طلب اعتماد') }} بانتظار المراجعة
            </span>
            <a href="{{ route('admin.approvals') }}" wire:navigate class="flex h-9 shrink-0 items-center rounded-lg border border-notice-action-line bg-white px-3 text-13 font-semibold text-notice-action hover:no-underline">مراجعة الطلبات</a>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <div @class(['flex flex-col gap-1.5 rounded-card border border-line bg-white p-4', 'max-lg:col-span-2' => $loop->first]) wire:key="kpi-{{ $loop->index }}">
                <span class="flex items-center gap-1.5 text-13 text-muted"><i class="ti {{ $kpi['icon'] }} text-17"></i>{{ $kpi['label'] }}</span>
                <span class="text-22 font-bold leading-[1.25]">{{ $kpi['value'] }}</span>
                <span @class([
                    'text-12',
                    'font-semibold text-success' => $kpi['tone'] === 'up',
                    'font-semibold text-danger' => $kpi['tone'] === 'down',
                    'text-muted' => $kpi['tone'] === 'neutral',
                ])>{{ $kpi['note'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-4">
        <x-ui.card class="min-w-0 flex-[2_1_440px]">
            <x-slot:heading>
                <h2 class="m-0 text-16 font-bold">الإيراد المحصّل</h2>
                <span class="text-18 font-bold">{{ Arabic::money(array_sum($revenue)) }}</span>
            </x-slot:heading>
            <x-month-chart :values="$revenue" :money="true" />
        </x-ui.card>

        <x-ui.card title="مساحات العمل" class="min-w-0 flex-[1_1_280px] gap-1" :count="array_sum($counts)">
            @foreach (\App\Enums\WorkspaceStatus::cases() as $status)
                <a href="{{ route('admin.workspaces', ['status' => $status->value]) }}" wire:navigate wire:key="status-{{ $status->value }}"
                   class="-mx-2 flex h-11 items-center justify-between rounded-field px-2 text-14 text-ink hover:bg-canvas hover:no-underline">
                    <span class="inline-flex h-[26px] items-center rounded-full px-2.5 text-13 font-semibold {{ $status->pillClasses() }}">{{ $status->label() }}</span>
                    <span class="flex items-center gap-1.5 font-semibold">{{ Arabic::number($counts[$status->value]) }}<i class="ti ti-chevron-left text-16 text-subtle"></i></span>
                </a>
            @endforeach
        </x-ui.card>
    </div>

    <div class="flex flex-wrap gap-4">
        <x-ui.card class="min-w-0 flex-[2_1_440px]">
            <x-slot:heading>
                <h2 class="m-0 text-16 font-bold">التسجيلات الجديدة</h2>
                <span class="text-18 font-bold">{{ Arabic::number(array_sum($signups)) }}</span>
            </x-slot:heading>
            <x-month-chart :values="$signups" unit=" تسجيل" />
        </x-ui.card>

        <x-ui.card title="الخطط" class="min-w-0 flex-[1_1_280px] gap-4">
            @foreach ($planMix as $row)
                <div class="flex flex-col gap-1.5" wire:key="plan-{{ $row['plan']->value }}">
                    <div class="flex justify-between text-14">
                        <span>{{ $row['plan']->label() }}</span>
                        <span class="font-semibold">{{ Arabic::number($row['count']) }} <span class="font-normal text-subtle">· {{ Arabic::percent($row['percent']) }}</span></span>
                    </div>
                    <x-ui.meter :percent="$row['percent']" />
                </div>
            @endforeach
        </x-ui.card>
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-2">
        <x-ui.card title="تحتاج متابعة" flush :count="count($attention)">
            @forelse ($attention as $item)
                <a href="{{ $item['url'] }}" wire:navigate wire:key="attention-{{ $loop->index }}"
                   class="flex items-center gap-3 border-t border-divider px-4 py-3 text-ink hover:bg-canvas hover:no-underline">
                    <span @class([
                        'flex size-9 shrink-0 items-center justify-center rounded-full',
                        'bg-danger-soft text-danger' => $item['tone'] === 'danger',
                        'bg-warning-soft text-warning' => $item['tone'] === 'warning',
                    ])><i class="ti {{ $item['icon'] }} text-18"></i></span>
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-14 font-semibold">{{ $item['text'] }}</span>
                        <span class="truncate text-12 text-muted">{{ $item['meta'] }}</span>
                    </span>
                    <i class="ti ti-chevron-left text-16 text-subtle"></i>
                </a>
            @empty
                <div class="flex items-center gap-3 border-t border-divider px-4 py-4 text-14 text-body">
                    <i class="ti ti-checks text-20 text-success"></i>لا شيء يحتاج متابعة الآن.
                </div>
            @endforelse
        </x-ui.card>

        <x-ui.card title="أحدث التسجيلات" flush>
            @forelse ($recent as $workspace)
                @php($state = $workspace->status())
                @php($owner = $workspace->memberships->first()?->user)
                <a href="{{ route('admin.workspaces.show', $workspace) }}" wire:navigate wire:key="recent-{{ $workspace->id }}"
                   class="flex items-center gap-3 border-t border-divider px-4 py-3 text-ink hover:bg-canvas hover:no-underline">
                    <x-ui.avatar :initial="$workspace->initial()" :size="36" square />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-14 font-semibold">{{ $workspace->name }}</span>
                        <span class="truncate text-12 text-muted">{{ $workspace->kind->label($owner?->gender) }} · {{ $workspace->city }} · {{ Arabic::ago($workspace->created_at) }}</span>
                    </span>
                    <span class="inline-flex h-6 shrink-0 items-center rounded-full px-2 text-12 font-semibold {{ $state->pillClasses() }}">{{ $state->label() }}</span>
                </a>
            @empty
                <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد تسجيلات بعد.</p>
            @endforelse
        </x-ui.card>
    </div>

    <x-ui.card title="آخر إجراءات الإدارة" flush>
        <x-slot:heading>
            <a href="{{ route('admin.log') }}" wire:navigate class="text-13 font-semibold">السجل كاملاً</a>
        </x-slot:heading>
        @forelse ($actions as $entry)
            <div class="flex items-center gap-3 border-t border-divider px-4 py-3" wire:key="action-{{ $entry->id }}">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-divider text-body"><i class="ti {{ $entry->icon() }} text-16"></i></span>
                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-14">
                        @if ($entry->workspace)
                            <a href="{{ route('admin.workspaces.show', $entry->workspace) }}" wire:navigate class="font-semibold text-ink">{{ $entry->workspace->name }}</a> ·
                        @elseif ($entry->user)
                            <a href="{{ route('admin.users.show', $entry->user) }}" wire:navigate class="font-semibold text-ink">{{ $entry->user->name }}</a> ·
                        @endif
                        {{ $entry->text() }}
                    </span>
                    <span class="text-12 text-muted">{{ $entry->admin?->name ?? 'النظام' }} · {{ Arabic::ago($entry->created_at) }}</span>
                </span>
            </div>
        @empty
            <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد إجراءات بعد.</p>
        @endforelse
    </x-ui.card>
</x-app.page>
