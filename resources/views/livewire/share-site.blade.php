<div>
    @if ($url)
        <x-ui.dialog title="مشاركة موقعي" width="lg:w-[440px]">
            <div class="flex gap-2">
                <span class="flex h-12 min-w-0 flex-1 items-center truncate rounded-field border border-line-strong bg-canvas px-3.5 font-mono text-14" dir="ltr">{{ $host }}</span>
                <x-ui.button size="lg" icon="ti-copy" class="px-4 text-14" x-on:click="$copy(@js($url), 'تم نسخ الرابط')">نسخ</x-ui.button>
            </div>

            <div class="flex gap-2">
                <x-ui.button variant="whatsapp" size="lg" icon="ti-brand-whatsapp" class="flex-1 text-14" :href="$whatsappUrl" target="_blank" rel="noopener">واتساب</x-ui.button>
                <x-ui.button variant="secondary" size="lg" icon="ti-external-link" class="flex-1 text-14" :href="$url" target="_blank" rel="noopener">فتح الموقع</x-ui.button>
            </div>

            <div class="flex flex-col items-center gap-3 rounded-xl border border-line p-4">
                <div class="w-[190px] rounded-lg border border-divider bg-white p-2.5 [&_svg]:block [&_svg]:h-auto [&_svg]:w-full" dir="ltr">
                    {!! $qr !!}
                </div>
                <div class="flex w-full gap-2">
                    <x-ui.button variant="secondary" size="sm" icon="ti-download" class="flex-1 text-13" :href="route('share.qr', 'png')">PNG</x-ui.button>
                    <x-ui.button variant="secondary" size="sm" icon="ti-download" class="flex-1 text-13" :href="route('share.qr', 'svg')">SVG</x-ui.button>
                </div>
            </div>
        </x-ui.dialog>
    @endif
</div>
