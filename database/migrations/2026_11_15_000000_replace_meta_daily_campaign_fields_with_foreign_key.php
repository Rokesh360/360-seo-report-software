<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $campaignIds = DB::table('meta_ads_daily_stats')
            ->distinct()
            ->pluck('campaign_id');

        foreach ($campaignIds as $campaignId) {
            $stats = DB::table('meta_ads_daily_stats')->where('campaign_id', $campaignId);
            $spend = (float) $stats->sum('spend');
            $impressions = (int) $stats->sum('impressions');
            $clicks = (int) $stats->sum('clicks');

            DB::table('meta_ads_campaigns')->insertOrIgnore([
                'campaign_id' => $campaignId,
                'campaign_name' => (clone $stats)->orderByDesc('report_date')->value('campaign_name'),
                'currency' => $stats->max('currency'),
                'spend' => $spend,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'reach' => (int) $stats->sum('reach'),
                'ctr' => $impressions ? round($clicks / $impressions * 100, 4) : 0,
                'cpc' => $clicks ? round($spend / $clicks, 4) : 0,
                'cpm' => $impressions ? round($spend / $impressions * 1000, 4) : 0,
                'leads' => (int) $stats->sum('leads'),
                'messaging_conversations' => (int) $stats->sum('messaging_conversations'),
                'days_count' => $stats->count(),
                'first_report_date' => $stats->min('report_date'),
                'last_report_date' => $stats->max('report_date'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->foreignId('meta_ads_campaign_id')
                ->nullable()
                ->constrained('meta_ads_campaigns')
                ->cascadeOnDelete()
                ->after('id');
        });

        $campaignIds = DB::table('meta_ads_campaigns')->pluck('id', 'campaign_id');
        foreach ($campaignIds as $campaignId => $id) {
            DB::table('meta_ads_daily_stats')
                ->where('campaign_id', $campaignId)
                ->update(['meta_ads_campaign_id' => $id]);
        }

        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('meta_ads_campaign_id')->nullable(false)->change();
            $table->dropUnique('meta_ads_daily_stats_report_date_campaign_id_unique');
            $table->dropUnique('meta_daily_date_campaign_unique');
            $table->dropColumn(['campaign_id', 'campaign_name']);
            $table->unique(['report_date', 'meta_ads_campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->string('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();
        });

        DB::table('meta_ads_daily_stats')
            ->orderBy('id')
            ->get()
            ->each(function ($stat): void {
                $campaign = DB::table('meta_ads_campaigns')
                    ->where('id', $stat->meta_ads_campaign_id)
                    ->first();

                DB::table('meta_ads_daily_stats')
                    ->where('id', $stat->id)
                    ->update([
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->campaign_name,
                    ]);
            });

        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->string('campaign_id')->nullable(false)->change();
            $table->dropUnique('meta_ads_daily_stats_report_date_meta_ads_campaign_id_unique');
            $table->dropForeign(['meta_ads_campaign_id']);
            $table->dropColumn('meta_ads_campaign_id');
            $table->unique(['report_date', 'campaign_id']);
            $table->unique(['report_date', 'campaign_id'], 'meta_daily_date_campaign_unique');
        });
    }
};
