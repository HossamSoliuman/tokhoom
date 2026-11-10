<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <label class="flex h-[38px] items-center gap-2 rounded-field border border-line bg-divider px-3 focus-within:border-primary focus-within:bg-white">
        <i class="ti ti-search text-17 text-muted"></i>
        <input type="search" wire:model.live.debounce.300ms="query" x-on:focus="open = true" x-on:input="open = true"
               placeholder="ابحث: عميل بالاسم أو الجوال، حي، رمز عقار أو رقم ترخيص"
               class="min-w-0 flex-1 border-0 bg-transparent text-14" autocomplete="off" aria-label="البحث">
    </label>

    @if (mb_strlen(trim($query)) >= 2)
        <div x-cloak x-show="open" x-transition.opacity.duration.100ms
             class="absolute inset-x-0 top-11 z-30 max-h-[320px] overflow-y-auto rounded-field border border-line bg-white py-1 shadow-pop">
            @forelse ($results as $result)
                <a href="{{ $result['url'] }}" wire:navigate x-on:click="open = false" wire:key="result-{{ $loop->index }}"
                   class="flex items-center gap-2.5 px-3.5 py-2.5 text-ink hover:bg-canvas hover:no-underline">
                    <i class="ti {{ $result['icon'] }} text-17 text-muted"></i>
                    <span class="min-w-0 flex-1 truncate text-14 font-medium">{{ $result['label'] }}</span>
                    <span class="shrink-0 text-12 text-muted" @if ($result['icon'] === 'ti-user') dir="ltr" @endif>{{ $result['meta'] }}</span>
                    <span class="shrink-0 rounded-full bg-divider px-2 text-11 font-semibold text-muted">{{ $result['kind'] }}</span>
                </a>
            @empty
                <p class="m-0 px-3.5 py-3 text-13 text-muted">لا نتائج ضمن صلاحياتك</p>
            @endforelse
        </div>
    @endif
</div>
