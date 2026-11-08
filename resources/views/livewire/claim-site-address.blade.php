<form wire:submit="start" class="flex flex-col gap-3"
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
    <label for="claim-slug-{{ $this->getId() }}" @class(['text-15 font-semibold', 'sr-only' => ! $withLabel])>اختر عنوان موقعك</label>

    <div class="grid grid-cols-2 gap-1 rounded-field bg-divider p-1" role="group" aria-label="نوع الحساب">
        @foreach (['office' => 'مكتب عقاري', 'agent' => 'وسيط مستقل'] as $value => $label)
            <button type="button" x-on:click="pickKind(@js($value))" :aria-pressed="$wire.kind === @js($value)"
                    class="h-10 rounded-lg text-15 font-semibold transition-colors"
                    :class="$wire.kind === @js($value) ? 'bg-white text-ink shadow-sm' : 'text-body'">{{ $label }}</button>
        @endforeach
    </div>

    <div class="flex flex-col gap-2.5 sm:flex-row">
        <div @class([
            'flex h-12 min-w-0 flex-1 items-center overflow-hidden rounded-field border-[1.5px] bg-white transition-colors',
            'border-danger' => $rejected,
            'border-success' => $state === 'available' && ! $rejected,
            'border-line-strong focus-within:border-primary' => $state !== 'available' && ! $rejected,
        ]) dir="ltr">
            <input id="claim-slug-{{ $this->getId() }}" name="slug" type="text" wire:model.live.debounce.400ms="slug" x-on:input="typed($event.target.value)"
                   placeholder="alrashid" autocomplete="off" autocapitalize="none" spellcheck="false" enterkeyhint="go"
                   class="h-full min-w-0 flex-1 border-0 bg-transparent px-4 font-mono text-16 outline-none">
            @if ($state === 'available' && ! $rejected)
                <i wire:loading.remove wire:target="slug" class="ti ti-circle-check-filled pe-3 text-20 text-success"></i>
            @endif
            <span class="flex h-full items-center border-l border-line bg-head px-3.5 font-mono text-14 text-muted">.{{ config('tokhoom.site_domain') }}</span>
        </div>
        <button type="submit" class="flex h-12 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-field bg-primary px-6 text-16 font-semibold text-white transition-colors hover:bg-primary-strong data-loading:opacity-70">
            ابدأ التجربة
            <i class="ti ti-arrow-left text-[19px]"></i>
        </button>
    </div>

    <div class="flex min-h-6 flex-col gap-2.5" aria-live="polite">
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
                @elseif ($state === 'reserved')
                    <span class="flex items-center gap-1.5 text-14 font-semibold text-danger"><i class="ti ti-circle-x text-18"></i>هذا العنوان غير متاح.</span>
                @elseif ($state === 'arabic')
                    <span class="text-14 text-ink-soft">اكتب العنوان بأحرف إنجليزية، مثل <span class="font-mono" dir="ltr">alrashid</span></span>
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
