<?php

namespace App\Services;

use App\Models\AssignService;
use App\Models\GstMaster;
use App\Models\MetaAdsDailyStat;
use App\Support\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class LowBudgetWhatsAppAlertService
{
    public function sendNewAlerts(): int
    {
        $recipients = $this->recipients();

        if ($recipients === []) {
            return 0;
        }

        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.meta.graph_api_version');

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new InvalidArgumentException(
                'Configure WHATSAPP_ACCESS_TOKEN with a valid WhatsApp Business Cloud API token.'
            );
        }

        if (! is_string($phoneNumberId) || ! ctype_digit(trim($phoneNumberId))) {
            throw new InvalidArgumentException('Configure a valid WHATSAPP_PHONE_NUMBER_ID.');
        }

        if (! is_string($version) || ! preg_match('/^v\d+(?:\.\d+)?$/D', $version)) {
            throw new InvalidArgumentException('Configure a valid META_GRAPH_API_VERSION.');
        }

        if (! str_contains($version, '.')) {
            $version .= '.0';
        }

        $gstPercentage = GstMaster::query()
            ->where('status', true)
            ->latest('id')
            ->value('percentage');
        $gstRate = $gstPercentage !== null ? (float) $gstPercentage / 100 : 0;
        $sent = 0;

        $assignments = AssignService::query()
            ->with('client:id,name')
            ->active()
            ->whereNotNull('campaign_ids')
            ->get();

        foreach ($assignments as $assignment) {
            if (empty($assignment->campaign_ids)) {
                continue;
            }

            $campaignIds = collect($assignment->campaign_ids)->map(fn($id) => (string) $id);
            $spent = (float) MetaAdsDailyStat::query()
                ->whereBetween('report_date', [
                    $assignment->from_date->toDateString(),
                    $assignment->to_date->toDateString(),
                ])
                ->whereIn('campaign_id', $campaignIds)
                ->sum('spend');
            $available = (float) $assignment->budget - ($spent * (1 + $gstRate));

            Log::info('Low budget check', [
                'assignment'   => $assignment->id,
                'available'    => $available,
                'limit'        => Helpers::LOW_BUDGET_LIMIT,
                'already_sent' => $assignment->low_budget_alert_sent_at,
            ]);

            if ($available > Helpers::LOW_BUDGET_LIMIT) {
                if ($assignment->low_budget_alert_sent_at !== null) {
                    $assignment->forceFill(['low_budget_alert_sent_at' => null])->save();
                }

                continue;
            }

            if ($assignment->low_budget_alert_sent_at !== null) {
                continue;
            }

            foreach ($recipients as $recipient) {
                $template = [
                    'name' => config('services.whatsapp.low_budget_template', 'low_budget_alert'),
                    'language' => [
                        'code' => config('services.whatsapp.template_language', 'en_US'),
                    ],
                ];

                if (config('services.whatsapp.template_has_params', true)) {
                    $template['components'] = [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $assignment->client?->name ?? 'Unknown client'],
                            ['type' => 'text', 'text' => $assignment->title ?: 'Service #' . $assignment->id],
                            ['type' => 'text', 'text' => '₹' . number_format($available)],
                            ['type' => 'text', 'text' => '₹' . number_format($spent * (1 + $gstRate))],
                            ['type' => 'text', 'text' => '₹' . number_format((float) $assignment->budget)],
                        ],
                    ]];
                }

                $response = Http::acceptJson()
                    ->withToken(trim($accessToken))
                    ->timeout(15)
                    ->post("https://graph.facebook.com/{$version}/" . trim($phoneNumberId) . '/messages', [
                        'messaging_product' => 'whatsapp',
                        'to' => $recipient,
                        'type' => 'template',
                        'template' => $template,
                    ]);

                if (! $response->successful()) {
                    $errorCode = (int) $response->json('error.code');
                    Log::error('WhatsApp low-budget alert failed', [
                        'status'     => $response->status(),
                        'body'       => $response->json(),
                        'recipient'  => $recipient,
                        'assignment' => $assignment->id,
                    ]);

                    $errorMessage = $errorCode === 190
                        ? 'Meta error 190: WHATSAPP_ACCESS_TOKEN is invalid or expired. Replace it with a valid WhatsApp Business Cloud API token.'
                        : ($response->json('error.message') ?? $response->body());

                    throw new RuntimeException(
                        'WhatsApp low-budget alert failed (HTTP ' . $response->status() . '): '
                            . $errorMessage
                    );
                }
            }

            $assignment->forceFill(['low_budget_alert_sent_at' => now()])->save();
            $sent++;
        }

        return $sent;
    }

    /**
     * @return list<string>
     */
    private function recipients(): array
    {
        $configured = config('services.whatsapp.alert_numbers');

        if (! is_string($configured) || trim($configured) === '') {
            return [];
        }

        $recipients = [];

        foreach (explode(',', $configured) as $number) {
            $number = trim($number);
            if ($number === '') {
                continue;
            }

            $number = preg_replace('/\D+/', '', $number);
            if (! is_string($number)) {
                throw new InvalidArgumentException('WHATSAPP_ALERT_NUMBERS contains an invalid phone number.');
            }

            if (strlen($number) === 11 && str_starts_with($number, '0')) {
                $number = substr($number, 1);
            }

            if (strlen($number) === 10) {
                $countryCode = config('services.whatsapp.default_country_code');
                if (! is_string($countryCode) || ! preg_match('/^\d{1,3}$/D', $countryCode)) {
                    throw new InvalidArgumentException(
                        'Configure a valid WHATSAPP_DEFAULT_COUNTRY_CODE for local phone numbers.'
                    );
                }

                $number = $countryCode . $number;
            }

            if (! preg_match('/^\d{8,15}$/D', $number)) {
                throw new InvalidArgumentException(
                    'Each WHATSAPP_ALERT_NUMBERS value must be a local 10-digit number or contain 8 to 15 digits.'
                );
            }

            $recipients[] = $number;
        }

        return array_values(array_unique($recipients));
    }
}
