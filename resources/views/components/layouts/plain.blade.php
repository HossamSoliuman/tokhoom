{{-- Standalone page without the dashboard shell, for full-page notices. --}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · تخوم العقار' : 'تخوم العقار' }}</title>
    <x-favicons />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh">
    <div class="flex min-h-dvh flex-col items-center gap-6 px-4 pb-10 pt-7 lg:py-14">
        <x-brand size="lg" latin />
        <div class="w-full max-w-[520px]">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
