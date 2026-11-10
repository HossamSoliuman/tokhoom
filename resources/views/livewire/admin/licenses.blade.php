<x-app.page title="التراخيص">
    <div class="scrollbar-none flex max-w-full gap-1.5 overflow-x-auto">
        @foreach (['expired' => 'منتهية', '30' => 'خلال 30 يوماً', '60' => 'خلال 60 يوماً', '90' => 'خلال 90 يوماً'] as $value => $label)
            <x-ui.chip wire:model.live="window" :value="$value" :active="$window === (string) $value" wire:key="window-{{ $value }}">{{ $label }}</x-ui.chip>
        @endforeach
    </div>

    <x-ui.card title="تراخيص المكاتب والوسطاء المستقلين" flush :count="$workspaces->count()">
        @forelse ($workspaces as $workspace)
            @php($owner = $workspace->memberships->first()?->user)
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 border-t border-divider px-4 py-3" wire:key="workspace-{{ $workspace->id }}">
                <div class="flex min-w-0 flex-[1_1_240px] items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-field bg-divider"><i class="ti {{ $workspace->kind->icon() }} text-20 text-body"></i></span>
                    <span class="flex min-w-0 flex-col">
                        <a href="{{ route('admin.workspaces.show', $workspace) }}" wire:navigate class="truncate text-15 font-semibold text-ink">{{ $workspace->name }}</a>
                        <span class="truncate text-13 text-muted">{{ $owner?->name }}@if ($owner?->mobile) · <span dir="ltr">{{ Phone::display($owner->mobile) }}</span>@endif</span>
                    </span>
                </div>
                <span class="flex flex-[0_1_200px] flex-col text-13">
                    <span class="text-muted">فال <span dir="ltr">{{ $workspace->fal_number }}</span></span>
                    <span class="text-14 font-semibold">{{ Arabic::date($workspace->license_expires_on) }}</span>
                </span>
                <x-admin.expiry-pill :date="$workspace->license_expires_on" />
                <div class="ms-auto flex gap-2">
                    @if ($owner?->mobile)
                        <x-ui.button variant="whatsapp" size="sm" icon="ti-brand-whatsapp" class="px-3 text-13" :href="Phone::whatsappUrl($owner->mobile)" target="_blank" rel="noopener" aria-label="واتساب" />
                    @endif
                    <x-ui.button variant="primary-outline" size="sm" class="text-13" wire:click="openRenewal({{ $workspace->id }})">تسجيل التجديد</x-ui.button>
                </div>
            </div>
        @empty
            <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد تراخيص في هذه الفترة.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="رخص الوسطاء في المكاتب" flush :count="$members->count()">
        @forelse ($members as $membership)
            <a href="{{ route('admin.workspaces.show', $membership->workspace) }}" wire:navigate wire:key="member-{{ $membership->id }}"
               class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-divider px-4 py-3 text-ink hover:bg-canvas hover:no-underline">
                <span class="flex min-w-0 flex-[1_1_240px] items-center gap-3">
                    <x-ui.avatar :initial="$membership->user->initials()" :size="36" />
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-14 font-semibold">{{ $membership->user->name }}</span>
                        <span class="truncate text-13 text-muted">{{ $membership->workspace->name }}</span>
                    </span>
                </span>
                <span class="flex flex-[0_1_200px] flex-col text-13">
                    <span class="text-muted">فال <span dir="ltr">{{ $membership->fal_number ?? '—' }}</span></span>
                    <span class="text-14 font-semibold">{{ Arabic::date($membership->fal_expires_on) }}</span>
                </span>
                <x-admin.expiry-pill :date="$membership->fal_expires_on" />
            </a>
        @empty
            <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد رخص في هذه الفترة.</p>
        @endforelse
    </x-ui.card>

    @if ($dialog === 'renew' && $renewing)
        <x-ui.dialog title="تسجيل تجديد الترخيص">
            <div class="flex flex-col gap-0.5 rounded-xl bg-canvas px-3.5 py-3">
                <span class="text-15 font-semibold">{{ $renewing->name }}</span>
                <span class="text-13 text-muted">فال <span dir="ltr">{{ $renewing->fal_number }}</span> · ينتهي حالياً {{ Arabic::date($renewing->license_expires_on) }}</span>
            </div>
            <x-ui.field label="تاريخ الانتهاء الجديد" error="expiresOn">
                <input type="date" wire:model="expiresOn" class="field">
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="renew">حفظ</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
