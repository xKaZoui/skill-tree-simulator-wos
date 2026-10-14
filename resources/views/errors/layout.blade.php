{{-- Server error and maintenance pages layout --}}
@php
    // the site fonts if the build is there, the system font otherwise
    $asset = fn (string $path) => rescue(fn () => Vite::asset($path), null, false);
    $plex = fn (string $weight) => $asset("node_modules/@fontsource/ibm-plex-sans-arabic/files/ibm-plex-sans-arabic-arabic-{$weight}-normal.woff2");
    $fonts = array_filter([
        ['Saira Semi Condensed', 300, $asset('resources/fonts/saira-semi-condensed-v13-latin-300.woff2')],
        ['Saira Semi Condensed', 700, $asset('resources/fonts/saira-semi-condensed-v13-latin-700.woff2')],
        ['IBM Plex Sans Arabic', 400, $plex('400')],
        ['IBM Plex Sans Arabic', 700, $plex('700')],
    ], fn ($font) => $font[2] !== null);
@endphp
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title_ar') - @yield('title_en')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-ar-32x32.png') }}">
    @yield('meta')

    <style>
        @foreach ($fonts as [$family, $weight, $url])
            @font-face { font-family: '{{ $family }}'; font-weight: {{ $weight }}; font-display: swap; src: url('{{ $url }}') format('woff2'); }
        @endforeach

        * { box-sizing: border-box; margin: 0; }
        html { font-family: 'Saira Semi Condensed', 'IBM Plex Sans Arabic', system-ui, sans-serif; background: #101828; color: #fff; }
        body { min-height: 100vh; min-height: 100dvh; display: flex; }
        main { position: relative; isolation: isolate; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem; padding: 2rem 1rem; overflow: hidden; text-align: center; }

        /* the error code, faded behind everything */
        .code { position: absolute; inset: 0; z-index: -1; display: flex; align-items: center; justify-content: center; font-size: clamp(9rem, 38vw, 25rem); font-weight: 700; line-height: 1; opacity: .06; user-select: none; }

        .logo img { display: block; width: 9rem; height: auto; }
        /* a link back home */
        a.logo img { transition: scale .2s, filter .2s; }
        a.logo:hover img { scale: 1.08; filter: drop-shadow(0 0 12px rgb(250 204 21 / .45)); }
        @media (prefers-reduced-motion: no-preference) {
            @keyframes float { 50% { transform: translateY(-8px); } }
            a.logo img { animation: float 2.4s ease-in-out infinite; }
            a.logo:hover img { animation-play-state: paused; }
        }

        .row { display: flex; flex-direction: column; align-items: center; gap: 1.25rem; }
        .npc { width: 7.5rem; height: auto; }
        .msg { display: flex; flex-direction: column; gap: .4rem; max-width: 22rem; }
        .title { font-size: 1.75rem; font-weight: 700; line-height: 1.2; letter-spacing: .02em; text-shadow: 0 2px 0 rgb(0 0 0 / .5); }
        .text { font-size: 1rem; font-weight: 300; line-height: 1.5; color: #9ca3af; }
        [lang="ar"] .text { font-weight: 400; }

        /* desktop */
        @media (min-width: 48rem) {
            .logo { position: absolute; top: 3rem; left: 50%; translate: -50% 0; }
            .row { flex-direction: row; gap: 2.5rem; }
            .msg { width: 18rem; text-align: start; align-items: stretch; }
            .npc { width: 9rem; }
        }
    </style>
</head>

<body>
    <main>
        <div class="code" aria-hidden="true">@yield('code')</div>

        @hasSection('home_link')
            <a class="logo" href="{{ url('/') }}" title="الرئيسية - Home"><img src="{{ asset('images/logos/logo-ar.webp') }}" alt="عالم الأسرار"></a>
        @else
            <span class="logo"><img src="{{ asset('images/logos/logo-ar.webp') }}" alt="عالم الأسرار"></span>
        @endif

        <div class="row">
            <div class="msg" lang="ar" dir="rtl">
                <p class="title">@yield('title_ar')</p>
                <p class="text">@yield('text_ar')</p>
            </div>
            <img class="npc" src="{{ asset('images/background/NPC.webp') }}" alt="" width="182" height="202">
            <div class="msg" lang="en" dir="ltr">
                <p class="title">@yield('title_en')</p>
                <p class="text">@yield('text_en')</p>
            </div>
        </div>
    </main>
</body>

</html>
