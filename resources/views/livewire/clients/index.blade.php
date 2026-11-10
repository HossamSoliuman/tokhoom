<x-app.page title="العملاء">
    <x-slot:actions>
        <x-ui.button icon="ti-user-plus" size="sm" wire:click="openAddClient">إضافة عميل</x-ui.button>
    </x-slot:actions>

    @if (! $hasAnyClient)
        <x-ui.empty icon="ti-users-plus" title="لا يوجد عملاء بعد" body="أضف عملاءك وطلباتهم لتصلك التطابقات مع العقارات تلقائياً.">
            <x-ui.button icon="ti-plus" wire:click="openAddClient" class="px-5">إضافة عميل</x-ui.button>
        </x-ui.empty>
    @else
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex h-[38px] max-w-[320px] flex-[1_1_220px] items-center gap-2 rounded-field border border-line bg-white px-3 focus-within:border-primary">
                <i class="ti ti-search text-muted"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث بالاسم أو الجوال" class="min-w-0 flex-1 border-0 bg-transparent text-14">
            </div>
            <x-app.segmented>
                <x-app.segmented-option wire:model.live="type" value="all" :active="$type === 'all'">الكل</x-app.segmented-option>
                @foreach (\App\Enums\ClientType::cases() as $case)
                    <x-app.segmented-option wire:model.live="type" :value="$case->value" :active="$type === $case->value" wire:key="type-{{ $case->value }}">{{ $case->label() }}</x-app.segmented-option>
                @endforeach
            </x-app.segmented>
        </div>

        @if ($clients->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-card border border-dashed border-line-strong bg-white px-5 py-10 text-center">
                <i class="ti ti-user-search text-28 text-muted"></i>
                <div class="text-16 font-semibold">لا يوجد عملاء بهذا البحث</div>
                <x-ui.button variant="secondary" size="sm" wire:click="clearSearch">مسح البحث</x-ui.button>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto rounded-card border border-line bg-white px-4 py-2 lg:block">
                <table class="w-full border-collapse text-13">
                    <thead>
                        <tr class="text-right text-12 text-muted">
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">العميل</th>
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">الجوال</th>
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">النوع</th>
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">المصدر</th>
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">الوسيط</th>
                            <th class="whitespace-nowrap border-b border-line px-2.5 py-2 font-semibold">التذكير القادم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            @php($next = $client->nextReminder)
                            <tr wire:key="row-{{ $client->id }}" class="group cursor-pointer" x-on:click="$event.target.closest('a') || Livewire.navigate(@js(route('clients.show', $client)))">
                                <td class="border-b border-divider px-2.5 py-2.5 group-last:border-b-0 group-hover:bg-head">
                                    <a href="{{ route('clients.show', $client) }}" wire:navigate class="flex items-center gap-2.5 font-bold text-ink hover:no-underline">
                                        <x-ui.avatar :initial="$client->initial()" :size="30" />{{ $client->name }}
                                    </a>
                                </td>
                                <td class="border-b border-divider px-2.5 py-2.5 text-right group-last:border-b-0 group-hover:bg-head" dir="ltr">{{ Phone::display($client->mobile) }}</td>
                                <td class="border-b border-divider px-2.5 py-2.5 group-last:border-b-0 group-hover:bg-head"><x-app.tag :tone="$client->type->tone()">{{ $client->type->label() }}</x-app.tag></td>
                                <td class="border-b border-divider px-2.5 py-2.5 text-body group-last:border-b-0 group-hover:bg-head">{{ $client->source->label() }}</td>
                                <td class="border-b border-divider px-2.5 py-2.5 text-body group-last:border-b-0 group-hover:bg-head">{{ $client->agent->name }}</td>
                                <td class="border-b border-divider px-2.5 py-2.5 group-last:border-b-0 group-hover:bg-head">
                                    @if ($next)
                                        <x-app.tag :tone="$next->isDue() ? 'bad' : 'gray'"><i class="ti ti-bell"></i>{{ Arabic::dayAndTime($next->due_at) }}</x-app.tag>
                                    @else
                                        <span class="text-subtle">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="flex flex-col gap-2 lg:hidden">
                @foreach ($clients as $client)
                    @php($next = $client->nextReminder)
                    <a href="{{ route('clients.show', $client) }}" wire:navigate wire:key="card-{{ $client->id }}"
                       @class([
                           'flex flex-col gap-1.5 rounded-[9px] border bg-white p-2.5 text-[12.5px] text-ink hover:no-underline',
                           'border-[#efb6b6] shadow-[inset_-3px_0_0_var(--color-danger)]' => $next?->isDue(),
                           'border-line' => ! $next?->isDue(),
                       ])>
                        <div class="flex items-center gap-2">
                            <span class="flex-1 text-[13.5px] font-bold">{{ $client->name }}</span>
                            <x-app.tag :tone="$client->type->tone()">{{ $client->type->label() }}</x-app.tag>
                        </div>
                        <span class="text-right text-muted" dir="ltr">{{ Phone::display($client->mobile) }}</span>
                        <div class="flex flex-wrap justify-between gap-2 text-muted">
                            <span>{{ $client->source->label() }} · {{ $client->agent->name }}</span>
                            <span @class(['flex items-center gap-1', 'font-semibold text-danger' => $next?->isDue()])>
                                <i class="ti ti-bell"></i>{{ $next ? Arabic::dayAndTime($next->due_at) : '—' }}
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
                        <x-ui.chip size="lg" wire:model="clientType" :value="$case->value" :active="$clientType === $case->value">{{ $case->label() }}</x-ui.chip>
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
