<form wire:submit="start" x-init="$wire.$watch('slug', value => $dispatch('site-address', value))" class="flex flex-col gap-3">
    @php($state = $this->slugState)
    <label for="claim-slug" class="text-15 font-semibold">اختر عنوان موقعك</label>

    <div class="flex flex-col gap-2.5 sm:flex-row">
        <div @class([
            'flex h-14 min-w-0 flex-1 items-center overflow-hidden rounded-field border-[1.5px] bg-white transition-colors',
            'border-danger' => $state === 'taken' || $errors->has('slug'),
            'border-success' => $state === 'available' && ! $errors->has('slug'),
            'border-line-strong focus-within:border-primary' => ! in_array($state, ['taken', 'available']) && ! $errors->has('slug'),
        ]) dir="ltr">
            <input id="claim-slug" type="text" wire:model.live.debounce.400ms="slug" x-on:input="$dispatch('site-address', $event.target.value)"
                   placeholder="alrashid" autocomplete="off" autocapitalize="none" spellcheck="false" enterkeyhint="go"
                   class="h-full min-w-0 flex-1 border-0 bg-transparent px-4 font-mono text-17 outline-none">
            <span class="flex h-full items-center border-l border-line bg-head px-3.5 font-mono text-14 text-muted">.{{ config('tokhoom.site_domain') }}</span>
        </div>
        <button type="submit" class="flex h-14 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-field bg-primary px-6 text-16 font-semibold text-white transition-colors hover:bg-primary-strong data-loading:opacity-70">
            ابدأ التجربة
            <i class="ti ti-arrow-left text-[19px]"></i>
        </button>
    </div>

    <div class="flex min-h-6 flex-col gap-2.5">
        <span wire:loading.flex wire:target="slug" class="items-center gap-2 text-14 text-muted">
            <i class="ti ti-loader-2 animate-spin text-17"></i>
            جارٍ التحقق من التوفر…
        </span>

        <div wire:loading.remove wire:target="slug" class="flex flex-col gap-2.5">
            @error('slug')
                <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>{{ $message }}</span>
            @else
                @if ($state === 'available')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-success"><i class="ti ti-circle-check text-18"></i>العنوان متاح. احجزه بإنشاء حسابك.</span>
                @elseif ($state === 'taken')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>هذا العنوان مستخدم.</span>
                @elseif ($state === 'invalid')
                    <span class="text-14 text-muted">أحرف إنجليزية صغيرة وأرقام وشرطة، 3 أحرف على الأقل.</span>
                @else
                    <span class="text-14 text-muted">تجربة {{ config('tokhoom.trial_days') }} يوماً بلا بطاقة دفع. يظهر موقعك للزوار بعد مراجعة الترخيص.</span>
                @endif
            @enderror

            @if ($this->suggestions)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-14 text-body">جرّب:</span>
                    @foreach ($this->suggestions as $suggestion)
                        <button type="button" wire:key="claim-suggestion-{{ $suggestion }}" wire:click="useSuggestion('{{ $suggestion }}')" dir="ltr"
                                class="h-9 rounded-full border border-line-strong bg-white px-3.5 font-mono text-13 text-ink hover:bg-canvas">{{ $suggestion }}</button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</form>
