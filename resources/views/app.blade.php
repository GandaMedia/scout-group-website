<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @php($groupProfile = app(\App\Settings\GroupProfileSettings::class))

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="{{ $groupProfile->group_name }}" />
        <link rel="manifest" href="/site.webmanifest" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,200..1000;1,6..12,200..1000&display=swap" rel="stylesheet">

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $groupProfile->group_name }}</title>
            <meta name="description" content="Skills for life, news, activities and joining information from {{ $groupProfile->group_name }}.">
            <link rel="canonical" href="{{ url()->current() }}">
            <meta property="og:site_name" content="{{ $groupProfile->group_name }}">
            <meta property="og:locale" content="en_GB">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $groupProfile->group_name }}">
            <meta property="og:description" content="Skills for life, news, activities and joining information from {{ $groupProfile->group_name }}.">
            <meta property="og:url" content="{{ url()->current() }}">
            <meta property="og:image" content="{{ url('/img/cubs-in-helmets-outdoors-jpg.jpg') }}">
            <meta property="og:image:alt" content="{{ $groupProfile->group_name }} Scouts">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $groupProfile->group_name }}">
            <meta name="twitter:description" content="Skills for life, news, activities and joining information from {{ $groupProfile->group_name }}.">
            <meta name="twitter:image" content="{{ url('/img/cubs-in-helmets-outdoors-jpg.jpg') }}">
            <meta name="twitter:image:alt" content="{{ $groupProfile->group_name }} Scouts">
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
