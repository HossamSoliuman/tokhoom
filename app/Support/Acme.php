<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * A small ACME client (RFC 8555) that gets Let's Encrypt certificates for
 * custom domains with the HTTP-01 challenge. While a challenge is open its
 * answer sits in the cache, where the /.well-known/acme-challenge route
 * serves it on the domain being validated.
 */
class Acme
{
    private const POLL_ATTEMPTS = 20;

    private const POLL_SECONDS = 3;

    /** @var array<string, mixed>|null */
    private ?array $directory = null;

    private ?string $nonce = null;

    private ?string $accountUrl = null;

    private ?OpenSSLAsymmetricKey $accountKey = null;

    public static function challengeCacheKey(string $token): string
    {
        return 'acme-challenge:'.$token;
    }

    /**
     * Order, validate and download a certificate for one domain.
     *
     * @return array{certificate: string, private_key: string, expires_at: Carbon}
     */
    public function issue(string $domain): array
    {
        if (! DomainVerifier::isValid($domain)) {
            throw new RuntimeException("Not a valid domain: {$domain}");
        }

        $this->registerAccount();

        $order = $this->post($this->endpoint('newOrder'), ['identifiers' => [['type' => 'dns', 'value' => $domain]]]);
        $orderUrl = (string) $order->header('Location');

        foreach ($order->json('authorizations', []) as $authorizationUrl) {
            $this->authorize($authorizationUrl);
        }

        $config = $this->opensslConfig($domain);

        try {
            $key = $this->newKey($config);
            $csr = openssl_csr_new(['commonName' => $domain], $key, ['digest_alg' => 'sha256', 'config' => $config, 'req_extensions' => 'san']);
            openssl_csr_export($csr, $csrPem);
            openssl_pkey_export($key, $keyPem, null, ['config' => $config]);
        } finally {
            @unlink($config);
        }

        $finalized = $this->post((string) $order->json('finalize'), ['csr' => self::base64url(self::pemToDer($csrPem))])->json();

        if (($finalized['status'] ?? null) !== 'valid') {
            $finalized = $this->poll($orderUrl, ['pending', 'ready', 'processing']);
        }

        if (($finalized['status'] ?? null) !== 'valid' || empty($finalized['certificate'])) {
            throw new RuntimeException('The certificate order for '.$domain.' ended as '.($finalized['status'] ?? 'unknown'));
        }

        $certificate = $this->post($finalized['certificate'], null, ['Accept' => 'application/pem-certificate-chain'])->body();
        $expiresAt = openssl_x509_parse($certificate)['validTo_time_t'] ?? null;

        if (! $expiresAt) {
            throw new RuntimeException('The certificate for '.$domain.' could not be read');
        }

        return [
            'certificate' => $certificate,
            'private_key' => $keyPem,
            'expires_at' => Carbon::createFromTimestamp($expiresAt),
        ];
    }

    /**
     * Create the account on first use, or look up the existing one for this key.
     */
    private function registerAccount(): void
    {
        if ($this->accountUrl) {
            return;
        }

        $email = config('tokhoom.certificates.email');

        $response = $this->post($this->endpoint('newAccount'), array_filter([
            'termsOfServiceAgreed' => true,
            'contact' => $email ? ['mailto:'.$email] : null,
        ]));

        $this->accountUrl = (string) $response->header('Location');
    }

    /**
     * Publish the HTTP-01 answer, ask the CA to check it, and wait for the result.
     */
    private function authorize(string $url): void
    {
        $authorization = $this->post($url, null)->json();

        if (($authorization['status'] ?? null) === 'valid') {
            return;
        }

        $challenge = collect($authorization['challenges'] ?? [])->firstWhere('type', 'http-01')
            ?? throw new RuntimeException('No HTTP challenge was offered for '.($authorization['identifier']['value'] ?? $url));

        $cacheKey = self::challengeCacheKey($challenge['token']);
        Cache::put($cacheKey, $challenge['token'].'.'.$this->thumbprint(), now()->addMinutes(10));

        try {
            $this->post($challenge['url'], (object) []);
            $authorization = $this->poll($url, ['pending', 'processing']);
        } finally {
            Cache::forget($cacheKey);
        }

        if (($authorization['status'] ?? null) !== 'valid') {
            $detail = collect($authorization['challenges'] ?? [])->pluck('error.detail')->filter()->first();

            throw new RuntimeException($detail ?: 'The domain could not be validated');
        }
    }

