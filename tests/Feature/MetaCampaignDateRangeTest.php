<?php

namespace Tests\Feature;

use App\Services\MetaCatalogService;
use App\Support\Helpers;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaCampaignDateRangeTest extends TestCase
{
    public function test_it_requests_campaign_spend_for_the_selected_dates_without_adding_gst(): void
    {
        config([
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.access_token' => 'test-token',
            'services.meta.ad_account_id' => '123456',
        ]);

        Http::fake([
            'graph.facebook.com/v26.0/act_123456/insights*' => Http::response([
                'data' => [[
                    'campaign_id' => '987654',
                    'campaign_name' => 'WhatsApp Leads',
                    'spend' => '125.50',
                    'account_currency' => 'INR',
                    'actions' => [[
                        'action_type' => 'onsite_conversion.messaging_conversation_started_7d',
                        'value' => '2',
                    ]],
                ]],
            ]),
        ]);

        $result = app(MetaCatalogService::class)
            ->campaignsForDateRange('2026-10-01', '2026-10-31');

        $this->assertTrue($result['ok']);
        $this->assertSame('125.50', $result['data'][0]['spend']);
        $this->assertSame(['whatsapp_meta_ads'], Helpers::campaignTypesFromApi($result['data'][0]));

        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/v26.0/act_123456/insights'
            && $request['level'] === 'campaign'
            && json_decode($request['time_range'], true) === [
                'since' => '2026-10-01',
                'until' => '2026-10-31',
            ]);
    }
}
