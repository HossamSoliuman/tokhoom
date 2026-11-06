<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\LoginCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * One-time codes for signing in with a mobile number. A code is kept hashed
 * for a few minutes and works once.
 */
class LoginCodes
{
    public const VALID_MINUTES = 5;

    /**
     * Text a new code to the account with this mobile. Numbers with no
     * account, or shared by several, get nothing, and the caller cannot tell.
     */
    public static function send(string $mobile): void
    {
        $users = User::where('mobile', $mobile)->limit(2)->get();

        if ($users->count() !== 1) {
            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::key($mobile), ['user_id' => $users->first()->id, 'hash' => Hash::make($code)], now()->addMinutes(self::VALID_MINUTES));

        $users->first()->notify(new LoginCode($code));
    }

    /**
     * The account the code signs in to, or null for a wrong or expired code.
     */
    public static function verify(string $mobile, string $code): ?User
    {
        $entry = Cache::get(self::key($mobile));

        if (! $entry || ! Hash::check($code, $entry['hash'])) {
            return null;
        }

        Cache::forget(self::key($mobile));

        return User::find($entry['user_id']);
    }

    private static function key(string $mobile): string
    {
        return 'login-code:'.$mobile;
    }
}
