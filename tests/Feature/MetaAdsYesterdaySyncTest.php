<?php

namespace Tests\Feature;

use App\Models\MetaAdsDailyStat;
use App\Services\MetaCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaAdsYesterdaySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_yesterday_stats_without_changing_today_stats(): void
    {
        config([
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.access_token' => 'test-token',
            'services.meta.ad_account_id' => '123456',
            'services.meta.business_id' => '123456',
        ]);

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        MetaAdsDailyStat::create([
            'report_date' => $yesterday,
            'campaign_id' => '987654',
            'spend' => 10,
        ]);
        MetaAdsDailyStat::create([
            'report_date' => $today,
            'campaign_id' => '987654',
            'spend' => 99,
        ]);

        Http::fake(fn () => Http::response([
            'data' => [[
                'id' => '987654',
                'campaign_id' => '987654',
                'campaign_name' => 'WhatsApp Campaign',
                'spend' => '25.50',
                'account_currency' => 'INR',
                'actions' => [],
            ]],
        ]));

        $count = app(MetaCatalogService::class)->storeYesterday();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('meta_ads_daily_stats', [
            'report_date' => $yesterday,
            'campaign_id' => '987654',
            'spend' => 25.50,
        ]);
        $this->assertDatabaseHas('meta_ads_daily_stats', [
            'report_date' => $today,
            'campaign_id' => '987654',
            'spend' => 99,
        ]);

        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/v26.0/act_123456/insights'
            && json_decode($request['time_range'], true) === [
                'since' => $yesterday,
                'until' => $yesterday,
            ]);
    }
}
