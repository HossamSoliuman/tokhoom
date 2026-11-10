<x-app.page title="إعدادات الموقع">
    <x-slot:actions>
        <x-ui.button icon="ti-check" wire:click="save">حفظ</x-ui.button>
    </x-slot:actions>

    <div class="flex flex-wrap items-start gap-6"
         x-data="{
             preview: false,
             ink(hex) {
                 const value = /^#[0-9a-f]{6}$/i.test(hex) ? hex.slice(1) : '1E3A4C';
                 const channel = (index) => {
                     const c = parseInt(value.substr(index, 2), 16) / 255;
                     return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                 };
                 const luminance = 0.2126 * channel(0) + 0.7152 * channel(2) + 0.0722 * channel(4);
                 return luminance > 0.4 ? '#14120F' : '#FFFFFF';
             },
             color() { return /^#[0-9a-f]{6}$/i.test($wire.brandColor) ? $wire.brandColor : '#1E3A4C' },
         }">
        <div class="flex min-w-0 flex-[1_1_400px] flex-col gap-4">
            <x-ui.button variant="secondary" size="lg" icon="ti-eye" class="lg:hidden" x-on:click="preview = true">معاينة الموقع</x-ui.button>

            <x-ui.card title="الهوية" class="gap-4">
                <div class="flex items-stretch gap-3">
                    <div class="flex w-24 flex-col gap-1.5">
                        <span class="text-14 font-medium">الشعار</span>
                        <label class="relative flex h-24 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-[1.5px] border-dashed border-faint font-bold"
                               :style="`background: ${color()}; color: ${ink(color())}`">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="" class="absolute inset-0 size-full bg-white object-contain">
                            @else
                                <span class="text-[32px]">{{ $workspace->initial() }}</span>
                            @endif
                            <input type="file" wire:model="logo" accept="image/*" class="sr-only">
                            <span wire:loading.flex wire:target="logo" class="absolute inset-0 items-center justify-center bg-white/70 text-ink"><i class="ti ti-loader-2 animate-spin text-24"></i></span>
                        </label>
                    </div>
                    <div class="flex flex-1 flex-col gap-1.5">
                        <span class="text-14 font-medium">صورة الغلاف</span>
                        <label class="photo-stripes relative flex h-24 cursor-pointer items-center justify-center gap-1.5 overflow-hidden rounded-xl border-[1.5px] border-dashed border-faint text-13 font-semibold text-body">
                            @if ($coverUrl)
                                <img src="{{ $coverUrl }}" alt="" class="absolute inset-0 size-full object-cover">
                            @endif
                            <span class="relative flex items-center gap-1.5 rounded-full bg-white/85 px-3 py-1"><i class="ti ti-upload text-18"></i>تغيير الصورة</span>
                            <input type="file" wire:model="cover" accept="image/*" class="sr-only">
                            <span wire:loading.flex wire:target="cover" class="absolute inset-0 items-center justify-center bg-white/70"><i class="ti ti-loader-2 animate-spin text-24"></i></span>
                        </label>
                    </div>
                </div>
                @error('logo') <span class="text-13 text-danger">{{ $message }}</span> @enderror
                @error('cover') <span class="text-13 text-danger">{{ $message }}</span> @enderror

                <div class="flex flex-col gap-2">
                    <span class="text-14 font-medium">لون الهوية</span>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (\App\Livewire\Site\Settings::SWATCHES as $swatch)
                            <button type="button" wire:click="$set('brandColor', '{{ $swatch }}')"
                                    class="size-10 rounded-full border-[3px] border-white p-0 outline-2"
                                    :style="{ outlineStyle: 'solid', outlineColor: $wire.brandColor.toUpperCase() === '{{ $swatch }}' ? '{{ $swatch }}' : 'transparent' }"
                                    style="background: {{ $swatch }}" aria-label="{{ $swatch }}"></button>
                        @endforeach
                        <span class="flex h-10 items-center gap-2 rounded-field border border-line-strong bg-white px-2.5">
                            <span class="size-5 rounded-[5px]" :style="`background: ${color()}`"></span>
                            <input type="text" wire:model.live.debounce.300ms="brandColor" maxlength="7" class="w-[76px] border-0 font-mono text-13" dir="ltr" aria-label="رمز اللون">
                        </span>
                    </div>
                    @error('brandColor') <span class="text-13 text-danger">{{ $message }}</span> @enderror
                </div>

                <x-ui.field label="نبذة عن المكتب" error="about">
                    <textarea wire:model="about" rows="3" class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-15 leading-6 focus:border-primary"></textarea>
                </x-ui.field>
            </x-ui.card>

            <x-ui.card title="التواصل" class="gap-3">
                <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3">
                    @foreach ([
                        ['whatsapp', 'واتساب', 'ti-brand-whatsapp', '05XXXXXXXX'],
                        ['phone', 'الهاتف', 'ti-phone', '011XXXXXXX'],
                        ['xHandle', 'X', 'ti-brand-x', '@'],
                        ['instagram', 'إنستغرام', 'ti-brand-instagram', '@'],
                        ['snapchat', 'سناب شات', 'ti-brand-snapchat', ''],
                        ['tiktok', 'تيك توك', 'ti-brand-tiktok', '@'],
                    ] as [$property, $label, $icon, $placeholder])
                        <x-ui.field :label="$label" :error="$property">
                            <span class="flex h-11 items-center gap-2 rounded-field border border-line-strong bg-white px-3 focus-within:border-primary">
                                <i class="ti {{ $icon }} text-18 text-muted"></i>
                                <input type="text" wire:model="{{ $property }}" placeholder="{{ $placeholder }}" class="min-w-0 flex-1 border-0 text-right text-14" dir="ltr">
                            </span>
                        </x-ui.field>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        {{-- Live preview: beside the form on desktop, full screen on mobile --}}
        <div :class="preview ? 'fixed inset-0 z-40 flex bg-white px-4 pb-4 pt-3' : 'hidden'"
             class="min-w-0 flex-col gap-2.5 lg:sticky lg:top-8 lg:z-auto lg:flex lg:flex-[1_1_520px] lg:bg-transparent lg:p-0">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-14 font-semibold text-body"><i class="ti ti-world"></i><span dir="ltr">{{ $workspace->siteHost() }}</span></span>
                <button type="button" x-on:click="preview = false" class="flex size-11 items-center justify-center rounded-full bg-divider text-ink lg:hidden" aria-label="إغلاق المعاينة">
                    <i class="ti ti-x text-20"></i>
                </button>
            </div>
            <div class="flex flex-1 flex-col overflow-hidden overflow-y-auto rounded-card border border-line-strong bg-canvas lg:flex-none">
                <div class="flex h-[52px] shrink-0 items-center justify-between border-b border-line bg-white px-3.5">
                    <span class="flex items-center gap-2 text-13 font-semibold">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="size-[30px] rounded-lg object-contain">
                        @else
                            <span class="flex size-[30px] items-center justify-center rounded-lg font-bold" :style="`background: ${color()}; color: ${ink(color())}`">{{ $workspace->initial() }}</span>
                        @endif
                        {{ $workspace->name }}
                    </span>
                    <span class="flex h-8 items-center gap-1 rounded-lg px-3 text-12 font-semibold" :style="`background: ${color()}; color: ${ink(color())}`">
                        <i class="ti ti-brand-whatsapp"></i>واتساب
                    </span>
                </div>
                <div class="photo-stripes-lg relative mx-3 mt-3 h-[170px] shrink-0 overflow-hidden rounded-field">
                    @if ($coverUrl)
                        <img src="{{ $coverUrl }}" alt="" class="absolute inset-0 size-full object-cover">
                    @endif
                </div>
                <div class="flex flex-col gap-1 px-3.5 py-3">
                    <span class="text-17 font-bold">{{ $workspace->name }}</span>
                    <span class="text-12 leading-5 text-body" x-text="$wire.about"></span>
                    <span class="text-11 text-muted">رخصة فال {{ $workspace->fal_number }}{{ $workspace->cr_number ? ' · السجل التجاري '.$workspace->cr_number : '' }}</span>
                </div>
                <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-2.5 px-3 pb-3.5">
                    @forelse ($previewListings as $listing)
                        <div class="overflow-hidden rounded-field border border-line bg-white">
                            <x-ui.photo :photo="$listing->coverPhoto" label="" class="h-[84px]">
                                <span class="absolute right-1.5 top-1.5 flex h-5 items-center rounded-full bg-white px-2 text-[10px] font-bold" :style="`color: ${color()}`">{{ $listing->purpose->label() }}</span>
                            </x-ui.photo>
                            <div class="flex flex-col gap-0.5 px-2.5 py-2">
                                <span class="text-13 font-bold">{{ $listing->priceLabel() }}</span>
                                <span class="text-11 text-muted">{{ $listing->type->label() }} · حي {{ $listing->district }}</span>
                            </div>
                        </div>
                    @empty
                        <span class="col-span-full py-4 text-center text-12 text-muted">ستظهر عقاراتك المتاحة هنا.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app.page>
