<x-app.page title="حسابي">
    <div class="flex max-w-[640px] flex-col gap-4">
        <x-ui.card title="بياناتي" class="gap-4">
            <form wire:submit="saveDetails" class="flex flex-col gap-4">
                <x-ui.field label="الاسم" error="name">
                    <input type="text" wire:model="name" class="field" autocomplete="name">
                </x-ui.field>

                <x-ui.field label="البريد الإلكتروني" error="email">
                    <input type="email" wire:model="email" class="field text-right" dir="ltr" autocomplete="email">
                </x-ui.field>

                <x-ui.field label="رقم الجوال" error="mobile">
                    <input type="tel" wire:model="mobile" class="field text-right" dir="ltr" placeholder="05XXXXXXXX" inputmode="tel" autocomplete="tel">
                </x-ui.field>

                <x-ui.button type="submit" icon="ti-check" class="self-start">حفظ</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="كلمة المرور" class="gap-4">
            <form wire:submit="changePassword" class="flex flex-col gap-4">
                <x-ui.field label="كلمة المرور الحالية" error="currentPassword">
                    <input type="password" wire:model="currentPassword" class="field text-right" dir="ltr" autocomplete="current-password">
                </x-ui.field>

                <x-ui.field label="كلمة المرور الجديدة" error="newPassword">
                    <input type="password" wire:model="newPassword" class="field text-right" dir="ltr" autocomplete="new-password">
                </x-ui.field>

                <x-ui.field label="تأكيد كلمة المرور الجديدة">
                    <input type="password" wire:model="newPassword_confirmation" class="field text-right" dir="ltr" autocomplete="new-password">
                </x-ui.field>

                <x-ui.button type="submit" variant="secondary" icon="ti-lock" class="self-start">تغيير كلمة المرور</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="التحقق بخطوتين" class="gap-4">
            @if ($twoFactorOn)
                <div class="flex items-center gap-2 text-14">
                    <span class="flex size-8 items-center justify-center rounded-full bg-success-soft text-success"><i class="ti ti-shield-check text-18"></i></span>
                    <span class="font-semibold">مفعّل</span>
                    <span class="text-muted">· {{ \App\Support\Arabic::count($recoveryLeft, 'رمز استرداد واحد متبقٍ', 'رمزا استرداد متبقيان', 'رموز استرداد متبقية', 'رمز استرداد متبقٍ') }}</span>
                </div>

                @if ($recoveryCodes)
                    <div class="flex flex-col gap-3 rounded-xl border border-notice-line bg-notice p-4">
                        <span class="text-14 font-semibold text-notice-ink">احفظ رموز الاسترداد في مكان آمن. كل رمز يُستخدم مرة واحدة إذا فقدت جوالك، ولن تظهر مرة أخرى.</span>
                        <div class="grid grid-cols-2 gap-2 font-mono text-14" dir="ltr">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <span class="rounded-lg bg-white px-3 py-2 text-center">{{ $recoveryCode }}</span>
                            @endforeach
                        </div>
                        <x-ui.button variant="secondary" size="sm" icon="ti-copy" class="self-start" x-on:click="$copy(@js(implode(chr(10), $recoveryCodes)), 'تم نسخ رموز الاسترداد')">نسخ الرموز</x-ui.button>
                    </div>
                @endif

                <form wire:submit="disableTwoFactor" class="flex flex-col gap-3 border-t border-divider pt-4">
                    <x-ui.field label="كلمة المرور الحالية لإيقاف التحقق بخطوتين" error="disablePassword">
                        <input type="password" wire:model="disablePassword" class="field text-right" dir="ltr" autocomplete="current-password">
                    </x-ui.field>
                    <x-ui.button type="submit" variant="danger-outline" icon="ti-shield-off" class="self-start">إيقاف التحقق بخطوتين</x-ui.button>
                </form>
            @elseif ($pendingSecret)
                <div class="flex flex-wrap items-start gap-4">
                    <div class="w-[170px] shrink-0 rounded-lg border border-divider bg-white p-2.5 [&_svg]:block [&_svg]:h-auto [&_svg]:w-full" dir="ltr">
                        {!! $pendingQr !!}
                    </div>
                    <div class="flex min-w-0 flex-[1_1_220px] flex-col gap-2 text-14 leading-6 text-body">
                        <span>امسح الرمز بتطبيق مصادقة مثل Google Authenticator أو Microsoft Authenticator، أو أدخل هذا المفتاح يدوياً:</span>
                        <span class="break-all rounded-lg bg-canvas px-3 py-2 font-mono text-13 text-ink" dir="ltr">{{ trim(chunk_split($pendingSecret, 4, ' ')) }}</span>
                    </div>
                </div>

                <form wire:submit="confirmTwoFactor" class="flex flex-col gap-3">
                    <x-ui.field label="الرمز الظاهر في التطبيق" error="twoFactorCode">
                        <input type="text" wire:model="twoFactorCode" class="field text-center font-mono tracking-[0.3em]" dir="ltr" placeholder="000000" inputmode="numeric" autocomplete="one-time-code" maxlength="6">
                    </x-ui.field>
                    <div class="flex gap-2">
                        <x-ui.button type="submit" icon="ti-check">تفعيل</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="cancelTwoFactor">إلغاء</x-ui.button>
                    </div>
                </form>
            @else
                <p class="text-14 leading-6 text-body">اطلب رمزاً من تطبيق مصادقة في جوالك عند كل تسجيل دخول، إضافة إلى كلمة المرور.</p>
                <x-ui.button variant="secondary" icon="ti-shield-lock" class="self-start" wire:click="startTwoFactor">تفعيل التحقق بخطوتين</x-ui.button>
            @endif
        </x-ui.card>
    </div>
</x-app.page>
