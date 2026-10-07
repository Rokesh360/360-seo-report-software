<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assign_services', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('assign_services', function (Blueprint $table) {
            $table->json('service_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assign_services', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable()->change();
        });
    }
};