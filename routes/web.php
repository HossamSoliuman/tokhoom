<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ListingQrController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteEventController;
use App\Http\Controllers\SwitchWorkspaceController;
use App\Http\Middleware\EnsureWorkspace;
use App\Http\Middleware\ResolveSite;
use App\Livewire\Admin;
use App\Livewire\Auth\Login;
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

Route::domain(config('tokhoom.app_domain'))->group(function () {
    Route::get('/s/{code}', ShortLinkController::class)->name('short-link');

    Route::get('/', HomeController::class)->name('home');

    Route::middleware('guest')->group(function () {
        Route::livewire('/login', Login::class)->name('login');
        Route::livewire('/signup', Signup::class)->name('signup');
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

            Route::livewire('/more', More::class)->name('more');
        });

        Route::middleware('can:platform-admin')->prefix('admin')->name('admin.')->group(function () {
            Route::livewire('/approvals', Admin\Approvals::class)->name('approvals');
            Route::livewire('/workspaces', Admin\Workspaces::class)->name('workspaces');
        });
    });
});

Route::middleware(ResolveSite::class)->name('site.')->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/agents/{agent}', [SiteController::class, 'agent'])->whereNumber('agent')->name('agent');
    Route::get('/listings/{code}', [SiteController::class, 'listing'])->name('listing');
    Route::post('/events', SiteEventController::class)->middleware('throttle:60,1')->name('events');
});
