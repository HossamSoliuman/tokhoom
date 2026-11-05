<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard domain
    |--------------------------------------------------------------------------
    |
    | The host serving the dashboard. Requests on any other host are treated
    | as a visit to a workspace's public site.
    |
    */

    'app_domain' => env('TOKHOOM_APP_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    /*
    |--------------------------------------------------------------------------
    | Public site domain
    |--------------------------------------------------------------------------
    |
    | Every workspace gets a public site at {slug}.{site_domain} unless it has
    | an active custom domain.
    |
    */

    'site_domain' => env('TOKHOOM_SITE_DOMAIN', 'tokhoom.sa'),

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

    'cname_target' => env('TOKHOOM_CNAME_TARGET', 'sites.tokhoom.sa'),

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

];
