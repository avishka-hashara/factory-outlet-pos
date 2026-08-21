<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| cPanel front controller
|--------------------------------------------------------------------------
| On this hosting account `public_html` is the (unchangeable) document root,
| but the Laravel application itself lives OUTSIDE the web root in
| ~/Jewellery_POS (cloned by deploy.sh). deploy.sh copies THIS file to
| public_html/index.php so that requests are routed into that app directory
| while only `public_html` is web-accessible.
|
| If you ever rename the repository folder, change $APP_NAME below.
*/

$APP_NAME = 'Jewellery_POS';

// public_html's parent is the account home dir, which also contains the repo.
$basePath = dirname(__DIR__).'/'.$APP_NAME;

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader (vendor/ is installed on cPanel)...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

// Tell Laravel that the public directory is THIS folder (public_html), so that
// public_path(), asset() and the storage/build symlinks resolve correctly.
$app->usePublicPath(__DIR__);

// Handle the request...
$app->handleRequest(Request::capture());
