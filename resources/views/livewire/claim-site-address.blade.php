<form wire:submit="start" class="flex flex-col gap-5"
      x-data="{
          id: $id('claim'),
          timer: null,
          typed(value) {
              if (! $store.claim) return;
              $store.claim.source = this.id;
              $store.claim.slug = $store.claim.clean(value);
              $store.claim.state = 'checking';
          },
          pickKind(kind) {
              if ($store.claim) { $store.claim.source = this.id; $store.claim.kind = kind; }
              $wire.set('kind', kind);
          },
          follow() {
              if (! $store.claim || $store.claim.source === this.id) return;
              const slug = $store.claim.slug, kind = $store.claim.kind;
              clearTimeout(this.timer);
              this.timer = setTimeout(() => {
                  const kindChanged = kind !== $wire.kind;
                  if (kindChanged) $wire.kind = kind;
                  if (slug !== $wire.slug) $wire.set('slug', slug);
                  else if (kindChanged) $wire.$refresh();
              }, 400);
          },
      }"
      x-init="$store.claim?.load($wire.slug, $wire.kind, @js($this->slugState))"
      x-effect="follow()">
    @php($state = $this->slugState)
    @php($rejected = in_array($state, ['taken', 'reserved']) || $errors->has('slug'))
    @php($domain = config('tokhoom.site_domain'))
    @if ($withLabel)
        <h2 class="text-18 font-bold">اختر عنوان موقعك</h2>
    @endif

    <div class="flex flex-col gap-2">
        <span class="text-14 font-semibold text-body">نوع الحساب</span>
        <div class="grid grid-cols-2 gap-2.5" role="group" aria-label="نوع الحساب">
            @foreach (['office' => ['مكتب عقاري', 'ti-building'], 'agent' => ['وسيط مستقل', 'ti-user']] as $value => [$label, $icon])
                <button type="button" x-on:click="pickKind(@js($value))" :aria-pressed="$wire.kind === @js($value)"
                        class="flex h-14 items-center gap-3 rounded-field border-[1.5px] px-3.5 text-start transition-colors"
                        :class="$wire.kind === @js($value) ? 'border-primary bg-primary-tint' : 'border-line hover:border-line-strong'">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors"
                          :class="$wire.kind === @js($value) ? 'bg-primary text-white' : 'bg-divider text-body'"><i class="ti {{ $icon }} text-19"></i></span>
                    <span class="flex-1 text-15 font-semibold">{{ $label }}</span>
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full border-[1.5px] transition-colors"
                          :class="$wire.kind === @js($value) ? 'border-primary bg-primary' : 'border-line-strong'">
                        <span class="size-2 rounded-full bg-white" x-show="$wire.kind === @js($value)"></span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <label for="claim-slug-{{ $this->getId() }}" class="text-14 font-semibold text-body">عنوان الموقع</label>
        <div class="flex flex-col gap-2.5 sm:flex-row">
            <div @class([
                'flex h-14 min-w-0 flex-1 items-center overflow-hidden rounded-field border-[1.5px] bg-white transition-colors focus-within:ring-4',
                'border-danger focus-within:ring-danger/10' => $rejected,
                'border-success focus-within:ring-success/10' => $state === 'available' && ! $rejected,
                'border-line-strong focus-within:border-primary focus-within:ring-primary/10' => $state !== 'available' && ! $rejected,
            ]) dir="ltr">
                <i class="ti ti-world ps-4 text-20 text-muted"></i>
                <input id="claim-slug-{{ $this->getId() }}" name="slug" type="text" wire:model.live.debounce.400ms="slug" x-on:input="typed($event.target.value)"
                       placeholder="alrashid" autocomplete="off" autocapitalize="none" spellcheck="false" enterkeyhint="go"
                       class="h-full min-w-0 flex-1 border-0 bg-transparent px-2.5 font-mono text-17 outline-none">
                <span class="pe-2 font-mono text-15 text-muted">.{{ $domain }}</span>
                <span class="flex w-9 shrink-0 justify-center pe-2">
                    <i wire:loading wire:target="slug" class="ti ti-loader-2 animate-spin text-19 text-muted"></i>
                    @if ($state === 'available' && ! $rejected)
                        <i wire:loading.remove wire:target="slug" class="ti ti-circle-check-filled text-20 text-success"></i>
                    @elseif ($rejected)
                        <i wire:loading.remove wire:target="slug" class="ti ti-circle-x-filled text-20 text-danger"></i>
                    @endif
                </span>
            </div>
            <button type="submit" class="flex h-14 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-field bg-primary px-7 text-17 font-bold text-white shadow-[0_6px_16px_rgba(14,107,90,0.25)] transition-colors hover:bg-primary-strong data-loading:opacity-70">
                ابدأ التجربة
                <i class="ti ti-arrow-left text-[19px]"></i>
            </button>
        </div>
    </div>

    <div class="flex min-h-6 flex-col gap-2.5" aria-live="polite">
        <span wire:loading.flex wire:target="slug" class="items-center gap-2 text-14 text-muted">جارٍ التحقق من التوفر…</span>

        <div wire:loading.remove wire:target="slug" class="flex flex-col gap-2.5">
            @error('slug')
                <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>{{ $message }}</span>
            @else
                @if ($state === 'available')
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-field bg-success-soft px-3.5 py-2.5">
                        <span class="flex items-center gap-1.5 text-14 font-semibold text-success"><i class="ti ti-circle-check text-18"></i>العنوان متاح. احجزه بإنشاء حسابك.</span>
                        <span class="flex items-center gap-1 font-mono text-13 text-ink" dir="ltr"><i class="ti ti-lock text-14 text-success"></i>{{ $slug }}.{{ $domain }}</span>
                    </div>
                @elseif ($state === 'taken')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>هذا العنوان مستخدم.</span>
                @elseif ($state === 'reserved')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>هذا العنوان غير متاح.</span>
                @elseif ($state === 'arabic')
                    <span class="text-14 text-ink-soft">اكتب العنوان بأحرف إنجليزية، مثل <span class="font-mono" dir="ltr">alrashid</span></span>
                @elseif ($state === 'invalid')
                    <span class="text-14 text-muted">أحرف إنجليزية صغيرة وأرقام وشرطة، 3 أحرف على الأقل.</span>
                @else
                    <span class="flex items-center gap-1.5 text-14 text-muted"><i class="ti ti-shield-check text-17 text-primary"></i>تجربة {{ config('tokhoom.trial_days') }} يوماً بلا بطاقة دفع. يظهر موقعك للزوار بعد مراجعة الترخيص.</span>
                @endif
            @enderror

            @if ($this->suggestions)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-14 text-body">جرّب:</span>
                    @foreach ($this->suggestions as $suggestion)
                        <button type="button" wire:key="claim-suggestion-{{ $suggestion }}" wire:click="useSuggestion('{{ $suggestion }}')" dir="ltr"
                                class="h-9 rounded-full border border-line-strong bg-white px-3.5 font-mono text-13 text-ink hover:border-primary hover:bg-primary-tint">{{ $suggestion }}</button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</form>
