<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Where the app lives. Normally one level up; on the cPanel host the public files sit in
// ~/store.novastaq.com and the app in a private sibling folder, ~/novastaq-store.
$base = is_file(__DIR__.'/../novastaq-store/vendor/autoload.php') ? __DIR__.'/../novastaq-store' : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $base.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

// Uploads and compiled assets are served from this folder, wherever it is.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
