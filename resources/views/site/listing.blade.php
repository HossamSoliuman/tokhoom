<x-layouts.site :workspace="$workspace" :title="$listing->title">
    <a href="{{ route('site.home') }}" class="mb-5 inline-flex items-center gap-1.5 text-14 font-semibold text-primary">
        <i class="ti ti-arrow-right"></i>
        كل العقارات
    </a>

    <div class="flex flex-col gap-3 rounded-card border border-line bg-white p-5">
        <h1 class="text-24 font-bold text-ink">{{ $listing->title }}</h1>
        <span class="text-20 font-bold text-primary">{{ $listing->priceLabel() }}</span>
        <x-listing-status :status="$listing->status" class="self-start" />
        @if ($listing->ad_license_number)
            <span class="text-13 text-muted">رقم ترخيص الإعلان: <span dir="ltr">{{ $listing->ad_license_number }}</span></span>
        @endif
    </div>
</x-layouts.site>
