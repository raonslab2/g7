<?php

namespace Modules\Raonslab\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Redirects legacy product document URLs to their native Page canonical. */
class LegacyPageRedirectController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $slug = (string) $request->route('native_slug');
        $locale = $request->route('locale');
        $prefix = is_string($locale) && $locale !== '' ? '/'.$locale : '';
        $target = $prefix.'/page/'.$slug;

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        return redirect()->to($target, 301);
    }
}
