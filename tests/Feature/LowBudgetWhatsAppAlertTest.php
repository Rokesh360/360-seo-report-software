<?php

namespace Tests\Feature;

use App\Models\AssignService;
use App\Models\Client;
use App\Models\GstMaster;
use App\Models\MetaAdsDailyStat;
use App\Services\LowBudgetWhatsAppAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LowBudgetWhatsAppAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ignores_campaign_spend_outside_the_assignment_date_range(): void
    {
        config([
            'services.whatsapp.alert_numbers' => '9876543210',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.default_country_code' => '91',
            'services.meta.graph_api_version' => 'v26.0',
        ]);
        Http::fake();

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
            'report_date' => now()->subDay()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 600,
        ]);

        $sent = app(LowBudgetWhatsAppAlertService::class)->sendNewAlerts();

        $this->assertSame(0, $sent);
        $this->assertNull($assignment->fresh()->low_budget_alert_sent_at);
        Http::assertNothingSent();
    }

    public function test_it_reports_an_invalid_whatsapp_token_explicitly(): void
    {
        config([
            'services.whatsapp.alert_numbers' => '9876543210',
            'services.whatsapp.access_token' => 'invalid-test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.default_country_code' => '91',
            'services.meta.graph_api_version' => 'v26.0',
        ]);
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Authentication Error',
                    'code' => 190,
                ],
            ], 401),
        ]);

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
        MetaAdsDailyStat::create([
            'report_date' => now()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 600,
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
        ]);
        Http::fake();

        $client = Client::create([
            'name' => 'Example Client',
            'website' => 'https://example.test',
        ]);
        $assignment = AssignService::create([
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
        MetaAdsDailyStat::create([
            'report_date' => now()->toDateString(),
            'campaign_id' => 'campaign-1',
            'spend' => 600,
        ]);

        $service = app(LowBudgetWhatsAppAlertService::class);

        $this->assertSame(1, $service->sendNewAlerts());
        $this->assertNotNull($assignment->fresh()->low_budget_alert_sent_at);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['to'] === '919876543210');
        Http::assertSent(fn ($request) => $request['to'] === '919123456789');

        $this->assertSame(0, $service->sendNewAlerts());
        Http::assertSentCount(2);

        MetaAdsDailyStat::query()->update(['spend' => 400]);
        $this->assertSame(0, $service->sendNewAlerts());
        $this->assertNull($assignment->fresh()->low_budget_alert_sent_at);

        MetaAdsDailyStat::query()->update(['spend' => 600]);
        $this->assertSame(1, $service->sendNewAlerts());
        Http::assertSentCount(4);
    }
}
