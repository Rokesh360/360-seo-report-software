<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_reports', function (Blueprint $table) {
            // WhatsApp: CTR (reach / impressions / frequency already exist)
            $table->decimal('whatsapp_ctr', 5, 2)->nullable()->after('whatsapp_clicks');

            // Lead Generation: its own reach / impressions / frequency / CTR
            $table->unsignedBigInteger('lead_reach')->nullable()->after('link_clicks');
            $table->unsignedBigInteger('lead_impressions')->nullable()->after('lead_reach');
            $table->decimal('lead_frequency', 8, 2)->nullable()->after('lead_impressions');
            $table->decimal('lead_ctr', 5, 2)->nullable()->after('lead_frequency');

            // Removed metrics
            $table->dropColumn(['total_enquiries', 'form_submissions']);
        });
    }

    public function down(): void
    {
        Schema::table('social_media_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('total_enquiries')->nullable();
            $table->unsignedBigInteger('form_submissions')->nullable();

            $table->dropColumn([
                'whatsapp_ctr',
                'lead_reach',
                'lead_impressions',
                'lead_frequency',
                'lead_ctr',
            ]);
        });
    }
};