<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// -----------------------------------------------------------------------------
// Subfolder deployment fix
// -----------------------------------------------------------------------------
// The app is served from https://degonl.360degweb.in/360-seo-monthly-reports
// (Laravel's public/ is rewritten internally; the browser never sees /public).
// We must tell Symfony the correct base URL and base path so that:
//   - route() / url() / redirect() include the /360-seo-monthly-reports prefix
//   - asset() serves files from /360-seo-monthly-reports/...
//   - redirects (e.g. login → dashboard) stay inside the subfolder
// -----------------------------------------------------------------------------
$_SERVER['SCRIPT_NAME']     = '/360-seo-monthly-reports/index.php';
$_SERVER['PHP_SELF']        = '/360-seo-monthly-reports/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
// Leave REQUEST_URI as the real browser path; Symfony derives basePath from
// SCRIPT_NAME vs REQUEST_URI and gets /360-seo-monthly-reports.
// -----------------------------------------------------------------------------

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());