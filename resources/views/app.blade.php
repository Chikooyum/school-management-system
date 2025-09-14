<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>BP System</title>

        {{-- Perintah ini akan terhubung ke VITE_DEV_SERVER_URL saat development --}}
        @vite('src/main.js')
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>

