<x-app.page :title="$client->name" :back="route('clients.index')" back-label="العملاء">
    <div class="flex flex-wrap items-center gap-3.5 rounded-card border border-line bg-white p-4">
        <x-ui.avatar :initial="$client->initial()" :size="56" tone="brand" />
        <div class="flex flex-[1_1_200px] flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-13 text-muted" dir="ltr">{{ Phone::display($client->mobile) }}</span>
                <span class="inline-flex h-6 items-center rounded-full bg-divider px-2.5 text-12">{{ $client->type->label() }}</span>
            </div>
            <span class="text-13 text-muted">المصدر: {{ $client->source->label() }} · الوسيط: {{ $client->agent->name }}</span>
        </div>
        <div class="flex flex-[1_1_220px] justify-end gap-2">
            <x-ui.button variant="whatsapp" icon="ti-brand-whatsapp" class="max-w-[140px] flex-1 text-14" :href="Phone::whatsappUrl($client->mobile)" target="_blank" rel="noopener">واتساب</x-ui.button>
            <x-ui.button variant="secondary" icon="ti-phone" class="max-w-[140px] flex-1 text-14" :href="Phone::telUrl($client->mobile)">اتصال</x-ui.button>
        </div>
    </div>

    <div class="flex flex-wrap items-start gap-4">
        <div class="flex min-w-0 flex-[2_1_460px] flex-col gap-4">
            <x-ui.card title="الطلبات" class="gap-3">
                <x-slot:heading>
                    <x-ui.button variant="ghost" size="xs" icon="ti-plus" class="text-14" wire:click="openRequest">إضافة طلب</x-ui.button>
                </x-slot:heading>
                @forelse ($client->requests as $request)
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] rounded-xl border border-line" wire:key="request-{{ $request->id }}">
                        @foreach (['الغرض' => $request->purpose->requestLabel(), 'النوع' => $request->typesLabel(), 'الأحياء' => $request->districtsLabel(), 'الميزانية' => $request->budgetLabel()] as $key => $value)
                            <div class="flex flex-col gap-0.5 border-b border-divider px-3.5 py-3">
                                <span class="text-12 text-muted">{{ $key }}</span>
                                <span class="text-14 font-semibold">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="m-0 text-14 text-muted">أضف طلب العميل لتصلك العقارات المطابقة.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="عقارات مطابقة" class="gap-3">
                @forelse ($matches as $listing)
                    <div class="flex items-center gap-3 rounded-xl border border-divider p-2.5" wire:key="match-{{ $listing->id }}">
                        <x-ui.photo :photo="$listing->coverPhoto" label="" class="h-14 w-[72px] shrink-0 rounded-lg" />
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <a href="{{ route('listings.show', $listing) }}" wire:navigate class="text-14 font-semibold text-ink">{{ $listing->title }}</a>
                            <span class="text-13 font-semibold text-primary">{{ $listing->priceLabel() }}</span>
                            <span class="text-12 text-muted">يطابق الغرض والنوع والحي والميزانية</span>
                        </div>
                        <x-ui.button variant="whatsapp" size="sm" icon="ti-brand-whatsapp" class="px-3 text-13"
                                     :href="Phone::whatsappUrl($client->mobile, $listing->pitchFor($client->name))" target="_blank" rel="noopener"
                                     wire:click="markSent({{ $listing->id }})">إرسال</x-ui.button>
                    </div>
                @empty
                    <p class="m-0 text-14 text-muted">لا توجد عقارات متاحة تطابق طلبات العميل حالياً.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="سجل النشاط" class="gap-1">
                <x-timeline :items="$timeline" class="mt-2" />
            </x-ui.card>
        </div>

        <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-4">
            <x-ui.card title="التذكيرات" class="gap-2.5">
                <x-slot:heading>
                    <button type="button" wire:click="openReminder" class="flex size-9 items-center justify-center rounded-lg text-primary hover:bg-primary-tint" aria-label="إضافة تذكير">
                        <i class="ti ti-plus text-[19px]"></i>
                    </button>
                </x-slot:heading>
                @forelse ($reminders as $reminder)
                    @php($done = $reminder->isDone())
                    <div class="flex items-center gap-2.5" wire:key="reminder-{{ $reminder->id }}">
                        <button type="button" wire:click="toggleReminder({{ $reminder->id }})" aria-label="{{ $done ? 'إلغاء الإنجاز' : 'تم الإنجاز' }}">
                            <x-ui.checkbox :checked="$done" size="sm" />
                        </button>
                        <div class="flex flex-1 flex-col">
                            <span @class(['text-14', 'text-subtle line-through' => $done])>{{ $reminder->title }}</span>
                            <span @class(['text-12', 'text-warning' => $reminder->isDue(), 'text-muted' => ! $reminder->isDue()])>{{ Arabic::dayAndTime($reminder->due_at) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="m-0 text-14 text-muted">لا توجد تذكيرات.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="الملاحظات" class="gap-3">
                <textarea wire:model="noteBody" rows="3" placeholder="أضف ملاحظة…"
                          class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-14 leading-[22px] focus:border-primary"></textarea>
                @error('noteBody')
                    <span class="-mt-1.5 text-13 text-danger">{{ $message }}</span>
                @enderror
                <div class="flex items-center justify-between gap-2">
                    <label class="group/check relative flex cursor-pointer items-center gap-2 text-14 text-ink">
                        <input type="checkbox" wire:model="noteShared" @checked($noteShared) class="sr-only">
                        <x-ui.switch />
                        <span x-text="$wire.noteShared ? 'مشتركة مع الفريق' : 'خاصة'">{{ $noteShared ? 'مشتركة مع الفريق' : 'خاصة' }}</span>
                    </label>
                    <x-ui.button size="sm" class="h-[38px] rounded-lg px-3.5 text-14" wire:click="addNote">حفظ</x-ui.button>
                </div>
                @foreach ($notes as $note)
                    <div class="flex flex-col gap-1.5 rounded-field bg-canvas p-3" wire:key="note-{{ $note->id }}">
                        <span class="text-14 leading-[22px]">{{ $note->body }}</span>
                        <span class="flex items-center gap-1.5 text-12 text-muted">
                            <i class="ti {{ $note->is_shared ? 'ti-users' : 'ti-lock' }} text-14"></i>
                            {{ $note->is_shared ? 'مشتركة مع الفريق' : 'خاصة' }} · {{ $note->author->name }} · {{ $note->created_at->gt(now()->subMinute()) ? 'الآن' : Arabic::date($note->created_at) }}
                        </span>
                    </div>
                @endforeach
            </x-ui.card>
        </div>
    </div>

    @if ($dialog === 'request')
        <x-ui.dialog title="إضافة طلب">
            <div class="grid grid-cols-2 gap-2">
                @foreach ([\App\Enums\ListingPurpose::Rent, \App\Enums\ListingPurpose::Sale] as $case)
                    <x-ui.choice wire:model="requestPurpose" :value="$case->value" :active="$requestPurpose === $case->value" class="h-[52px] text-15">{{ $case->requestLabel() }}</x-ui.choice>
                @endforeach
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-14 font-medium">أنواع العقار</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ([\App\Enums\ListingType::Apartment, \App\Enums\ListingType::Villa, \App\Enums\ListingType::Floor, \App\Enums\ListingType::Land, \App\Enums\ListingType::Commercial] as $case)
                        <x-ui.chip size="lg" multiple wire:model="requestTypes" :value="$case->value" :active="in_array($case->value, $requestTypes, true)">{{ $case->label() }}</x-ui.chip>
                    @endforeach
                </div>
                @error('requestTypes')
                    <span class="text-13 text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-14 font-medium">الأحياء</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($districtOptions as $district)
                        <x-ui.chip size="lg" multiple wire:model="requestDistricts" :value="$district" :active="in_array($district, $requestDistricts, true)" wire:key="district-{{ $loop->index }}">{{ $district }}</x-ui.chip>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-14 font-medium" x-text="$wire.requestPurpose === 'rent' ? 'الميزانية (ريال سنوياً)' : 'الميزانية (ريال)'">{{ $requestPurpose === 'rent' ? 'الميزانية (ريال سنوياً)' : 'الميزانية (ريال)' }}</span>
                <div class="flex gap-2">
                    <input type="text" inputmode="numeric" wire:model="budgetMin" placeholder="من" class="field min-w-0 flex-1">
                    <input type="text" inputmode="numeric" wire:model="budgetMax" placeholder="إلى" class="field min-w-0 flex-1">
                </div>
                @error('budgetMax')
                    <span class="text-13 text-danger">{{ $message }}</span>
                @enderror
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="saveRequest">حفظ الطلب</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'reminder')
        <x-ui.dialog title="إضافة تذكير">
            <x-ui.field label="التذكير" error="reminderTitle">
                <input type="text" wire:model="reminderTitle" placeholder="مثال: اتصال متابعة بعد المعاينة" class="field" autofocus>
            </x-ui.field>
            <x-ui.field label="الموعد" error="reminderDueAt">
                <input type="datetime-local" wire:model="reminderDueAt" class="field">
            </x-ui.field>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\ReminderChannel::cases() as $case)
                    <x-ui.choice wire:model="reminderChannel" :value="$case->value" :active="$reminderChannel === $case->value" :icon="$case->icon()" class="h-16 text-14">{{ $case->label() }}</x-ui.choice>
                @endforeach
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="saveReminder">حفظ التذكير</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
