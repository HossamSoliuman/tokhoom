{{-- Segmented control track. Fill it with x-app.segmented-option radios. --}}
<div {{ $attributes->class('scrollbar-none inline-flex max-w-full overflow-x-auto rounded-field bg-divider p-[3px]') }}>
    {{ $slot }}
</div>
