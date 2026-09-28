<?php

use Illuminate\Support\Facades\Route;
use Modules\Raonslab\Product\Http\Controllers\LegacyPageRedirectController;

$legacyPages = [
    'info/services' => 'service',
    'info/cases' => 'cases',
    'info/principles' => 'technology',
    'policy/privacy' => 'privacy',
    'policy/community' => 'terms',
    'policy/ai-workspace' => 'ai-workspace-policy',
    'policy/open-source' => 'open-source',
];

foreach ($legacyPages as $legacyPath => $nativeSlug) {
    $name = str_replace('/', '.', $legacyPath);

    Route::get('/'.$legacyPath, LegacyPageRedirectController::class)
        ->defaults('native_slug', $nativeSlug)
        ->name('raonslab-product.compatibility.'.$name);

    Route::get('/{locale}/'.$legacyPath, LegacyPageRedirectController::class)
        ->where('locale', '[a-z]{2}(?:-[A-Za-z]{2})?')
        ->defaults('native_slug', $nativeSlug)
        ->name('raonslab-product.compatibility.localized.'.$name);
}
