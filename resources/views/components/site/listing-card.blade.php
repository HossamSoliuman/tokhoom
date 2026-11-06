{{-- A listing in the site's grid. Expects coverPhoto, agent and photos_count loaded. --}}
@props(['listing', 'showAgent' => false])

<a href="{{ route('site.listing', $listing->short_code) }}"
   {{ $attributes->class('group flex flex-col overflow-hidden rounded-card border border-line bg-white text-ink transition-colors hover:border-faint hover:text-ink') }}>
    <x-ui.photo :photo="$listing->coverPhoto" class="aspect-[4/3]" large>
        <span class="absolute right-3 top-3 flex gap-1.5 lg:right-3.5 lg:top-3.5">
            <span class="flex h-[26px] items-center rounded-full bg-white px-2.5 text-13 font-semibold text-brand-text lg:h-7 lg:px-3">{{ $listing->purpose->label() }}</span>
            @if ($listing->status === \App\Enums\ListingStatus::Reserved)
                <span class="flex h-[26px] items-center rounded-full bg-warning-soft px-2.5 text-13 font-semibold text-warning lg:h-7 lg:px-3">محجوز</span>
            @endif
        </span>
        @if ($listing->photos_count > 0)
            <span class="absolute bottom-3 left-3 flex h-6 items-center gap-1 rounded-full bg-ink/70 px-2 text-12 text-white lg:bottom-3.5 lg:left-3.5">
                <i class="ti ti-photo"></i>{{ $listing->photos_count }}
            </span>
        @endif
    </x-ui.photo>

    <div class="flex flex-1 flex-col gap-1.5 px-4 pb-4 pt-3.5 lg:px-[18px] lg:pb-[18px] lg:pt-4">
        <div class="flex items-baseline gap-1.5">
            <span class="text-20 font-bold lg:text-22">{{ $listing->priceAmount() }}</span>
            <span class="text-13 text-muted lg:text-14">{{ $listing->priceUnit() }}</span>
        </div>
        <div class="text-15 font-medium lg:text-16">{{ $listing->shortTitle() }}</div>
        <div class="flex flex-wrap gap-x-3.5 gap-y-1 text-13 text-muted lg:gap-x-4 lg:text-14">
            @if ($listing->area)
                <span class="flex items-center gap-1"><i class="ti ti-ruler-measure text-16 lg:text-17"></i>{{ \App\Support\Arabic::number($listing->area) }} م²</span>
            @endif
            @if ($listing->bedrooms)
                <span class="flex items-center gap-1"><i class="ti ti-bed text-16 lg:text-17"></i>{{ \App\Support\Arabic::count($listing->bedrooms, 'غرفة', 'غرفتان', 'غرف', 'غرفة') }}</span>
            @endif
            @if ($listing->bathrooms)
                <span class="flex items-center gap-1"><i class="ti ti-bath text-16 lg:text-17"></i>{{ \App\Support\Arabic::countWithNumber($listing->bathrooms, 'دورة مياه', 'دورات مياه', 'دورة مياه') }}</span>
            @endif
        </div>
        @if ($showAgent && $listing->agent)
            <div class="mt-auto flex items-center gap-2 border-t border-divider pt-2.5 text-13 text-muted lg:pt-3">
                <x-site.avatar :name="$listing->agent->name" class="size-6 text-11 lg:size-[26px]" />
                {{ $listing->agent->name }}
            </div>
        @endif
    </div>
</a>
