<div class="flex w-full max-w-[520px] flex-col gap-5">
    @if ($step <= 4)
        <div class="flex gap-1.5">
            @foreach (\App\Livewire\Auth\Signup::STEPS as $index => $label)
                <div class="flex flex-1 flex-col gap-1.5">
                    <span @class(['h-1 rounded-sm', 'bg-primary' => $index < $step, 'bg-line' => $index >= $step])></span>
                    <span @class([
                        'text-12',
                        'text-primary' => $index < $step,
                        'text-subtle' => $index >= $step,
                        'font-semibold' => $index === $step - 1,
                    ])>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form wire:submit="next" class="flex flex-col gap-4 rounded-2xl border border-line bg-white p-5 lg:p-7">
        <h1 class="m-0 text-22 font-bold">{{ $this->title() }}</h1>

        @if ($step === 1)
            <x-ui.field label="الاسم الكامل" error="name">
                <input type="text" wire:model="name" class="field" placeholder="خالد الراشد" autocomplete="name" autofocus>
            </x-ui.field>
            <x-ui.field label="البريد الإلكتروني" error="email">
                <input type="email" wire:model="email" class="field text-right" dir="ltr" placeholder="name@example.com" autocomplete="email">
            </x-ui.field>
            <x-ui.field label="رقم الجوال" error="mobile">
                <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="tel">
            </x-ui.field>
            <x-ui.field label="كلمة المرور" error="password">
                <input type="password" wire:model="password" class="field text-right" dir="ltr" placeholder="8 أحرف على الأقل" autocomplete="new-password">
            </x-ui.field>
        @endif

        @if ($step === 2)
            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3">
                @foreach ([
                    ['office', 'مكتب عقاري', 'ti-building', 'لديك سجل تجاري وفريق من الوسطاء'],
                    ['agent', 'وسيط مستقل', 'ti-user', 'تعمل بشكل مستقل برخصة فال شخصية'],
                ] as [$value, $label, $icon, $description])
                    <label class="group/kind relative flex cursor-pointer flex-col items-start gap-2.5 rounded-card border-2 border-line bg-white p-5 text-start text-ink not-has-checked:hover:bg-canvas has-checked:border-primary has-checked:bg-primary-tint has-focus-visible:ring-2 has-focus-visible:ring-primary/30">
                        <input type="radio" wire:model="kind" value="{{ $value }}" @checked($kind === $value) class="sr-only">
                        <span class="flex size-[52px] items-center justify-center rounded-xl bg-divider text-body group-has-checked/kind:bg-primary group-has-checked/kind:text-white">
                            <i class="ti {{ $icon }} text-28"></i>
                        </span>
                        <span class="text-17 font-bold">{{ $label }}</span>
                        <span class="text-14 leading-[22px] text-body">{{ $description }}</span>
                        <i class="ti ti-circle-check-filled absolute left-3.5 top-3.5 hidden text-24 text-primary group-has-checked/kind:inline"></i>
                    </label>
                @endforeach
            </div>
        @endif

        @if ($step === 3)
            @if ($this->isOffice())
                <x-ui.field label="الاسم التجاري" error="tradeName">
                    <input type="text" wire:model="tradeName" class="field" placeholder="مكتب الراشد العقاري">
                </x-ui.field>
                <x-ui.field label="رقم السجل التجاري" error="crNumber">
                    <input type="text" wire:model="crNumber" class="field text-right" dir="ltr" placeholder="1010567890" inputmode="numeric">
                </x-ui.field>
                <x-ui.field label="رقم ترخيص فال للمنشأة" error="falNumber">
                    <input type="text" wire:model="falNumber" class="field text-right" dir="ltr" placeholder="1200045678" inputmode="numeric">
                </x-ui.field>
            @else
                <x-ui.field label="الاسم الكامل" error="name">
                    <input type="text" wire:model="name" class="field" placeholder="سارة القحطاني">
                </x-ui.field>
                <x-ui.field label="رقم رخصة فال" error="falNumber">
                    <input type="text" wire:model="falNumber" class="field text-right" dir="ltr" placeholder="1100012345" inputmode="numeric">
                </x-ui.field>
            @endif
            <x-ui.field :label="$this->isOffice() ? 'تاريخ انتهاء الترخيص' : 'تاريخ انتهاء الرخصة'" error="licenseExpiresOn">
                <input type="date" wire:model="licenseExpiresOn" class="field">
            </x-ui.field>
            <x-ui.field label="المدينة" error="city">
                <x-ui.select wire:model="city" size="lg">
                    @foreach (\App\Support\SiteAddress::cities() as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        @endif

        @if ($step === 4)
            @php($state = $this->slugState)
            <div @class([
                'flex h-[52px] items-center overflow-hidden rounded-field border-[1.5px] bg-white',
                'border-danger' => in_array($state, ['taken', 'reserved']),
                'border-success' => $state === 'available',
                'border-line-strong focus-within:border-primary' => ! in_array($state, ['taken', 'reserved', 'available']),
            ]) dir="ltr">
                <input type="text" wire:model.live.debounce.400ms="slug" placeholder="alrashid" autocomplete="off" autocapitalize="none" spellcheck="false"
                       class="h-full min-w-0 flex-1 border-0 px-3.5 font-mono text-16">
                <span class="flex h-full items-center border-l border-line bg-head px-3.5 font-mono text-14 text-muted">.{{ config('tokhoom.site_domain') }}</span>
            </div>

            <span wire:loading.flex wire:target="slug" class="items-center gap-2 text-14 text-muted">
                <i class="ti ti-loader-2 animate-spin text-17"></i>
                جارٍ التحقق من التوفر…
            </span>

            <div wire:loading.remove wire:target="slug" class="flex flex-col gap-3">
                @if ($state === 'available')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-success">
                        <i class="ti ti-circle-check text-18"></i>
                        العنوان متاح
                    </span>
                @elseif ($state === 'taken')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger">
                        <i class="ti ti-circle-x text-18"></i>
                        هذا العنوان مستخدم. جرّب أحد هذه العناوين:
                    </span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->suggestions as $suggestion)
                            <button type="button" wire:key="suggestion-{{ $suggestion }}" wire:click="useSuggestion('{{ $suggestion }}')" dir="ltr"
                                    class="h-9 rounded-full border border-line-strong bg-white px-3.5 font-mono text-13 text-ink hover:bg-canvas">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                @elseif ($state === 'reserved')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger">
                        <i class="ti ti-circle-x text-18"></i>
                        هذا العنوان غير متاح.
                    </span>
                @elseif ($state === 'invalid')
                    <span class="text-14 text-muted">استخدم أحرفاً إنجليزية صغيرة وأرقاماً وشرطة، 3 أحرف على الأقل.</span>
                @endif
                @error('slug')
                    <span class="text-13 text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex flex-col gap-1.5 border-t border-divider pt-4">
                <label class="group/check flex cursor-pointer items-start gap-2.5 text-start">
                    <input type="checkbox" wire:model="acceptsTerms" @checked($acceptsTerms) class="sr-only">
                    <x-ui.checkbox size="sm" class="mt-px group-has-focus-visible/check:ring-2 group-has-focus-visible/check:ring-primary/30" />
                    <span class="text-14 leading-[22px] text-body">
                        أوافق على <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">شروط الاستخدام</a> و<a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">سياسة الخصوصية</a>، وأتحمّل مسؤولية تراخيصي وإعلاناتي.
                    </span>
                </label>
                @error('acceptsTerms')
                    <span class="text-13 text-danger">{{ $message }}</span>
                @enderror
            </div>
        @endif

        @if ($step === 5 && $this->workspace)
            <div class="flex flex-col items-center gap-3 py-2 text-center">
                <span class="flex size-[72px] items-center justify-center rounded-full bg-warning-soft text-warning">
                    <i class="ti ti-hourglass text-[34px]"></i>
                </span>
                <p class="m-0 text-15 leading-[26px] text-pretty text-body">نراجع بيانات الترخيص خلال يوم عمل واحد. يمكنك تجهيز موقعك وإضافة العقارات الآن، وسيظهر الموقع للزوار بعد الاعتماد.</p>
                <span class="flex h-9 items-center rounded-full bg-divider px-3.5 font-mono text-14" dir="ltr">{{ $this->workspace->siteHost() }}</span>
            </div>
        @endif

        <div class="mt-1 flex gap-2.5">
            @if ($step > $this->firstStep() && $step < 5)
                <x-ui.button variant="secondary" size="lg" wire:click="back">رجوع</x-ui.button>
            @endif
            @if ($step === 5 && $this->workspace)
                <x-ui.button variant="secondary" size="lg" icon="ti-eye" class="flex-1" :href="$this->workspace->siteUrl()" target="_blank" rel="noopener">معاينة الموقع</x-ui.button>
            @endif
            <x-ui.button type="submit" size="lg" class="flex-1" :disabled="$step === 4 && $this->slugState !== 'available'">
                {{ match (true) { $step === 5 => 'الذهاب إلى لوحة التحكم', $step === 4 => 'إنشاء الحساب', default => 'متابعة' } }}
            </x-ui.button>
        </div>
    </form>

    @if ($step === 1)
        <span class="text-center text-14 text-body">لديك حساب؟ <a href="{{ route('login') }}" wire:navigate>تسجيل الدخول</a></span>
    @elseif (auth()->check() && $step < 5 && auth()->user()->currentMembership())
        <a href="{{ route('today') }}" wire:navigate class="text-center text-14">العودة إلى لوحة التحكم</a>
    @endif
</div>
