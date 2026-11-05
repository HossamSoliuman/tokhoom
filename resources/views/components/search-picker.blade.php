{{-- Dropdown with a search box. Picking an option sets the given Livewire property. --}}
@props(['label', 'property', 'options', 'value' => '', 'error' => null])

<div {{ $attributes->class('relative flex flex-col gap-1.5') }}
     x-data="{ open: false, q: '', options: @js(array_values($options)), get filtered() { return this.options.filter((option) => option.includes(this.q.trim())) } }"
     x-on:click.outside="open = false"
     x-on:keydown.escape="open = false">
    <span class="text-14 font-medium">{{ $label }}</span>
    <button type="button" x-on:click="open = ! open; q = ''; $nextTick(() => open && $refs.search.focus())"
            :class="open ? 'border-primary' : 'border-line-strong'"
            class="flex h-12 items-center justify-between rounded-field border-[1.5px] bg-white px-3.5 text-15">
        <span @class(['text-ink' => $value, 'text-subtle' => ! $value])>{{ $value ?: 'اختر '.$label }}</span>
        <i class="ti ti-chevron-down text-muted"></i>
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         class="absolute inset-x-0 top-20 z-[12] flex flex-col gap-0.5 rounded-xl border border-line bg-white p-2 shadow-pop">
        <div class="mb-1 flex h-10 items-center gap-2 rounded-lg bg-head px-2.5">
            <i class="ti ti-search text-muted"></i>
            <input type="text" x-ref="search" x-model="q" placeholder="ابحث عن {{ $label }}" class="min-w-0 flex-1 border-0 bg-transparent text-14">
        </div>
        <div class="flex max-h-[200px] flex-col gap-0.5 overflow-y-auto">
            <template x-for="option in filtered" :key="option">
                <button type="button" x-on:click="$wire.set('{{ $property }}', option); open = false"
                        :class="option === @js($value) ? 'bg-primary-tint font-semibold text-primary' : 'text-ink hover:bg-canvas'"
                        class="flex h-10 shrink-0 items-center justify-between rounded-lg px-2.5 text-start text-15">
                    <span x-text="option"></span>
                    <i x-show="option === @js($value)" class="ti ti-check"></i>
                </button>
            </template>
            <span x-show="filtered.length === 0" class="p-2.5 text-14 text-muted">لا توجد نتائج</span>
        </div>
    </div>

    @if ($error)
        @error($error)
            <span class="text-13 text-danger">{{ $message }}</span>
        @enderror
    @endif
</div>
