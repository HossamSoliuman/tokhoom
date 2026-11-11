<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Custom domain certificates on disk, as {domain}.crt and {domain}.key in
 * the certificates path, where the web server picks them up by host name.
 */
class CertificateStore
{
    public static function path(string $domain, string $extension): string
    {
        return rtrim((string) config('tokhoom.certificates.path'), '/\\').DIRECTORY_SEPARATOR.$domain.'.'.$extension;
    }

    public static function put(string $domain, string $certificate, string $privateKey): void
    {
        $directory = dirname(self::path($domain, 'crt'));

        if (! is_dir($directory)) {
            File::ensureDirectoryExists($directory, 0750);
            self::matchParentOwner($directory);
        }

        self::write(self::path($domain, 'key'), $privateKey, 0640);
        self::write(self::path($domain, 'crt'), $certificate, 0644);
    }

    public static function delete(?string $domain): void
    {
        if (blank($domain)) {
            return;
        }

        File::delete([self::path($domain, 'crt'), self::path($domain, 'key')]);
    }

    /**
     * Swap the file in with a rename, so the web server never reads half of it.
     */
    private static function write(string $path, string $contents, int $mode): void
    {
        $temporary = $path.'.tmp';

        File::put($temporary, $contents);
        @chmod($temporary, $mode);
        self::matchParentOwner($temporary);
        File::move($temporary, $path);
    }

    /**
     * When the scheduler runs as root, hand files to the account that owns
     * storage so the web server can read them.
     */
    private static function matchParentOwner(string $path): void
    {
        if (! function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            return;
        }

        $parent = dirname($path);
        @chown($path, (int) fileowner($parent));
        @chgrp($path, (int) filegroup($parent));
    }
}
