@php
    use App\Support\Arabic;

    $agent = $listing->agent;
    $whatsapp = $agent?->mobile ?: $workspace->contactWhatsapp();
    $phone = $agent?->mobile ?: $workspace->contactPhone();
    $pageUrl = request()->url();
    $whatsappText = 'السلام عليكم، أستفسر عن '.$listing->title.' — '.$listing->priceLabel()."\n".$pageUrl;
    $heading = $listing->type->label().' '.$listing->purpose->label().($listing->district ? ' في حي '.$listing->district : '');
    $agentUrl = $workspace->isOffice() && $membership && ! $membership->deactivated_at ? route('site.agent', $listing->agent_id) : route('site.home');
    $side = $listing->photos->slice(1, 4)->values();
    $facts = array_values(array_filter([
        $listing->area ? ['ti-ruler-measure', 'المساحة', Arabic::number($listing->area).' م²'] : null,
        $listing->bedrooms ? ['ti-bed', 'غرف النوم', $listing->bedrooms] : null,
        $listing->bathrooms ? ['ti-bath', 'دورات المياه', $listing->bathrooms] : null,
        $listing->age !== null ? ['ti-calendar', 'عمر العقار', $listing->age === 0 ? 'جديد' : Arabic::count($listing->age, 'سنة واحدة', 'سنتان', 'سنوات', 'سنة')] : null,
        $listing->floors ? ['ti-stairs', 'الأدوار', $listing->floors] : null,
    ]));
    $hasMap = $listing->latitude !== null && $listing->longitude !== null;
@endphp

