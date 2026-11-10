<x-app.page title="طلبات اعتماد التراخيص">
    @if ($queue->isEmpty())
        <x-ui.empty icon="ti-checks" tone="success" title="لا توجد طلبات بانتظار المراجعة" />
    @else
        <div class="flex flex-col gap-2.5">
            @foreach ($queue as $workspace)
                @php($owner = $workspace->memberships->first()?->user)
                @php($soon = $workspace->license_expires_on->lte(now()->addDays(60)))
                <div class="flex flex-wrap items-center gap-x-6 gap-y-4 rounded-card border border-line bg-white p-4" wire:key="queue-{{ $workspace->id }}">
                    <div class="flex flex-[1_1_220px] items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-field bg-divider"><i class="ti {{ $workspace->kind->icon() }} text-22 text-body"></i></span>
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <a href="{{ route('admin.workspaces.show', $workspace) }}" wire:navigate class="text-15 font-semibold text-ink">{{ $workspace->name }}</a>
                            <span class="text-13 text-muted">{{ $workspace->kind->label($owner?->gender) }} · {{ $workspace->city }} · قُدّم {{ Arabic::ago($workspace->created_at) }}</span>
                            @if ($owner)
                                <span class="text-13 text-body">{{ $owner->name }}@if ($owner->mobile) · <span dir="ltr">{{ Phone::display($owner->mobile) }}</span>@endif</span>
                            @endif
                        </div>
                    </div>
                    <div class="grid flex-[2_1_360px] grid-cols-3 gap-2 text-13">
                        <span class="flex flex-col gap-0.5"><span class="text-muted">رخصة فال</span><span class="text-14 font-semibold">{{ $workspace->fal_number }}</span></span>
                        <span class="flex flex-col gap-0.5"><span class="text-muted">السجل التجاري</span><span class="text-14 font-semibold">{{ $workspace->cr_number ?? '—' }}</span></span>
                        <span class="flex flex-col gap-0.5"><span class="text-muted">الانتهاء</span><span @class(['text-14 font-semibold', 'text-warning' => $soon])>{{ Arabic::date($workspace->license_expires_on) }}</span></span>
                    </div>
                    <div class="ms-auto flex flex-none gap-2">
                        <x-ui.button variant="danger-outline" size="sm" class="px-4 text-14" wire:click="openReject({{ $workspace->id }})">رفض</x-ui.button>
                        <x-ui.button size="sm" icon="ti-check" class="px-4 text-14" wire:click="approve({{ $workspace->id }})">اعتماد</x-ui.button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($dialog === 'reject' && $rejecting)
        <x-ui.dialog title="رفض الطلب">
            <div class="flex flex-col gap-0.5 rounded-xl bg-canvas px-3.5 py-3">
                <span class="text-15 font-semibold">{{ $rejecting->name }}</span>
                <span class="text-13 text-muted">فال {{ $rejecting->fal_number }}</span>
            </div>
            <div class="flex flex-col gap-2">
                @foreach (\App\Enums\RejectionReason::cases() as $case)
                    <x-ui.radio-option wire:model="reason" :value="$case->value" :active="$reason === $case->value" :label="$case->label()" />
                @endforeach
                @error('reason')
                    <span class="text-13 text-danger">{{ $message }}</span>
                @enderror
            </div>
            <x-ui.field label="رسالة للمتقدم" error="message">
                <textarea wire:model="message" rows="3" placeholder="توضيح إضافي يظهر في رسالة الرفض" class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-15 leading-6 focus:border-primary"></textarea>
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="reject" :disabled="$reason === ''" x-bind:disabled="! $wire.reason">رفض الطلب</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
