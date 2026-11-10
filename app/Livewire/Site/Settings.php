<?php

namespace App\Livewire\Site;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Livewire\Concerns\ShowsToasts;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('إعدادات الموقع')]
class Settings extends Component
{
    use InteractsWithWorkspace, ShowsToasts, WithFileUploads;

    public const SWATCHES = ['#1E3A4C', '#145A55', '#7A2E3A', '#4F5D2F', '#2E2A25', '#A9704F'];

    public string $brandColor = '#1E3A4C';

    public string $about = '';

    public string $whatsapp = '';

    public string $phone = '';

    public string $xHandle = '';

    public string $instagram = '';

    public string $snapchat = '';

    public string $tiktok = '';

    public ?TemporaryUploadedFile $logo = null;

    public ?TemporaryUploadedFile $cover = null;

    public function boot(): void
    {
        Gate::authorize('manage-site');
    }

    public function mount(): void
    {
        $workspace = $this->workspace();

        $this->brandColor = strtoupper($workspace->brand_color);
        $this->about = (string) $workspace->about;
        $this->whatsapp = (string) $workspace->whatsapp;
        $this->phone = (string) $workspace->phone;
        $this->xHandle = (string) $workspace->x_handle;
        $this->instagram = (string) $workspace->instagram;
        $this->snapchat = (string) $workspace->snapchat;
        $this->tiktok = (string) $workspace->tiktok;
    }

    public function updatedLogo(): void
    {
        $this->validateOnly('logo');
    }

    public function updatedCover(): void
    {
        $this->validateOnly('cover');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'brandColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'about' => ['nullable', 'string', 'max:600'],
            'whatsapp' => ['nullable', 'regex:/^05\d{8}$/'],
            'phone' => ['nullable', 'regex:/^0\d{8,9}$/'],
            'xHandle' => ['nullable', 'string', 'max:60'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'snapchat' => ['nullable', 'string', 'max:60'],
            'tiktok' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function save(): void
    {
        $this->whatsapp = $this->whatsapp !== '' ? Phone::normalize($this->whatsapp) : '';
        $this->phone = (string) preg_replace('/\D/', '', $this->phone);

        $this->validate(messages: [
            'brandColor.regex' => 'اكتب اللون بصيغة ‎#RRGGBB.',
            'whatsapp.regex' => 'أدخل رقم واتساب سعودي يبدأ بـ 05.',
            'phone.regex' => 'أدخل رقم هاتف صحيح.',
        ], attributes: ['whatsapp' => 'واتساب', 'phone' => 'الهاتف']);

        $workspace = $this->workspace();

        $workspace->fill([
            'brand_color' => strtoupper($this->brandColor),
            'about' => trim($this->about) ?: null,
            'whatsapp' => $this->whatsapp ?: null,
            'phone' => $this->phone ?: null,
            'x_handle' => trim($this->xHandle) ?: null,
            'instagram' => trim($this->instagram) ?: null,
            'snapchat' => trim($this->snapchat) ?: null,
            'tiktok' => trim($this->tiktok) ?: null,
        ]);

        foreach (['logo' => 'logo_path', 'cover' => 'cover_path'] as $property => $column) {
            if ($this->{$property}) {
                if ($workspace->{$column}) {
                    Storage::disk('public')->delete($workspace->{$column});
                }

                $workspace->{$column} = $this->{$property}->store('workspaces/'.$workspace->id, 'public');
                $this->{$property} = null;
            }
        }

        $workspace->save();

        $this->toast('تم حفظ إعدادات الموقع');
    }

    public function render(): View
    {
        $workspace = $this->workspace();

        return view('livewire.site.settings', [
            'workspace' => $workspace,
            'logoUrl' => $this->logo?->temporaryUrl() ?? $workspace->logoUrl(),
            'coverUrl' => $this->cover?->temporaryUrl() ?? $workspace->coverUrl(),
            'previewListings' => $workspace->listings()
                ->live()
                ->with('coverPhoto')
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
