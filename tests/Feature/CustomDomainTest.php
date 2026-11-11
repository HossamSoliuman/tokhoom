<?php

use App\Support\Acme;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config([
        'tokhoom.certificates.directory_url' => 'https://acme.test/directory',
        'tokhoom.certificates.account_key' => storage_path('framework/testing/acme/account.key'),
        'tokhoom.certificates.path' => storage_path('framework/testing/certificates'),
    ]);

    Sleep::fake();
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/acme'));
    File::deleteDirectory(storage_path('framework/testing/certificates'));
});

function base64UrlDecode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * A self-signed certificate standing in for the one Let's Encrypt returns.
 */
function selfSignedCertificate(string $domain): string
{
    $config = tempnam(sys_get_temp_dir(), 'cert');
    file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\n");

    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048, 'config' => $config]);
    $csr = openssl_csr_new(['commonName' => $domain], $key, ['digest_alg' => 'sha256', 'config' => $config]);
    openssl_x509_export(openssl_csr_sign($csr, null, $key, 90, ['digest_alg' => 'sha256', 'config' => $config]), $pem);
    unlink($config);

    return $pem;
}

/**
 * Fake the ACME server for one order whose HTTP challenge ends as given.
 *
 * @param  array<string, mixed>  $finalAuthorization
 */
function fakeAcmeServer(array $finalAuthorization, ?Closure $onChallenge = null, ?string $certificate = null): void
{
    $nonce = ['Replay-Nonce' => 'nonce'];

    Http::fake([
        'acme.test/directory' => Http::response([
            'newNonce' => 'https://acme.test/nonce',
            'newAccount' => 'https://acme.test/account',
            'newOrder' => 'https://acme.test/order',
        ]),
        'acme.test/nonce' => Http::response('', 200, $nonce),
        'acme.test/account' => Http::response(['status' => 'valid'], 201, [...$nonce, 'Location' => 'https://acme.test/account/1']),
        'acme.test/order' => Http::response([
            'status' => 'pending',
            'authorizations' => ['https://acme.test/authz/1'],
            'finalize' => 'https://acme.test/finalize/1',
        ], 201, [...$nonce, 'Location' => 'https://acme.test/order/1']),
        'acme.test/authz/1' => Http::sequence()
            ->push(['status' => 'pending', 'challenges' => [
                ['type' => 'dns-01', 'url' => 'https://acme.test/challenge/2', 'token' => 'token-2'],
                ['type' => 'http-01', 'url' => 'https://acme.test/challenge/1', 'token' => 'token-1'],
            ]], 200, $nonce)
            ->push($finalAuthorization, 200, $nonce),
        'acme.test/challenge/1' => function () use ($onChallenge, $nonce) {
            $onChallenge?->__invoke();

            return Http::response(['status' => 'processing'], 200, $nonce);
        },
        'acme.test/finalize/1' => Http::response(['status' => 'processing'], 200, $nonce),
        'acme.test/order/1' => Http::response(['status' => 'valid', 'certificate' => 'https://acme.test/certificate/1'], 200, $nonce),
        'acme.test/certificate/1' => Http::response($certificate ?? '', 200, $nonce),
    ]);
}

test('a certificate is ordered, validated over HTTP and downloaded', function () {
    $pem = selfSignedCertificate('www.alrashid.sa');
    $published = null;

    fakeAcmeServer(['status' => 'valid'], function () use (&$published) {
        $published = Cache::get(Acme::challengeCacheKey('token-1'));
    }, $pem);

    $issued = app(Acme::class)->issue('www.alrashid.sa');

    expect($issued['certificate'])->toBe($pem)
        ->and(openssl_pkey_get_private($issued['private_key']))->not->toBeFalse()
        ->and($issued['expires_at']->isFuture())->toBeTrue()
        ->and($published)->toStartWith('token-1.')
        ->and(Cache::has(Acme::challengeCacheKey('token-1')))->toBeFalse();

    $accountKey = openssl_pkey_get_private(file_get_contents(config('tokhoom.certificates.account_key')));
    $publicKey = openssl_pkey_get_public(openssl_pkey_get_details($accountKey)['key']);

    Http::assertSent(function (Request $request) use ($publicKey) {
        if ($request->url() !== 'https://acme.test/order') {
            return false;
        }

        $jws = json_decode($request->body(), true);
        $header = json_decode(base64UrlDecode($jws['protected']), true);
        $payload = json_decode(base64UrlDecode($jws['payload']), true);

        return $header['kid'] === 'https://acme.test/account/1'
            && $payload['identifiers'] === [['type' => 'dns', 'value' => 'www.alrashid.sa']]
            && openssl_verify($jws['protected'].'.'.$jws['payload'], base64UrlDecode($jws['signature']), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    });

    Http::assertSent(function (Request $request) {
        if ($request->url() !== 'https://acme.test/finalize/1') {
            return false;
        }

        $csr = json_decode(base64UrlDecode(json_decode($request->body(), true)['payload']), true)['csr'];
        $pem = "-----BEGIN CERTIFICATE REQUEST-----\n".chunk_split(base64_encode(base64UrlDecode($csr)), 64)."-----END CERTIFICATE REQUEST-----\n";

        return openssl_csr_get_subject($pem)['CN'] === 'www.alrashid.sa';
    });
});

test('a failed HTTP check reports why and withdraws the challenge answer', function () {
    fakeAcmeServer(['status' => 'invalid', 'challenges' => [
        ['type' => 'http-01', 'status' => 'invalid', 'error' => ['detail' => 'Timeout during connect']],
    ]]);

    expect(fn () => app(Acme::class)->issue('www.alrashid.sa'))->toThrow(RuntimeException::class, 'Timeout during connect')
        ->and(Cache::has(Acme::challengeCacheKey('token-1')))->toBeFalse();
});

test('the challenge answer is served on any host while it is open', function () {
    Cache::put(Acme::challengeCacheKey('token-1'), 'token-1.thumbprint', 60);

    $this->get(hostUrl('www.alrashid.sa', '/.well-known/acme-challenge/token-1'))
        ->assertOk()
        ->assertContent('token-1.thumbprint');

    $this->get(hostUrl('www.alrashid.sa', '/.well-known/acme-challenge/token-2'))->assertNotFound();
});
