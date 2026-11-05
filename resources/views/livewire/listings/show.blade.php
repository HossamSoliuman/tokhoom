<x-app.page :title="$listing->title" :back="route('listings.index')" back-label="العقارات">
    <div class="flex flex-wrap items-center gap-4 rounded-card border border-line bg-white p-3.5">
        <x-ui.photo :photo="$listing->coverPhoto" class="h-[84px] w-[120px] shrink-0 rounded-field" />
        <div class="flex min-w-0 flex-[1_1_220px] flex-col gap-1.5">
            <div class="flex flex-wrap items-center gap-2">
                <x-listing-status :status="$listing->status" class="h-7" />
                <span class="text-17 font-bold text-primary">{{ $listing->priceLabel() }}</span>
            </div>
            <span class="text-13 text-muted">
                {{ $listing->agent->name }}
                · ترخيص الإعلان {{ $listing->ad_license_number ?? '—' }}{{ $listing->isAdLicenseExpired() ? ' (منتهٍ)' : '' }}
                @if ($listing->published_at)
                    · نُشر {{ Arabic::date($listing->published_at) }}
                @endif
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" size="md" icon="ti-share" class="px-3.5 text-14" wire:click="$dispatch('open-share', { listingId: {{ $listing->id }} })">مشاركة</x-ui.button>
            <x-ui.button variant="secondary" size="md" icon="ti-pencil" class="px-3.5 text-14" :href="route('listings.edit', $listing)" wire:navigate>تعديل</x-ui.button>
            @if ($listing->isLive())
                <x-ui.button size="md" icon="ti-external-link" class="px-3.5 text-14" :href="$listing->publicUrl()" target="_blank" rel="noopener">عرض في الموقع</x-ui.button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-[repeat(auto-fit,minmax(140px,1fr))]">
        @foreach ($kpis as $kpi)
            <div class="flex flex-col gap-1 rounded-card border border-line bg-white p-3.5">
                <span class="flex items-center gap-1.5 text-13 text-muted"><i class="ti {{ $kpi['icon'] }} text-17"></i>{{ $kpi['label'] }}</span>
                <span class="text-24 font-bold leading-[1.25]">{{ $kpi['value'] }}</span>
                <span @class([
                    'text-12 font-semibold',
                    'text-success' => $kpi['tone'] === 'up',
                    'text-danger' => $kpi['tone'] === 'down',
                    'text-muted' => $kpi['tone'] === 'neutral',
                ])>{{ $kpi['note'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-start gap-4">
        <div class="flex min-w-0 flex-[2_1_460px] flex-col gap-4">
            <x-ui.card>
                <x-slot:heading>
                    <h2 class="m-0 text-16 font-bold">الأداء · آخر 30 يوماً</h2>
                    <div class="flex gap-0.5 rounded-field border border-line-strong p-[3px]">
                        @foreach (['views' => 'المشاهدات', 'whatsapp' => 'واتساب', 'inquiries' => 'الاستفسارات'] as $value => $label)
                            <button type="button" wire:click="$set('metric', '{{ $value }}')" @class([
                                'h-[34px] rounded-[7px] px-3 text-13 font-semibold',
                                'bg-primary text-white' => $metric === $value,
                                'text-ink hover:bg-canvas' => $metric !== $value,
                            ])>{{ $label }}</button>
                        @endforeach
                    </div>
                </x-slot:heading>
                <x-bar-chart :values="$series" height="h-[170px]" />
                <div class="flex justify-between text-12 text-subtle">
                    @foreach ($chartLabels as $date)
                        <span>{{ Arabic::date($date, false) }}</span>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card title="الاستفسارات على هذا العقار" flush>
                @forelse ($inquiries as $inquiry)
                    @php($stage = $stages->get($inquiry->client_id) ?? \App\Enums\DealStage::New)
                    <div class="flex flex-wrap items-center gap-3 border-t border-divider px-4 py-3" wire:key="inquiry-{{ $inquiry->id }}">
                        <x-ui.avatar :initial="Arabic::initial($inquiry->name)" :size="38" />
                        <div class="flex min-w-0 flex-[1_1_160px] flex-col gap-0.5">
                            @if ($inquiry->client_id)
                                <a href="{{ route('clients.show', $inquiry->client_id) }}" wire:navigate class="text-15 font-semibold text-ink">{{ $inquiry->name }}</a>
                            @else
                                <span class="text-15 font-semibold">{{ $inquiry->name }}</span>
                            @endif
                            <span class="text-12 text-muted">
                                {{ $inquiry->source->label() }} · {{ Arabic::ago($inquiry->created_at) }}
                                · {{ $inquiry->responded_at ? 'رُدّ خلال '.Arabic::minutesShort($inquiry->waitMinutes()) : 'بانتظار الرد' }}
                            </span>
                        </div>
                        <span class="flex h-[26px] items-center rounded-full px-2.5 text-12 font-semibold {{ $stage->pillClasses() }}">{{ $stage === \App\Enums\DealStage::New ? 'جديد' : $stage->label() }}</span>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد استفسارات على هذا العقار بعد.</p>
                @endforelse
            </x-ui.card>
        </div>

        <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-4">
            <x-ui.card title="مصادر الزيارات">
                @foreach ($sources as $row)
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between text-14">
                            <span>{{ $row['source']->trafficLabel() }}</span>
                            <span class="font-semibold">{{ Arabic::number($row['count']) }} <span class="font-normal text-subtle">· {{ $row['percent'] }}%</span></span>
                        </div>
                        <x-ui.meter :percent="$row['percent']" :color="$row['source']->colorClass()" />
                    </div>
                @endforeach
            </x-ui.card>

            <x-ui.card class="gap-3">
                <x-slot:heading>
                    <h2 class="m-0 text-16 font-bold">جودة الإعلان</h2>
                    <span class="text-15 font-bold {{ $quality->scoreClass() }}">{{ $quality->score() }} / 100</span>
                </x-slot:heading>
                @foreach ($quality->checks as $check)
                    <div class="flex items-start gap-2.5">
                        <i @class([
                            'ti mt-px shrink-0 text-[19px]',
                            'ti-circle-check text-success' => $check['state'] === 'pass',
                            'ti-alert-circle text-warning-dot' => $check['state'] === 'warn',
                            'ti-circle-x text-danger' => $check['state'] === 'fail',
                        ])></i>
                        <span class="flex-1 text-14 leading-[22px]">{{ $check['text'] }}</span>
                    </div>
                @endforeach
            </x-ui.card>

            <x-ui.card title="السجل" class="gap-1">
                <x-timeline :items="$history" size="sm" class="mt-2" />
            </x-ui.card>
        </div>
    </div>

    <livewire:listings.share-dialog />
</x-app.page>
