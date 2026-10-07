<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_media_reports')) {
            return;
        }

        if (Schema::hasColumn('social_media_reports', 'assign_service_id')
            && ! Schema::hasColumn('social_media_reports', 'assign_service_ids')) {
            Schema::table('social_media_reports', function (Blueprint $table) {
                $table->renameColumn('assign_service_id', 'assign_service_ids');
            });
        }

        $columns = array_values(array_filter([
            'reporting_period_start',
            'reporting_period_end',
        ], fn (string $column) => Schema::hasColumn('social_media_reports', $column)));

        if ($columns !== []) {
            Schema::table('social_media_reports', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('social_media_reports')) {
            return;
        }

        $columns = array_values(array_filter([
            'reporting_period_start',
            'reporting_period_end',
        ], fn (string $column) => ! Schema::hasColumn('social_media_reports', $column)));

        if ($columns !== []) {
            Schema::table('social_media_reports', function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->date($column)->nullable();
                }
            });
        }

        if (Schema::hasColumn('social_media_reports', 'assign_service_ids')
            && ! Schema::hasColumn('social_media_reports', 'assign_service_id')) {
            Schema::table('social_media_reports', function (Blueprint $table) {
                $table->renameColumn('assign_service_ids', 'assign_service_id');
            });
        }
    }
};
