<?php

namespace App\Http\Controllers;

use App\Support\Acme;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Answers Let's Encrypt's HTTP-01 check on any host while a custom domain's
 * certificate is being issued.
 */
class AcmeChallengeController extends Controller
{
    public function __invoke(string $token): Response
    {
        $answer = Cache::get(Acme::challengeCacheKey($token));

        abort_if($answer === null, 404);

        return response($answer, 200, ['Content-Type' => 'text/plain']);
    }
}
