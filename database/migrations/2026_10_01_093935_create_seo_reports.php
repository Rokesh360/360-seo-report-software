<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_reports')) {
            return;
        }

        Schema::create('seo_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');

            $table->string('reporting_period'); // e.g. 2026-08
            $table->string('prepared_by')->default('360DegreeInfo SEO Team');

            $table->text('overview')->nullable();
            $table->json('on_page_items')->nullable();

            $table->string('seo_score_image')->nullable();

            $table->json('performance_images')->nullable();
            $table->json('backlinks_items')->nullable();

            $table->json('keywords')->nullable();
            $table->json('queries')->nullable();
            $table->json('images')->nullable();

            $table->text('conclusion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_reports');
    }
};
