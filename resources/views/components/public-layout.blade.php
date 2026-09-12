<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('cerimonial.company_name') }} — Área do cliente</title>

        @include('layouts._favicons')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-9 w-auto" />
                        <span class="font-semibold text-gray-800">{{ config('cerimonial.company_name') }}</span>
                    </div>
                    <span class="text-xs text-gray-500 uppercase tracking-wide">Área do cliente</span>
                </div>
            </nav>

            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                @if (session('success'))
                    <div class="max-w-5xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="max-w-5xl mx-auto mt-4 px-4 sm:px-6 lg:px-8">
                        <div class="rounded-md bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
