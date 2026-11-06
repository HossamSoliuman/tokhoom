<?php

namespace App\Livewire\Site;

use App\Actions\ReceiveInquiry;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Listing;
use App\Support\Phone;
use App\Support\SiteTraffic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The inquiry form on a listing page. The message reaches the listing's agent
 * and files the visitor as a client.
 */
class InquiryForm extends Component
{
    use ShowsToasts;

    private const MAX_PER_HOUR = 5;

    #[Locked]
    public Listing $listing;

    public string $name = '';

    public string $mobile = '';

    public string $message = '';

    public bool $consent = false;

    public function send(ReceiveInquiry $receiveInquiry): void
    {
        $this->mobile = Phone::normalize($this->mobile);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'regex:/^05\d{8}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], [
            'name.required' => 'أدخل الاسم',
            'mobile.required' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'mobile.regex' => 'أدخل رقم جوال صحيح يبدأ بـ 05',
            'consent.accepted' => 'يلزم الموافقة لإرسال الاستفسار',
        ]);

        $key = 'inquiry:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $this->toast('أرسلت عدة استفسارات مؤخراً، حاول مرة أخرى لاحقاً', 'err');

            return;
        }

        RateLimiter::hit($key, 3600);

        $receiveInquiry->handle(
            $this->listing,
            trim($this->name),
            $this->mobile,
            trim($this->message) ?: null,
            SiteTraffic::source($this->listing->workspace),
        );

        $this->reset('name', 'mobile', 'message', 'consent');
        $this->toast('تم إرسال استفسارك. سيتواصل معك الوسيط قريباً.');
    }

    public function render(): View
    {
        return view('livewire.site.inquiry-form');
    }
}
