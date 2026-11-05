<?php

namespace App\Support;

use App\Models\Listing;

/**
 * A 0–100 quality score from five checks worth 20 points each: passing checks
 * earn 20, warnings 10 and failures nothing.
 */
class ListingQuality
{
    public const PASS = 'pass';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    /**
     * @var list<array{state: string, text: string}>
     */
    public array $checks = [];

    public function __construct(Listing $listing, int $photoCount)
    {
        $recommended = Listing::RECOMMENDED_PHOTOS;
        $this->checks[] = $photoCount >= $recommended
            ? $this->check(self::PASS, $photoCount.' صور · العدد الموصى به '.$recommended.' أو أكثر')
            : $this->check(self::WARN, $photoCount.' صور فقط · أضف '.($recommended - $photoCount).' صور لرفع المشاهدات');

        $length = mb_strlen((string) $listing->description);
        $this->checks[] = $length >= 200
            ? $this->check(self::PASS, 'الوصف مكتمل ('.Arabic::number($length).' حرفاً)')
            : $this->check(self::WARN, $length ? 'الوصف قصير · أضف تفاصيل التقسيم والتشطيب والخدمات' : 'لا يوجد وصف للعقار');

        $this->checks[] = match (true) {
            ! $listing->ad_license_number => $this->check(self::FAIL, 'لا يوجد رقم ترخيص إعلان'),
            $listing->isAdLicenseExpired() => $this->check(self::FAIL, 'ترخيص الإعلان منتهٍ منذ '.Arabic::date($listing->ad_license_expires_on).' · الإعلان مخفي من الموقع'),
            (bool) $listing->ad_license_expires_on => $this->check(self::PASS, 'ترخيص الإعلان ساري حتى '.Arabic::date($listing->ad_license_expires_on)),
            default => $this->check(self::WARN, 'أضف تاريخ انتهاء ترخيص الإعلان لنذكّرك قبل انتهائه'),
        };

        $days = $listing->daysSincePriceChange();
        $this->checks[] = $days !== null && $days > 45
            ? $this->check(self::WARN, 'لم يُحدَّث السعر منذ '.Arabic::days($days))
            : $this->check(self::PASS, 'السعر محدَّث مؤخراً');

        $this->checks[] = $listing->tour_url
            ? $this->check(self::PASS, 'جولة ثلاثية الأبعاد مضافة')
            : $this->check(self::WARN, 'لا توجد جولة ثلاثية الأبعاد');
    }

    public function score(): int
    {
        return array_sum(array_map(fn (array $check) => match ($check['state']) {
            self::PASS => 20,
            self::WARN => 10,
            default => 0,
        }, $this->checks));
    }

    public function scoreClass(): string
    {
        return match (true) {
            $this->score() >= 80 => 'text-success',
            $this->score() >= 50 => 'text-warning',
            default => 'text-danger',
        };
    }

    /**
     * @return array{state: string, text: string}
     */
    private function check(string $state, string $text): array
    {
        return ['state' => $state, 'text' => $text];
    }
}
