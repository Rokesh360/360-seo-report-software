<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetaCampaignSyncController;

Route::post(
    '/meta/sync-campaigns',
    [MetaCampaignSyncController::class, 'syncCampaigns']
);

Route::post(
    '/meta-catalog/send-to-360-leads',
    [MetaCampaignSyncController::class, 'sendSelectedTo360Leads']
)->name('meta-catalog.send-to-360-leads');
