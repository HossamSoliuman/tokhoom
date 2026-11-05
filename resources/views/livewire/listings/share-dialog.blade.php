<div>
    @if ($listing)
        <x-ui.dialog title="مشاركة العقار">
            <div class="flex gap-2">
                <span class="flex h-12 min-w-0 flex-1 items-center truncate rounded-field border border-line-strong bg-canvas px-3.5 font-mono text-14" dir="ltr">{{ $listing->shortLink() }}</span>
                <x-ui.button size="lg" icon="ti-copy" class="px-4 text-14" x-on:click="$copy('{{ $listing->shortUrl() }}', 'تم نسخ الرابط')">نسخ</x-ui.button>
            </div>

            <div class="grid grid-cols-4 gap-2">
                @foreach ($socials as $social)
                    @if ($social['url'])
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" wire:click="recordShare"
                           class="flex h-[72px] flex-col items-center justify-center gap-1.5 rounded-xl border border-line bg-white text-12 font-medium text-ink hover:bg-canvas hover:no-underline">
                            <span class="flex size-9 items-center justify-center rounded-full {{ $social['classes'] }}"><i class="ti {{ $social['icon'] }} text-20"></i></span>
                            {{ $social['label'] }}
                        </a>
                    @else
                        <button type="button" wire:click="recordShare" x-on:click="$copy(@js($listing->shortUrl()), 'تم نسخ الرابط، الصقه في إنستغرام')"
                                class="flex h-[72px] flex-col items-center justify-center gap-1.5 rounded-xl border border-line bg-white text-12 font-medium text-ink hover:bg-canvas">
                            <span class="flex size-9 items-center justify-center rounded-full {{ $social['classes'] }}"><i class="ti {{ $social['icon'] }} text-20"></i></span>
                            {{ $social['label'] }}
                        </button>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-wrap gap-4">
                <div class="flex flex-[1_1_200px] flex-col items-center gap-3 rounded-xl border border-line p-4">
                    <div class="w-[170px] rounded-lg border border-divider bg-white p-2.5 [&_svg]:block [&_svg]:h-auto [&_svg]:w-full" dir="ltr">
                        {!! $qr !!}
                    </div>
                    <div class="flex w-full gap-2">
                        <x-ui.button variant="secondary" size="sm" icon="ti-download" class="flex-1 text-13" :href="route('listings.qr', [$listing, 'png'])">PNG</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" icon="ti-download" class="flex-1 text-13" :href="route('listings.qr', [$listing, 'svg'])">SVG</x-ui.button>
                    </div>
                </div>

                <div class="flex flex-[1_1_220px] flex-col justify-end rounded-xl bg-[#ECE5DB] p-3.5">
                    <div class="me-auto flex max-w-[250px] flex-col gap-1.5 rounded-[10px] bg-[#DCF5C9] p-[5px]">
                        <div class="overflow-hidden rounded-[7px] bg-white/65">
                            <x-ui.photo :photo="$listing->coverPhoto" label="" class="h-[110px]" />
                            <div class="flex flex-col gap-0.5 p-2">
                                <span class="text-13 font-bold">{{ $listing->title }}</span>
                                <span class="text-12 text-body">{{ $listing->priceLabel() }} · {{ $listing->workspace->name }}</span>
                                <span class="text-end text-11 text-muted" dir="ltr">{{ $listing->workspace->siteHost() }}</span>
                            </div>
                        </div>
                        <span class="px-1 font-mono text-12 text-[#1F6FB2]" dir="ltr">{{ $listing->shortLink() }}</span>
                        <span class="px-1 pb-0.5 text-left text-[10px] text-muted">{{ now()->format('H:i') }} ✓✓</span>
                    </div>
                </div>
            </div>
        </x-ui.dialog>
    @endif
</div>
