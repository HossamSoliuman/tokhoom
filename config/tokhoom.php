<?php

$appDomain = env('TOKHOOM_APP_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST));

$siteDomain = env('TOKHOOM_SITE_DOMAIN', $appDomain);

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard domain
    |--------------------------------------------------------------------------
    |
    | The host serving the dashboard. Requests on any other host are treated
    | as a visit to a workspace's public site. www.{app_domain} redirects here.
    |
    */

    'app_domain' => $appDomain,

    /*
    |--------------------------------------------------------------------------
    | Public site domain
    |--------------------------------------------------------------------------
    |
    | Every workspace gets a public site at {slug}.{site_domain} unless it has
    | an active custom domain. Defaults to the dashboard domain, so a wildcard
    | DNS record and server name for *.{app_domain} serve every office.
    |
    */

    'site_domain' => $siteDomain,

    /*
    |--------------------------------------------------------------------------
    | Short links
    |--------------------------------------------------------------------------
    |
    | Listing share links look like {short_domain}/{code}. Point the short
    | domain at this application so the redirect route can resolve them.
    |
    */

    'short_domain' => env('TOKHOOM_SHORT_DOMAIN', 'tkh.sa'),

    /*
    |--------------------------------------------------------------------------
    | Custom domains
    |--------------------------------------------------------------------------
    |
    | Offices point their own domain at this host with a CNAME record.
    |
    */

    'cname_target' => env('TOKHOOM_CNAME_TARGET', 'sites.'.$siteDomain),

    /*
    |--------------------------------------------------------------------------
    | Reserved site addresses
    |--------------------------------------------------------------------------
    */

    'reserved_slugs' => [
        'www', 'app', 'admin', 'api', 'mail', 'sites', 'help', 'support', 'blog',
        'static', 'cdn', 'assets', 'status', 'docs', 'tokhoom', 'dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial length
    |--------------------------------------------------------------------------
    */

    'trial_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Invoicing
    |--------------------------------------------------------------------------
    |
    | Tokhoom's details printed on subscription invoices. With a VAT number
    | set, invoices become simplified tax invoices with the ZATCA QR code and
    | the VAT included in plan prices shown separately.
    |
    */

    'seller' => [
        'name' => env('TOKHOOM_SELLER_NAME', 'تخوم'),
        'vat_number' => env('TOKHOOM_VAT_NUMBER'),
        'cr_number' => env('TOKHOOM_CR_NUMBER'),
        'address' => env('TOKHOOM_SELLER_ADDRESS'),
    ],

    'vat_rate' => 15,

];
