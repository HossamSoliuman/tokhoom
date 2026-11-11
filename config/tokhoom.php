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
    | Custom domain certificates
    |--------------------------------------------------------------------------
    |
    | Verified custom domains get a Let's Encrypt certificate, saved as
    | {domain}.crt and {domain}.key under the certificates path. The web
    | server loads them by the requested host name, so new ones need no reload.
    |
    */

    'certificates' => [
        'directory_url' => env('TOKHOOM_ACME_DIRECTORY', 'https://acme-v02.api.letsencrypt.org/directory'),
        'email' => env('TOKHOOM_ACME_EMAIL', env('TOKHOOM_SUPPORT_EMAIL')),
        'path' => env('TOKHOOM_CERTIFICATES_PATH', storage_path('app/private/certificates')),
        'account_key' => storage_path('app/private/acme/account.key'),
        'renew_days' => 30,
    ],

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
    | Plans
    |--------------------------------------------------------------------------
    |
    | Monthly prices in riyals (VAT included), the seats each plan includes
    | and the agent plan's listing limit. Seats beyond what a plan includes
    | cost extra_seat_price each a month. Platform admins can change these
    | from the admin settings.
    |
    */

    'plans' => [
        'agent' => ['price' => 99, 'seats' => 1, 'listing_limit' => 30],
        'office' => ['price' => 349, 'seats' => 5],
        'plus' => ['price' => 799, 'seats' => 15],
    ],

    'extra_seat_price' => 49,

    /*
    |--------------------------------------------------------------------------
    | Card collection
    |--------------------------------------------------------------------------
    |
    | Automatic tries on a saved card, one a day, before the owner has to pay
    | by hand and platform admins are told.
    |
    */

    'collection_attempts' => 3,

    /*
    |--------------------------------------------------------------------------
    | Signups
    |--------------------------------------------------------------------------
    |
    | When closed, nobody can create a new workspace. Invited members can
    | still join the workspaces that invited them.
    |
    */

    'signups_open' => true,

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
        'name' => env('TOKHOOM_SELLER_NAME', 'تخوم العقار'),
        'vat_number' => env('TOKHOOM_VAT_NUMBER'),
        'cr_number' => env('TOKHOOM_CR_NUMBER'),
        'address' => env('TOKHOOM_SELLER_ADDRESS'),
    ],

    'vat_rate' => 15,

    /*
    |--------------------------------------------------------------------------
    | Landing page
    |--------------------------------------------------------------------------
    |
    | The sample office site linked from the landing page, and the support
    | contacts shown there. Anything left empty is hidden.
    |
    */

    'demo_site_url' => env('TOKHOOM_DEMO_SITE_URL'),

    'support' => [
        'whatsapp' => env('TOKHOOM_SUPPORT_WHATSAPP'),
        'phone' => env('TOKHOOM_SUPPORT_PHONE'),
        'email' => env('TOKHOOM_SUPPORT_EMAIL'),
        'hours' => env('TOKHOOM_SUPPORT_HOURS', 'الأحد إلى الخميس، 9 ص – 6 م'),
    ],

];
