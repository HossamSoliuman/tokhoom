{{-- Days until a license runs out, or how long ago it did. --}}
@props(['date'])

@php($days = (int) today()->diffInDays($date, false))

<span {{ $attributes->class([
    'inline-flex h-[26px] shrink-0 items-center rounded-full px-2.5 text-13 font-semibold',
    'bg-danger-soft text-danger' => $days < 0,
    'bg-warning-soft text-warning' => $days >= 0 && $days <= 30,
    'bg-divider text-body' => $days > 30,
]) }}>
    @if ($days < 0)
        انتهى منذ {{ Arabic::days(abs($days)) }}
    @elseif ($days === 0)
        ينتهي اليوم
    @else
        بعد {{ Arabic::days($days) }}
    @endif
</span>
