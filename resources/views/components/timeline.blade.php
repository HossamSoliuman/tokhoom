{{-- Activity history with an icon rail. --}}
@props(['items', 'size' => 'md'])

<div {{ $attributes->class('flex flex-col') }}>
    @forelse ($items as $activity)
        <div class="flex gap-3" wire:key="activity-{{ $activity->id }}">
            <div class="flex flex-col items-center">
                <span @class([
                    'flex shrink-0 items-center justify-center rounded-full bg-divider text-body',
                    'size-[30px]' => $size === 'sm',
                    'size-8' => $size === 'md',
                ])><i class="ti {{ $activity->icon() }} {{ $size === 'sm' ? 'text-15' : 'text-16' }}"></i></span>
                @unless ($loop->last)
                    <span class="min-h-3 w-px flex-1 bg-line"></span>
                @endunless
            </div>
            <div class="flex flex-col gap-0.5 pb-4 pt-1">
                <span class="text-14">{{ $activity->text() }}</span>
                <span class="text-12 text-subtle">{{ $size === 'md' && ! $activity->occurred_at->isMidnight() ? Arabic::dateTime($activity->occurred_at) : Arabic::date($activity->occurred_at) }}</span>
            </div>
        </div>
    @empty
        <p class="m-0 text-14 text-muted">لا يوجد نشاط بعد.</p>
    @endforelse
</div>
