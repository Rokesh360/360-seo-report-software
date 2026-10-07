<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_reports', function (Blueprint $table) {
            $table->id();

            // Multi assign services stored as JSON
            $table->json('assign_service_ids')->nullable();

            $table->string('platforms')->nullable();
            $table->string('campaign_objective')->nullable();
            $table->text('campaign_overview')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('campaign_focus')->nullable();
            $table->text('key_highlights')->nullable();

            // Common metrics
            $table->unsignedBigInteger('total_leads')->nullable();
            $table->unsignedBigInteger('total_conversations')->nullable();
            $table->unsignedBigInteger('total_enquiries')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('whatsapp_clicks')->nullable();
            $table->unsignedBigInteger('form_submissions')->nullable();
            $table->unsignedBigInteger('link_clicks')->nullable();
            $table->decimal('frequency', 8, 2)->nullable();

            // Engagement
            $table->unsignedBigInteger('total_engagement')->nullable();
            $table->unsignedBigInteger('reactions')->nullable();
            $table->unsignedBigInteger('comments')->nullable();
            $table->unsignedBigInteger('shares')->nullable();
            $table->unsignedBigInteger('saves')->nullable();
            $table->unsignedBigInteger('profile_visits')->nullable();
            $table->unsignedBigInteger('new_followers')->nullable();

            // JSON payloads
            $table->json('campaign_results')->nullable();
            $table->json('creative_performance')->nullable();
            $table->json('audience_performance')->nullable();
            $table->json('top_posts')->nullable();
            $table->json('content_analysis')->nullable();
            $table->json('performance_highlights')->nullable();
            $table->json('key_observations')->nullable();
            $table->json('monthly_summary')->nullable();
            $table->json('next_month_plan')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_reports');
    }
};