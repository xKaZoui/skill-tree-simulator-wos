<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="{{ url()->current() }}">

    @vite(['resources/css/app.css'])
    @vite(['resources/js/app.js'])

    {{-- Favicons (Arabic or English version) --}}
    @php($iconLocale = app()->getLocale() === 'ar' ? 'ar' : 'en')
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset("images/favicon/favicon-{$iconLocale}-16x16.png") }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset("images/favicon/favicon-{$iconLocale}-32x32.png") }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset("images/favicon/favicon-{$iconLocale}-48x48.png") }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset("images/favicon/apple-touch-icon-{$iconLocale}.png") }}">

    <title>{{ isset($title) ? $title . ' - ' : '' }} {{ __('app.name') }}</title>

    <script>
        /*to prevent Firefox FOUC*/
        let FF_FOUC_FIX;
    </script>

    <meta property="og:title" content="{{ isset($title) ? $title . ' - ' : '' }} {{ __('app.name') }}">
    <meta property="og:description" content="{{ $pageDescription ?? __('app.description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- Link previews --}}
    @php($pageImage ??= asset('images/pages/preview-home.webp'))
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ isset($title) ? $title . ' - ' : '' }} {{ __('app.name') }}">
    <meta name="twitter:description" content="{{ $pageDescription ?? __('app.description') }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

</head>

<body class="bg-gray-900">
    <x-layouts.header />
    <main>
        {{ $slot }}
    </main>
    <x-layouts.footer />

</body>

</html>
