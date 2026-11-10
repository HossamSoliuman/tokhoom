{{-- Small rounded status pill. --}}
@props(['tone' => 'gray'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-[9px] py-0.5 text-[11.5px] font-semibold',
    'bg-success-soft text-success' => $tone === 'good',
    'bg-warning-soft text-warning' => $tone === 'warn',
    'bg-danger-soft text-danger' => $tone === 'bad',
    'bg-closed text-closed-ink' => $tone === 'gray',
    'bg-primary-soft text-primary' => $tone === 'brand',
    'bg-buyer-soft text-buyer' => $tone === 'buyer',
    'bg-tenant-soft text-tenant' => $tone === 'tenant',
]) }}>{{ $slot }}</span>
