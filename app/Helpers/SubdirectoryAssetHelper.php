<?php

use Illuminate\Support\Facades\App;

if (!function_exists('subdirAsset')) {
    function subdirAsset($path)
    {
        return asset((App::environment('production') ? env('APP_DIR') : '') . "/" . $path);
    }
}

if (!function_exists('subdirMix')) {
    function subdirMix($path)
    {
        return asset((App::environment('production') ? env('APP_DIR') : '') . "/" . $path);
    }
}
