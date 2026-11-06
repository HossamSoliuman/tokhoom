{{--
    WhatsApp or call link that counts the click. "icon" shows the icon alone,
    "number" shows the phone number instead of the word.
--}}
@props(['kind', 'number', 'text' => null, 'listing' => null, 'icon' => false, 'showNumber' => false])

@php
    $whatsapp = $kind === 'whatsapp';
    $href = $whatsapp ? \App\Support\Phone::whatsappUrl($number, $text) : \App\Support\Phone::telUrl($number);
@endphp

<a href="{{ $href }}" @if ($whatsapp) target="_blank" rel="noopener" @endif
   x-on:click="$track('{{ $whatsapp ? 'whatsapp_click' : 'call_click' }}', @js($listing))"
   @if ($icon) aria-label="{{ $whatsapp ? 'واتساب' : 'اتصال' }}" @endif
   {{ $attributes->class([
       'flex shrink-0 items-center justify-center gap-2 rounded-field font-semibold transition hover:no-underline',
       'bg-brand text-brand-ink hover:text-brand-ink hover:brightness-95' => $whatsapp,
       'border border-line-strong bg-white text-ink hover:bg-canvas hover:text-ink' => ! $whatsapp,
       'px-4' => ! $icon,
   ]) }}>
    <i class="ti {{ $whatsapp ? 'ti-brand-whatsapp text-[1.35em]' : 'ti-phone text-[1.25em]' }}"></i>
    @unless ($icon)
        @if ($showNumber && ! $whatsapp)
            <span dir="ltr">{{ \App\Support\Phone::display($number) }}</span>
        @else
            {{ $whatsapp ? 'واتساب' : 'اتصال' }}
        @endif
    @endunless
</a>
