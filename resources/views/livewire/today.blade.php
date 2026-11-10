<x-app.page :title="$greeting">
    <x-slot:actions>
        <x-ui.button icon="ti-plus" size="sm" :href="route('listings.create')" wire:navigate class="max-lg:hidden">إضافة عقار</x-ui.button>
    </x-slot:actions>

    @if ($isEmpty)
        <x-ui.empty icon="ti-sun" title="لا شيء لليوم" body="لا توجد متابعات أو استفسارات جديدة. أضف عقاراً لتبدأ.">
            <x-ui.button icon="ti-plus" :href="route('listings.create')" wire:navigate class="px-5">إضافة عقار</x-ui.button>
        </x-ui.empty>
    @else
        <div class="grid items-start gap-3.5 lg:grid-cols-[1.4fr_1fr]">
            <x-ui.card title="مهام اليوم" :count="$openTasks" count-tone="brand" class="gap-1">
                <div class="flex flex-col">
                    @foreach ($warnings as $warning)
                        <x-app.task tone="bad" kind="ترخيص" :title="$warning['text']" wire:key="warning-{{ $loop->index }}">
                            @if ($warning['cta'])
                                <x-ui.button size="xs" :href="$warning['url']" wire:navigate>{{ $warning['cta'] }}</x-ui.button>
                            @endif
                        </x-app.task>
                    @endforeach

                    @foreach ($awaiting as $inquiry)
                        @php($wait = $inquiry->waitMinutes())
                        <x-app.task :tone="$wait > 30 ? 'bad' : 'warn'" kind="استفسار" wire:key="awaiting-{{ $inquiry->id }}"
                                    :title="$inquiry->name.' — '.($inquiry->listing?->title ?? 'استفسار عام')"
                                    :meta="'بانتظار الرد منذ '.($wait < 600 ? Arabic::minutesShort($wait) : Arabic::ago($inquiry->created_at)).' · '.($inquiry->agent?->name ?? 'غير مسند')">
                            <x-ui.button variant="whatsapp" size="xs" icon="ti-brand-whatsapp"
                                         :href="$inquiry->replyUrl($workspaceName)" target="_blank" rel="noopener"
                                         wire:click="markResponded({{ $inquiry->id }})">رد</x-ui.button>
                        </x-app.task>
                    @endforeach

                    @foreach ($reminders as $reminder)
                        @php($done = $reminder->isDone())
                        <x-app.task :tone="$reminder->due_at->isPast() ? 'warn' : 'info'" kind="متابعة" :done="$done" wire:key="reminder-{{ $reminder->id }}"
                                    :title="$reminder->title"
                                    :meta="($reminder->due_at->isToday() ? Arabic::time($reminder->due_at) : Arabic::dayAndTime($reminder->due_at)).' · '.$reminder->client->name">
                            <a href="{{ $reminder->channel === \App\Enums\ReminderChannel::Whatsapp ? Phone::whatsappUrl($reminder->client->mobile) : Phone::telUrl($reminder->client->mobile) }}"
                               @if ($reminder->channel === \App\Enums\ReminderChannel::Whatsapp) target="_blank" rel="noopener" @endif
                               class="flex size-9 shrink-0 items-center justify-center rounded-field border border-line bg-white text-ink hover:bg-divider"
                               aria-label="{{ $reminder->channel->label() }}">
                                <i class="ti {{ $reminder->channel->icon() }} text-17"></i>
                            </a>
                            <button type="button" wire:click="toggleReminder({{ $reminder->id }})" aria-label="{{ $done ? 'إلغاء الإنجاز' : 'تم الإنجاز' }}">
                                <x-ui.checkbox :checked="$done" />
                            </button>
                        </x-app.task>
                    @endforeach

                    @foreach ($health as $row)
                        @if ($row['count'] > 0)
                            <x-app.task :tone="$row['tone'] === 'danger' ? 'bad' : ($row['tone'] === 'warning' ? 'warn' : 'info')" kind="عقارات" wire:key="health-{{ $loop->index }}"
                                        :title="$row['text']" :meta="Arabic::countWithNumber($row['count'], 'عقار', 'عقارات', 'عقاراً')">
                                <x-ui.button variant="quiet" size="xs" :href="$row['url']" wire:navigate>عرض</x-ui.button>
                            </x-app.task>
                        @endif
                    @endforeach

                    @foreach ($matches as $match)
                        <x-app.task tone="info" kind="تطابق" wire:key="match-{{ $match['client']->id }}-{{ $match['listing']->id }}"
                                    :title="$match['listing']->title"
                                    :meta="'يطابق طلب '.$match['client']->name.' · '.$match['listing']->priceLabel()">
                            <x-ui.button variant="quiet" size="xs" icon="ti-send"
                                         :href="Phone::whatsappUrl($match['client']->mobile, $match['listing']->pitchFor($match['client']->name))"
                                         target="_blank" rel="noopener"
                                         wire:click="markSent({{ $match['client']->id }}, {{ $match['listing']->id }})">إرسال</x-ui.button>
                        </x-app.task>
                    @endforeach

                    @if ($openTasks === 0 && $reminders->isEmpty())
                        <p class="m-0 py-6 text-center text-13 text-muted">لا توجد مهام مفتوحة. يومك نظيف.</p>
                    @endif
                </div>
            </x-ui.card>

            <div class="flex flex-col gap-3.5">
                <x-ui.card title="استفسارات جديدة" :count="$newInquiries" class="gap-1">
                    <div class="flex flex-col">
                        @forelse ($inquiries as $inquiry)
                            <x-app.task :bar="false" wire:key="inquiry-{{ $inquiry->id }}"
                                        :title="$inquiry->name"
                                        :meta="($inquiry->listing?->title ?? 'استفسار عام').' · '.$inquiry->source->label().' · '.Arabic::ago($inquiry->created_at)">
                                @if ($inquiry->client_id)
                                    <x-ui.button variant="quiet" size="xs" :href="route('clients.show', $inquiry->client_id)" wire:navigate>الملف</x-ui.button>
                                @endif
                            </x-app.task>
                        @empty
                            <p class="m-0 py-4 text-13 text-muted">لا توجد استفسارات هذا الأسبوع.</p>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card title="آخر 7 أيام">
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach ($kpis as $kpi)
                            <x-app.kpi :label="$kpi['label']" :value="$kpi['value']" :change="$kpi['change']" :up="$kpi['up']" compact />
                        @endforeach
                        <x-app.kpi label="متوسط زمن الرد" compact
                                   :value="$response['average'] !== null ? Arabic::countWithNumber($response['average'], 'دقيقة', 'دقائق', 'دقيقة') : '—'" />
                        <x-app.kpi :label="'رُدّ عليها خلال '.\App\Models\Inquiry::RESPONSE_TARGET_MINUTES.' دقيقة'" compact
                                   :value="$response['onTime'] !== null ? Arabic::percent($response['onTime']) : '—'" />
                    </div>
                </x-ui.card>

                <x-ui.card title="مسار العملاء · آخر 30 يوماً" class="gap-3">
                    <x-funnel :steps="$funnel" />
                </x-ui.card>
            </div>
        </div>
    @endif
</x-app.page>
