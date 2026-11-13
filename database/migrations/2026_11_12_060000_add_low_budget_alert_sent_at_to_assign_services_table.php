<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assign_services', function (Blueprint $table) {
            $table->timestamp('low_budget_alert_sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assign_services', function (Blueprint $table) {
            $table->dropColumn('low_budget_alert_sent_at');
        });
    }
};
