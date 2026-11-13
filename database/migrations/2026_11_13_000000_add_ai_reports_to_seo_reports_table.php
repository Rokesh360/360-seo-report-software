<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_reports', function (Blueprint $table) {
            $table->json('ai_reports')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seo_reports', function (Blueprint $table) {
            $table->dropColumn('ai_reports');
        });
    }
};
