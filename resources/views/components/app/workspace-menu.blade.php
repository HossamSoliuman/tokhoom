@props(['shell'])

<div x-cloak x-show="$store.shell.switcher" x-on:click="$store.shell.switcher = false" class="fixed inset-0 z-[25]"></div>
<div x-cloak x-show="$store.shell.switcher" x-transition.opacity.duration.100ms
     class="fixed right-2 top-[60px] z-[26] flex w-[300px] flex-col gap-0.5 rounded-card border border-line bg-white p-2 shadow-pop lg:right-4 lg:top-[132px] lg:w-[280px]">
    @foreach ($shell['workspaces'] as $workspace)
        @php($current = $workspace->id === $shell['workspace']->id)
        <form method="POST" action="{{ route('workspaces.switch', $workspace) }}">
            @csrf
            <button type="submit" @class([
                'flex h-14 w-full items-center gap-2.5 rounded-field px-2.5 text-start text-ink',
                'bg-primary-tint' => $current,
                'hover:bg-canvas' => ! $current,
            ])>
                <x-ui.avatar :initial="$workspace->initial()" :size="36" tone="solid" square />
                <span class="flex flex-1 flex-col">
                    <span class="text-14 font-semibold">{{ $workspace->name }}</span>
                    <span class="text-12 text-muted">{{ \App\Enums\MemberRole::from($workspace->pivot->role)->label($shell['user']->gender, $workspace->kind) }}</span>
                </span>
                @if ($current)
                    <i class="ti ti-check text-18 text-primary"></i>
                @endif
            </button>
        </form>
    @endforeach
    <div class="my-1 h-px bg-divider"></div>
    <a href="{{ route('workspaces.create') }}" class="flex h-11 items-center gap-2.5 rounded-field px-3 text-14 font-semibold text-primary hover:bg-canvas hover:no-underline">
        <i class="ti ti-plus text-18"></i>
        إنشاء مساحة عمل
    </a>
</div>
