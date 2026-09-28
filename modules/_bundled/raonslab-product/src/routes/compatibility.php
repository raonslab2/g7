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
$supportedLocales = array_values(array_filter(
    config('app.supported_locales', ['ko', 'en']),
    fn (mixed $locale): bool => is_string($locale) && preg_match('/^[a-z]{2}(?:-[A-Za-z]{2})?$/', $locale) === 1,
));
$localePattern = implode('|', array_map(
    fn (string $locale): string => preg_quote($locale, '/'),
    $supportedLocales,
));
$defaultLocale = (string) config('app.locale', 'ko');

foreach ($legacyPages as $legacyPath => $nativeSlug) {
    $name = str_replace('/', '.', $legacyPath);

    Route::get('/'.$legacyPath, LegacyPageRedirectController::class)
        ->defaults('native_slug', $nativeSlug)
        ->name('raonslab-product.compatibility.'.$name);

    if ($localePattern !== '') {
        Route::get('/{locale}/'.$legacyPath, LegacyPageRedirectController::class)
            ->where('locale', $localePattern)
            ->defaults('native_slug', $nativeSlug)
            ->defaults('native_default_locale', $defaultLocale)
            ->name('raonslab-product.compatibility.localized.'.$name);
    }
}