    /**
     * Fetch a resource until its status leaves the waiting ones.
     *
     * @param  list<string>  $waiting
     * @return array<string, mixed>
     */
    private function poll(string $url, array $waiting): array
    {
        for ($attempt = 1; $attempt <= self::POLL_ATTEMPTS; $attempt++) {
            Sleep::for(self::POLL_SECONDS)->seconds();

            $resource = $this->post($url, null)->json();

            if (! in_array($resource['status'] ?? null, $waiting, true)) {
                return $resource;
            }
        }

        throw new RuntimeException('Timed out waiting for '.$url);
    }

    /**
     * Send a signed request. A null payload is a POST-as-GET.
     *
     * @param  array<string, mixed>|object|null  $payload
     * @param  array<string, string>  $headers
     */
    private function post(string $url, array|object|null $payload, array $headers = []): Response
    {
        for ($attempt = 1; ; $attempt++) {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->withBody(json_encode($this->sign($url, $payload), JSON_UNESCAPED_SLASHES), 'application/jose+json')
                ->post($url);

            $this->nonce = $response->header('Replay-Nonce') ?: null;

            if ($response->successful()) {
                return $response;
            }

            if ($attempt < 3 && $response->json('type') === 'urn:ietf:params:acme:error:badNonce') {
                continue;
            }

            throw new RuntimeException('ACME error '.$response->status().': '.($response->json('detail') ?? $response->body()));
        }
    }

    /**
     * Flattened JWS signed with the account key: by key ID once the account
     * exists, by the public key itself before that.
     *
     * @param  array<string, mixed>|object|null  $payload
     * @return array{protected: string, payload: string, signature: string}
     */
    private function sign(string $url, array|object|null $payload): array
    {
        $header = ['alg' => 'RS256', 'nonce' => $this->nonce(), 'url' => $url];
        $this->nonce = null;

        if ($this->accountUrl) {
            $header['kid'] = $this->accountUrl;
        } else {
            $header['jwk'] = $this->jwk();
        }

        $protected = self::base64url(json_encode($header, JSON_UNESCAPED_SLASHES));
        $body = $payload === null ? '' : self::base64url(json_encode($payload, JSON_UNESCAPED_SLASHES));

        openssl_sign($protected.'.'.$body, $signature, $this->accountKey(), OPENSSL_ALGO_SHA256);

        return ['protected' => $protected, 'payload' => $body, 'signature' => self::base64url($signature)];
    }

    private function nonce(): string
    {
        return $this->nonce ??= (string) Http::timeout(30)->head($this->endpoint('newNonce'))->header('Replay-Nonce');
    }

    private function endpoint(string $name): string
    {
        $this->directory ??= Http::timeout(30)->get(config('tokhoom.certificates.directory_url'))->throw()->json();

        return $this->directory[$name] ?? throw new RuntimeException("The ACME directory has no {$name}");
    }

    /**
     * The account key, created and saved on first use.
     */
    private function accountKey(): OpenSSLAsymmetricKey
    {
        if ($this->accountKey) {
            return $this->accountKey;
        }

        $path = config('tokhoom.certificates.account_key');

        if (! is_file($path)) {
            $config = $this->opensslConfig();

            try {
                openssl_pkey_export($this->newKey($config), $pem, null, ['config' => $config]);
            } finally {
                @unlink($config);
            }

            File::ensureDirectoryExists(dirname($path), 0700);
            File::put($path, $pem);
            @chmod($path, 0600);
        }

        return $this->accountKey = openssl_pkey_get_private(File::get($path))
            ?: throw new RuntimeException('The ACME account key could not be read');
    }

    /**
     * @return array{e: string, kty: string, n: string}
     */
    private function jwk(): array
    {
        $rsa = openssl_pkey_get_details($this->accountKey())['rsa'];

        return ['e' => self::base64url($rsa['e']), 'kty' => 'RSA', 'n' => self::base64url($rsa['n'])];
    }

    private function thumbprint(): string
    {
        return self::base64url(hash('sha256', json_encode($this->jwk()), true));
    }

    private function newKey(string $config): OpenSSLAsymmetricKey
    {
        return openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048, 'config' => $config])
            ?: throw new RuntimeException('A private key could not be created');
    }

    /**
     * A throwaway OpenSSL config, so key and CSR creation work without a
     * system openssl.cnf, carrying the domain as the subject alternative name.
     */
    private function opensslConfig(?string $domain = null): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'acme');
        $lines = ['[req]', 'distinguished_name = dn', '[dn]'];

        if ($domain !== null) {
            $lines = [...$lines, '[san]', 'subjectAltName = DNS:'.$domain];
        }

        file_put_contents($path, implode("\n", $lines)."\n");

        return $path;
    }

    private static function pemToDer(string $pem): string
    {
        return (string) base64_decode((string) preg_replace('/-----[^-]+-----|\s+/', '', $pem));
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
