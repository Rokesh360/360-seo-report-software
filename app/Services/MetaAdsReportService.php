<?php

namespace App\Services;

use App\Models\MetaAdsDailyStat;
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

        $current  = $this->summarise($from, $to);
        $previous = $this->summarise($prevFrom, $prevTo);

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

    private function summarise(Carbon $from, Carbon $to): array
    {
        $rows = MetaAdsDailyStat::whereBetween('report_date', [$from->toDateString(), $to->toDateString()])->get();

        $campaigns = $rows->groupBy('campaign_id')->map(function ($g) {
            $t = $this->totals($g);
            // Latest saved name wins
            $t['name'] = $g->sortByDesc('report_date')->first()->campaign_name;
            $t['type'] = $this->guessType($t);
            return $t;
        })->sortByDesc('spend')->values()->all();

        $daily = $rows->groupBy(fn ($r) => $r->report_date->toDateString())
            ->map(fn ($g, $date) => ['date' => $date] + $this->totals($g))
            ->sortKeys()->values()->all();

        return [
            'currency'       => $rows->first()->currency ?? 'INR',
            'days_with_data' => count($daily),
            'totals'         => $this->totals($rows),
            'campaigns'      => $campaigns,
            'daily'          => $daily,
        ];
    }

    private function totals($rows): array
    {
        $spend = round((float) $rows->sum('spend'), 2);
        $impr  = (int) $rows->sum('impressions');
        $click = (int) $rows->sum('clicks');
        $leads = (int) $rows->sum('leads');
        $msgs  = (int) $rows->sum('messaging_conversations');

        return [
            'spend'                    => $spend,
            'impressions'              => $impr,
            'clicks'                   => $click,
            'leads'                    => $leads,
            'messaging_conversations'  => $msgs,
            'ctr_percent'              => $impr ? round($click / $impr * 100, 2) : null,
            'cpc'                      => $click ? round($spend / $click, 2) : null,
            'cpm'                      => $impr ? round($spend / $impr * 1000, 2) : null,
            'cost_per_lead'            => $leads ? round($spend / $leads, 2) : null,
            'cost_per_conversation'    => $msgs ? round($spend / $msgs, 2) : null,
            // Reach is deliberately not summed: it counts unique people, so adding days overstates it.
        ];
    }

    private function guessType(array $t): string
    {
        if ($t['leads'] > 0) return 'lead generation';
        if ($t['messaging_conversations'] > 0) return 'messaging / WhatsApp';
        return 'awareness / engagement';
    }

    /** Send the numbers to Gemini and return the report as markdown. */
    public function generate(array $payload): string
    {
        $key = config('services.gemini.api_key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('Set GEMINI_API_KEY in your .env file to enable AI reports.');
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
                ->withHeaders(['x-goog-api-key' => trim($key)])
                ->timeout(90)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/'
                        . rawurlencode(config('services.gemini.model', 'gemini-2.5-flash'))
                        . ':generateContent',
                    [
                        'systemInstruction' => [
                            'parts' => [['text' => $system]],
                        ],
                        'contents' => [[
                            'role'  => 'user',
                            'parts' => [['text' => $user]],
                        ]],
                        'generationConfig' => [
                            'temperature'     => 0.3,
                            'maxOutputTokens' => 2000,
                        ],
                    ]
                );
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not connect to Google Gemini. Please try again.', previous: $e);
        }

        if (! $res->successful()) {
            throw new RuntimeException(
                $res->json('error.message') ?? 'The Gemini request failed (HTTP ' . $res->status() . ').'
            );
        }

        $text = collect($res->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter(fn ($part) => is_string($part))
            ->implode("\n");

        if (trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty report. Please try again.');
        }

        return trim($text);
    }
}