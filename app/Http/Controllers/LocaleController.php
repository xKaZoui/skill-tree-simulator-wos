<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $to = $request->query('to');
        $safe = is_string($to) && preg_match('#^/(?![/\\\\])#', $to);

        return redirect()->to($safe ? $to : route('Home'))
            ->withCookie(cookie()->forever('locale', $locale));
    }
}
