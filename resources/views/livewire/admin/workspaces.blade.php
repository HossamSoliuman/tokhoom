<x-app.page title="مساحات العمل">
    <div class="overflow-hidden rounded-card border border-line bg-white">
        <div class="hidden h-11 grid-cols-[2fr_1.6fr_1fr_1fr_1fr_130px] items-center bg-head px-5 text-13 font-semibold text-muted lg:grid">
            <span>مساحة العمل</span><span>العنوان</span><span>الخطة</span><span>العقارات</span><span>الحالة</span><span></span>
        </div>
        @foreach ($workspaces as $workspace)
            @php($owner = $workspace->memberships->firstWhere('role', \App\Enums\MemberRole::Owner)?->user)
            @php($members = $workspace->memberships->count())
            @php($suspended = $workspace->isSuspended())
            @php($trial = ! $suspended && $workspace->isOnTrial())
            <div class="grid grid-cols-2 items-center gap-x-4 gap-y-1.5 border-t border-divider px-5 py-3.5 text-14 first:border-t-0 lg:grid-cols-[2fr_1.6fr_1fr_1fr_1fr_130px] lg:first:border-t" wire:key="workspace-{{ $workspace->id }}">
                <span class="col-span-2 flex items-center gap-2.5 lg:col-span-1">
                    <x-ui.avatar :initial="$workspace->initial()" :size="36" square />
                    <span class="flex flex-col">
                        <span class="font-semibold">{{ $workspace->name }}</span>
                        <span class="text-12 text-muted">
                            {{ $workspace->kind->label($owner?->gender) }}{{ $workspace->isOffice() ? ' · '.Arabic::count($members, 'عضو واحد', 'عضوان', 'أعضاء', 'عضواً') : '' }}
                        </span>
                    </span>
                </span>
                <span class="text-right text-13 text-body" dir="ltr">{{ $workspace->siteHost() }}</span>
                <span class="text-body">{{ $trial ? 'تجربة' : $workspace->plan->shortLabel() }}</span>
                <span class="text-body">{{ $workspace->listings_count }}<span class="lg:hidden"> عقارات</span></span>
                <span>
                    <span @class([
                        'inline-flex h-[26px] items-center rounded-full px-2.5 text-13 font-semibold',
                        'bg-danger-soft text-danger' => $suspended,
                        'bg-primary-soft text-primary-strong' => $trial,
                        'bg-success-soft text-success' => ! $suspended && ! $trial,
                    ])>{{ $suspended ? 'موقوف' : ($trial ? 'فترة تجريبية' : 'نشط') }}</span>
                </span>
                <span class="flex justify-end">
                    <x-ui.button :variant="$suspended ? 'primary-outline' : 'danger-outline'" size="xs" class="text-13" wire:click="confirm({{ $workspace->id }})">
                        {{ $suspended ? 'إعادة التفعيل' : 'إيقاف' }}
                    </x-ui.button>
                </span>
            </div>
        @endforeach
    </div>

    @if (in_array($dialog, ['suspend', 'reactivate'], true) && $target)
        <x-ui.dialog :title="$dialog === 'suspend' ? 'إيقاف مساحة العمل' : 'إعادة تفعيل مساحة العمل'">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">
                @if ($dialog === 'suspend')
                    سيتوقف موقع «{{ $target->name }}» عن الظهور للزوار ولن يتمكن أعضاؤه من الدخول إلى لوحة التحكم. تبقى البيانات محفوظة ويمكن إعادة التفعيل لاحقاً.
                @else
                    سيعود موقع «{{ $target->name }}» للظهور ويتمكن أعضاؤه من الدخول مجدداً.
                @endif
            </p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                @if ($dialog === 'suspend')
                    <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="suspend">إيقاف</x-ui.button>
                @else
                    <x-ui.button size="lg" class="flex-1" wire:click="reactivate">إعادة التفعيل</x-ui.button>
                @endif
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
