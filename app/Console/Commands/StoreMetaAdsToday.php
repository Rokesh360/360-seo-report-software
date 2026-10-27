<?php

namespace App\Console\Commands;

use App\Services\MetaCatalogService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class StoreMetaAdsToday extends Command
{
    protected $signature = 'meta-ads:store-today';

    protected $description = "Fetch and store today's Meta Ads campaign statistics";

    public function handle(MetaCatalogService $service): int
    {
        try {
            $count = $service->storeToday();
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Saved {$count} campaign(s) for " . now()->toDateString() . '.');

        return self::SUCCESS;
    }
}
