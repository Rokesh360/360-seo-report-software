<?php

namespace App\Services;

use App\Support\MetaAdsReportHelper;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaAdsReportService
{
    /** Aggregated numbers for the period, plus the previous period for comparison. */
    public function buildPayload(Carbon $from, Carbon $to): array
    {
        $days     = (int) abs($from->diffInDays($to)) + 1;
        $prevTo   = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        $current  = MetaAdsReportHelper::summarise($from, $to);
        $previous = MetaAdsReportHelper::summarise($prevFrom, $prevTo);

        return [
            'currency'          => $current['currency'],
            'period'            => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => $days],
            'days_with_data'    => $current['days_with_data'],
            'totals'            => $current['totals'],
            'previous_period'   => ['from' => $prevFrom->toDateString(), 'to' => $prevTo->toDateString(), 'totals' => $previous['totals']],
            'campaigns'         => $current['campaigns'],
            'daily'             => $current['daily'],
        ];
    }

    public function hasData(array $payload): bool
    {
        return $payload['days_with_data'] > 0;
    }

    /** Send the numbers to Grok and return the report as markdown. */
    public function generate(array $payload): string
    {
        $key = config('services.grok.api_key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('Set GROK_API_KEY in your .env file to enable AI reports.');
        }

        $system = <<<TXT
You are a senior paid-social analyst writing a performance report on Meta (Facebook/Instagram) ads for a marketing agency.
Rules:
- Use ONLY the numbers in the JSON. Never invent figures, benchmarks or causes you cannot see in the data.
- Amounts are in the currency given in the data. Write them with the currency code.
- Compare against previous_period when its totals are non-zero, and give the percentage change.
- "days_with_data" shows how many days in the period have saved data. If it is lower than period.days, say the report covers only those days.
- Judge each campaign by its own goal: lead campaigns by leads and cost per lead, messaging campaigns by conversations and cost per conversation, others by CTR, CPC and CPM.
- Write in clear, plain English with markdown headings and bullet points. Do not use tables.
- Be specific and concise. Name campaigns exactly as given.
TXT;

        $user = "Write the report with these sections:\n"
            . "## Executive summary (3-4 sentences)\n"
            . "## Key metrics (spend, impressions, clicks, CTR, CPC, leads, conversations, with change vs previous period)\n"
            . "## Best performing campaigns\n"
            . "## Campaigns needing attention (high cost, low results, wasted spend)\n"
            . "## Daily trend (spikes, drops)\n"
            . "## Recommendations (5 concrete actions, in priority order)\n\n"
            . "DATA:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE);

        try {
            $res = Http::acceptJson()
                ->withToken(trim($key))
                ->timeout(90)
                ->post('https://api.x.ai/v1/chat/completions', [
                    'model' => config('services.grok.model', 'grok-3-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not connect to Grok. Please try again.', previous: $e);
        }

        if (! $res->successful()) {
            throw new RuntimeException(
                $res->json('error.message') ?? 'The Grok request failed (HTTP ' . $res->status() . ').'
            );
        }

        $text = $res->json('choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Grok returned an empty report. Please try again.');
        }

        return trim($text);
    }
}