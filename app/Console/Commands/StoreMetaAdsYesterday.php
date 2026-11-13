<?php

namespace App\Console\Commands;

use App\Services\LowBudgetWhatsAppAlertService;
use App\Services\MetaCatalogService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use InvalidArgumentException;
use RuntimeException;

class StoreMetaAdsYesterday extends Command
{
    protected $signature = 'meta-ads:store-yesterday';

    protected $description = "Fetch and store yesterday's Meta Ads campaign statistics";

    public function handle(
        MetaCatalogService $service,
        LowBudgetWhatsAppAlertService $lowBudgetWhatsAppAlertService
    ): int {
        try {
            $count = $service->storeYesterday();
            $alertsSent = $lowBudgetWhatsAppAlertService->sendNewAlerts();
        } catch (ConnectionException | InvalidArgumentException | RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Saved {$count} campaign(s) for " . now()->subDay()->toDateString() . '.');

        if (trim((string) config('services.whatsapp.alert_numbers')) === '') {
            $this->warn('No WhatsApp low-budget alerts sent; configure WHATSAPP_ALERT_NUMBERS.');
        } else {
            $this->info("Sent {$alertsSent} low-budget alert(s) to configured WhatsApp recipients.");
        }

        return self::SUCCESS;
    }
}
