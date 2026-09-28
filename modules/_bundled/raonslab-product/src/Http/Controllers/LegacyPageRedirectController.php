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
        $target = '/page/'.$slug;
        $query = $request->query();

        if (is_string($locale) && $locale !== '') {
            unset($query['locale']);
            if ($locale !== (string) $request->route('native_default_locale', 'ko')) {
                $query['locale'] = $locale;
            }
        }

        if ($query !== []) {
            $target .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return redirect()->to($target, 301);
    }
}