<x-layouts.site :workspace="$workspace" :title="$listing->title"
                :description="$listing->priceLabel().' · '.$listing->locationLabel()"
                :image="$listing->photos->first()?->url()"
                :mobile-footer="false">
    <div x-data="gallery(@js($photos))" class="mx-auto max-w-[1248px] pb-24 lg:px-6 lg:pb-0 lg:pt-6">
        {{-- Desktop: back and share --}}
        <div class="mb-4 hidden items-center justify-between lg:flex">
            <a href="{{ route('site.home') }}" class="flex h-10 items-center gap-1.5 px-1 text-15 font-medium text-ink hover:text-ink">
                <i class="ti ti-arrow-right text-18"></i>كل العقارات
            </a>
            <button type="button" x-on:click="$share(@js($pageUrl), @js($listing->title), @js($listing->short_code))"
                    class="flex h-10 items-center gap-1.5 rounded-field border border-line-strong bg-white px-4 text-14 font-semibold">
                <i class="ti ti-share text-18"></i>مشاركة
            </button>
        </div>

        {{-- Phones: swipeable photos --}}
        <div class="relative lg:hidden">
            <div class="scrollbar-none flex snap-x snap-mandatory overflow-x-auto" x-on:scroll.debounce.60ms="slid($el)">
                @forelse ($listing->photos as $index => $photo)
                    <x-ui.photo :photo="$photo" large class="h-[300px] w-full shrink-0 cursor-zoom-in snap-start" x-on:click="show({{ $index }})" />
                @empty
                    <div class="photo-stripes-lg h-[300px] w-full"></div>
                @endforelse
            </div>
            <a href="{{ route('site.home') }}" class="absolute right-3.5 top-3.5 flex size-11 items-center justify-center rounded-full bg-white text-ink hover:text-ink" aria-label="كل العقارات">
                <i class="ti ti-arrow-right text-20"></i>
            </a>
            <button type="button" x-on:click="$share(@js($pageUrl), @js($listing->title), @js($listing->short_code))"
                    class="absolute left-3.5 top-3.5 flex size-11 items-center justify-center rounded-full bg-white" aria-label="مشاركة">
                <i class="ti ti-share text-20"></i>
            </button>
            @if ($listing->photos->isNotEmpty())
                <span class="absolute bottom-3.5 left-3.5 flex h-[26px] items-center rounded-full bg-ink/70 px-2.5 text-13 text-white" dir="ltr" x-text="counter">1 / {{ $listing->photos->count() }}</span>
            @endif
        </div>

        {{-- Desktop: photo grid --}}
        <div @class([
            'hidden h-[508px] gap-2 overflow-hidden rounded-2xl lg:grid',
            'grid-cols-1' => $side->isEmpty(),
            'grid-cols-[2fr_1fr]' => $side->isNotEmpty() && $side->count() < 4,
            'grid-cols-[2fr_1fr_1fr] grid-rows-2' => $side->count() === 4,
        ])>
            <x-ui.photo :photo="$listing->photos->first()" large
                        @class(['row-span-2', 'cursor-zoom-in' => $listing->photos->isNotEmpty()])
                        x-on:click="show(0)">
                @if ($listing->tour_url || $listing->video_url)
                    <div class="absolute bottom-4 right-4 flex gap-2">
                        @if ($listing->tour_url)
                            <a href="{{ $listing->tour_url }}" target="_blank" rel="noopener" x-on:click.stop
                               class="flex h-10 items-center gap-1.5 rounded-full bg-white px-3.5 text-14 font-semibold text-ink hover:text-ink">
                                <i class="ti ti-view-360 text-[19px]"></i>جولة ثلاثية الأبعاد
                            </a>
                        @endif
                        @if ($listing->video_url)
                            <a href="{{ $listing->video_url }}" target="_blank" rel="noopener" x-on:click.stop
                               class="flex h-10 items-center gap-1.5 rounded-full bg-white px-3.5 text-14 font-semibold text-ink hover:text-ink">
                                <i class="ti ti-player-play text-17"></i>فيديو
                            </a>
                        @endif
                    </div>
                @endif
            </x-ui.photo>
            @if ($side->isNotEmpty())
                <div @class(['grid auto-rows-fr gap-2' => $side->count() < 4, 'contents' => $side->count() === 4])>
                    @foreach ($side as $index => $photo)
                        <x-ui.photo :photo="$photo" class="cursor-zoom-in" x-on:click="show({{ $index + 1 }})">
                            @if ($loop->last && $listing->photos->count() > 1)
                                <span class="absolute bottom-3.5 left-3.5 flex h-9 items-center gap-1.5 rounded-full bg-white px-3.5 text-14 font-semibold">
                                    <i class="ti ti-layout-grid text-17"></i>عرض كل الصور ({{ $listing->photos->count() }})
                                </span>
                            @endif
                        </x-ui.photo>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-7 p-5 lg:mt-9 lg:grid lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start lg:gap-14 lg:p-0">
            <div class="flex flex-col gap-7 lg:gap-9">
                <div class="flex flex-col gap-1.5 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
                    <div class="flex flex-col gap-1.5 lg:gap-2">
                        <div class="flex gap-1.5">
                            <span class="inline-flex h-[26px] items-center rounded-full bg-brand-soft px-2.5 text-13 font-semibold text-brand-text lg:h-7 lg:px-3 lg:text-14">{{ $listing->purpose->label() }}</span>
                            <span class="inline-flex h-[26px] items-center rounded-full bg-divider px-2.5 text-13 font-medium text-body lg:h-7 lg:px-3 lg:text-14">{{ $listing->type->label() }}</span>
                            @if ($listing->status === \App\Enums\ListingStatus::Reserved)
                                <span class="inline-flex h-[26px] items-center rounded-full bg-warning-soft px-2.5 text-13 font-semibold text-warning lg:h-7 lg:px-3 lg:text-14">محجوز</span>
                            @endif
                        </div>
                        <div class="mt-1 flex items-baseline gap-1.5 lg:hidden">
                            <span class="text-28 font-bold">{{ $listing->priceAmount() }}</span>
                            <span class="text-15 text-muted">{{ $listing->priceUnit() }}</span>
                        </div>
                        <h1 class="text-[19px] font-semibold leading-7 lg:text-[32px] lg:font-bold lg:leading-[44px]">{{ $heading }}</h1>
                        @if ($listing->locationLabel())
                            <div class="flex items-center gap-1 text-14 text-muted lg:text-16"><i class="ti ti-map-pin text-16 lg:text-18"></i>{{ $listing->locationLabel() }}</div>
                        @endif
                    </div>
                    <div class="hidden shrink-0 items-baseline gap-1.5 lg:flex">
                        <span class="text-[34px] font-bold">{{ $listing->priceAmount() }}</span>
                        <span class="text-16 text-muted">{{ $listing->priceUnit() }}</span>
                    </div>
                </div>

                @if ($listing->tour_url || $listing->video_url)
                    <div class="-mt-2 flex gap-2 lg:hidden">
                        @if ($listing->tour_url)
                            <a href="{{ $listing->tour_url }}" target="_blank" rel="noopener" class="flex h-11 flex-1 items-center justify-center gap-1.5 rounded-field border border-line-strong bg-white text-14 font-semibold text-ink hover:text-ink">
                                <i class="ti ti-view-360 text-[19px]"></i>جولة ثلاثية الأبعاد
                            </a>
                        @endif
                        @if ($listing->video_url)
                            <a href="{{ $listing->video_url }}" target="_blank" rel="noopener" class="flex h-11 flex-1 items-center justify-center gap-1.5 rounded-field border border-line-strong bg-white text-14 font-semibold text-ink hover:text-ink">
                                <i class="ti ti-player-play text-18"></i>فيديو
                            </a>
                        @endif
                    </div>
                @endif

                @if ($facts)
                    <div class="grid grid-cols-3 gap-2 lg:hidden">
                        @foreach ($facts as [$icon, $label, $value])
                            <div class="flex flex-col gap-0.5 rounded-xl border border-line bg-white p-3">
                                <i class="ti {{ $icon }} text-20 text-brand-text"></i>
                                <span class="mt-1 text-16 font-bold">{{ $value }}</span>
                                <span class="text-12 text-muted">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="hidden divide-x divide-divider rounded-card border border-line bg-white lg:flex rtl:divide-x-reverse">
                        @foreach ($facts as [$icon, $label, $value])
                            <div class="flex flex-1 flex-col gap-1 p-[18px]">
                                <i class="ti {{ $icon }} text-22 text-brand-text"></i>
                                <span class="mt-1 text-18 font-bold">{{ $value }}</span>
                                <span class="text-13 text-muted">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($listing->features)
                    <section class="flex flex-col gap-3 lg:gap-3.5">
                        <h2 class="text-17 font-bold lg:text-20">المميزات</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($listing->features as $feature)
                                <span class="inline-flex h-[34px] items-center gap-1.5 rounded-full border border-line bg-white px-3.5 text-14 lg:h-[38px] lg:px-4 lg:text-15">
                                    <i class="ti ti-check text-brand-text"></i>{{ $feature }}
                                </span>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($listing->description)
                    <section class="flex flex-col gap-2.5 lg:gap-3">
                        <h2 class="text-17 font-bold lg:text-20">الوصف</h2>
                        <p class="whitespace-pre-line text-15 leading-[27px] text-pretty text-ink-soft lg:max-w-[720px] lg:text-16 lg:leading-[30px]">{{ $listing->description }}</p>
                    </section>
                @endif

                @if ($hasMap)
                    <section class="flex flex-col gap-2.5 lg:gap-3">
                        <h2 class="text-17 font-bold lg:text-20">الموقع</h2>
                        <div class="map-stripes relative isolate h-[200px] overflow-hidden rounded-card lg:h-[340px]"
                             x-data="approximateMap({ latitude: @js(round($listing->latitude, 3)), longitude: @js(round($listing->longitude, 3)), color: @js(\App\Support\BrandColor::normalize($workspace->brand_color)) })">
                            <div x-ref="map" class="absolute inset-0 z-0" x-show="! failed"></div>
                            <span class="absolute bottom-2.5 right-2.5 z-[1000] flex h-7 items-center gap-1 rounded-full bg-white px-2.5 text-13 lg:bottom-3.5 lg:right-3.5 lg:h-8 lg:px-3 lg:text-14">
                                <i class="ti ti-map-pin"></i>{{ $listing->locationLabel() }} · موقع تقريبي
                            </span>
                        </div>
                    </section>
                @endif

                <dl class="grid rounded-xl border border-line bg-white text-14 lg:grid-cols-2 lg:text-15">
                    <div class="flex justify-between gap-3 px-3.5 py-3 lg:px-[18px] lg:py-4">
                        <dt class="text-muted">رقم ترخيص الإعلان</dt>
                        <dd class="font-semibold" dir="ltr">{{ $listing->ad_license_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-divider px-3.5 py-3 lg:border-s lg:border-t-0 lg:px-[18px] lg:py-4">
                        <dt class="text-muted">{{ $membership?->fal_number ? 'رخصة فال للوسيط' : 'رخصة فال' }}</dt>
                        <dd class="font-semibold" dir="ltr">{{ $membership?->fal_number ?: $workspace->fal_number }}</dd>
                    </div>
                </dl>
            </div>

            <aside class="flex flex-col gap-7 lg:sticky lg:top-6 lg:gap-[18px] lg:rounded-2xl lg:border lg:border-line lg:bg-white lg:p-6">
                @if ($agent)
                    <div class="flex flex-col gap-3.5 rounded-card border border-line bg-white p-4 lg:gap-[18px] lg:rounded-none lg:border-0 lg:p-0">
                        <a href="{{ $agentUrl }}" class="flex items-center gap-3 text-ink hover:text-ink">
                            <x-site.avatar :name="$agent->name" class="size-14 text-20 lg:size-[60px]" />
                            <span class="flex flex-1 flex-col gap-0.5">
                                <span class="text-16 font-semibold lg:text-17">{{ $agent->name }}</span>
                                <span class="text-13 text-muted">{{ $membership ? $membership->roleLabel().' · ' : '' }}{{ $workspace->name }}</span>
                            </span>
                            <i class="ti ti-chevron-left text-18 text-subtle"></i>
                        </a>
                        <div class="flex gap-2">
                            @if ($whatsapp)
                                <x-site.contact kind="whatsapp" :number="$whatsapp" :text="$whatsappText" :listing="$listing->short_code" class="h-11 flex-1 text-14" />
                            @endif
                            @if ($phone)
                                <x-site.contact kind="call" :number="$phone" :listing="$listing->short_code" class="h-11 flex-1 text-14" />
                            @endif
                        </div>
                    </div>
                    <div class="hidden h-px bg-line lg:block"></div>
                @endif

                <livewire:site.inquiry-form :listing="$listing" />
            </aside>
        </div>

        {{-- Phones: contact bar --}}
        @if ($whatsapp || $phone)
            <div class="fixed inset-x-0 bottom-0 z-20 flex gap-2.5 border-t border-line bg-white px-4 pb-[calc(12px+env(safe-area-inset-bottom))] pt-3 lg:hidden">
                @if ($whatsapp)
                    <x-site.contact kind="whatsapp" :number="$whatsapp" :text="$whatsappText" :listing="$listing->short_code" class="h-12 flex-1 text-15" />
                @endif
                @if ($phone)
                    <x-site.contact kind="call" :number="$phone" :listing="$listing->short_code" class="h-12 flex-1 text-15" />
                @endif
            </div>
        @endif

        {{-- Full-screen photo viewer --}}
        <template x-if="photos.length">
            <div x-show="open" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="معرض الصور"
                 class="fixed inset-0 z-50 flex flex-col bg-[#14120F] text-white"
                 x-on:keydown.escape.window="open && close()"
                 x-on:keydown.arrow-left.window="open && next()"
                 x-on:keydown.arrow-right.window="open && prev()">
                <div class="flex items-center justify-between py-3 pe-3 ps-5 lg:px-7 lg:py-5">
                    <span class="text-15 lg:text-16" dir="ltr" x-text="counter"></span>
                    <button type="button" x-on:click="close()" class="flex size-11 items-center justify-center rounded-full bg-white/12 lg:size-12" aria-label="إغلاق">
                        <i class="ti ti-x text-20 lg:text-22"></i>
                    </button>
                </div>
                <div class="relative flex min-h-0 flex-1 items-center justify-center lg:px-[120px]">
                    <template x-if="photos[index].url">
                        <img :src="photos[index].url" alt="" class="max-h-full w-full object-contain lg:max-w-[1000px] lg:rounded-lg">
                    </template>
                    <template x-if="! photos[index].url">
                        <div class="flex h-[300px] w-full items-center justify-center lg:aspect-[3/2] lg:h-auto lg:max-h-full lg:max-w-[1000px] lg:rounded-lg"
                             style="background: repeating-linear-gradient(135deg, #2A2621 0 14px, #24201C 14px 28px)">
                            <span class="font-mono text-11 text-[#B5ADA2] lg:text-12" dir="ltr" x-text="photos[index].label"></span>
                        </div>
                    </template>
                    <button type="button" x-on:click="prev()" class="absolute right-3 flex size-11 items-center justify-center rounded-full bg-white/14 lg:right-9 lg:size-14" aria-label="الصورة السابقة">
                        <i class="ti ti-chevron-right text-20 lg:text-24"></i>
                    </button>
                    <button type="button" x-on:click="next()" class="absolute left-3 flex size-11 items-center justify-center rounded-full bg-white/14 lg:left-9 lg:size-14" aria-label="الصورة التالية">
                        <i class="ti ti-chevron-left text-20 lg:text-24"></i>
                    </button>
                </div>
                <div class="scrollbar-none flex gap-1.5 overflow-x-auto p-4 lg:justify-center lg:gap-2 lg:p-5">
                    <template x-for="(photo, position) in photos" :key="position">
                        <button type="button" x-on:click="index = position"
                                class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border-2 lg:w-20"
                                :class="position === index ? 'border-white opacity-100' : 'border-transparent opacity-55'"
                                :style="photo.url ? '' : 'background: repeating-linear-gradient(135deg, #3A352F 0 6px, #2F2B26 6px 12px)'"
                                :aria-label="`الصورة ${position + 1}`">
                            <template x-if="photo.url"><img :src="photo.url" alt="" class="size-full object-cover"></template>
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-layouts.site>
