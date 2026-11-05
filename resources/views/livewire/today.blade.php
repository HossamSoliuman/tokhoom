<x-app.page title="اليوم">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="hidden lg:inline-flex">إضافة عقار</x-ui.button>
    </x-slot:actions>

    @if ($isEmpty)
        <x-ui.empty icon="ti-sun" title="لا شيء لليوم" body="لا توجد متابعات أو استفسارات جديدة. أضف عقاراً لتبدأ.">
            <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="px-5">إضافة عقار</x-ui.button>
        </x-ui.empty>
    @else
        @if ($warnings)
            <div class="flex flex-col gap-2">
                @foreach ($warnings as $warning)
                    <div class="flex items-center gap-3 rounded-xl border border-notice-line bg-notice px-3.5 py-3" wire:key="warning-{{ $loop->index }}">
                        <i class="ti ti-alert-triangle shrink-0 text-20 text-warning"></i>
                        <span class="flex-1 text-14 leading-[22px] text-notice-ink">{{ $warning['text'] }}</span>
                        @if ($warning['cta'])
                            <a href="{{ $warning['url'] }}" wire:navigate class="flex h-9 shrink-0 items-center rounded-lg border border-notice-action-line bg-white px-3 text-13 font-semibold text-notice-action hover:no-underline">{{ $warning['cta'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <h2 class="m-0 text-17 font-bold">آخر 7 أيام</h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-[repeat(auto-fit,minmax(150px,1fr))]">
                @foreach ($kpis as $kpi)
                    <div class="flex flex-col gap-1.5 rounded-card border border-line bg-white p-4">
                        <span class="flex items-center gap-1.5 text-13 text-muted"><i class="ti {{ $kpi['icon'] }} text-17"></i>{{ $kpi['label'] }}</span>
                        <span class="text-26 font-bold leading-[1.2]">{{ $kpi['value'] }}</span>
                        <span @class(['text-13 font-semibold', 'text-success' => $kpi['up'], 'text-danger' => ! $kpi['up']])>{{ $kpi['change'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-[repeat(auto-fit,minmax(340px,1fr))]">
            <x-ui.card title="بانتظار الرد" flush>
                <div class="mx-4 mb-2 grid grid-cols-2 rounded-xl border border-divider">
                    <div class="flex flex-col gap-0.5 border-l border-divider px-3 py-2.5">
                        <span class="text-12 text-muted">متوسط زمن الرد</span>
                        <span class="text-18 font-bold">{{ $response['average'] !== null ? Arabic::countWithNumber($response['average'], 'دقيقة', 'دقائق', 'دقيقة') : '—' }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5 px-3 py-2.5">
                        <span class="text-12 text-muted">رُدّ عليها خلال {{ \App\Models\Inquiry::RESPONSE_TARGET_MINUTES }} دقيقة</span>
                        <span class="text-18 font-bold">{{ $response['onTime'] !== null ? Arabic::percent($response['onTime']) : '—' }}</span>
                    </div>
                </div>
                @forelse ($awaiting as $inquiry)
                    @php($wait = $inquiry->waitMinutes())
                    <div class="flex items-center gap-3 border-t border-divider px-4 py-3" wire:key="awaiting-{{ $inquiry->id }}">
                        <span @class([
                            'flex h-[30px] min-w-14 shrink-0 items-center justify-center gap-1 rounded-full px-2 text-13 font-bold',
                            'bg-danger-soft text-danger' => $wait > 30,
                            'bg-warning-soft text-warning' => $wait > 15 && $wait <= 30,
                            'bg-success-soft text-success' => $wait <= 15,
                        ])>
                            <i class="ti ti-clock text-14"></i>{{ $wait < 600 ? Arabic::minutesShort($wait) : Arabic::ago($inquiry->created_at) }}
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="text-15 font-semibold">{{ $inquiry->name }}</span>
                            <span class="truncate text-13 text-muted">{{ $inquiry->listing?->title ?? 'استفسار عام' }} · {{ $inquiry->agent?->name ?? 'غير مسند' }}</span>
                        </div>
                        <x-ui.button variant="whatsapp" size="sm" icon="ti-brand-whatsapp" class="px-3 text-13"
                                     :href="$inquiry->replyUrl($workspaceName)"
                                     target="_blank" rel="noopener"
                                     wire:click="markResponded({{ $inquiry->id }})">رد</x-ui.button>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد استفسارات بانتظار الرد.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="مسار العملاء · آخر 30 يوماً" class="gap-3">
                <x-funnel :steps="$funnel" />
            </x-ui.card>

            <x-ui.card title="صحة العقارات" flush>
                @foreach ($health as $row)
                    <a href="{{ $row['url'] }}" wire:navigate class="flex items-center gap-3 border-t border-divider px-4 py-3 text-start text-ink hover:bg-canvas hover:no-underline">
                        <span @class([
                            'flex size-9 shrink-0 items-center justify-center rounded-field',
                            'bg-danger-soft text-danger' => $row['tone'] === 'danger',
                            'bg-warning-soft text-warning' => $row['tone'] === 'warning',
                            'bg-divider text-body' => $row['tone'] === 'neutral',
                        ])><i class="ti {{ $row['icon'] }} text-[19px]"></i></span>
                        <span class="flex-1 text-14">{{ $row['text'] }}</span>
                        <span class="text-15 font-bold">{{ $row['count'] }}</span>
                        <i class="ti ti-chevron-left text-subtle"></i>
                    </a>
                @endforeach
            </x-ui.card>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-[repeat(auto-fit,minmax(340px,1fr))]">
            <x-ui.card title="متابعات اليوم" flush :count="$remindersLeft">
                @forelse ($reminders as $reminder)
                    @php($done = $reminder->isDone())
                    <div class="flex items-center gap-3 border-t border-divider px-4 py-3" wire:key="reminder-{{ $reminder->id }}">
                        <button type="button" wire:click="toggleReminder({{ $reminder->id }})" aria-label="{{ $done ? 'إلغاء الإنجاز' : 'تم الإنجاز' }}">
                            <x-ui.checkbox :checked="$done" />
                        </button>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span @class(['text-15 font-medium', 'text-subtle line-through' => $done])>{{ $reminder->title }}</span>
                            <span class="text-13 text-muted">{{ $reminder->due_at->isToday() ? Arabic::time($reminder->due_at) : Arabic::dayAndTime($reminder->due_at) }} · {{ $reminder->client->name }}</span>
                        </div>
                        <a href="{{ $reminder->channel === \App\Enums\ReminderChannel::Whatsapp ? Phone::whatsappUrl($reminder->client->mobile) : Phone::telUrl($reminder->client->mobile) }}"
                           @if ($reminder->channel === \App\Enums\ReminderChannel::Whatsapp) target="_blank" rel="noopener" @endif
                           class="flex size-10 shrink-0 items-center justify-center rounded-field border border-line bg-white text-ink hover:bg-canvas"
                           aria-label="{{ $reminder->channel->label() }}">
                            <i class="ti {{ $reminder->channel->icon() }} text-18"></i>
                        </a>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد متابعات اليوم.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="استفسارات جديدة" flush :count="$newInquiries" count-tone="brand">
                @forelse ($inquiries as $inquiry)
                    <div class="flex items-center gap-3 border-t border-divider px-4 py-3" wire:key="inquiry-{{ $inquiry->id }}">
                        <x-ui.avatar :initial="Arabic::initial($inquiry->name)" :size="40" />
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            @if ($inquiry->client_id)
                                <a href="{{ route('clients.show', $inquiry->client_id) }}" wire:navigate class="text-15 font-semibold text-ink">{{ $inquiry->name }}</a>
                            @else
                                <span class="text-15 font-semibold">{{ $inquiry->name }}</span>
                            @endif
                            <span class="truncate text-13 text-muted">{{ $inquiry->listing?->title ?? 'استفسار عام' }}</span>
                            <span class="text-12 text-subtle">{{ $inquiry->source->label() }} · {{ Arabic::ago($inquiry->created_at) }}</span>
                        </div>
                        <a href="{{ $inquiry->replyUrl($workspaceName) }}" target="_blank" rel="noopener" wire:click="markResponded({{ $inquiry->id }})"
                           class="flex size-10 shrink-0 items-center justify-center rounded-field bg-whatsapp text-white" aria-label="واتساب">
                            <i class="ti ti-brand-whatsapp text-20"></i>
                        </a>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد استفسارات هذا الأسبوع.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="تطابقات" flush>
                @forelse ($matches as $match)
                    <div class="flex items-center gap-3 border-t border-divider px-4 py-3" wire:key="match-{{ $match['client']->id }}-{{ $match['listing']->id }}">
                        <x-ui.photo :photo="$match['listing']->coverPhoto" label="" class="h-11 w-14 shrink-0 rounded-lg" />
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <a href="{{ route('listings.show', $match['listing']) }}" wire:navigate class="truncate text-14 font-semibold text-ink">{{ $match['listing']->title }}</a>
                            <span class="text-13 text-muted">يطابق طلب {{ $match['client']->name }} · {{ $match['listing']->priceLabel() }}</span>
                        </div>
                        <x-ui.button variant="secondary" size="sm" icon="ti-send" class="px-3 text-13"
                                     :href="Phone::whatsappUrl($match['client']->mobile, $match['listing']->pitchFor($match['client']->name))"
                                     target="_blank" rel="noopener"
                                     wire:click="markSent({{ $match['client']->id }}, {{ $match['listing']->id }})">إرسال</x-ui.button>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد تطابقات جديدة.</p>
                @endforelse
            </x-ui.card>
        </div>
    @endif
</x-app.page>
