<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assign_service_id')->constrained('assign_services')->cascadeOnDelete();
            $table->json('data');
            $table->json('overview_images')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_reports');
    }
};
