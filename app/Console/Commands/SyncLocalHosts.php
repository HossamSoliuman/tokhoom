<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Local development: the hosts file has no wildcards, so every workspace
 * subdomain and the short link domain get their own 127.0.0.1 entry inside
 * a managed block. Run it again after adding workspaces.
 */
#[Signature('tokhoom:local-hosts {--path= : Hosts file to write instead of the system one}')]
#[Description('Point workspace site subdomains at this machine in the hosts file')]
class SyncLocalHosts extends Command
{
    private const BLOCK_START = '# tokhoom sites start';

    private const BLOCK_END = '# tokhoom sites end';

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('This command is for local development only.');

            return self::FAILURE;
        }

        $path = $this->option('path') ?: $this->systemHostsPath();
        $current = is_file($path) ? file_get_contents($path) : '';

        $hosts = Workspace::orderBy('slug')->pluck('slug')
            ->map(fn (string $slug) => $slug.'.'.config('tokhoom.site_domain'))
            ->push(config('tokhoom.short_domain'));

        $block = collect([self::BLOCK_START])
            ->merge($hosts->map(fn (string $host) => '127.0.0.1 '.$host))
            ->push(self::BLOCK_END)
            ->implode(PHP_EOL);

        $pattern = '/'.preg_quote(self::BLOCK_START, '/').'.*?'.preg_quote(self::BLOCK_END, '/').'/s';
        $updated = preg_match($pattern, $current)
            ? preg_replace($pattern, $block, $current)
            : rtrim($current).PHP_EOL.PHP_EOL.$block.PHP_EOL;

        if (@file_put_contents($path, $updated) === false) {
            $this->error("Could not write {$path}. Run the terminal as administrator.");

            return self::FAILURE;
        }

        $this->info("Wrote {$hosts->count()} hosts to {$path}.");

        return self::SUCCESS;
    }

    private function systemHostsPath(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'C:\Windows\System32\drivers\etc\hosts' : '/etc/hosts';
    }
}
