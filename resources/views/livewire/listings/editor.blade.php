<x-app.page :title="$listing ? 'تعديل العقار' : 'إضافة عقار'" :back="$listing ? route('listings.show', $listing) : route('listings.index')" :back-label="$listing ? 'العقار' : 'العقارات'">
    <div class="flex max-w-[880px] flex-col gap-4">
        {{-- Photos --}}
        <x-ui.card class="gap-3">
            <x-slot:heading>
                <h2 class="m-0 text-16 font-bold">الصور</h2>
                <span class="text-13 text-muted">{{ count($photos) }} من {{ \App\Models\Listing::MAX_PHOTOS }}</span>
            </x-slot:heading>

            <div x-data="{ uploading: false, progress: 0, count: 0 }"
                 x-on:livewire-upload-start="uploading = true; progress = 0"
                 x-on:livewire-upload-finish="uploading = false"
                 x-on:livewire-upload-cancel="uploading = false"
                 x-on:livewire-upload-error="uploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 class="grid grid-cols-[repeat(auto-fill,minmax(104px,1fr))] gap-2">
                <div wire:sort="reorderPhoto" class="contents">
                    @foreach ($photos as $index => $photo)
                        <div wire:key="photo-{{ $photo['key'] }}" wire:sort:item="{{ $photo['key'] }}"
                             @class(['relative aspect-square cursor-grab overflow-hidden rounded-field border-2', 'border-primary' => $index === 0, 'border-transparent' => $index > 0])>
                            @if ($photo['id'])
                                <x-ui.photo :photo="$savedPhotos->get($photo['id'])" class="size-full" />
                            @elseif (isset($pending[$photo['key']]))
                                <img src="{{ $pending[$photo['key']]->temporaryUrl() }}" alt="" class="size-full object-cover">
                            @endif
                            @if ($index === 0)
                                <span class="absolute right-1.5 top-1.5 flex h-[22px] items-center rounded-full bg-primary px-2 text-11 font-semibold text-white">الغلاف</span>
                            @endif
                            <button type="button" wire:sort:ignore wire:click="removePhoto('{{ $photo['key'] }}')"
                                    class="absolute left-1.5 top-1.5 flex size-[26px] items-center justify-center rounded-full bg-ink/65 text-white" aria-label="حذف الصورة">
                                <i class="ti ti-x text-14"></i>
                            </button>
                            <i class="ti ti-grip-horizontal absolute bottom-1.5 left-1/2 -translate-x-1/2 text-16 text-photo-label"></i>
                        </div>
                    @endforeach
                </div>

                <template x-for="slot in (uploading ? count : 0)" :key="slot">
                    <div class="photo-stripes relative aspect-square overflow-hidden rounded-field">
                        <div class="absolute inset-0 flex flex-col justify-end gap-1 bg-canvas/70 p-2">
                            <span class="text-center text-12 font-semibold" x-text="progress + '%'"></span>
                            <span class="h-1 overflow-hidden rounded-sm bg-line"><span class="block h-full bg-primary" :style="'width: ' + progress + '%'"></span></span>
                        </div>
                    </div>
                </template>

                @if (count($photos) < \App\Models\Listing::MAX_PHOTOS)
                    <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-field border-[1.5px] border-dashed border-faint bg-canvas text-13 font-semibold text-primary">
                        <i class="ti ti-camera-plus text-24"></i>
                        إضافة صور
                        <input type="file" wire:model="uploads" multiple accept="image/*" class="sr-only" x-on:change="count = $event.target.files.length">
                    </label>
                @endif
            </div>
            @error('uploads.*')
                <span class="text-13 text-danger">{{ $message }}</span>
            @enderror
        </x-ui.card>

        {{-- Basics --}}
        <x-ui.card>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\ListingPurpose::cases() as $case)
                    <x-ui.choice wire:model.live="purpose" :value="$case->value" :active="$purpose === $case->value" class="h-14 text-16">{{ $case->label() }}</x-ui.choice>
                @endforeach
            </div>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(76px,1fr))] gap-2">
                @foreach (\App\Enums\ListingType::cases() as $case)
                    <x-ui.choice wire:model="type" :value="$case->value" :active="$type === $case->value" :icon="$case->icon()" class="h-16 text-14">{{ $case->label() }}</x-ui.choice>
                @endforeach
            </div>

            <x-ui.field :label="$purpose === 'rent' ? 'الإيجار السنوي' : 'السعر'" error="price">
                <span class="flex h-12 items-center overflow-hidden rounded-field border border-line-strong bg-white focus-within:border-primary">
                    <input type="text" inputmode="numeric" wire:model.blur="price"
                           x-on:input="$el.value = $el.value.replace(/[^\d]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                           class="h-full min-w-0 flex-1 border-0 px-3.5 text-16 font-semibold">
                    <span class="flex h-full items-center whitespace-nowrap border-r border-line bg-head px-3.5 text-14 text-muted">{{ $purpose === 'rent' ? 'ريال / سنوياً' : 'ريال' }}</span>
                </span>
            </x-ui.field>

            <div class="grid grid-cols-[repeat(auto-fit,minmax(100px,1fr))] gap-2.5">
                @foreach (['area' => 'المساحة (م²)', 'bedrooms' => 'الغرف', 'bathrooms' => 'دورات المياه', 'age' => 'العمر (سنة)', 'floors' => 'الأدوار'] as $field => $label)
                    <x-ui.field :label="$label" size="sm" :error="$field">
                        <input type="text" inputmode="numeric" wire:model.blur="{{ $field }}" class="field px-3 text-16"
                               x-on:input="$el.value = $el.value.replace(/[^\d]/g, '')">
                    </x-ui.field>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Location --}}
        <x-ui.card title="الموقع">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-2.5">
                <x-search-picker label="المدينة" property="city" :options="\App\Support\Locations::cities()" :value="$city" error="city" />
                <x-search-picker label="الحي" property="district" :options="$districts" :value="$district" :live="false" error="district" wire:key="district-picker-{{ $city }}" />
            </div>

            <div wire:ignore x-data="locationPicker({ latitude: @js($latitude), longitude: @js($longitude) })"
                 class="map-stripes relative isolate h-[200px] overflow-hidden rounded-xl">
                <div x-ref="map" class="absolute inset-0 z-0"></div>
                <i class="ti ti-map-pin-filled pointer-events-none absolute left-1/2 top-1/2 z-[500] -mt-[30px] -translate-x-1/2 -translate-y-1/2 text-[40px] text-primary"></i>
                <span class="pointer-events-none absolute inset-x-2.5 bottom-2.5 z-[500] flex h-9 items-center justify-center gap-1.5 rounded-full bg-white text-13">
                    <i class="ti ti-hand-move"></i>
                    حرّك الخريطة لتحديد الموقع · يظهر للزوار كمنطقة تقريبية
                </span>
            </div>
        </x-ui.card>

        {{-- Features and description --}}
        <x-ui.card title="المميزات والوصف">
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Support\Locations::FEATURES as $feature)
                    <x-ui.chip multiple wire:model="features" :value="$feature" :active="in_array($feature, $features, true)" class="font-medium" wire:key="feature-{{ $loop->index }}">
                        <i class="ti ti-check hidden text-15 group-has-checked/chip:inline"></i>
                        {{ $feature }}
                    </x-ui.chip>
                @endforeach
            </div>
            <textarea wire:model="description" rows="4" placeholder="اكتب وصفاً مختصراً للعقار: التقسيم، التشطيب، القرب من الخدمات…"
                      class="resize-y rounded-field border border-line-strong px-3.5 py-3 text-15 leading-6 focus:border-primary"></textarea>
        </x-ui.card>

        {{-- Licenses and links --}}
        <x-ui.card title="التراخيص والروابط">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-3">
                <x-ui.field label="رقم ترخيص الإعلان" required error="adLicenseNumber">
                    <input type="text" inputmode="numeric" wire:model.live.debounce.300ms="adLicenseNumber" placeholder="72000XXXXX" dir="ltr"
                           @class(['field border-[1.5px] text-right', 'border-success' => strlen($adLicenseNumber) === 10])>
                </x-ui.field>
                <x-ui.field label="تاريخ انتهاء الترخيص" error="adLicenseExpiresOn">
                    <input type="date" wire:model="adLicenseExpiresOn" class="field">
                </x-ui.field>
                <x-ui.field label="رقم عقد الوساطة" error="brokerageContractNumber">
                    <input type="text" inputmode="numeric" wire:model="brokerageContractNumber" class="field text-right" dir="ltr">
                </x-ui.field>
                <x-ui.field label="رابط الجولة الثلاثية" optional error="tourUrl">
                    <input type="url" wire:model="tourUrl" placeholder="https://" class="field text-left" dir="ltr">
                </x-ui.field>
                <x-ui.field label="رابط الفيديو" optional error="videoUrl">
                    <input type="url" wire:model="videoUrl" placeholder="https://" class="field text-left" dir="ltr">
                </x-ui.field>
            </div>
        </x-ui.card>
    </div>

    <div class="sticky bottom-[76px] z-[6] -mx-1 flex max-w-[888px] flex-wrap items-center gap-2.5 rounded-card border border-line bg-white p-3 lg:bottom-4">
        @if (! $this->canPublish())
            <span class="flex flex-[1_1_100%] items-center gap-1.5 text-13 text-muted">
                <i class="ti ti-lock text-16"></i>
                أدخل رقم ترخيص الإعلان لتفعيل النشر
            </span>
        @endif
        @if ($listing && $listing->isPublished())
            <x-ui.button variant="secondary" size="lg" class="flex-1" :href="route('listings.show', $listing)" wire:navigate>إلغاء</x-ui.button>
            <x-ui.button size="lg" class="flex-1" wire:click="publish" :disabled="! $this->canPublish()">حفظ التعديلات</x-ui.button>
        @else
            <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="saveDraft">حفظ كمسودة</x-ui.button>
            <x-ui.button size="lg" class="flex-1" wire:click="publish" :disabled="! $this->canPublish()">نشر</x-ui.button>
        @endif
    </div>
</x-app.page>
