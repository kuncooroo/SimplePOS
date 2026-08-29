<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Receipt — '.config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }

                body {
                    background: #ffffff !important;
                }

                .receipt-shell {
                    box-shadow: none !important;
                    border: none !important;
                    margin: 0 !important;
                    max-width: none !important;
                    padding: 0 !important;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-canvas text-ink print:bg-white">
        <div class="mx-auto flex min-h-screen max-w-lg flex-col p-4 print:max-w-none print:p-0">
            @yield('content')
        </div>
    </body>
</html>
