<?php

namespace Tests\Feature;

use App\Models\AssignService;
use App\Models\Client;
use App\Models\GstMaster;
use App\Models\MetaAdsDailyStat;
use App\Services\LowBudgetWhatsAppAlertService;
use App\Support\Helpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LowBudgetWhatsAppAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_available_budget_uses_meta_spend_including_gst(): void
    {
        $client = Client::create([
            'name' => 'Example Client',
            'website' => 'https://example.test',
        ]);
        $assignment = AssignService::create([
            'client_id' => $client->id,
            'service_id' => [],
            'from_date' => now()->toDateString(),
            'to_date' => now()->addMonth()->toDateString(),
            'budget' => 7000,
            'title' => 'Campaign Service',
            'campaign_ids' => ['campaign-1'],
        ]);
        GstMaster::create([
            'name' => 'GST',
            'percentage' => 10,
            'status' => true,
        ]);
        MetaAdsDailyStat::create([
            'report_date' => now()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 1,
        ]);

        $rows = Helpers::lowBudgetRows(
            AssignService::with('client')->get(),
            null,
            [$assignment->id => 5751],
            0.1
        );

        $this->assertSame(673.9, $rows->sole()['available']);
        $this->assertSame(5751.0, $rows->sole()['spent_excl_gst']);
        $this->assertSame(6326.1, $rows->sole()['spent']);
    }

    public function test_it_uses_live_meta_spend_instead_of_daily_stats(): void
    {
        config([
            'services.whatsapp.alert_numbers' => '9876543210',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.default_country_code' => '91',
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.access_token' => 'meta-test-token',
            'services.meta.ad_account_id' => '123456',
        ]);
        Http::fake([
            'graph.facebook.com/v26.0/act_123456/insights*' => Http::response([
                'data' => [[
                    'campaign_id' => 'campaign-1',
                    'spend' => '600',
                ]],
            ]),
            'graph.facebook.com/v26.0/123456789/messages' => Http::response([]),
        ]);

        $client = Client::create([
            'name' => 'Example Client',
            'website' => 'https://example.test',
        ]);
        $assignment = AssignService::create([
            'client_id' => $client->id,
            'service_id' => [],
            'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(),
            'budget' => 2150,
            'title' => 'Campaign Service',
            'campaign_ids' => ['campaign-1'],
        ]);
        GstMaster::create([
            'name' => 'GST',
            'percentage' => 10,
            'status' => true,
        ]);
        MetaAdsDailyStat::create([
            'report_date' => now()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 1,
        ]);

        $sent = app(LowBudgetWhatsAppAlertService::class)->sendNewAlerts();

        $this->assertSame(1, $sent);
        $this->assertNotNull($assignment->fresh()->low_budget_alert_sent_at);
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/v26.0/act_123456/insights'
            && $request['fields'] === 'campaign_id,spend'
            && json_decode($request['time_range'], true) === [
                'since' => now()->toDateString(),
                'until' => now()->toDateString(),
            ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/messages')
            && $request['template']['components'][0]['parameters'][2]['text'] === '₹1,490.00'
            && $request['template']['components'][0]['parameters'][3]['text'] === '₹660.00');
    }

    public function test_it_reports_an_invalid_whatsapp_token_explicitly(): void
    {
        config([
            'services.whatsapp.alert_numbers' => '9876543210',
            'services.whatsapp.access_token' => 'invalid-test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.default_country_code' => '91',
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.access_token' => 'meta-test-token',
            'services.meta.ad_account_id' => '123456',
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/insights')) {
                return Http::response([
                    'data' => [[
                        'campaign_id' => 'campaign-1',
                        'spend' => '700',
                    ]],
                ]);
            }

            return Http::response([
                'error' => [
                    'message' => 'Authentication Error',
                    'code' => 190,
                ],
            ], 401);
        });

        $client = Client::create([
            'name' => 'Example Client',
            'website' => 'https://example.test',
        ]);
        AssignService::create([
            'client_id' => $client->id,
            'service_id' => [],
            'from_date' => now()->toDateString(),
            'to_date' => now()->addMonth()->toDateString(),
            'budget' => 2150,
            'title' => 'Campaign Service',
            'campaign_ids' => ['campaign-1'],
        ]);
        GstMaster::create([
            'name' => 'GST',
            'percentage' => 10,
            'status' => true,
        ]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WHATSAPP_ACCESS_TOKEN is invalid or expired');

        app(LowBudgetWhatsAppAlertService::class)->sendNewAlerts();
    }

    public function test_it_sends_to_every_configured_number_only_once_until_budget_recovers(): void
    {
        config([
            'services.whatsapp.alert_numbers' => '9876543210,9123456789',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.default_country_code' => '91',
            'services.whatsapp.low_budget_template' => 'low_budget_alert',
            'services.whatsapp.template_language' => 'en_US',
            'services.meta.graph_api_version' => 'v26.0',
            'services.meta.access_token' => 'meta-test-token',
            'services.meta.ad_account_id' => '123456',
        ]);
        $metaSpend = '5751';
        Http::fake(function ($request) use (&$metaSpend) {
            if (str_contains($request->url(), '/insights')) {
                return Http::response([
                    'data' => [[
                        'campaign_id' => 'campaign-1',
                        'spend' => $metaSpend,
                    ]],
                ]);
            }

            return Http::response([]);
        });

        $client = Client::create([
            'name' => 'Example Client',
            'website' => 'https://example.test',
        ]);
        $assignment = AssignService::create([
            'client_id' => $client->id,
            'service_id' => [],
            'from_date' => now()->toDateString(),
            'to_date' => now()->addMonth()->toDateString(),
            'budget' => 7000,
            'title' => 'Campaign Service',
            'campaign_ids' => ['campaign-1'],
        ]);
        GstMaster::create([
            'name' => 'GST',
            'percentage' => 10,
            'status' => true,
        ]);
        MetaAdsDailyStat::create([
            'report_date' => now()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 1,
        ]);

        $service = app(LowBudgetWhatsAppAlertService::class);

        $this->assertSame(1, $service->sendNewAlerts());
        $this->assertNotNull($assignment->fresh()->low_budget_alert_sent_at);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['to'] === '919876543210');
        Http::assertSent(fn ($request) => $request['to'] === '919123456789');
        Http::assertSent(fn ($request) => $request['to'] === '919876543210'
            && $request['template']['components'][0]['parameters'][2]['text'] === '₹673.90'
            && $request['template']['components'][0]['parameters'][3]['text'] === '₹6,326.10');

        $this->assertSame(0, $service->sendNewAlerts());
        Http::assertSentCount(4);

        $metaSpend = '4000';
        $this->assertSame(0, $service->sendNewAlerts());
        $this->assertNull($assignment->fresh()->low_budget_alert_sent_at);
        Http::assertSentCount(5);

        $metaSpend = '5751';
        $this->assertSame(1, $service->sendNewAlerts());
        Http::assertSentCount(8);
    }
}
