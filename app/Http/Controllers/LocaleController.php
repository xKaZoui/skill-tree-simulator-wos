<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        session()->put('locale', $locale);
        return redirect()->to(session()->previousUrl() ?? route('Home'));
    }
}
