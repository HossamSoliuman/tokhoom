<x-app.page title="الفريق">
    <x-slot:actions>
        <x-ui.button icon="ti-user-plus" size="sm" wire:click="openInvite">دعوة عضو</x-ui.button>
    </x-slot:actions>

    <div class="overflow-hidden rounded-card border border-line bg-white">
        <div class="hidden h-10 grid-cols-[2fr_1.2fr_1.4fr_1fr_150px] items-center border-b border-line px-5 text-12 font-semibold text-muted lg:grid">
            <span>العضو</span><span>الدور</span><span>انتهاء رخصة فال</span><span>عقارات نشطة</span><span></span>
        </div>

        @foreach ($members as $member)
            @php($warn = $member->falExpiresWithin(60))
            @php($count = $activeCounts->get($member->user_id, 0))
            <div class="grid grid-cols-2 items-center gap-x-4 gap-y-2 border-t border-divider px-5 py-3.5 text-14 first:border-t-0 lg:grid-cols-[2fr_1.2fr_1.4fr_1fr_150px] lg:first:border-t" wire:key="member-{{ $member->id }}">
                <span class="col-span-2 flex items-center gap-3 lg:col-span-1">
                    <x-ui.avatar :initial="$member->user->initials()" :size="34" tone="brand" />
                    <span class="flex flex-col">
                        <span class="text-14 font-bold">{{ $member->user->name }}</span>
                        <span class="text-right text-12 text-muted" dir="ltr">{{ $member->role === \App\Enums\MemberRole::Owner ? $member->user->email : Phone::display($member->user->mobile ?: $member->user->email) }}</span>
                    </span>
                </span>
                <span>
                    <x-app.tag :tone="$member->role === \App\Enums\MemberRole::Owner ? 'brand' : 'gray'">{{ $member->roleLabel() }}</x-app.tag>
                </span>
                <span>
                    <x-app.tag :tone="$warn ? 'warn' : 'good'">
                        @if ($warn)
                            <i class="ti ti-alert-triangle"></i>
                        @endif
                        <span class="lg:hidden">فال:</span> {{ Arabic::date($member->fal_expires_on) }}
                    </x-app.tag>
                </span>
                <span class="text-body">{{ $count }}<span class="lg:hidden"> عقارات نشطة</span></span>
                <span class="flex justify-end">
                    @if ($member->role !== \App\Enums\MemberRole::Owner && $member->user_id !== auth()->id())
                        <x-ui.button variant="danger-outline" size="xs" class="text-13" wire:click="openDeactivate({{ $member->id }})">إيقاف</x-ui.button>
                    @endif
                </span>
            </div>
        @endforeach

        @foreach ($invitations as $invitation)
            <div class="grid grid-cols-2 items-center gap-x-4 gap-y-2 border-t border-divider px-5 py-3.5 text-14 lg:grid-cols-[2fr_1.2fr_1.4fr_1fr_150px]" wire:key="invitation-{{ $invitation->id }}">
                <span class="col-span-2 flex items-center gap-3 lg:col-span-1">
                    <span class="photo-stripes size-[34px] shrink-0 rounded-full"></span>
                    <span class="flex flex-col">
                        <span class="text-15 font-semibold">{{ $invitation->name ?? ($invitation->channel === \App\Enums\InvitationChannel::Mobile ? Phone::display($invitation->contact) : $invitation->contact) }}</span>
                        <span class="text-right text-12 text-muted" dir="ltr">{{ $invitation->channel === \App\Enums\InvitationChannel::Mobile ? Phone::display($invitation->contact) : $invitation->contact }}</span>
                    </span>
                </span>
                <span><x-app.tag tone="warn">دعوة معلّقة</x-app.tag></span>
                <span class="text-body">—</span>
                <span class="text-body">—</span>
                <span class="flex justify-end gap-1.5">
                    <x-ui.button variant="quiet" size="xs" class="text-13" wire:click="resend({{ $invitation->id }})">إعادة الإرسال</x-ui.button>
                    <button type="button" wire:click="cancelInvite({{ $invitation->id }})" class="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-canvas" aria-label="إلغاء الدعوة">
                        <i class="ti ti-x text-17"></i>
                    </button>
                </span>
            </div>
        @endforeach
    </div>

    @if ($dialog === 'invite')
        <x-ui.dialog title="دعوة عضو">
            <div class="flex gap-0.5 rounded-field border border-line-strong p-[3px]">
                @foreach (['mobile' => 'رقم الجوال', 'email' => 'البريد الإلكتروني'] as $value => $label)
                    <button type="button" wire:click="$set('channel', '{{ $value }}')" @class([
                        'h-10 flex-1 rounded-[7px] text-14 font-semibold',
                        'bg-primary text-white' => $channel === $value,
                        'text-ink' => $channel !== $value,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
            <x-ui.field :label="$channel === 'email' ? 'البريد الإلكتروني' : 'رقم الجوال'" error="contact">
                <input type="{{ $channel === 'email' ? 'email' : 'tel' }}" wire:model="contact" placeholder="{{ $channel === 'email' ? 'name@example.com' : '05XXXXXXXX' }}" class="field text-right" dir="ltr" autofocus>
            </x-ui.field>
            <div class="flex flex-col gap-2">
                <span class="text-14 font-medium">الدور</span>
                <x-ui.radio-option :active="$role === 'agent'" label="وسيط" description="يدير عقاراته وعملاءه وصفقاته فقط" wire:click="$set('role', 'agent')" />
                <x-ui.radio-option :active="$role === 'manager'" label="مدير" description="يرى كل العقارات والعملاء والتقارير ويدير الفريق" wire:click="$set('role', 'manager')" />
            </div>
            <span class="text-13 text-muted">{{ $seatsLeft > 0 ? 'متبقٍ '.$seatsLeft.' من مقاعد خطتك' : 'لا توجد مقاعد متاحة في خطتك' }}</span>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="sendInvite">إرسال الدعوة</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'deactivate' && $leaving)
        <x-ui.dialog title="إيقاف عضو">
            <div class="flex items-center gap-3 rounded-xl bg-canvas p-3">
                <x-ui.avatar :initial="$leaving->user->initials()" :size="44" tone="brand" />
                <div class="flex flex-col">
                    <span class="text-15 font-semibold">{{ $leaving->user->name }}</span>
                    <span class="text-13 text-muted">لن يتمكن من الدخول بعد الإيقاف</span>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($leavingCounts as $label => $count)
                    <div class="flex flex-col gap-0.5 rounded-xl border border-line p-3 text-center">
                        <span class="text-20 font-bold">{{ $count }}</span>
                        <span class="text-13 text-muted">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <x-ui.field label="نقل العقارات والعملاء والصفقات إلى" error="recipientId">
                <x-ui.select wire:model="recipientId" size="lg">
                    @foreach ($recipients as $recipient)
                        <option value="{{ $recipient->id }}">{{ $recipient->user->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="deactivate">إيقاف ونقل البيانات</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
