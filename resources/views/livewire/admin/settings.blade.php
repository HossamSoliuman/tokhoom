<x-app.page title="الإعدادات">
    <x-slot:actions>
        <x-ui.button type="submit" form="settings-form" icon="ti-check">حفظ</x-ui.button>
    </x-slot:actions>

    <div class="grid items-start gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="scrollbar-none -mx-3 flex gap-1.5 overflow-x-auto px-3 lg:sticky lg:top-6 lg:mx-0 lg:flex-col lg:gap-0.5 lg:overflow-visible lg:rounded-card lg:border lg:border-line lg:bg-white lg:p-1.5" aria-label="أقسام الإعدادات">
            @foreach ($sections as $key => $item)
                <button type="button" wire:click="$set('section', '{{ $key }}')" wire:key="section-{{ $key }}" @class([
                    'flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-field px-3 text-14 transition-colors lg:h-11',
                    'bg-primary-soft font-semibold text-primary' => $section === $key,
                    'border border-line bg-white text-body hover:bg-canvas lg:border-0' => $section !== $key,
                ]) @if ($section === $key) aria-current="page" @endif>
                    <i class="ti {{ $item['icon'] }} text-18"></i>{{ $item['label'] }}
                </button>
            @endforeach
        </nav>

        <form id="settings-form" wire:submit="save" class="flex min-w-0 flex-col gap-4" wire:key="form-{{ $section }}">
            @include('livewire.admin.settings.'.$section)
        </form>
    </div>
</x-app.page>
