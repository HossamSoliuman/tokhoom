<div class="grid items-start gap-4 md:grid-cols-3">
    @foreach ([
        ['agent', 'خطة الوسيط', 'agent_listing_limit', 'حد العقارات', 'عقار'],
        ['office', 'خطة المكتب', 'office_seats', 'المقاعد المشمولة', 'مقعد'],
        ['plus', 'خطة المكتب بلس', 'plus_seats', 'المقاعد المشمولة', 'مقعد'],
    ] as [$plan, $title, $limitField, $limitLabel, $limitUnit])
        <x-ui.card :title="$title" class="gap-4" wire:key="plan-{{ $plan }}">
            <x-ui.field label="السعر الشهري شامل الضريبة" error="form.{{ $plan }}_price">
                <x-admin.unit-input unit="ريال" wire:model="form.{{ $plan }}_price" min="1" />
            </x-ui.field>
            <x-ui.field :label="$limitLabel" error="form.{{ $limitField }}">
                <x-admin.unit-input :unit="$limitUnit" wire:model="form.{{ $limitField }}" min="1" />
            </x-ui.field>
        </x-ui.card>
    @endforeach
</div>

<x-ui.card title="الاشتراك والتحصيل" class="gap-4">
    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.field label="سعر المقعد الإضافي شهرياً" error="form.extra_seat_price">
            <x-admin.unit-input unit="ريال" wire:model="form.extra_seat_price" min="0" />
        </x-ui.field>
        <x-ui.field label="مدة التجربة المجانية" error="form.trial_days">
            <x-admin.unit-input unit="يوم" wire:model="form.trial_days" min="0" />
        </x-ui.field>
        <x-ui.field label="محاولات الخصم من البطاقة" error="form.collection_attempts">
            <x-admin.unit-input unit="محاولة" wire:model="form.collection_attempts" min="1" max="10" />
        </x-ui.field>
    </div>
    <p class="m-0 text-13 leading-[22px] text-muted">تسري الأسعار الجديدة على الفواتير القادمة، والفواتير الصادرة تبقى بمبالغها. مدة التجربة الجديدة تبدأ مع من يسجّل من الآن.</p>
</x-ui.card>
