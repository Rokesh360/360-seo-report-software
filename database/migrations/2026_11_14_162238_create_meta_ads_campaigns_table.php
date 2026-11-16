<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meta_ads_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id')->unique();
            $table->string('campaign_name')->nullable();
            $table->string('currency', 10)->nullable();

            $table->decimal('spend', 14, 2)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('ctr', 10, 4)->default(0);   // recalculated, never summed
            $table->decimal('cpc', 12, 4)->default(0);   // recalculated
            $table->decimal('cpm', 12, 4)->default(0);   // recalculated
            $table->unsignedInteger('leads')->default(0);
            $table->unsignedInteger('messaging_conversations')->default(0);

            $table->unsignedInteger('days_count')->default(0);
            $table->date('first_report_date')->nullable();
            $table->date('last_report_date')->nullable();
            $table->timestamps();
        });

        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->unique(['report_date', 'campaign_id'], 'meta_daily_date_campaign_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_ads_daily_stats', function (Blueprint $table) {
            $table->dropUnique('meta_daily_date_campaign_unique');
        });
        Schema::dropIfExists('meta_ads_campaigns');
    }
};
