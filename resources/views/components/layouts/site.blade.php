{{-- Public site page for an office or agent. --}}
@props(['workspace', 'title' => null])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · '.$workspace->name : $workspace->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-canvas">
    <header class="border-b border-line bg-white">
        <div class="mx-auto flex max-w-[960px] items-center gap-3 px-4 py-4 lg:px-6">
            <a href="{{ route('site.home') }}" class="flex items-center gap-3 hover:no-underline">
                @if ($workspace->logoUrl())
                    <img src="{{ $workspace->logoUrl() }}" alt="" class="size-11 rounded-full object-cover">
                @endif
                <span class="text-20 font-bold text-ink">{{ $workspace->name }}</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-[960px] px-4 py-8 lg:px-6">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-[960px] px-4 pb-10 text-13 text-muted lg:px-6">
        @if ($workspace->fal_number)
            <span>رقم رخصة فال: <span dir="ltr">{{ $workspace->fal_number }}</span></span>
        @endif
    </footer>
</body>
</html>
