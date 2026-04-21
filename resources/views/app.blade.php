<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Impersonator') }}</title>

        <script>
            (() => {
                const storageKey = 'theme';
                const root = document.documentElement;
                const storedTheme = localStorage.getItem(storageKey);
                const theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'dark';

                root.classList.toggle('dark', theme === 'dark');
                root.dataset.theme = theme;
            })();
        </script>

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="bg-background font-sans antialiased text-foreground">
        @inertia
    </body>
</html>
