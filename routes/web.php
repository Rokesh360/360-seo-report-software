<?php

use App\Http\Controllers\AssignServiceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAdsReportController;
use App\Http\Controllers\MetaCatalogController;
use App\Http\Controllers\SeoReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialMediaReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

// Route::get('/clear-cache', function () {
//     Artisan::call('optimize:clear');

//     return 'Laravel optimization and route caches cleared successfully.';
// });

//     Route::get('/storage-link', function () {
//     Artisan::call('storage:link');

//     return 'Storage link created successfully.';
// });

Route::middleware('auth')->group(function () {

    // Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/meta-catalog', ['App\\Http\\Controllers\\MetaCatalogController', 'index'])->name('meta-catalog.index');
    Route::get('/meta-catalog/connect', ['App\\Http\\Controllers\\MetaCatalogController', 'connect'])->name('meta-catalog.connect');
    Route::get('/meta-catalog/callback', ['App\\Http\\Controllers\\MetaCatalogController', 'callback'])->name('meta-catalog.callback');

    Route::get('/google-ads', [GoogleAdsReportController::class, 'index'])->name('google-ads.index');
    Route::get('/google-ads/clients/{client}', [GoogleAdsReportController::class, 'clientServices'])
        ->whereNumber('client')
        ->name('google-ads.client-services');
    Route::get('/google-ads/assign-services/{assignService}', [GoogleAdsReportController::class, 'previousMetrics'])
        ->whereNumber('assignService')
        ->name('google-ads.assign-service');
    Route::get('/google-ads-reports/create', [GoogleAdsReportController::class, 'create'])
        ->name('google-ads-reports.create');
    Route::post('/google-ads-reports', [GoogleAdsReportController::class, 'store'])
        ->name('google-ads-reports.store');
    Route::get('/google-ads-reports/{googleAdsReport}/edit', [GoogleAdsReportController::class, 'edit'])
        ->whereNumber('googleAdsReport')
        ->name('google-ads-reports.edit');
    Route::put('/google-ads-reports/{googleAdsReport}', [GoogleAdsReportController::class, 'update'])
        ->whereNumber('googleAdsReport')
        ->name('google-ads-reports.update');
    Route::delete('/google-ads-reports/{googleAdsReport}', [GoogleAdsReportController::class, 'destroy'])
        ->whereNumber('googleAdsReport')
        ->name('google-ads-reports.destroy');
    Route::get('/google-ads-reports/{googleAdsReport}/pdf', [GoogleAdsReportController::class, 'exportPdf'])
        ->whereNumber('googleAdsReport')
        ->name('google-ads-reports.pdf');

    /* ============================================================
     |  SEO Reports
     ============================================================ */
    Route::resource('seo-reports', SeoReportController::class)->except(['show']);

    Route::prefix('seo-reports')->name('seo-reports.')->group(function () {
        Route::get('trash', [SeoReportController::class, 'trash'])->name('trash');
        Route::post('{id}/restore', [SeoReportController::class, 'restore'])
            ->whereNumber('id')
            ->name('restore');
        Route::get('{id}/pdf', [SeoReportController::class, 'exportPdf'])
            ->whereNumber('id')
            ->name('pdf');
    });

    /* ============================================================
     |  Clients
     ============================================================ */
    Route::resource('clients', ClientController::class)->except(['show']);

    /* ============================================================
     |  Services
     ============================================================ */
    Route::resource('services', ServiceController::class);

    /* ============================================================
     |  Assign Services
     ============================================================ */
    Route::resource('assign-services', AssignServiceController::class);

    Route::prefix('assign-services')->name('assign-services.')->group(function () {
        Route::get('lookup', [AssignServiceController::class, 'lookup'])->name('lookup');
    });

    /* ============================================================
     |  Social Media Reports
     |
     |  Static / custom routes must be declared BEFORE the
     |  resource route so they don't get swallowed by {social_media_report}.
     ============================================================ */

    // Custom wizard-style entry points (Client → Assign Service → Metrics)
    Route::prefix('metrics-reports')->name('metrics-reports.')->group(function () {

        // Step 1 → Client cards
        // (handled by the resource `index` — no extra route needed)

        // Step 2 → Assign services for a client
        Route::get('client/{client}', [SocialMediaReportController::class, 'clientServices'])
            ->whereNumber('client')
            ->name('client-services');

        // Step 3 → Metrics (previous + today) for an assign service
        Route::get('assign-service/{assignService}', [SocialMediaReportController::class, 'serviceMetrics'])
            ->whereNumber('assignService')
            ->name('service-metrics');

        // Bulk PDF export
        Route::get('export-all-pdf', [SocialMediaReportController::class, 'exportAllPdf'])
            ->name('export-all-pdf');

        // Single PDF export
        Route::get('{id}/pdf', [SocialMediaReportController::class, 'exportPdf'])
            ->whereNumber('id')
            ->name('pdf');
    });

    // Standard resource CRUD (index, create, store, edit, update, destroy)
    Route::resource('metrics-reports', SocialMediaReportController::class)
        ->except(['show']);

    Route::get(
        'metrics-reports/assign-services',
        [SocialMediaReportController::class, 'assignServicesList']
    )
        ->name('metrics-reports.assign-services');

    Route::get(
        'metrics-reports/assign-services/{assignService}/previous-metrics',
        [SocialMediaReportController::class, 'previousMetrics']
    )->name('metrics-reports.previous-metrics');


    Route::get(
        'metrics-reports/service/{assignService}/pdf',
        [SocialMediaReportController::class, 'exportServicePdf']
    )
        ->name('metrics-reports.service-pdf');
});
