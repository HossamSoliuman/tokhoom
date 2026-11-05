<x-layouts.plain title="مساحة العمل موقوفة">
    <x-ui.empty icon="ti-lock" title="مساحة العمل موقوفة" body="أوقفت إدارة المنصة {{ $workspace->name }} مؤقتاً، وموقعه لا يظهر للزوار. بياناتك محفوظة. تواصل مع الدعم لإعادة التفعيل.">
        <div class="flex flex-wrap justify-center gap-2">
            @if (auth()->user()->workspaces()->whereKeyNot($workspace->id)->exists())
                @foreach (auth()->user()->workspaces()->whereKeyNot($workspace->id)->get() as $other)
                    <form method="POST" action="{{ route('workspaces.switch', $other) }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">الانتقال إلى {{ $other->name }}</x-ui.button>
                    </form>
                @endforeach
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="danger-outline">تسجيل الخروج</x-ui.button>
            </form>
        </div>
    </x-ui.empty>
</x-layouts.plain>
