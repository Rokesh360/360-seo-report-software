<?php

use App\Http\Controllers\AssignServiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SeoReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialMediaReportController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/seo-reports/trash', [SeoReportController::class, 'trash'])->name('seo-reports.trash');
    Route::post('/seo-reports/{id}/restore', [SeoReportController::class, 'restore'])
        ->whereNumber('id')
        ->name('seo-reports.restore');
    Route::get('/seo-reports/{id}/pdf', [SeoReportController::class, 'exportPdf'])
        ->whereNumber('id')
        ->name('seo-reports.pdf');
    Route::resource('seo-reports', SeoReportController::class)->except(['show']);
    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('social-media-reports', SocialMediaReportController::class)->except(['show']);
    Route::get(
        'social-media-reports/export-all-pdf',
        [SocialMediaReportController::class, 'exportAllPdf']
    )->name('social-media-reports.export-all-pdf');

    Route::get(
        'social-media-reports/{id}/pdf',
        [SocialMediaReportController::class, 'exportPdf']
    )->name('social-media-reports.pdf');
    Route::resource('services', ServiceController::class);
    Route::resource('assign-services', AssignServiceController::class);
});
