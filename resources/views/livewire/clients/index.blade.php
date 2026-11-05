<x-app.page title="العملاء">
    <x-slot:actions>
        <x-ui.button icon="ti-user-plus" wire:click="openAddClient">إضافة عميل</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyClient)
        <x-ui.empty icon="ti-users-plus" title="لا يوجد عملاء بعد" body="أضف عملاءك وطلباتهم لتصلك التطابقات مع العقارات تلقائياً.">
            <x-ui.button icon="ti-plus" wire:click="openAddClient" class="px-5">إضافة عميل</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex h-11 max-w-[360px] flex-[1_1_240px] items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
                <i class="ti ti-search text-muted"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم أو الجوال" class="min-w-0 flex-1 border-0 bg-transparent text-15">
            </div>
            <div class="scrollbar-none flex max-w-full gap-1.5 overflow-x-auto">
                <x-ui.chip :active="$type === 'all'" wire:click="$set('type', 'all')">الكل</x-ui.chip>
                @foreach (\App\Enums\ClientType::cases() as $case)
                    <x-ui.chip :active="$type === $case->value" wire:click="$set('type', '{{ $case->value }}')" wire:key="type-{{ $case->value }}">{{ $case->label() }}</x-ui.chip>
                @endforeach
            </div>
        </div>

        @if ($clients->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
                <i class="ti ti-user-search text-28 text-muted"></i>
                <div class="text-16 font-semibold">لا يوجد عملاء بهذا البحث</div>
                <x-ui.button variant="secondary" size="sm" wire:click="clearSearch">مسح البحث</x-ui.button>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden rounded-card border border-line bg-white lg:block">
                <div class="grid h-11 grid-cols-[1.6fr_1.2fr_1fr_1fr_1.3fr_1.3fr] items-center bg-head px-5 text-13 font-semibold text-muted">
                    <span>العميل</span><span>الجوال</span><span>النوع</span><span>المصدر</span><span>الوسيط</span><span>التذكير القادم</span>
                </div>
                @foreach ($clients as $client)
                    @php($next = $client->nextReminder)
                    <a href="{{ route('clients.show', $client) }}" wire:navigate wire:key="row-{{ $client->id }}"
                       class="grid h-[60px] grid-cols-[1.6fr_1.2fr_1fr_1fr_1.3fr_1.3fr] items-center border-t border-divider px-5 text-14 text-ink hover:bg-canvas hover:no-underline">
                        <span class="flex items-center gap-2.5 font-semibold">
                            <x-ui.avatar :initial="$client->initial()" :size="34" />{{ $client->name }}
                        </span>
                        <span class="text-right" dir="ltr">{{ Phone::display($client->mobile) }}</span>
                        <span><span class="inline-flex h-[26px] items-center rounded-full bg-divider px-2.5 text-13">{{ $client->type->label() }}</span></span>
                        <span class="text-body">{{ $client->source->label() }}</span>
                        <span class="text-body">{{ $client->agent->name }}</span>
                        <span @class(['flex items-center gap-1.5', 'font-semibold text-warning' => $next?->isDue(), 'text-body' => ! $next?->isDue()])>
                            <i class="ti ti-bell text-16"></i>{{ $next ? Arabic::dayAndTime($next->due_at) : '—' }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Mobile cards --}}
            <div class="flex flex-col gap-2.5 lg:hidden">
                @foreach ($clients as $client)
                    @php($next = $client->nextReminder)
                    <a href="{{ route('clients.show', $client) }}" wire:navigate wire:key="card-{{ $client->id }}"
                       class="flex flex-col gap-2.5 rounded-card border border-line bg-white p-3.5 text-ink hover:no-underline">
                        <div class="flex items-center gap-2.5">
                            <x-ui.avatar :initial="$client->initial()" :size="40" />
                            <div class="flex flex-1 flex-col">
                                <span class="text-15 font-semibold">{{ $client->name }}</span>
                                <span class="text-right text-13 text-muted" dir="ltr">{{ Phone::display($client->mobile) }}</span>
                            </div>
                            <span class="inline-flex h-[26px] items-center rounded-full bg-divider px-2.5 text-13">{{ $client->type->label() }}</span>
                        </div>
                        <div class="flex flex-wrap justify-between gap-2 text-13 text-body">
                            <span>{{ $client->source->label() }} · {{ $client->agent->name }}</span>
                            <span @class(['flex items-center gap-1', 'font-semibold text-warning' => $next?->isDue()])>
                                <i class="ti ti-bell text-15"></i>{{ $next ? Arabic::dayAndTime($next->due_at) : '—' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($total > $clients->count())
                <x-ui.button variant="secondary" class="self-center" wire:click="showMore">عرض المزيد</x-ui.button>
            @endif
        @endif
    @endif

    @if ($dialog === 'client')
        <x-ui.dialog title="إضافة عميل">
            <x-ui.field label="الاسم" error="name">
                <input type="text" wire:model="name" placeholder="الاسم الكامل" class="field" autofocus>
            </x-ui.field>
            <x-ui.field label="رقم الجوال" error="mobile">
                <input type="tel" inputmode="tel" wire:model.live.debounce.400ms="mobile" placeholder="05XXXXXXXX" dir="ltr"
                       @class(['field border-[1.5px] text-right', 'border-warning-dot' => $this->duplicate])>
            </x-ui.field>
            @if ($duplicate = $this->duplicate)
                <div class="flex items-start gap-2.5 rounded-xl border border-notice-line bg-notice p-3">
                    <i class="ti ti-alert-triangle shrink-0 text-20 text-warning"></i>
                    <div class="flex flex-1 flex-col gap-2">
                        <span class="text-14 leading-[22px] text-notice-ink">هذا الرقم مسجّل للعميل {{ $duplicate->name }} ({{ $duplicate->agent->name }}).</span>
                        @can('view', $duplicate)
                            <a href="{{ route('clients.show', $duplicate) }}" wire:navigate class="flex h-[34px] items-center self-start rounded-lg border border-notice-action-line bg-white px-3 text-13 font-semibold text-notice-action hover:no-underline">فتح ملف العميل</a>
                        @endcan
                    </div>
                </div>
            @endif
            <div class="flex flex-col gap-2">
                <span class="text-14 font-medium">النوع</span>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Enums\ClientType::cases() as $case)
                        <x-ui.chip size="lg" :active="$clientType === $case->value" wire:click="$set('clientType', '{{ $case->value }}')">{{ $case->label() }}</x-ui.chip>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <x-ui.field label="المصدر" error="source">
                    <x-ui.select wire:model="source" size="lg">
                        @foreach ([\App\Enums\LeadSource::Direct, \App\Enums\LeadSource::Site, \App\Enums\LeadSource::Qr, \App\Enums\LeadSource::SharedLink] as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="الوسيط" error="agentId">
                    <x-ui.select wire:model="agentId" size="lg">
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="save" :disabled="(bool) $this->duplicate">حفظ العميل</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
