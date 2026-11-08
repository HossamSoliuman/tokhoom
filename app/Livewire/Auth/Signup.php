<?php

namespace App\Livewire\Auth;

use App\Actions\CreateWorkspace;
use App\Enums\WorkspaceKind;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Phone;
use App\Support\SiteAddress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Step-by-step signup: account, account type, license, site address, then a
 * "pending approval" screen. Signed-in users start at the account type step to
 * create another workspace.
 */
#[Layout('layouts::auth')]
#[Title('إنشاء حساب')]
class Signup extends Component
{
    public const STEPS = ['الحساب', 'نوع الحساب', 'الترخيص', 'عنوان الموقع'];

    public int $step = 1;

    public string $name = '';

    public string $email = '';

    public string $mobile = '';

    public string $password = '';

    public string $kind = 'office';

    public string $tradeName = '';

    public string $crNumber = '';

    public string $falNumber = '';

    public string $licenseExpiresOn = '';

    public string $city = 'الرياض';

    public string $slug = '';

    #[Locked]
    public ?int $workspaceId = null;

    public function mount(): void
    {
        $this->slug = SiteAddress::clean((string) request()->query('slug', ''));

        if (Auth::check()) {
            $this->step = 2;
            $this->name = Auth::user()->name;
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slug = SiteAddress::clean($value);
    }

    public function pickKind(string $kind): void
    {
        $this->kind = WorkspaceKind::from($kind)->value;
    }

    public function useSuggestion(string $slug): void
    {
        $this->slug = SiteAddress::clean($slug);
    }

    public function back(): void
    {
        if ($this->step > $this->firstStep() && $this->step < 5) {
            $this->step--;
        }
    }

    public function next(CreateWorkspace $createWorkspace): void
    {
        match ($this->step) {
            1 => $this->validateAccount(),
            2 => null,
            3 => $this->validateLicense(),
            4 => $this->finish($createWorkspace),
            default => $this->redirectRoute('today', navigate: true),
        };

        if ($this->step < 4) {
            $this->step++;
        }
    }

    #[Computed]
    public function slugState(): string
    {
        return SiteAddress::state($this->slug);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function suggestions(): array
    {
        return $this->slugState === 'taken' ? SiteAddress::suggestions($this->slug, $this->city) : [];
    }

    #[Computed]
    public function workspace(): ?Workspace
    {
        return $this->workspaceId ? Workspace::find($this->workspaceId) : null;
    }

    public function isOffice(): bool
    {
        return $this->kind === WorkspaceKind::Office->value;
    }

    public function firstStep(): int
    {
        return Auth::check() && $this->step < 5 && ! $this->workspaceId ? 2 : 1;
    }

    public function title(): string
    {
        return match ($this->step) {
            1 => 'إنشاء حساب',
            2 => 'كيف ستستخدم تخوم؟',
            3 => $this->isOffice() ? 'بيانات ترخيص المكتب' : 'بيانات رخصة فال',
            4 => 'اختر عنوان موقعك',
            default => 'طلبك قيد المراجعة',
        };
    }

    private function validateAccount(): void
    {
        $this->email = Str::lower(trim($this->email));
        $this->mobile = Phone::normalize($this->mobile);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'mobile.regex' => 'أدخل رقم جوال سعودي يبدأ بـ 05.',
        ]);
    }

    private function validateLicense(): void
    {
        $rules = [
            'falNumber' => ['required', 'digits:10'],
            'licenseExpiresOn' => ['required', 'date', 'after:today'],
            'city' => ['required', Rule::in(SiteAddress::cities())],
        ];

        if ($this->isOffice()) {
            $rules['tradeName'] = ['required', 'string', 'max:120'];
            $rules['crNumber'] = ['required', 'digits:10'];
        } else {
            $rules['name'] = ['required', 'string', 'max:120'];
        }

        $this->validate($rules, [
            'licenseExpiresOn.after' => 'الترخيص منتهٍ. أدخل ترخيصاً سارياً.',
        ], [
            'name' => 'الاسم الكامل',
            'falNumber' => $this->isOffice() ? 'رقم ترخيص فال للمنشأة' : 'رقم رخصة فال',
        ]);
    }

    private function finish(CreateWorkspace $createWorkspace): void
    {
        if ($this->slugState !== 'available') {
            $this->addError('slug', 'اختر عنواناً متاحاً.');

            return;
        }

        $user = Auth::user() ?? $this->createUser();

        $workspace = $createWorkspace->handle($user, [
            'kind' => WorkspaceKind::from($this->kind),
            'name' => $this->isOffice() ? trim($this->tradeName) : trim($this->name),
            'slug' => $this->slug,
            'city' => $this->city,
            'cr_number' => $this->isOffice() ? $this->crNumber : null,
            'fal_number' => $this->falNumber,
            'license_expires_on' => $this->licenseExpiresOn,
        ]);

        $this->workspaceId = $workspace->id;
        $this->password = '';
        $this->step = 5;
    }

    private function createUser(): User
    {
        $this->validateAccount();

        $user = User::create([
            'name' => trim($this->name),
            'email' => $this->email,
            'mobile' => $this->mobile,
            'password' => $this->password,
        ]);

        Auth::login($user, remember: true);
        session()->regenerate();

        return $user;
    }

    public function render(): View
    {
        return view('livewire.auth.signup');
    }
}
