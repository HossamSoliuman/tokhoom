<x-app.page :title="$workspace->name" :back="route('admin.workspaces')" back-label="مساحات العمل">
    <x-slot:actions>
        @if ($workspace->approval_status === \App\Enums\ApprovalStatus::Pending)
            <x-ui.button variant="danger-outline" wire:click="openReject">رفض</x-ui.button>
            <x-ui.button icon="ti-check" wire:click="approve">اعتماد</x-ui.button>
        @elseif ($workspace->approval_status === \App\Enums\ApprovalStatus::Rejected)
            <x-ui.button icon="ti-check" wire:click="approve">اعتماد بعد التصحيح</x-ui.button>
        @else
            @if ($canOpenSite)
                <x-ui.button variant="secondary" icon="ti-external-link" :href="$workspace->siteUrl()" target="_blank" rel="noopener">فتح الموقع</x-ui.button>
            @endif
            @if ($workspace->isSuspended())
                <x-ui.button icon="ti-lock-open" wire:click="openSuspend">إعادة التفعيل</x-ui.button>
            @else
                <x-ui.button variant="danger-outline" icon="ti-lock" wire:click="openSuspend">إيقاف</x-ui.button>
            @endif
        @endif
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-3 rounded-card border border-line bg-white p-4">
        @if ($workspace->logoUrl())
            <img src="{{ $workspace->logoUrl() }}" alt="" class="size-12 shrink-0 rounded-field border border-line object-contain">
        @else
            <x-ui.avatar :initial="$workspace->initial()" :size="48" tone="solid" square />
        @endif
        <div class="flex min-w-0 flex-[1_1_240px] flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex h-[26px] items-center rounded-full px-2.5 text-13 font-semibold {{ $status->pillClasses() }}">{{ $status->label() }}</span>
                <span class="text-14 text-body">{{ $workspace->kind->label($owner?->gender) }} · {{ $workspace->city }}</span>
            </div>
            <span class="flex flex-wrap gap-x-3 text-13 text-muted">
                <span dir="ltr">{{ $workspace->siteHost() }}</span>
                <span>سُجّل {{ Arabic::date($workspace->created_at) }}</span>
            </span>
        </div>
    </div>

    @if ($status === \App\Enums\WorkspaceStatus::Rejected)
        <div class="flex items-start gap-3 rounded-xl border border-line bg-white px-3.5 py-3">
            <i class="ti ti-circle-x shrink-0 text-20 text-danger"></i>
            <div class="flex flex-1 flex-col gap-1 text-14 leading-[22px]">
                <span class="font-semibold">رُفض الطلب {{ Arabic::date($workspace->rejected_at) }}: {{ $workspace->rejection_reason?->label() }}</span>
                @if ($workspace->rejection_message)
                    <span class="text-body">{{ $workspace->rejection_message }}</span>
                @endif
            </div>
        </div>
    @elseif ($workspace->isSuspended())
        <div class="flex items-start gap-3 rounded-xl bg-danger-soft px-3.5 py-3 text-14 leading-[22px] text-danger-ink">
            <i class="ti ti-lock shrink-0 text-20"></i>
            <span class="flex-1">
                موقوفة منذ {{ Arabic::date($workspace->suspended_at) }}. الموقع لا يظهر للزوار ولا يدخل الأعضاء إلى لوحة التحكم.
                @if (filled($lastSuspension?->properties['reason'] ?? null))
                    السبب: {{ $lastSuspension->properties['reason'] }}
                @endif
            </span>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-5">
        @foreach ($stats as $stat)
            <div class="flex flex-col gap-1 rounded-card border border-line bg-white p-3.5" wire:key="stat-{{ $loop->index }}">
                <span class="flex items-center gap-1.5 text-13 text-muted"><i class="ti {{ $stat['icon'] }} text-17"></i>{{ $stat['label'] }}</span>
                <span class="text-22 font-bold leading-[1.25]">{{ Arabic::number($stat['value']) }}</span>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-2">
        <div class="flex flex-col gap-4">
            <x-ui.card>
                <x-slot:heading>
                    <h2 class="m-0 text-16 font-bold">الترخيص</h2>
                    <x-ui.button variant="ghost" size="xs" icon="ti-pencil" class="text-14" wire:click="openLicense">تعديل</x-ui.button>
                </x-slot:heading>
                <dl class="m-0 grid grid-cols-2 gap-x-4 gap-y-3 text-14">
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-13 text-muted">{{ $workspace->isOffice() ? 'رخصة فال للمنشأة' : 'رخصة فال' }}</dt>
                        <dd class="m-0 font-semibold" dir="ltr">{{ $workspace->fal_number }}</dd>
                    </div>
                    @if ($workspace->isOffice())
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-13 text-muted">السجل التجاري</dt>
                            <dd class="m-0 font-semibold" dir="ltr">{{ $workspace->cr_number ?? '—' }}</dd>
                        </div>
                    @endif
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-13 text-muted">ينتهي في</dt>
                        <dd @class(['m-0 font-semibold', 'text-danger' => $workspace->license_expires_on->isPast(), 'text-warning' => $licenseSoon && ! $workspace->license_expires_on->isPast()])>
                            {{ Arabic::date($workspace->license_expires_on) }}{{ $workspace->license_expires_on->isPast() ? ' · منتهٍ' : '' }}
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-13 text-muted">الاعتماد</dt>
                        <dd class="m-0 font-semibold">{{ $workspace->approved_at ? Arabic::date($workspace->approved_at) : '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="المالك">
                @if ($owner)
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :initial="$owner->initials()" :size="44" tone="brand" />
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a href="{{ route('admin.users.show', $owner) }}" wire:navigate class="truncate text-15 font-semibold">{{ $owner->name }}</a>
                            <span class="truncate text-13 text-muted" dir="ltr">{{ $owner->email }}</span>
                        </div>
                    </div>
                    @if ($owner->mobile)
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="flex-1 text-right text-15 font-semibold" dir="ltr">{{ Phone::display($owner->mobile) }}</span>
                            <x-ui.button variant="whatsapp" size="sm" icon="ti-brand-whatsapp" class="text-13" :href="Phone::whatsappUrl($owner->mobile)" target="_blank" rel="noopener">واتساب</x-ui.button>
                            <x-ui.button variant="secondary" size="sm" icon="ti-phone" class="text-13" :href="Phone::telUrl($owner->mobile)">اتصال</x-ui.button>
                        </div>
                    @endif
                @else
                    <p class="m-0 text-14 text-muted">لا يوجد مالك نشط.</p>
                @endif
            </x-ui.card>

            <x-ui.card class="gap-4">
                <x-slot:heading>
                    <h2 class="m-0 text-16 font-bold">الاشتراك</h2>
                    <span class="flex gap-1">
                        <x-ui.button variant="ghost" size="xs" icon="ti-gift" class="text-14" wire:click="openDays">إضافة أيام</x-ui.button>
                        <x-ui.button variant="ghost" size="xs" icon="ti-arrows-exchange" class="text-14" wire:click="openPlan">تغيير الخطة</x-ui.button>
                    </span>
                </x-slot:heading>
                <div class="flex items-start justify-between gap-3">
                    <span class="text-18 font-bold">{{ $workspace->plan->label() }}</span>
                    <span class="flex items-baseline gap-1">
                        <span class="text-20 font-bold">{{ Arabic::number($workspace->monthlyTotal()) }}</span>
                        <span class="text-13 text-muted">ريال / شهرياً</span>
                    </span>
                </div>
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between text-14">
                        <span>المقاعد</span>
                        <span class="font-semibold">{{ $usedSeats }} من {{ $workspace->paidSeats() }}</span>
                    </div>
                    <x-ui.meter :percent="round($usedSeats / max(1, $workspace->paidSeats()) * 100)" />
                </div>
                <dl class="m-0 flex flex-col gap-2.5 border-t border-divider pt-3 text-14">
                    @if ($workspace->isOnTrial())
                        <div class="flex justify-between gap-3"><dt class="text-muted">تنتهي التجربة</dt><dd class="m-0 font-semibold">{{ Arabic::date($workspace->trial_ends_at) }} · {{ Arabic::days($workspace->trialDaysLeft()) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-muted">مدفوع حتى</dt><dd class="m-0 font-semibold">{{ $workspace->paid_until ? Arabic::date($workspace->paid_until) : '—' }}</dd></div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">الخصم القادم</dt>
                        <dd @class(['m-0 font-semibold', 'text-warning' => $workspace->isApproved() && $workspace->isBillingDue()])>{{ $workspace->isBillingDue() ? 'مستحق منذ '.Arabic::date($workspace->nextChargeDate()) : Arabic::date($workspace->nextChargeDate()) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">البطاقة</dt>
                        <dd class="m-0 font-semibold">
                            @if ($workspace->card_last_four)
                                <span dir="ltr">{{ $workspace->card_brand }} •••• {{ $workspace->card_last_four }}</span>
                                <span class="font-normal text-muted">{{ $workspace->hasSavedCard() ? '· تجديد تلقائي' : '' }}</span>
                            @else
                                لا توجد بطاقة محفوظة
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            @if ($workspace->custom_domain)
                <x-ui.card>
                    <x-slot:heading>
                        <h2 class="m-0 text-16 font-bold">النطاق الخاص</h2>
                        @php($domainStatus = $workspace->domain_status ?? \App\Enums\DomainStatus::Pending)
                        <span class="flex h-7 items-center gap-1.5 rounded-full px-3 text-13 font-semibold {{ $domainStatus->pillClasses() }}">
                            <i class="ti {{ $domainStatus->icon() }} text-15"></i>{{ $domainStatus->label() }}
                        </span>
                    </x-slot:heading>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="flex-1 text-right text-15 font-semibold" dir="ltr">{{ $workspace->custom_domain }}</span>
                        <x-ui.button variant="secondary" size="sm" icon="ti-refresh" class="text-13" wire:click="checkDomain">تحقق الآن</x-ui.button>
                        <x-ui.button variant="danger-outline" size="sm" class="text-13" wire:click="openRemoveDomain">إلغاء الربط</x-ui.button>
                    </div>
                    @if ($workspace->domain_checked_at)
                        <span class="text-13 text-muted">آخر تحقق {{ Arabic::dayAndTime($workspace->domain_checked_at) }}</span>
                    @endif
                </x-ui.card>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            <x-ui.card title="الفريق" flush :count="$members->whereNull('deactivated_at')->count()">
                @foreach ($members as $member)
                    @php($inactive = $member->deactivated_at !== null)
                    <a href="{{ route('admin.users.show', $member->user) }}" wire:navigate wire:key="member-{{ $member->id }}"
                       @class(['flex items-center gap-3 border-t border-divider px-4 py-3 text-ink hover:bg-canvas hover:no-underline', 'opacity-60' => $inactive])>
                        <x-ui.avatar :initial="$member->user->initials()" :size="36" />
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-14 font-semibold">{{ $member->user->name }}</span>
                            <span class="truncate text-12 text-muted">{{ $member->roleLabel() }}{{ $member->fal_number ? ' · فال '.$member->fal_number : '' }}</span>
                        </span>
                        @if ($inactive)
                            <span class="inline-flex h-6 items-center rounded-full bg-divider px-2 text-12 font-semibold text-body">غادر</span>
                        @elseif ($member->falExpiresWithin(30))
                            <span class="inline-flex h-6 items-center rounded-full bg-warning-soft px-2 text-12 font-semibold text-warning">فال ينتهي {{ Arabic::shortDate($member->fal_expires_on) }}</span>
                        @endif
                    </a>
                @endforeach
            </x-ui.card>

            <x-ui.card title="الفواتير" flush>
                @forelse ($invoices as $invoice)
                    <div class="flex items-center gap-2.5 border-t border-divider px-4 py-2.5 text-14" wire:key="invoice-{{ $invoice->id }}">
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="font-semibold" dir="ltr">{{ $invoice->number }}</span>
                            <span class="text-12 text-muted">{{ Arabic::date($invoice->issued_on) }}{{ ! $invoice->paid_at && $invoice->payment_attempts ? ' · '.Arabic::countWithNumber($invoice->payment_attempts, 'محاولة تحصيل', 'محاولات تحصيل', 'محاولة تحصيل') : '' }}</span>
                        </span>
                        <span class="font-semibold">{{ Arabic::money($invoice->amount) }}</span>
                        <x-admin.invoice-status :invoice="$invoice" />
                        @unless ($invoice->paid_at)
                            <x-ui.button variant="primary-outline" size="xs" class="text-13" wire:click="openPayment({{ $invoice->id }})">تسجيل دفعة</x-ui.button>
                        @endunless
                        <a href="{{ route('admin.invoices.show', $invoice) }}" target="_blank" class="flex size-9 items-center justify-center rounded-lg text-ink hover:bg-canvas" aria-label="عرض الفاتورة">
                            <i class="ti ti-file-invoice text-18"></i>
                        </a>
                    </div>
                @empty
                    <p class="m-0 border-t border-divider px-4 py-4 text-14 text-muted">لا توجد فواتير بعد.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="السجل والملاحظات">
                <div class="flex flex-col gap-2">
                    <textarea wire:model="note" rows="2" placeholder="ملاحظة داخلية لفريق المنصة" aria-label="ملاحظة"
                              class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-15 leading-6 focus:border-primary"></textarea>
                    @error('note')
                        <span class="text-13 text-danger">{{ $message }}</span>
                    @enderror
                    <x-ui.button variant="secondary" size="sm" icon="ti-note" class="self-end text-14" wire:click="addNote">إضافة ملاحظة</x-ui.button>
                </div>
                @forelse ($history as $entry)
                    <div class="flex gap-3 border-t border-divider pt-3" wire:key="history-{{ $entry->id }}">
                        <span @class([
                            'flex size-8 shrink-0 items-center justify-center rounded-full',
                            'bg-sand-soft text-sand-ink' => $entry->isNote(),
                            'bg-divider text-body' => ! $entry->isNote(),
                        ])><i class="ti {{ $entry->icon() }} text-16"></i></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="text-14 leading-[22px] text-ink [overflow-wrap:anywhere]">{{ $entry->text() }}</span>
                            <span class="text-12 text-muted">{{ $entry->admin?->name ?? 'النظام' }} · {{ Arabic::ago($entry->created_at) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="m-0 text-14 text-muted">لا توجد إجراءات أو ملاحظات بعد.</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>

    @if ($dialog === 'reject')
        <x-ui.dialog title="رفض الطلب">
            <div class="flex flex-col gap-2">
                @foreach (\App\Enums\RejectionReason::cases() as $case)
                    <x-ui.radio-option :active="$reason === $case->value" :label="$case->label()" wire:click="$set('reason', '{{ $case->value }}')" wire:key="reason-{{ $case->value }}" />
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
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="reject" :disabled="$reason === ''">رفض الطلب</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'suspend')
        <x-ui.dialog title="إيقاف مساحة العمل">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">
                سيتوقف موقع «{{ $workspace->name }}» عن الظهور للزوار ولن يتمكن أعضاؤه من الدخول إلى لوحة التحكم. تبقى البيانات محفوظة ويمكن إعادة التفعيل لاحقاً.
            </p>
            <x-ui.field label="السبب" optional error="suspendReason">
                <textarea wire:model="suspendReason" rows="2" placeholder="يظهر في سجل مساحة العمل لفريق المنصة فقط" class="resize-none rounded-field border border-line-strong px-3 py-2.5 text-15 leading-6 focus:border-primary"></textarea>
            </x-ui.field>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="suspend">إيقاف</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'reactivate')
        <x-ui.dialog title="إعادة تفعيل مساحة العمل">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">سيعود موقع «{{ $workspace->name }}» للظهور ويتمكن أعضاؤه من الدخول مجدداً.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="reactivate">إعادة التفعيل</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'license')
        <x-ui.dialog title="بيانات الترخيص">
            <x-ui.field :label="$workspace->isOffice() ? 'الاسم التجاري' : 'الاسم'" error="name">
                <input type="text" wire:model="name" class="field">
            </x-ui.field>
            <div class="grid grid-cols-2 gap-2.5">
                <x-ui.field label="رخصة فال" error="falNumber">
                    <input type="text" inputmode="numeric" wire:model="falNumber" class="field text-right" dir="ltr">
                </x-ui.field>
                @if ($workspace->isOffice())
                    <x-ui.field label="السجل التجاري" error="crNumber">
                        <input type="text" inputmode="numeric" wire:model="crNumber" class="field text-right" dir="ltr">
                    </x-ui.field>
                @endif
                <x-ui.field label="ينتهي في" error="licenseExpiresOn">
                    <input type="date" wire:model="licenseExpiresOn" class="field">
                </x-ui.field>
                <x-ui.field label="المدينة" error="city">
                    <x-ui.select wire:model="city" size="lg">
                        @foreach ($cities as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="saveLicense">حفظ</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'days')
        <x-ui.dialog title="إضافة أيام مجانية">
            <div class="flex flex-wrap gap-2">
                @foreach ([7, 14, 30, 60] as $option)
                    <x-ui.chip size="lg" :active="$days === $option" wire:click="$set('days', {{ $option }})" wire:key="days-{{ $option }}">{{ Arabic::days($option) }}</x-ui.chip>
                @endforeach
            </div>
            <x-ui.field label="عدد الأيام" error="days">
                <input type="number" min="1" max="365" wire:model.live.debounce.300ms="days" class="field">
            </x-ui.field>
            <p class="m-0 text-14 leading-[22px] text-body">
                {{ $workspace->paid_until ? 'تُضاف إلى الشهر المدفوع' : 'تُضاف إلى الفترة التجريبية' }}، فيتأخر الخصم القادم دون رسوم.
            </p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="grantDays">إضافة</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'plan')
        <x-ui.dialog title="تغيير الخطة">
            @foreach (\App\Enums\Plan::cases() as $case)
                <x-ui.radio-option :active="$plan === $case->value" :label="$case->label()" :description="$case->description()" class="rounded-card" wire:click="$set('plan', '{{ $case->value }}')" wire:key="plan-{{ $case->value }}">
                    <span class="flex flex-col items-end">
                        <span class="text-18 font-bold">{{ Arabic::number($case->monthlyPrice()) }}</span>
                        <span class="text-12 text-muted">ريال / شهرياً</span>
                    </span>
                </x-ui.radio-option>
            @endforeach
            @error('plan')
                <span class="text-13 text-danger">{{ $message }}</span>
            @enderror
            @if ($plan !== 'agent')
                <x-ui.field label="المقاعد المدفوعة" error="seats">
                    <input type="number" min="1" max="100" wire:model="seats" class="field">
                </x-ui.field>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button size="lg" class="flex-1" wire:click="savePlan">حفظ</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif

    @if ($dialog === 'payment' && $payingInvoice)
        <x-admin.payment-dialog :invoice="$payingInvoice" />
    @endif

    @if ($dialog === 'domain')
        <x-ui.dialog title="إلغاء ربط النطاق">
            <p class="m-0 text-15 leading-[26px] text-pretty text-ink-soft">سيعود موقع «{{ $workspace->name }}» إلى عنوانه على تخوم <span dir="ltr">{{ $workspace->slug }}.{{ config('tokhoom.site_domain') }}</span> ويتوقف النطاق <span dir="ltr">{{ $workspace->custom_domain }}</span> عن العمل.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" size="lg" class="flex-1" wire:click="closeDialog">إلغاء</x-ui.button>
                <x-ui.button variant="danger" size="lg" class="flex-1" wire:click="removeDomain">إلغاء الربط</x-ui.button>
            </x-slot:footer>
        </x-ui.dialog>
    @endif
</x-app.page>
