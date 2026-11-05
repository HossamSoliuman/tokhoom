{{-- Standalone page without the dashboard shell, for full-page notices. --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · تخوم' : 'تخوم' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh">
    <div class="flex min-h-dvh flex-col items-center gap-6 px-4 pb-10 pt-7 lg:py-14">
        <span class="flex items-center gap-2.5">
            <x-logo class="size-10" />
            <span class="text-28 font-bold text-primary">تخوم</span>
        </span>
        <div class="w-full max-w-[520px]">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
