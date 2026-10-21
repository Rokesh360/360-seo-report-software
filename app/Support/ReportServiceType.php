<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ReportServiceType
{
    public static function googleAdsTypeFromServiceName(string $name): ?string
    {
        $normalized = Str::lower($name);

        if (str_contains($normalized, 'youtube') && str_contains($normalized, 'ads')) {
            return 'google_youtube_ads';
        }

        if (str_contains($normalized, 'call') && str_contains($normalized, 'ads')) {
            return 'google_call_ads';
        }

        return str_contains($normalized, 'google') && str_contains($normalized, 'ads')
            ? 'google_ads'
            : null;
    }

    public static function label(string $type): string
    {
        return match ($type) {
            'whatsapp_meta_ads' => 'WhatsApp Meta Ads',
            'lead_generation_ads' => 'Lead Generation Ads',
            'social_media_engagement' => 'Social Media Engagement',
            'google_youtube_ads' => 'YouTube Ads',
            'google_call_ads' => 'Call Ads',
            'google_ads' => 'Google Ads',
            default => Str::headline($type),
        };
    }

    public static function isGoogleAds(string $type): bool
    {
        return in_array($type, ['google_youtube_ads', 'google_call_ads', 'google_ads'], true);
    }
}
