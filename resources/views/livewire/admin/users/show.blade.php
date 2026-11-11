<x-app.page :title="$user->name" :back="route('admin.users')" back-label="المستخدمون">
    <x-slot:actions>
        <x-ui.button variant="secondary" icon="ti-pencil" wire:click="openDetails">تعديل البيانات</x-ui.button>
        <x-ui.button variant="secondary" icon="ti-key" wire:click="sendPasswordReset">إرسال رابط كلمة المرور</x-ui.button>
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-3 rounded-card border border-line bg-white p-4">
        <x-ui.avatar :initial="$user->initials()" :size="48" tone="brand" />
        <div class="flex min-w-0 flex-[1_1_240px] flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-15 font-semibold" dir="ltr">{{ $user->email }}</span>
                @if ($user->is_platform_admin)
                    <span class="inline-flex h-[22px] items-center rounded-full bg-sand-soft px-2 text-12 font-semibold text-sand-ink">إدارة المنصة</span>
                @endif
                @if ($user->isBlocked())
                    <span class="inline-flex h-[22px] items-center rounded-full bg-danger-soft px-2 text-12 font-semibold text-danger">موقوف</span>
                @endif
            </div>
            <span class="flex flex-wrap gap-x-3 text-13 text-muted">
                @if ($user->mobile)
                    <span dir="ltr">{{ Phone::display($user->mobile) }}</span>
                @endif
                <span>انضم {{ Arabic::date($user->created_at) }}</span>
                <span>آخر نشاط {{ $lastSeen ? Arabic::ago($lastSeen) : '—' }}</span>
            </span>
        </div>
        @if ($user->mobile)
            <x-ui.button variant="whatsapp" size="sm" icon="ti-brand-whatsapp" class="text-13" :href="Phone::whatsappUrl($user->mobile)" target="_blank" rel="noopener">واتساب</x-ui.button>
        @endif
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-2">
        <div class="flex flex-col gap-4">
            <x-ui.card title="مساحات العمل" flush :count="$memberships->whereNull('deactivated_at')->count()">
                @forelse ($memberships as $membership)
                    @php($inactive = $membership->deactivated_at !== null)
                    @php($state = $membership->workspace->status())
                    <a href="{{ route('admin.workspaces.show', $membership->workspace) }}" wire:navigate wire:key="membership-{{ $membership->id }}"
                       @class(['flex items-center gap-3 border-t border-divider px-4 py-3 text-ink hover:bg-canvas hover:no-underline', 'opacity-60' => $inactive])>
                        <x-ui.avatar :initial="$membership->workspace->initial()" :size="36" square />
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-14 font-semibold">{{ $membership->workspace->name }}</span>
                            <span class="truncate text-12 text-muted">{{ $membership->roleLabel() }}{{ $inactive ? ' · غادر '.Arabic::date($membership->deactivated_at) : '' }}</span>
                        </span>
                        <span class="inline-flex h-6 items-center rounded-full px-2 text-12 font-semibold {{ $state->pillClasses() }}">{{ $state->label() }}</span>
                    </a>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا ينتمي إلى أي مساحة عمل.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="الأمان والصلاحيات" flush>
                <div class="flex items-center gap-3 border-t border-divider px-4 py-3.5">
                    <i class="ti ti-login-2 text-22 text-body"></i>
                    <span class="flex flex-1 flex-col">
                        <span class="text-14 font-semibold">تسجيل الدخول</span>
                        <span @class(['text-13', 'text-danger' => $user->isBlocked(), 'text-muted' => ! $user->isBlocked()])>{{ $user->isBlocked() ? 'موقوف منذ '.Arabic::date($user->blocked_at) : 'مسموح' }}</span>
                    </span>
                    @unless ($isSelf)
                        <x-ui.button :variant="$user->isBlocked() ? 'primary-outline' : 'danger-outline'" size="xs" class="text-13" wire:click="confirmBlock">
                            {{ $user->isBlocked() ? 'إعادة التفعيل' : 'إيقاف الحساب' }}
                        </x-ui.button>
                    @endunless
                </div>
                <div class="flex items-center gap-3 border-t border-divider px-4 py-3.5">
                    <i class="ti ti-shield-lock text-22 text-body"></i>
                    <span class="flex flex-1 flex-col">
                        <span class="text-14 font-semibold">التحقق بخطوتين</span>
                        <span class="text-13 text-muted">{{ $user->hasTwoFactor() ? 'مفعّل منذ '.Arabic::date($user->two_factor_confirmed_at) : 'غير مفعّل' }}</span>
                    </span>
                    @if ($user->hasTwoFactor())
                        <x-ui.button variant="danger-outline" size="xs" class="text-13" wire:click="confirmTwoFactorReset">إلغاء</x-ui.button>
                    @endif
                </div>
                <div class="flex items-center gap-3 border-t border-divider px-4 py-3.5">
                    <i class="ti ti-shield text-22 text-body"></i>
                    <span class="flex flex-1 flex-col">
                        <span class="text-14 font-semibold">إدارة المنصة</span>
                        <span class="text-13 text-muted">{{ $user->is_platform_admin ? 'يدير المنصة' : 'لا يملك صلاحية' }}</span>
                    </span>
                    @unless ($isSelf)
                        <x-ui.button :variant="$user->is_platform_admin ? 'danger-outline' : 'primary-outline'" size="xs" class="text-13" wire:click="confirmAdmin">
                            {{ $user->is_platform_admin ? 'سحب الصلاحية' : 'منح الصلاحية' }}
                        </x-ui.button>
                    @endunless
                </div>
            </x-ui.card>
        </div>

        <x-ui.card title="السجل">
            @forelse ($history as $entry)
                <div @class(['flex gap-3', 'border-t border-divider pt-3' => ! $loop->first]) wire:key="history-{{ $entry->id }}">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-divider text-body"><i class="ti {{ $entry->icon() }} text-16"></i></span>
                    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                        <span class="text-14 leading-[22px]">{{ $entry->text() }}</span>
                        <span class="text-12 text-muted">{{ $entry->admin?->name ?? 'النظام' }} · {{ Arabic::ago($entry->created_at) }}</span>
                    </div>
                </div>
            @empty
                <p class="m-0 text-14 text-muted">لم تُتخذ أي إجراءات على هذا الحساب.</p>
            @endforelse
        </x-ui.card>
    </div>

    @if ($dialog === 'admin')
        <x-ui.dialog :title="$user->is_platform_admin ? 'سحب صلاحية إدارة المنصة' : 'منح صلاحية إدارة المنصة'">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">
                @if ($user->is_platform_admin)
                    لن يتمكن {{ $user->name }} من دخول إدارة المنصة بعد الآن. يبقى حسابه وعضويته في مساحات العمل كما هي.
                @else
                    سيتمكن {{ $user->name }} من اعتماد التراخيص وإيقاف مساحات العمل ورؤية فواتير جميع المشتركين.
                @endif
            </p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button :variant="$user->is_platform_admin ? 'danger' : 'primary'" size="lg" class="flex-1" wire:click="toggleAdmin">
                    {{ $user->is_platform_admin ? 'سحب الصلاحية' : 'منح الصلاحية' }}
                </x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'details')
        <x-ui.dialog title="تعديل بيانات الحساب">
            <form id="details-form" wire:submit="saveDetails" class="flex flex-col gap-4">
                <x-ui.field label="الاسم" error="name">
                    <input type="text" wire:model="name" class="field" autocomplete="off">
                </x-ui.field>
                <x-ui.field label="البريد الإلكتروني" error="email">
                    <input type="email" wire:model="email" class="field text-right" dir="ltr" autocomplete="off">
                </x-ui.field>
                <x-ui.field label="رقم الجوال" error="mobile" optional>
                    <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="off">
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button type="submit" form="details-form" size="lg" class="flex-1">حفظ</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'block')
        <x-ui.dialog :title="$user->isBlocked() ? 'إعادة تفعيل الحساب' : 'إيقاف الحساب'">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">
                @if ($user->isBlocked())
                    سيتمكن {{ $user->name }} من تسجيل الدخول مرة أخرى بكلمة المرور نفسها.
                @else
                    لن يتمكن {{ $user->name }} من تسجيل الدخول، ويخرج من جلساته المفتوحة. تبقى عضويته في مساحات العمل وبياناتها كما هي.
                @endif
            </p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button :variant="$user->isBlocked() ? 'primary' : 'danger'" size="lg" class="flex-1" wire:click="toggleBlock">
                    {{ $user->isBlocked() ? 'إعادة التفعيل' : 'إيقاف الحساب' }}
                </x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'two-factor')
        <x-ui.dialog title="إلغاء التحقق بخطوتين">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">
                سيدخل {{ $user->name }} بكلمة المرور وحدها حتى يعيد تفعيل التحقق بخطوتين من صفحة حسابه. تأكد من هويته قبل المتابعة.
            </p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="resetTwoFactor">إلغاء التحقق</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
