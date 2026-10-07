<?php

use App\Http\Controllers\AssignServiceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SeoReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialMediaReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {

    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

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
    Route::prefix('social-media-reports')->name('social-media-reports.')->group(function () {

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
    Route::resource('social-media-reports', SocialMediaReportController::class)
        ->except(['show']);

    Route::get(
        'social-media-reports/assign-services',
        [SocialMediaReportController::class, 'assignServicesList']
    )
        ->name('social-media-reports.assign-services');
});
