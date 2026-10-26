<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meta_ads_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->index();
            $table->string('campaign_id');
            $table->string('campaign_name')->nullable();
            $table->decimal('spend', 14, 2)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->decimal('ctr', 10, 4)->default(0);
            $table->decimal('cpc', 12, 4)->default(0);
            $table->decimal('cpm', 12, 4)->default(0);
            $table->unsignedInteger('leads')->default(0);
            $table->unsignedInteger('messaging_conversations')->default(0);
            $table->string('currency', 10)->nullable();
            $table->json('actions')->nullable();
            $table->timestamps();

            $table->unique(['report_date', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads_daily_stats');
    }
};