<x-layouts.site :workspace="$workspace">
    <h1 class="mb-5 text-24 font-bold text-ink">العقارات المتاحة</h1>

    @if ($listings->isEmpty())
        <x-ui.empty icon="ti-home" title="لا توجد عقارات معروضة حالياً" />
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($listings as $listing)
                <a href="{{ route('site.listing', $listing->short_code) }}" class="flex flex-col gap-2 rounded-card border border-line bg-white p-4 hover:border-line-strong hover:no-underline">
                    <span class="text-16 font-semibold text-ink">{{ $listing->title }}</span>
                    <span class="text-15 font-bold text-primary">{{ $listing->priceLabel() }}</span>
                    <x-listing-status :status="$listing->status" class="self-start" />
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.site>
