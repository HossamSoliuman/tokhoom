<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Support\LoginCodes;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Sign in with email and password, or with a code texted to the mobile.
 */
#[Layout('layouts::auth')]
#[Title('تسجيل الدخول')]
class Login extends Component
{
    private const CODES_PER_WINDOW = 3;

    private const CODE_GUESSES_PER_WINDOW = 5;

    private const CODES_PER_VISITOR = 10;

    private const CODE_GUESSES_PER_VISITOR = 20;

    private const WINDOW_SECONDS = 600;

    private const TWO_FACTOR_GUESSES_PER_WINDOW = 5;

    public string $method = 'email';

    public string $email = '';

    public string $password = '';

    public string $mobile = '';

    public string $code = '';

    /**
     * The number the last code went to; the code is checked against it.
     */
    #[Locked]
    public string $codeMobile = '';

    public bool $remember = true;

    public string $twoFactorCode = '';

    /**
     * The member who passed the first step and still owes an authenticator code.
     */
    #[Locked]
    public ?int $twoFactorUserId = null;

    #[Locked]
    public int $twoFactorExpiresAt = 0;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'محاولات كثيرة. حاول مرة أخرى بعد '.RateLimiter::availableIn($key).' ثانية.');

            return;
        }

        if (! Auth::validate(['email' => Str::lower($this->email), 'password' => $this->password])) {
            RateLimiter::hit($key);
            $this->addError('email', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');

            return;
        }

        RateLimiter::clear($key);
        $this->passedFirstStep(Auth::getLastAttempted());
    }

    public function sendCode(): void
    {
        abort_unless(SmsChannel::delivers(), 404);

        $this->mobile = Phone::normalize($this->mobile);

        $this->validate(['mobile' => ['required', 'regex:/^05\d{8}$/']], [
            'mobile.required' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'mobile.regex' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
        ]);

        $key = 'login-code-send:'.$this->mobile;
        $visitorKey = 'login-code-send:'.request()->ip();

        foreach ([$key => self::CODES_PER_WINDOW, $visitorKey => self::CODES_PER_VISITOR] as $limiter => $limit) {
            if (RateLimiter::tooManyAttempts($limiter, $limit)) {
                $this->addError('mobile', 'أرسلنا عدة رموز. حاول مرة أخرى بعد '.RateLimiter::availableIn($limiter).' ثانية.');

                return;
            }
        }

        RateLimiter::hit($key, self::WINDOW_SECONDS);
        RateLimiter::hit($visitorKey, self::WINDOW_SECONDS);
        LoginCodes::send($this->mobile);

        $this->code = '';
        $this->codeMobile = $this->mobile;
    }

    public function verifyCode(): void
    {
        abort_if($this->codeMobile === '', 404);

        $this->code = Phone::digits($this->code);
        $this->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'أدخل الرمز المكوّن من 6 أرقام',
            'code.digits' => 'أدخل الرمز المكوّن من 6 أرقام',
        ]);

        $key = 'login-code-verify:'.$this->codeMobile;
        $visitorKey = 'login-code-verify:'.request()->ip();

        foreach ([$key => self::CODE_GUESSES_PER_WINDOW, $visitorKey => self::CODE_GUESSES_PER_VISITOR] as $limiter => $limit) {
            if (RateLimiter::tooManyAttempts($limiter, $limit)) {
                $this->addError('code', 'محاولات كثيرة. حاول مرة أخرى بعد '.RateLimiter::availableIn($limiter).' ثانية.');

                return;
            }
        }

        $user = LoginCodes::verify($this->codeMobile, $this->code);

        if (! $user instanceof User) {
            RateLimiter::hit($key, self::WINDOW_SECONDS);
            RateLimiter::hit($visitorKey, self::WINDOW_SECONDS);
            $this->addError('code', 'الرمز غير صحيح أو انتهت صلاحيته.');

            return;
        }

        RateLimiter::clear($key);
        $this->passedFirstStep($user);
    }

    public function verifyTwoFactor(): void
    {
        abort_if($this->twoFactorUserId === null || $this->twoFactorExpiresAt < now()->timestamp, 404);

        $this->validate(['twoFactorCode' => ['required', 'string', 'max:20']], [
            'twoFactorCode.required' => 'أدخل الرمز من تطبيق المصادقة',
        ]);

        $key = 'two-factor:'.$this->twoFactorUserId;

        if (RateLimiter::tooManyAttempts($key, self::TWO_FACTOR_GUESSES_PER_WINDOW)) {
            $this->addError('twoFactorCode', 'محاولات كثيرة. حاول مرة أخرى بعد '.RateLimiter::availableIn($key).' ثانية.');

            return;
        }

        $user = User::findOrFail($this->twoFactorUserId);

        if (! $user->passesTwoFactor($this->twoFactorCode)) {
            RateLimiter::hit($key, self::WINDOW_SECONDS);
            $this->addError('twoFactorCode', 'الرمز غير صحيح.');

            return;
        }

        RateLimiter::clear($key);
        Auth::login($user, $this->remember);
        $this->signedIn();
    }

    /**
     * Sign in now, or ask for the authenticator code first when the member set one up.
     */
    private function passedFirstStep(User $user): void
    {
        if (! $user->hasTwoFactor()) {
            Auth::login($user, $this->remember);
            $this->signedIn();

            return;
        }

        $this->twoFactorUserId = $user->id;
        $this->twoFactorExpiresAt = now()->addMinutes(10)->timestamp;
        $this->resetErrorBag();
    }

    /**
     * Back to the mobile field to correct the number or ask for a new code.
     */
    public function changeMobile(): void
    {
        $this->reset('code', 'codeMobile');
        $this->resetErrorBag();
    }

    private function signedIn(): void
    {
        session()->regenerate();

        $this->redirectIntended(route('home'));
    }

    public function render(): View
    {
        return view('livewire.auth.login', ['canUseMobile' => SmsChannel::delivers()]);
    }
}
