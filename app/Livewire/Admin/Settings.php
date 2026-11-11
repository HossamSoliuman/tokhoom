<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AdminAction;
use App\Notifications\MailSettingsCheck;
use App\Support\Phone;
use App\Support\PlatformSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Platform-wide settings: plan prices, invoicing details, support contacts,
 * signups, the payment gateway and outgoing email. Each section saves on its own.
 */
#[Title('الإعدادات')]
class Settings extends Component
{
    use ShowsToasts;

    #[Url]
    public string $section = 'plans';

    /**
     * The open section's settings as edited. Secrets start empty and keep
     * their saved value unless a new one is typed.
     *
     * @var array<string, mixed>
     */
    public array $form = [];

    public function boot(): void
    {
        Gate::authorize('platform-admin');
    }

    public function mount(): void
    {
        $this->loadSection();
    }

    public function updatedSection(): void
    {
        $this->loadSection();
    }

    /**
     * Fill the form with the open section's settings in effect, dropping
     * unsaved edits of the section that was open before.
     */
    private function loadSection(): void
    {
        if (! isset(PlatformSettings::SECTIONS[$this->section])) {
            $this->section = 'plans';
        }

        $this->form = collect($this->fields())->mapWithKeys(fn (string $field) => [$field => match (true) {
            PlatformSettings::isSecret($field) => '',
            $field === 'reserved_slugs' => implode("\n", PlatformSettings::get($field)),
            default => PlatformSettings::get($field) ?? '',
        }])->all();

        $this->resetValidation();
    }

    /**
     * @return list<string>
     */
    private function fields(): array
    {
        return PlatformSettings::SECTIONS[$this->section]['fields'];
    }

    public function save(): void
    {
        $values = $this->normalized();

        Validator::make(['form' => $values], $this->sectionRules(), $this->messages(), $this->attributeLabels())->validate();

        $changed = PlatformSettings::save(array_filter(
            $values,
            fn (mixed $value, string $field) => ! PlatformSettings::isSecret($field) || filled($value),
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed !== []) {
            AdminAction::record('settings.updated', properties: ['section' => $this->section, 'fields' => $changed]);
        }

        $this->loadSection();

        $changed === []
            ? $this->toast('لا تغييرات لحفظها', 'info')
            : $this->toast('تم حفظ '.PlatformSettings::SECTIONS[$this->section]['label']);
    }

    /**
     * Send a test email to the signed-in admin with the saved mail settings.
     */
    public function sendTestEmail(): void
    {
        abort_unless($this->section === 'mail', 404);

        $admin = auth()->user();

        try {
            $admin->notifyNow(new MailSettingsCheck);
        } catch (TransportExceptionInterface $exception) {
            $this->toast('تعذّر الإرسال: '.Str::limit($exception->getMessage(), 160), 'err');

            return;
        }

        config('mail.default') === 'log'
            ? $this->toast('حُفظت الرسالة في سجل التطبيق، فالإرسال متوقف', 'info')
            : $this->toast('أُرسلت رسالة تجريبية إلى '.$admin->email);
    }

    /**
     * The open section's values cleaned up: digits from Arabic numerals,
     * mobiles in the 05 form and reserved addresses as a list.
     *
     * @return array<string, mixed>
     */
    private function normalized(): array
    {
        $values = array_intersect_key($this->form, array_flip($this->fields()));

        foreach ($values as $field => $value) {
            $values[$field] = match ($field) {
                'support_whatsapp' => filled($value) ? Phone::normalize($value) : null,
                'support_phone', 'seller_vat_number', 'seller_cr_number' => filled($value) ? Phone::digits($value) : null,
                'reserved_slugs' => array_values(array_unique(preg_split('/[\s,،]+/u', Str::lower((string) $value), flags: PREG_SPLIT_NO_EMPTY))),
                'signups_open' => (bool) $value,
                default => is_string($value) ? trim($value) : $value,
            };
        }

        return $values;
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function sectionRules(): array
    {
        $smtp = ($this->form['mail_mailer'] ?? null) === 'smtp';
        $price = ['required', 'integer', 'min:1', 'max:100000'];
        $seats = ['required', 'integer', 'min:1', 'max:100'];

        $rules = [
            'agent_price' => $price,
            'agent_listing_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'office_price' => $price,
            'office_seats' => $seats,
            'plus_price' => $price,
            'plus_seats' => $seats,
            'extra_seat_price' => ['required', 'integer', 'min:0', 'max:100000'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'collection_attempts' => ['required', 'integer', 'min:1', 'max:10'],

            'seller_name' => ['required', 'string', 'max:120'],
            'seller_vat_number' => ['nullable', 'regex:/^3\d{13}3$/'],
            'seller_cr_number' => ['nullable', 'digits:10'],
            'seller_address' => ['nullable', 'string', 'max:255'],
            'vat_rate' => ['required', 'integer', 'min:0', 'max:100'],

            'support_whatsapp' => ['nullable', 'regex:/^05\d{8}$/'],
            'support_phone' => ['nullable', 'regex:/^(0\d{8,9}|9200\d{5}|800\d{7})$/'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'support_hours' => ['nullable', 'string', 'max:120'],
            'demo_site_url' => ['nullable', 'url:http,https', 'max:255'],

            'signups_open' => ['boolean'],
            'reserved_slugs' => ['array', 'max:500'],
            'reserved_slugs.*' => ['string', 'max:63', 'regex:/^[a-z0-9][a-z0-9-]*$/'],

            'tap_secret_key' => ['nullable', 'string', 'max:255', 'regex:/^sk_(test|live)_\S+$/'],
            'tap_merchant_id' => ['nullable', 'string', 'max:100'],

            'mail_mailer' => ['required', Rule::in(['smtp', 'log'])],
            'mail_host' => [Rule::requiredIf($smtp), 'nullable', 'string', 'max:255'],
            'mail_port' => [Rule::requiredIf($smtp), 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['required', 'email', 'max:190'],
            'mail_from_name' => ['required', 'string', 'max:120'],
        ];

        return collect($rules)
            ->filter(fn (array $rule, string $key) => in_array(Str::before($key, '.'), $this->fields(), true))
            ->mapWithKeys(fn (array $rule, string $key) => ['form.'.$key => $rule])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'form.seller_vat_number.regex' => 'الرقم الضريبي 15 رقماً يبدأ وينتهي بالرقم 3.',
            'form.support_whatsapp.regex' => 'أدخل رقم جوال سعودي يبدأ بـ 05.',
            'form.support_phone.regex' => 'أدخل رقماً مثل 0112345678 أو 920012345.',
            'form.reserved_slugs.*.regex' => 'اكتب العناوين بأحرف إنجليزية صغيرة وأرقام وشرطة فقط.',
            'form.tap_secret_key.regex' => 'المفتاح السري يبدأ بـ sk_test_ أو sk_live_.',
            'form.mail_mailer.required' => 'اختر طريقة الإرسال.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributeLabels(): array
    {
        return collect($this->fields())->mapWithKeys(fn (string $field) => ['form.'.$field => PlatformSettings::label($field)])->all();
    }

    public function render(): View
    {
        $tapKey = (string) PlatformSettings::get('tap_secret_key');

        return view('livewire.admin.settings', [
            'sections' => PlatformSettings::SECTIONS,
            'tapKey' => $tapKey === '' ? null : preg_replace('/^(sk_(?:test|live)_)?.*(.{4})$/', '$1••••$2', $tapKey),
            'tapTestMode' => str_starts_with($tapKey, 'sk_test_'),
            'mailPasswordSet' => filled(PlatformSettings::get('mail_password')),
        ]);
    }
}
