<?php

use App\Http\Controllers\Admin as AdminHttp;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ListingQrController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteEventController;
use App\Http\Controllers\SiteQrController;
use App\Http\Controllers\SwitchWorkspaceController;
use App\Http\Controllers\TapReturnController;
use App\Http\Controllers\TapWebhookController;
use App\Http\Controllers\WwwRedirectController;
use App\Http\Middleware\EnsureWorkspace;
use App\Http\Middleware\ResolveSite;
use App\Livewire\Account;
use App\Livewire\Admin;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\Signup;
use App\Livewire\Billing;
use App\Livewire\Clients;
use App\Livewire\Deals;
use App\Livewire\Invitations;
use App\Livewire\Listings;
use App\Livewire\More;
use App\Livewire\Reports;
use App\Livewire\Site;
use App\Livewire\Team;
use App\Livewire\Today;
use Illuminate\Support\Facades\Route;

Route::domain(config('tokhoom.short_domain'))->group(function () {
    Route::get('/{code}', ShortLinkController::class)->name('short-link.domain');
});

Route::domain('www.'.config('tokhoom.app_domain'))->group(function () {
    Route::get('/{path?}', WwwRedirectController::class)->where('path', '.*')->name('www-redirect');
});

Route::domain(config('tokhoom.app_domain'))->group(function () {
    Route::get('/s/{code}', ShortLinkController::class)->name('short-link');

    Route::get('/', HomeController::class)->name('home');
    Route::post('/webhooks/tap', TapWebhookController::class)->name('webhooks.tap');

    Route::middleware('guest')->group(function () {
        Route::livewire('/login', Login::class)->name('login');
        Route::livewire('/signup', Signup::class)->name('signup');
        Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
        Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    });

    Route::livewire('/invitations/{token}', Invitations\Accept::class)->name('invitations.accept');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', LogoutController::class)->name('logout');
        Route::livewire('/workspaces/create', Signup::class)->name('workspaces.create');
        Route::post('/workspaces/{workspace}/switch', SwitchWorkspaceController::class)->name('workspaces.switch');

        Route::middleware(EnsureWorkspace::class)->group(function () {
            Route::livewire('/today', Today::class)->name('today');

            Route::livewire('/listings', Listings\Index::class)->name('listings.index');
            Route::livewire('/listings/create', Listings\Editor::class)->name('listings.create');
            Route::livewire('/listings/{listing}', Listings\Show::class)->name('listings.show');
            Route::livewire('/listings/{listing}/edit', Listings\Editor::class)->name('listings.edit');
            Route::get('/listings/{listing}/qr.{format}', ListingQrController::class)
                ->whereIn('format', ['svg', 'png'])
                ->name('listings.qr');

            Route::livewire('/clients', Clients\Index::class)->name('clients.index');
            Route::livewire('/clients/{client}', Clients\Show::class)->name('clients.show');

            Route::livewire('/deals', Deals\Board::class)->name('deals.index');

            Route::livewire('/team', Team\Index::class)->name('team')->can('manage-team');

            Route::livewire('/reports', Reports\Index::class)->name('reports')->can('view-reports');
            Route::get('/reports/export', ReportExportController::class)->name('reports.export')->can('view-reports');

            Route::livewire('/site', Site\Settings::class)->name('site')->can('manage-site');

            Route::livewire('/billing', Billing\Index::class)->name('billing')->can('manage-billing');
            Route::get('/billing/invoices/{invoice}', InvoiceController::class)->name('billing.invoice')->can('manage-billing');
            Route::get('/billing/tap/return', TapReturnController::class)->name('billing.tap-return')->can('manage-billing');

            Route::livewire('/more', More::class)->name('more');
            Route::livewire('/account', Account::class)->name('account');
            Route::get('/share/qr.{format}', SiteQrController::class)->whereIn('format', ['svg', 'png'])->name('share.qr');
        });

        Route::middleware('can:platform-admin')->prefix('admin')->name('admin.')->group(function () {
            Route::livewire('/', Admin\Overview::class)->name('overview');
            Route::livewire('/approvals', Admin\Approvals::class)->name('approvals');
            Route::livewire('/workspaces', Admin\Workspaces\Index::class)->name('workspaces');
            Route::get('/workspaces/export', AdminHttp\WorkspaceExportController::class)->name('workspaces.export');
            Route::livewire('/workspaces/{workspace}', Admin\Workspaces\Show::class)->name('workspaces.show');
            Route::livewire('/users', Admin\Users\Index::class)->name('users');
            Route::livewire('/users/{user}', Admin\Users\Show::class)->name('users.show');
            Route::livewire('/invoices', Admin\Invoices::class)->name('invoices');
            Route::get('/invoices/export', AdminHttp\InvoiceExportController::class)->name('invoices.export');
            Route::get('/invoices/{invoice}', AdminHttp\InvoiceController::class)->name('invoices.show');
            Route::livewire('/listings', Admin\Listings::class)->name('listings');
            Route::livewire('/licenses', Admin\Licenses::class)->name('licenses');
            Route::livewire('/more', Admin\More::class)->name('more');
            Route::livewire('/account', Account::class)->name('account');
        });
    });
});

Route::middleware(ResolveSite::class)->name('site.')->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/agents/{agent}', [SiteController::class, 'agent'])->whereNumber('agent')->name('agent');
    Route::get('/listings/{code}', [SiteController::class, 'listing'])->name('listing');
    Route::post('/events', SiteEventController::class)->middleware('throttle:60,1')->name('events');
});
