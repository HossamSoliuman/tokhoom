@php
    $input = 'w-full rounded-field border bg-white px-3.5 text-15 transition-colors focus:border-brand';
@endphp

<form wire:submit="send" class="flex flex-col gap-3.5 lg:gap-4" novalidate>
    <h2 class="text-17 font-bold">أرسل استفساراً</h2>

    <label class="flex flex-col gap-1.5">
        <span class="text-14 font-medium">الاسم</span>
        <input type="text" wire:model="name" placeholder="الاسم الكامل" autocomplete="name"
               @class([$input, 'h-12 lg:h-11', $errors->has('name') ? 'border-danger' : 'border-line-strong'])>
        @error('name')
            <span class="flex items-center gap-1 text-13 text-danger"><i class="ti ti-alert-circle"></i>{{ $message }}</span>
        @enderror
    </label>

    <label class="flex flex-col gap-1.5">
        <span class="text-14 font-medium">رقم الجوال</span>
        <input type="tel" wire:model="mobile" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="tel" dir="ltr"
               @class([$input, 'h-12 text-right lg:h-11', $errors->has('mobile') ? 'border-danger' : 'border-line-strong'])>
        @error('mobile')
            <span class="flex items-center gap-1 text-13 text-danger"><i class="ti ti-alert-circle"></i>{{ $message }}</span>
        @enderror
    </label>

    <label class="flex flex-col gap-1.5">
        <span class="text-14 font-medium">الرسالة</span>
        <textarea wire:model="message" rows="3" placeholder="أرغب بمعرفة المزيد عن هذا العقار وترتيب موعد للمعاينة."
                  class="{{ $input }} resize-none border-line-strong py-3 leading-6 lg:py-2.5"></textarea>
    </label>

    <div class="flex flex-col gap-1.5">
        <button type="button" wire:click="$toggle('consent')" role="checkbox" aria-checked="{{ $consent ? 'true' : 'false' }}"
                class="flex items-start gap-2.5 text-start">
            <span @class([
                'mt-px flex size-[22px] shrink-0 items-center justify-center rounded-md border-[1.5px] text-brand-ink lg:size-5',
                'border-brand bg-brand' => $consent,
                'border-danger bg-white' => ! $consent && $errors->has('consent'),
                'border-faint bg-white' => ! $consent && ! $errors->has('consent'),
            ])>
                @if ($consent)
                    <i class="ti ti-check text-[15px] lg:text-14"></i>
                @endif
            </span>
            <span class="text-14 leading-[22px] text-ink-soft">أوافق على أن يتواصل معي المكتب بخصوص هذا العقار</span>
        </button>
        @error('consent')
            <span class="flex items-center gap-1 text-13 text-danger"><i class="ti ti-alert-circle"></i>{{ $message }}</span>
        @enderror
    </div>

    <button type="submit" class="h-12 rounded-field bg-brand text-15 font-semibold text-brand-ink transition hover:brightness-95 data-loading:opacity-60">
        إرسال
    </button>
</form>
