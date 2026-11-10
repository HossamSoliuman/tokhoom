<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · تخوم' : 'تخوم' }}</title>
    <x-favicons />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <div class="flex min-h-dvh flex-col items-center gap-6 px-4 pb-10 pt-7 lg:px-6 lg:py-14">
        <a href="{{ url('/') }}" class="hover:no-underline">
            <x-brand size="lg" latin />
        </a>
        {{ $slot }}
    </div>

    <x-app.toasts />

    @livewireScriptConfig
</body>
</html>
